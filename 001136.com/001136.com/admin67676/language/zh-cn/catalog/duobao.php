<?php
// Heading
$_['heading_title']           = '一元夺宝';

// Text
$_['text_home']               = '首页';
$_['text_list']               = '夺宝商品列表';
$_['text_add']                = '新增夺宝商品';
$_['text_edit']               = '编辑夺宝商品';
$_['text_draw']               = '开奖处理';
$_['text_success_save']       = '夺宝商品保存成功！';
$_['text_success_delete']     = '已删除所选夺宝商品。';
$_['text_success_draw']       = '开奖信息更新成功。';
$_['text_status_draft']       = '草稿';
$_['text_status_active']      = '进行中';
$_['text_status_suspended']   = '暂停中';
$_['text_status_completed']   = '已开奖';
$_['text_status_cancelled']   = '已取消';
$_['text_filter']             = '筛选';

// Column
$_['column_title']            = '夺宝标题';
$_['column_product']          = '关联商品';
$_['column_issue_no']         = '期号';
$_['column_total_slots']      = '总人次';
$_['column_joined_slots']     = '已参与';
$_['column_price']            = '单次金额';
$_['column_status']           = '状态';
$_['column_start_time']       = '开始时间';
$_['column_end_time']         = '结束时间';
$_['column_action']           = '操作';

// Entry
$_['entry_title']             = '夺宝标题';
$_['entry_product']           = '关联商品';
$_['entry_issue_no']          = '期号';
$_['entry_total_slots']       = '总人次';
$_['entry_joined_slots']      = '已参与人次';
$_['entry_price']             = '单次金额';
$_['entry_status']            = '活动状态';
$_['entry_start_time']        = '活动开始时间';
$_['entry_end_time']          = '活动结束时间';
$_['entry_sub_title']         = '副标题';
$_['entry_meta_title']        = 'SEO 标题';
$_['entry_meta_description']  = 'SEO 描述';
$_['entry_meta_keyword']      = 'SEO 关键字';
$_['entry_description']       = '商品详情';
$_['entry_notes']             = '备注说明';
$_['entry_winner_customer']   = '中奖会员ID';
$_['entry_winner_order']      = '中奖订单ID';
$_['entry_winner_ticket']     = '中奖号码';

// Robot Config Entries
$_['entry_robot_enabled']     = '启用机器人购买';
$_['entry_robot_target']      = '目标百分比';
$_['entry_robot_target_help'] = '机器人购买达到此百分比后自动停止（1-100）';
$_['entry_auto_draw_type']    = '自动派奖类型';
$_['entry_robot_schedules']   = '购买时间段';
$_['entry_schedule_time']     = '时间段';
$_['entry_schedule_target']   = '目标进度 (%)';
$_['entry_schedule_quantity'] = '购买数量';
$_['entry_schedule_interval'] = '购买间隔 (秒)';

// Robot Config Text
$_['text_robot_disabled']     = '禁用';
$_['text_robot_enabled']      = '启用';
$_['text_auto_draw_real']     = '优先派给真人';
$_['text_auto_draw_robot']    = '优先派给机器人';
$_['text_auto_draw_random']   = '随机派奖';
$_['text_add_schedule']       = '添加时间段';
$_['text_robot_config_help']  = '机器人将在设定的时间段内自动购买票券，达到目标后停止。多个时间段按顺序执行。';

// Button
$_['button_draw']             = '开奖';

// Tab
$_['tab_general']             = '基本信息';
$_['tab_data']                = '期次设置';
$_['tab_robot']               = '机器人配置';

// Error
$_['error_permission']        = '警告：您没有权限修改一元夺宝！';
$_['error_selected']          = '警告：请先选择需要删除的夺宝商品！';
$_['error_title']             = '夺宝标题长度应在 3-255 个字符之间！';
$_['error_product']           = '请选择一个关联商品！';
$_['error_issue_no']          = '期号必须为大于 0 的整数！';
$_['error_total_slots']       = '总人次必须为大于 0 的整数！';
$_['error_joined_slots']      = '已参与人次不能小于 0 且不能超过总人次！';
$_['error_price']             = '单次金额必须大于 0！';
$_['error_meta_title']        = 'SEO 标题长度应在 3-255 个字符之间！';
$_['error_start_time']        = '开始时间格式不正确！';
$_['error_end_time_invalid']  = '结束时间格式不正确！';
$_['error_end_time_before_start'] = '结束时间不能早于开始时间！';
$_['error_winner_ticket']     = '请输入中奖号码！';
$_['error_not_found']         = '无法找到该夺宝活动，请刷新后重试！';
$_['error_warning']           = '警告：请仔细检查表单中的错误！';
