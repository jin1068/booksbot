<?php
// Heading
$_['heading_title']    = '手机 / 平板规格批量导入';

// Text
$_['text_home']        = '控制面板';
$_['text_catalog']     = '商品目录';
$_['text_description'] = '点击下方按钮可将中英文规格表同步为后台“商品管理 &gt; 商品列表”内的可编辑商品。导入程序会根据既定的“手机与平板”分类结构写入，并自动生成颜色/容量选项与价格。';
$_['text_notes']       = '使用说明';
$_['text_notes_list']  = '• 读取规格数据后会创建带有“颜色 / 存储容量”选项的商品，并自动应用满减规则（满1000减100 / 满2000减200 / 满3000减350），页面展示的价格即为优惠后价格。<br/>• 允许重复执行导入；系统会检测商品名称，已存在的记录将跳过避免重复。<br/>• 导入不会自动填充商品图片和营销素材，后续可在后台自行上传。<br/>• 若需要新增规格字段或促销话术，可在代码中调整模板后再次导入。';
$_['text_stats']       = '上次导入结果';
$_['text_stats_detail']= '新增商品：%d 个 | 已存在跳过：%d 个';
$_['text_last_run']    = '上次执行：%s';
$_['text_success']     = '本次共导入 %d 个商品，跳过 %d 条已存在的数据。可前往「商品管理 &gt; 商品列表」查看并继续编辑。';
$_['text_no_history']  = '暂无导入记录。';
$_['text_preview_title']   = '导入预览';
$_['text_preview_created'] = '待新增商品';
$_['text_preview_skipped'] = '跳过 / 重复商品';
$_['text_preview_warnings']= '可能存在的问题';
$_['text_preview_error']   = '预览失败，请稍后重试。';
$_['text_recent_warnings'] = '近期导入警示';
$_['text_preview_empty']   = '暂无数据';
$_['text_reason_duplicate']       = '已存在相同名称的商品，自动跳过。';
$_['text_reason_invalid_storage'] = '缺少存储容量或价格信息。';
$_['text_reason_missing_name']    = '源数据缺少商品名称。';

// Button
$_['button_import']    = '执行导入';
$_['button_preview']   = '预览导入';
$_['button_close']     = '关闭';

// Error
$_['error_permission'] = '警告：您没有权限执行规格批量导入。';
