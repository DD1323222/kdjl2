<?php

function adminCatalogCsrfToken()
{
	if (!isset($_SESSION['admin_catalog_csrf']) || !is_string($_SESSION['admin_catalog_csrf']) ||
		!preg_match('/^[a-f0-9]{40}$/D', $_SESSION['admin_catalog_csrf']))
	{
		$_SESSION['admin_catalog_csrf'] = sha1(uniqid(mt_rand(), true));
	}
	return $_SESSION['admin_catalog_csrf'];
}

function adminCatalogVerifyCsrf()
{
	$posted = adminPost('csrf_token');
	return is_string($posted) && $posted !== '' && $posted === adminCatalogCsrfToken();
}

function adminCatalogTextLength($value)
{
	if (function_exists('mb_strlen')) return mb_strlen($value, 'UTF-8');
	$count = @preg_match_all('/./us', $value, $matches);
	return $count === false ? strlen($value) : $count;
}

function adminCatalogText($field, $maxLength, $required, &$errors, $trimValue = true)
{
	$value = (string)adminPost($field);
	if ($trimValue) $value = trim($value);
	if (strpos($value, "\0") !== false)
	{
		$errors[] = $field . ' 不能包含空字节。';
		return '';
	}
	if ($required && $value === '') $errors[] = $field . ' 不能为空。';
	if ($maxLength > 0 && adminCatalogTextLength($value) > $maxLength)
		$errors[] = $field . ' 最多允许 ' . intval($maxLength) . ' 个字符。';
	return $value;
}

function adminCatalogInteger($field, $min, $max, $nullable, &$errors)
{
	$value = trim((string)adminPost($field));
	if ($value === '' && $nullable) return null;
	if (!preg_match('/^-?[0-9]+$/D', $value))
	{
		$errors[] = $field . ' 必须是整数。';
		return intval($min);
	}
	$number = intval($value);
	if ($number < $min || $number > $max)
	{
		$errors[] = $field . ' 必须在 ' . $min . ' 到 ' . $max . ' 之间。';
		return intval($min);
	}
	return $number;
}

function adminCatalogSqlText($db, $value)
{
	return "'" . $db->escape($value) . "'";
}

function adminCatalogSqlNumber($value)
{
	return $value === null ? 'NULL' : (string)intval($value);
}

function adminRefreshBbCache($db, $mem, $changedIds)
{
	$oldRows = kdjlMemArrayValue($mem, MEM_BB_KEY);
	$rows = $db->getRecords('SELECT * FROM bb ORDER BY id');
	if (!is_array($rows)) return false;
	kdjlInvalidateChangedBaseConfigRows($mem, 'bb', $oldRows, $rows, $changedIds);
	$byId = array();
	$byName = array();
	foreach ($rows as $row)
	{
		$byId[intval($row['id'])] = $row;
		$byName[$row['name']] = $row;
	}
	$ok = $mem->set(array('k' => MEM_BB_KEY, 'v' => $rows));
	$ok = $mem->set(array('k' => 'db_bbid', 'v' => $byId)) && $ok;
	$ok = $mem->set(array('k' => 'db_bbname', 'v' => $byName)) && $ok;
	return $ok;
}

function adminPetResourceSpecs()
{
	return array(
		'imgstand' => array('prefix' => 'z', 'input' => 'resource_z', 'label' => '站立动作', 'recommended' => '250×180，透明背景，面向右侧，至少 2 帧的自然待机动作'),
		'imgack' => array('prefix' => 'g', 'input' => 'resource_g', 'label' => '普通攻击', 'recommended' => '250×180，透明背景，面向右侧，至少 2 帧且包含完整攻击与回位动作'),
		'imgdie' => array('prefix' => 's', 'input' => 'resource_s', 'label' => '技能动作', 'recommended' => '250×180，透明背景，面向右侧，至少 2 帧且与普通攻击明显不同'),
		'headimg' => array('prefix' => 't', 'input' => 'resource_t', 'label' => '头像', 'recommended' => '75×75 或 36×36，透明背景，单帧 GIF'),
		'cardimg' => array('prefix' => 'k', 'input' => 'resource_k', 'label' => '宠物卡片', 'recommended' => '67×84，透明背景，单帧 GIF'),
		'effectimg' => array('prefix' => 'q', 'input' => 'resource_q', 'label' => '详情展示图', 'recommended' => '190×250，透明背景，通常为单帧 GIF')
	);
}

