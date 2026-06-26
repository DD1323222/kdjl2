<?php
/**
	This file Note table field desc. map table field 
*/
//$table['player']
$table['bb'] = array(
						'name'	=>	'宝贝名字',
						'wx'	=>	'五行',
						'ac'	=>	'攻击',
						'mc'	=>	'防御',
						'hp'	=>	'生命',
						'mp'	=>	'魔法',
						'speed'	=>	'速度',
						'hits'	=>	'命中',
						'miss'	=>	'躲避',
						'imgstand'	=>	'站立图片名',
						'imgack'	=>	'攻击图片名',
						'imgdie'	=>	'施法图片名',
						'skillist'	=>	'技能列表',
						'czl'	=>	'成长率',
						'kx'	=>	'抗性',
						'remakelevel'	=>	'进化等级',
						'remakeid'	=>	'进化后的宝贝ID',
						'remakepid'	=>	'进化需要道具ID',
						'nowexp'	=>	'当前经验',
						'lexp'	=>	'升级经验',
						'subyl'	=>	'减晕',
						'subsl'	=>	'减睡',
						'subdl'	=>	'减毒',
						'subxl'	=>	'减虚',
						'subfl'	=>	'减防',
						'subhl'	=>	'减缓',
						'subkl'	=>	'减抗',
						'headimg'	=>	'头像图片',
						'cardimg'	=>	'卡片图',
						'effectimg'	=>	'展示图',
						'bbdesc'	=>	'宝宝介绍'
					);

$table['exptolv'] = array(
							'level'	=>	'等级',
							'nxtlvexp'	=>	'升级经验',
						  );					
$table['gpc']	=array( 'name'	=>	'怪物名字',
						'level'	=>	'等级',
						'hp'	=>	'生命',
						'mp'	=>	'魔法',
						'ac'	=>	'攻击',
						'mc'	=>	'防御',
						'speed'	=>	'速度',
						'hits'	=>	'命中',
						'miss'	=>	'躲避',
						'catchv'	=>	'捕捉率',
						'catchid'	=>	'捕捉id',
						'skill'	=>	'使用技能',
						'imgstand'	=>	'站立图',
						'imgack'	=>	'攻击图片名',
						'imgdie'	=>	'施法图片名',
						'droplist'	=>	'掉落物品ID',
						'exps'	=>	'经验',
						'money'	=>	'金钱',
						'boss'	=>	'是否BOSS',
						'wx'	=>	'五行',
						'kx'	=>	'抗性'
						  );

$table['map'] = array(
					  'name'	=>	'地图名称',
					  'desc'	=>	'地图描述',
					  'gpclist'	=>	'出现怪物',
					  'level'	=>	'等级范围',
					  'img'	=>	'区域图'
);

$table['props'] = array(
						 'name'	=>	'道具名称',
						 'requires'	=>	'需求',
						 'usages'	=>	'说明',
						 'effect'	=>	'效果',
						 'sell'	=>	'卖出价格',
						 'buy'	=>	'买入价格',
						 'yb'   =>	'元宝价格',
						 'stime'	=>	'加入时间',
						 'endtime'	=>	'结束时间',
						 'img'	=>	'道具图标',
						 'vary'	=>	'叠加类型',
						 'varyname'	=>	'道具类型',
						 'postion'	=>	'装备位置',
						 'pluseffect'	=>	'附加属性',
						 'plusflag'	=>	'是否可以强化',
						 'pluspid'	=>	'强化需求道具ID',
						 'plusget'	=>	'强化等级效果',
						 'plusnum'	=>	'镶嵌孔',
						 'propscolor' => '颜色',
						 'propslock' => '可否交易',
						 'series' => '套装',
						 'serieseffect' => '套装效果',
						);

$table['skillsys'] = array(
							'pid'	=>	'道具ID',
							'name'	=>	'技能名称',
							'vary'	=>	'技能类型',
							'wx'	=>	'技能五行',
							'img'	=>	'技能图',
							'ackvalue'	=>	'效果值',
							'plus'	=>	'附加效果值',
							'requires'	=>	'需求',
							'uhp'	=>	'消耗生命',
							'ump'	=>	'消耗魔法',
							'ackstyle'	=>	'攻击方式',
							'imgeft'	=>	'攻击效果图'
						  );

$table['wx'] = array(
						'j'	=>	'金属性',
						'm'	=>	'木属性',
						's'	=>	'水属性',
						'h'	=>	'火属性',
						't'	=>	'土属性',
						'wx'	=>	'五行',
						'hp'	=>	'生命',
						'mp'	=>	'魔法',
						'ac'	=>	'攻击',
						'mc'	=>	'防御',
						'speed'	=>	'速度',
						'hits'	=>	'命中',
						'miss'	=>	'躲避'
					);		

$table['task'] = array(
						'title'	=>	'任务名称',
						'fromnpc'	=>	'接受NPC',
						'frommsg'	=>	'接受对话',
						'okmsg'	=>	'完成对话',
						'oknpc'	=>	'完成NPC',
						'okneed'	=>	'完成条件',
						'result'	=>	'奖励物品',
						'cid'	=>	'关联任务ID',
						'limitlv'	=>	'需要等级',
					);											  
?>