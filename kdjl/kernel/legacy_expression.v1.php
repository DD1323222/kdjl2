<?php
class KdjlLegacyExpression
{
	public static function matches($row, $expression, &$error)
	{
		$error = '';
		if(!is_array($row))
		{
			$error = 'legacy expression row is not an array';
			return false;
		}
		$condition = '';
		$body = '';
		if(!self::extractIf($expression, $condition, $body, $error)) return false;
		if(preg_match('/^\$ret\s*=\s*(?:\$rs|1|true)\s*;\s*$/iD', $body) !== 1)
		{
			$error = 'unsupported legacy expression result';
			return false;
		}
		return self::evaluateCondition($condition, $row, $error);
	}

	public static function applyUpdate(&$row, $expression, &$error)
	{
		$error = '';
		if(!is_array($row))
		{
			$error = 'legacy expression row is not an array';
			return false;
		}
		$condition = '';
		$body = '';
		if(!self::extractIf($expression, $condition, $body, $error)) return false;
		if(!self::evaluateCondition($condition, $row, $error)) return false;

		$body = trim($body);
		if(strlen($body) >= 2 && $body[0] === '{' && substr($body, -1) === '}')
			$body = substr($body, 1, -1);
		$statements = self::splitStatements($body, $error);
		if($error !== '' || count($statements) < 1) return false;

		$copy = $row;
		$changed = false;
		foreach($statements as $statement)
		{
			$statement = trim($statement);
			if($statement === '') continue;
			if(preg_match('/^\$update\s*=\s*(?:1|true)$/iD', $statement) === 1) continue;
			if(!preg_match('/^\$rs\s*\[\s*([\'\"])([A-Za-z_][A-Za-z0-9_]*)\1\s*\]\s*=\s*(.*?)\s*$/sD', $statement, $parts))
			{
				$error = 'unsupported legacy update statement';
				return false;
			}
			$valid = false;
			$value = self::decodeUpdateValue($parts[3], $copy, $valid);
			if(!$valid)
			{
				$error = 'unsupported legacy update value';
				return false;
			}
			$copy[$parts[2]] = $value;
			$changed = true;
		}
		if(!$changed)
		{
			$error = 'empty legacy update';
			return false;
		}
		$row = $copy;
		return true;
	}

	private static function extractIf($expression, &$condition, &$body, &$error)
	{
		if(!is_string($expression))
		{
			$error = 'legacy expression is not a string';
			return false;
		}
		$expression = trim($expression);
		if(preg_match('/^if\b/i', $expression, $match) !== 1)
		{
			$error = 'unsupported legacy expression';
			return false;
		}
		$offset = strlen($match[0]);
		$length = strlen($expression);
		while($offset < $length && ctype_space($expression[$offset])) $offset++;
		if($offset >= $length || $expression[$offset] !== '(')
		{
			$error = 'legacy expression condition is missing';
			return false;
		}
		$start = $offset + 1;
		$depth = 1;
		$quote = '';
		$escaped = false;
		for($offset = $start; $offset < $length; $offset++)
		{
			$char = $expression[$offset];
			if($quote !== '')
			{
				if($escaped) $escaped = false;
				else if($char === '\\') $escaped = true;
				else if($char === $quote) $quote = '';
				continue;
			}
			if($char === '\'' || $char === '"')
			{
				$quote = $char;
				continue;
			}
			if($char === '(') $depth++;
			else if($char === ')' && --$depth === 0)
			{
				$condition = substr($expression, $start, $offset - $start);
				$body = trim(substr($expression, $offset + 1));
				if(trim($condition) === '' || $body === '')
				{
					$error = 'legacy expression is incomplete';
					return false;
				}
				return true;
			}
		}
		$error = 'legacy expression parentheses are unbalanced';
		return false;
	}

	private static function evaluateCondition($condition, $row, &$error)
	{
		$condition = self::stripOuterParentheses(trim($condition));
		$parts = self::splitLogical($condition, 'or', $error);
		if($error !== '') return false;
		if(count($parts) > 1)
		{
			foreach($parts as $part)
			{
				$branchError = '';
				if(self::evaluateCondition($part, $row, $branchError)) return true;
				if($branchError !== '')
				{
					$error = $branchError;
					return false;
				}
			}
			return false;
		}
		$parts = self::splitLogical($condition, 'and', $error);
		if($error !== '') return false;
		if(count($parts) > 1)
		{
			foreach($parts as $part)
				if(!self::evaluateCondition($part, $row, $error)) return false;
			return true;
		}
		return self::evaluateComparison($condition, $row, $error);
	}

	private static function splitLogical($expression, $operator, &$error)
	{
		$parts = array();
		$start = 0;
		$length = strlen($expression);
		$depth = 0;
		$quote = '';
		$escaped = false;
		$symbol = $operator === 'or' ? '||' : '&&';
		$wordLength = strlen($operator);
		for($i=0; $i<$length; $i++)
		{
			$char = $expression[$i];
			if($quote !== '')
			{
				if($escaped) $escaped = false;
				else if($char === '\\') $escaped = true;
				else if($char === $quote) $quote = '';
				continue;
			}
			if($char === '\'' || $char === '"')
			{
				$quote = $char;
				continue;
			}
			if($char === '(') { $depth++; continue; }
			if($char === ')')
			{
				if(--$depth < 0) { $error = 'legacy condition parentheses are unbalanced'; return array(); }
				continue;
			}
			if($depth !== 0) continue;
			$foundLength = 0;
			if(substr($expression, $i, 2) === $symbol) $foundLength = 2;
			else if(strcasecmp(substr($expression, $i, $wordLength), $operator) === 0)
			{
				$before = $i > 0 ? $expression[$i-1] : '';
				$after = $i+$wordLength < $length ? $expression[$i+$wordLength] : '';
				if(($before === '' || preg_match('/[^A-Za-z0-9_]/', $before)) &&
					($after === '' || preg_match('/[^A-Za-z0-9_]/', $after))) $foundLength = $wordLength;
			}
			if($foundLength > 0)
			{
				$part = trim(substr($expression, $start, $i-$start));
				if($part === '') { $error = 'legacy condition has an empty branch'; return array(); }
				$parts[] = $part;
				$i += $foundLength-1;
				$start = $i+1;
			}
		}
		if($quote !== '' || $depth !== 0) { $error = 'legacy condition is unbalanced'; return array(); }
		$part = trim(substr($expression, $start));
		if($part === '') { $error = 'legacy condition has an empty branch'; return array(); }
		$parts[] = $part;
		return $parts;
	}