function adminGifSkipSubBlocks($data, &$offset, $length)
{
	while ($offset < $length)
	{
		$size = ord($data[$offset]);
		$offset++;
		if ($size === 0) return true;
		if ($offset + $size > $length) return false;
		$offset += $size;
	}
	return false;
}

function adminGifInfo($path)
{
	$data = @file_get_contents($path);
	if (!is_string($data)) return false;
	$length = strlen($data);
	if ($length < 14 || (substr($data, 0, 6) !== 'GIF87a' && substr($data, 0, 6) !== 'GIF89a')) return false;
	$size = @getimagesize($path);
	if (!is_array($size) || !isset($size[0], $size[1], $size[2]) || intval($size[2]) !== IMAGETYPE_GIF) return false;
	$packed = ord($data[10]);
	$offset = 13;
	if (($packed & 0x80) !== 0) $offset += 3 * (1 << (($packed & 0x07) + 1));
	$frames = 0;
	$trailer = false;
	while ($offset < $length)
	{
		$marker = ord($data[$offset]);
		if ($marker === 0x3B)
		{
			$offset++;
			$trailer = true;
			break;
		}
		if ($marker === 0x21)
		{
			if ($offset + 2 > $length) return false;
			$offset += 2;
			if (!adminGifSkipSubBlocks($data, $offset, $length)) return false;
			continue;
		}
		if ($marker !== 0x2C || $offset + 10 > $length) return false;
		$localPacked = ord($data[$offset + 9]);
		$offset += 10;
		if (($localPacked & 0x80) !== 0) $offset += 3 * (1 << (($localPacked & 0x07) + 1));
		if ($offset >= $length) return false;
		$offset++;
		if (!adminGifSkipSubBlocks($data, $offset, $length)) return false;
		$frames++;
	}
	if (!$trailer || $frames < 1) return false;
	$tail = substr($data, $offset);
	if ($tail !== '' && !preg_match('/^[\x00\x09\x0A\x0D\x20]*$/D', $tail)) return false;
	return array('width' => intval($size[0]), 'height' => intval($size[1]), 'frames' => $frames, 'bytes' => $length);
}

function adminValidatePetUploads(&$errors)
{
	$uploads = array();
	$totalBytes = 0;
	foreach (adminPetResourceSpecs() as $field => $spec)
	{
		$input = $spec['input'];
		if (!isset($_FILES[$input])) continue;
		$file = $_FILES[$input];
		if (!is_array($file) || is_array($file['error']) || is_array($file['tmp_name']))
		{
			$errors[] = $spec['label'] . ' 的上传数据无效。';
			continue;
		}
		$error = intval($file['error']);
		if ($error === UPLOAD_ERR_NO_FILE) continue;
		if ($error !== UPLOAD_ERR_OK)
		{
			$errors[] = $spec['label'] . ' 上传失败，错误码 ' . $error . '。';
			continue;
		}
		$tmp = (string)$file['tmp_name'];
		$bytes = @filesize($tmp);
		if (!is_uploaded_file($tmp) || $bytes === false || $bytes < 1 || $bytes > 4 * 1024 * 1024)
		{
			$errors[] = $spec['label'] . ' 必须是 1 字节到 4 MB 的有效上传文件。';
			continue;
		}
		$info = adminGifInfo($tmp);
		if (!is_array($info))
		{
			$errors[] = $spec['label'] . ' 不是结构完整的 GIF 文件，或文件尾含有额外内容。';
			continue;
		}
		if ($info['width'] > 600 || $info['height'] > 600)
		{
			$errors[] = $spec['label'] . ' 的宽高均不能超过 600 像素。';
			continue;
		}
		if (($spec['prefix'] === 'z' || $spec['prefix'] === 'g' || $spec['prefix'] === 's') && $info['frames'] < 2)
		{
			$errors[] = $spec['label'] . ' 必须是至少 2 帧的动态 GIF。';
			continue;
		}
		$totalBytes += intval($bytes);
		$uploads[$field] = array('tmp' => $tmp, 'spec' => $spec, 'info' => $info);
	}
	if ($totalBytes > 20 * 1024 * 1024)
	{
		$errors[] = '本次上传资源总大小不能超过 20 MB。';
		return array();
	}
	return $uploads;
}