	private static function stripOuterParentheses($expression)
	{
		while(strlen($expression) >= 2 && $expression[0] === '(' && substr($expression, -1) === ')' && self::outerParenthesesWrap($expression))
			$expression = trim(substr($expression, 1, -1));
		return $expression;
	}

	private static function outerParenthesesWrap($expression)
	{
		$length = strlen($expression);
		$depth = 0;
		$quote = '';
		$escaped = false;
		for($i=0; $i<$length; $i++)
		{
			$char = $expression[$i];
			if($quote !== '')
			{
				if($escaped) $escaped = false;
				else if($char === '\\') $escaped = true;
				else if($char === $quote) $quote = '';
				continue;
			}
			if($char === '\'' || $char === '"') { $quote = $char; continue; }
			if($char === '(') $depth++;
			else if($char === ')' && --$depth === 0 && $i !== $length-1) return false;
		}
		return $depth === 0 && $quote === '';
	}

	private static function evaluateComparison($condition, $row, &$error)
	{
		if(!preg_match('/^\s*\$rs\s*\[\s*([\'\"])([A-Za-z_][A-Za-z0-9_]*)\1\s*\]\s*(===|!==|==|!=|>=|<=|>|<)\s*(.*?)\s*$/sD', $condition, $parts))
		{
			$error = 'unsupported legacy condition';
			return false;
		}
		$valid = false;
		$right = self::decodeValue($parts[4], $row, $valid);
		if(!$valid)
		{
			$error = 'unsupported legacy value';
			return false;
		}
		$left = array_key_exists($parts[2], $row) ? $row[$parts[2]] : null;
		return self::compareValues($left, $right, $parts[3]);
	}

	private static function decodeValue($value, $row, &$valid)
	{
		$value = trim($value);
		$valid = true;
		if(preg_match('/^([\'\"])(.*)\1$/sD', $value, $parts)) return stripcslashes($parts[2]);
		if(preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D', $value)) return strpos($value, '.') === false ? intval($value) : floatval($value);
		if(preg_match('/^\$rs\s*\[\s*([\'\"])([A-Za-z_][A-Za-z0-9_]*)\1\s*\]$/D', $value, $parts))
			return array_key_exists($parts[2], $row) ? $row[$parts[2]] : null;
		if(strcasecmp($value, 'null') === 0) return null;
		if(strcasecmp($value, 'true') === 0) return true;
		if(strcasecmp($value, 'false') === 0) return false;
		$valid = false;
		return null;
	}

	private static function decodeUpdateValue($value, $row, &$valid)
	{
		$value = trim($value);
		if(preg_match('/^(\$rs\s*\[\s*([\'\"])([A-Za-z_][A-Za-z0-9_]*)\2\s*\])\s*([+\-*\/.])\s*(.*?)\s*$/sD', $value, $parts))
		{
			$left = array_key_exists($parts[3], $row) ? $row[$parts[3]] : null;
			$right = self::decodeValue($parts[5], $row, $valid);
			if(!$valid) return null;
			switch($parts[4])
			{
				case '+': return $left + $right;
				case '-': return $left - $right;
				case '*': return $left * $right;
				case '/': if(floatval($right) == 0) { $valid=false; return null; } return $left / $right;
				case '.': return (string)$left.(string)$right;
			}
		}
		return self::decodeValue($value, $row, $valid);
	}

	private static function splitStatements($body, &$error)
	{
		$statements = array();
		$start = 0;
		$length = strlen($body);
		$depth = 0;
		$quote = '';
		$escaped = false;
		for($i=0; $i<$length; $i++)
		{
			$char = $body[$i];
			if($quote !== '')
			{
				if($escaped) $escaped = false;
				else if($char === '\\') $escaped = true;
				else if($char === $quote) $quote = '';
				continue;
			}
			if($char === '\'' || $char === '"') { $quote = $char; continue; }
			if($char === '(') $depth++;
			else if($char === ')') $depth--;
			else if($char === ';' && $depth === 0)
			{
				$statements[] = substr($body, $start, $i-$start);
				$start = $i+1;
			}
			if($depth < 0) { $error = 'legacy update parentheses are unbalanced'; return array(); }
		}
		if($quote !== '' || $depth !== 0) { $error = 'legacy update is unbalanced'; return array(); }
		if(trim(substr($body, $start)) !== '') $statements[] = substr($body, $start);
		return $statements;
	}

	private static function compareValues($left, $right, $operator)
	{
		switch($operator)
		{
			case '===': return $left === $right;
			case '!==': return $left !== $right;
			case '==': return $left == $right;
			case '!=': return $left != $right;
			case '>=': return $left >= $right;
			case '<=': return $left <= $right;
			case '>': return $left > $right;
			case '<': return $left < $right;
		}
		return false;
	}
}
?>