function adminPetImageFileName($value)
{
	$value = trim((string)$value);
	if ($value === '' || strlen($value) > 50 || basename($value) !== $value ||
		!preg_match('/^[A-Za-z0-9_.-]+\.gif$/iD', $value)) return false;
	return $value;
}

function adminPetImagePath($fileName)
{
	$fileName = adminPetImageFileName($fileName);
	return $fileName === false ? false : dirname(__FILE__) . '/../images/bb/' . $fileName;
}

function adminInstallPetUploads($uploads, $petId, &$error)
{
	$state = array('operations' => array());
	if (count($uploads) === 0) return $state;
	$directory = realpath(dirname(__FILE__) . '/../images/bb');
	if ($directory === false || !is_dir($directory) || !is_writable($directory))
	{
		$error = '宠物资源目录不存在或不可写。';
		return false;
	}
	$token = sha1(uniqid(mt_rand(), true));
	foreach ($uploads as $field => $upload)
	{
		$prefix = $upload['spec']['prefix'];
		$stage = $directory . DIRECTORY_SEPARATOR . '.admin-upload-' . $token . '-' . $prefix . '.tmp';
		$destination = $directory . DIRECTORY_SEPARATOR . $prefix . intval($petId) . '.gif';
		if (!move_uploaded_file($upload['tmp'], $stage))
		{
			$error = $upload['spec']['label'] . ' 无法写入资源目录。';
			adminRestorePetUploads($state);
			return false;
		}
		@chmod($stage, 0644);
		$state['operations'][] = array('field' => $field, 'stage' => $stage, 'destination' => $destination, 'backup' => '', 'installed' => false, 'file' => $prefix . intval($petId) . '.gif');
	}
	foreach ($state['operations'] as $index => $operation)
	{
		$destination = $operation['destination'];
		if (is_link($destination) || (file_exists($destination) && !is_file($destination)))
		{
			$error = '目标资源路径不是普通文件，已拒绝覆盖。';
			adminRestorePetUploads($state);
			return false;
		}
		if (is_file($destination))
		{
			$backup = dirname($destination) . DIRECTORY_SEPARATOR . '.admin-backup-' . $token . '-' . $operation['file'] . '.tmp';
			if (!@rename($destination, $backup))
			{
				$error = '无法备份原有宠物资源。';
				adminRestorePetUploads($state);
				return false;
			}
			$state['operations'][$index]['backup'] = $backup;
		}
		if (!@rename($operation['stage'], $destination))
		{
			$error = '无法安装新的宠物资源。';
			adminRestorePetUploads($state);
			return false;
		}
		$state['operations'][$index]['installed'] = true;
	}
	return $state;
}

function adminRestorePetUploads($state)
{
	if (!is_array($state) || !isset($state['operations']) || !is_array($state['operations'])) return;
	for ($index = count($state['operations']) - 1; $index >= 0; $index--)
	{
		$operation = $state['operations'][$index];
		if (!empty($operation['installed']) && is_file($operation['destination'])) @unlink($operation['destination']);
		if (!empty($operation['backup']) && is_file($operation['backup'])) @rename($operation['backup'], $operation['destination']);
		if (!empty($operation['stage']) && is_file($operation['stage'])) @unlink($operation['stage']);
	}
}

function adminFinalizePetUploads($state)
{
	if (!is_array($state) || !isset($state['operations']) || !is_array($state['operations'])) return;
	foreach ($state['operations'] as $operation)
	{
		if (!empty($operation['backup']) && is_file($operation['backup'])) @unlink($operation['backup']);
		if (!empty($operation['stage']) && is_file($operation['stage'])) @unlink($operation['stage']);
	}
}

function adminInstalledPetFileNames($state)
{
	$result = array();
	if (!is_array($state) || !isset($state['operations'])) return $result;
	foreach ($state['operations'] as $operation) $result[$operation['field']] = $operation['file'];
	return $result;
}
