<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * 一元夺宝后台管理控制器
 */
class Duobao extends \Opencart\System\Engine\Controller {
	/**
	 * 首页列表入口
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('catalog/duobao');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token'])
		];

		$data['add'] = $this->url->link('catalog/duobao.form', 'user_token=' . $this->session->data['user_token']);
		$data['delete'] = $this->url->link('catalog/duobao.delete', 'user_token=' . $this->session->data['user_token']);

		$data['filter_title'] = $this->request->get['filter_title'] ?? '';
		$data['filter_status'] = $this->request->get['filter_status'] ?? '';
		$data['user_token'] = $this->session->data['user_token'];

		$data['list'] = $this->load->controller('catalog/duobao.getList');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/duobao', $data));
	}

	/**
	 * AJAX 刷新列表
	 */
	public function list(): void {
		$this->load->language('catalog/duobao');

		$this->response->setOutput($this->load->controller('catalog/duobao.getList'));
	}

	/**
	 * 列表数据渲染
	 */
	public function getList(): string {
		$this->load->language('catalog/duobao');
		$this->load->model('catalog/duobao');

		$filter_title = $this->request->get['filter_title'] ?? '';
		$filter_status = $this->request->get['filter_status'] ?? '';
		$sort = $this->request->get['sort'] ?? 'd.date_added';
		$order = $this->request->get['order'] ?? 'DESC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		$url = '';
		if ($filter_title !== '') {
			$url .= '&filter_title=' . urlencode(html_entity_decode($filter_title, ENT_QUOTES, 'UTF-8'));
		}
		if ($filter_status !== '') {
			$url .= '&filter_status=' . $filter_status;
		}
		if ($sort !== '') {
			$url .= '&sort=' . $sort;
		}
		if ($order !== '') {
			$url .= '&order=' . $order;
		}
		if ($page) {
			$url .= '&page=' . $page;
		}

		$data['action'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . $url);

		$filter_data = [
			'filter_title'  => $filter_title,
			'filter_status' => $filter_status,
			'sort'          => $sort,
			'order'         => $order,
			'start'         => ($page - 1) * $this->config->get('config_pagination_admin'),
			'limit'         => $this->config->get('config_pagination_admin')
		];

		$results = $this->model_catalog_duobao->getDuobaos($filter_data);

		$status_map = $this->getStatusMap();

		$data['duobaos'] = [];
		foreach ($results as $result) {
			$issue_status = $result['issue_status'] ?? $result['status'];
			$start_time = $result['issue_start_time'] ? date($this->language->get('datetime_format'), strtotime($result['issue_start_time'])) : '';
			$end_time = $result['issue_end_time'] ? date($this->language->get('datetime_format'), strtotime($result['issue_end_time'])) : '';

			// 获取票券统计
			$ticket_stats = ['total' => 0, 'real' => 0, 'robot' => 0];
			if (isset($result['issue_id']) && $result['issue_id']) {
				$ticket_stats = $this->model_catalog_duobao->getIssueTicketStats((int)$result['issue_id']);
			}

		$data['duobaos'][] = [
			'duobao_id'    => $result['duobao_id'],
			'issue_id'     => $result['issue_id'] ?? 0,
			'title'        => $result['title'] ?? '',
			'product_name' => $result['product_name'] ?? '',
			'issue_no'     => $result['current_issue_no'] ?? $result['issue_no'],
			'total_slots'  => $result['issue_total_slots'] ?? $result['total_slots'],
			'joined_slots' => $result['issue_joined_slots'] ?? $result['joined_slots'],
			'price'        => number_format((float)$result['price'], 2, '.', ''),
			'status'       => $status_map[$issue_status] ?? $issue_status,
			'status_code'  => $issue_status,
			'start_time'   => $start_time,
			'end_time'     => $end_time,
			'ticket_stats' => $ticket_stats,
			'robot_enabled' => $result['robot_enabled'] ?? 0,
			'edit'         => $this->url->link('catalog/duobao.form', 'user_token=' . $this->session->data['user_token'] . '&duobao_id=' . $result['duobao_id'] . $url),
			'draw'         => $this->url->link('catalog/duobao.draw', 'user_token=' . $this->session->data['user_token'] . '&duobao_id=' . $result['duobao_id'] . '&issue_id=' . ((int)($result['issue_id'] ?? 0))),
			'export'       => $this->url->link('catalog/duobao.export', 'user_token=' . $this->session->data['user_token'] . '&duobao_id=' . $result['duobao_id'] . '&issue_id=' . ((int)($result['issue_id'] ?? 0)))
		];
		}

		$duobao_total = $this->model_catalog_duobao->getTotalDuobaos($filter_data);

		// 排序链接
		$url = '';
		if ($filter_title !== '') {
			$url .= '&filter_title=' . urlencode(html_entity_decode($filter_title, ENT_QUOTES, 'UTF-8'));
		}
		if ($filter_status !== '') {
			$url .= '&filter_status=' . $filter_status;
		}
		$url .= ($order === 'ASC') ? '&order=DESC' : '&order=ASC';

		$data['sort_title'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=dd.title' . $url);
		$data['sort_product'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=product_name' . $url);
		$data['sort_issue'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.issue_no' . $url);
		$data['sort_total'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.total_slots' . $url);
		$data['sort_joined'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.joined_slots' . $url);
		$data['sort_status'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.status' . $url);
		$data['sort_start'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.start_time' . $url);
		$data['sort_end'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.end_time' . $url);
		$data['sort_added'] = $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . '&sort=d.date_added' . $url);

		$url = '';
		if ($filter_title !== '') {
			$url .= '&filter_title=' . urlencode(html_entity_decode($filter_title, ENT_QUOTES, 'UTF-8'));
		}
		if ($filter_status !== '') {
			$url .= '&filter_status=' . $filter_status;
		}
		if ($sort) {
			$url .= '&sort=' . $sort;
		}
		if ($order) {
			$url .= '&order=' . $order;
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $duobao_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('catalog/duobao.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf(
			$this->language->get('text_pagination'),
			($duobao_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0,
			((($page - 1) * $this->config->get('config_pagination_admin')) > ($duobao_total - $this->config->get('config_pagination_admin'))) ? $duobao_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')),
			$duobao_total,
			ceil($duobao_total / $this->config->get('config_pagination_admin'))
		);

		$data['sort'] = $sort;
		$data['order'] = $order;

		return $this->load->view('catalog/duobao_list', $data);
	}

	/**
	 * 夺宝表单
	 */
	public function form(): void {
		$this->load->language('catalog/duobao');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/duobao');
		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();

		$duobao_id = isset($this->request->get['duobao_id']) ? (int)$this->request->get['duobao_id'] : 0;

		$data['text_form'] = $duobao_id ? $this->language->get('text_edit') : $this->language->get('text_add');

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('catalog/duobao.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token']);

		$duobao_info = [];
		if ($duobao_id) {
			$duobao_info = $this->model_catalog_duobao->getDuobao($duobao_id);
		}

		$data['duobao_id'] = $duobao_info['duobao_id'] ?? 0;
		$data['issue_id'] = $duobao_info['issue_id'] ?? 0;
		$data['product_id'] = $duobao_info['product_id'] ?? 0;
		$data['product_name'] = $duobao_info['product_name'] ?? '';
		$data['issue_no'] = $duobao_info['issue_no'] ?? 1;
		$data['total_slots'] = $duobao_info['issue_total_slots'] ?? $duobao_info['total_slots'] ?? 0;
		$data['joined_slots'] = $duobao_info['issue_joined_slots'] ?? $duobao_info['joined_slots'] ?? 0;
		$data['price'] = $duobao_info['price'] ?? '1.00';
		$data['status'] = $duobao_info['issue_status'] ?? $duobao_info['status'] ?? 'draft';
		$data['start_time'] = isset($duobao_info['issue_start_time']) && $duobao_info['issue_start_time'] ? date('Y-m-d\TH:i', strtotime($duobao_info['issue_start_time'])) : '';
		$data['end_time'] = isset($duobao_info['issue_end_time']) && $duobao_info['issue_end_time'] ? date('Y-m-d\TH:i', strtotime($duobao_info['issue_end_time'])) : '';

		$description_info = [];
		if ($duobao_id) {
			$description_info = $this->model_catalog_duobao->getDescriptions($duobao_id);
		}

		$data['languages'] = $languages;
		$data['duobao_description'] = [];
		foreach ($languages as $language) {
			$language_id = (int)$language['language_id'];
			$data['duobao_description'][$language_id] = $description_info[$language_id] ?? [
				'title'            => '',
				'sub_title'        => '',
				'meta_title'       => '',
				'meta_description' => '',
				'meta_keyword'     => '',
				'description'      => ''
			];
		}

		$default_language_id = (int)$this->config->get('config_language_id');
		$default_description = $data['duobao_description'][$default_language_id] ?? (reset($data['duobao_description']) ?: []);

		$data['title'] = $default_description['title'] ?? '';
		$data['notes'] = '';

		$data['status_options'] = [
			'draft'     => $this->language->get('text_status_draft'),
			'active'    => $this->language->get('text_status_active'),
			'suspended' => $this->language->get('text_status_suspended')
		];

		// 机器人配置
		$data['robot_enabled'] = 0;
		$data['robot_target_percent'] = 80;
		$data['auto_draw_type'] = '';
		$data['robot_schedules'] = [];

		if ($duobao_id && $data['issue_id']) {
			$robot_config = $this->model_catalog_duobao->getRobotConfig($data['issue_id']);
			if ($robot_config) {
				$data['robot_enabled'] = $robot_config['robot_enabled'];
				$data['robot_target_percent'] = $robot_config['robot_target_percent'];
			}
			
			$issue_info = $this->model_catalog_duobao->getIssue($duobao_id, $data['issue_id']);
			if ($issue_info) {
				$data['auto_draw_type'] = $issue_info['auto_draw_type'] ?? '';
			}
			
			$robot_schedules = $this->model_catalog_duobao->getRobotSchedules($data['issue_id']);
			foreach ($robot_schedules as $schedule) {
				$data['robot_schedules'][] = [
					'schedule_id'    => $schedule['schedule_id'],
					'start_time'     => $schedule['start_time'] ? date('Y-m-d\TH:i', strtotime($schedule['start_time'])) : '',
					'end_time'       => $schedule['end_time'] ? date('Y-m-d\TH:i', strtotime($schedule['end_time'])) : '',
					'target_percent' => $schedule['target_percent'],
					'quantity_min'   => $schedule['quantity_min'],
					'quantity_max'   => $schedule['quantity_max'],
					'interval_min'   => $schedule['purchase_interval_min'],
					'interval_max'   => $schedule['purchase_interval_max']
				];
			}
		}

		// CKEditor语言设置
		$data['ckeditor'] = 'zh-cn';
		if ($this->config->get('config_language') == 'en-gb') {
			$data['ckeditor'] = 'en';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/duobao_form', $data));
	}

	/**
	 * 保存夺宝商品
	 */
	public function save(): void {
		$this->load->language('catalog/duobao');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/duobao')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$duobao_id = (int)($this->request->post['duobao_id'] ?? 0);
		$issue_id = (int)($this->request->post['issue_id'] ?? 0);
		$product_id = (int)($this->request->post['product_id'] ?? 0);
		$issue_no = (int)($this->request->post['issue_no'] ?? 1);
		$total_slots = (int)($this->request->post['total_slots'] ?? 0);
		
		// 🔒 重要：编辑现有活动时，不从POST读取joined_slots，防止覆盖真实购买数据
		$joined_slots = null;
		if ($duobao_id && $issue_id) {
			// 编辑模式：从数据库读取当前值
			$this->load->model('catalog/duobao');
			$current_issue = $this->model_catalog_duobao->getIssue($duobao_id, $issue_id);
			if ($current_issue) {
				$joined_slots = (int)$current_issue['joined_slots'];
			}
		} else {
			// 新建模式：使用POST值或默认0
			$joined_slots = (int)($this->request->post['joined_slots'] ?? 0);
		}
		
		$price = (float)($this->request->post['price'] ?? 0);
		$status = (string)($this->request->post['status'] ?? 'draft');
		$start_time_raw = $this->request->post['start_time'] ?? '';
		$end_time_raw = $this->request->post['end_time'] ?? '';

		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		$descriptions = $this->request->post['duobao_description'] ?? [];
		$default_language_id = (int)$this->config->get('config_language_id');

		// 向后兼容单语言字段
		if (!$descriptions) {
			$title = trim((string)($this->request->post['title'] ?? ''));
			$descriptions = [
				$default_language_id => [
					'title'            => $title,
					'sub_title'        => (string)($this->request->post['sub_title'] ?? ''),
					'meta_title'       => (string)($this->request->post['meta_title'] ?? $title),
					'meta_description' => (string)($this->request->post['meta_description'] ?? ''),
					'meta_keyword'     => (string)($this->request->post['meta_keyword'] ?? ''),
					'description'      => (string)($this->request->post['description'] ?? '')
				]
			];
		}

		// 规范化描述数组
		$normalized_descriptions = [];
		foreach ($languages as $language) {
			$language_id = (int)$language['language_id'];
			$language_description = $descriptions[$language_id] ?? null;

			if ($language_description) {
				$normalized_descriptions[$language_id] = [
					'title'            => trim((string)($language_description['title'] ?? '')),
					'sub_title'        => (string)($language_description['sub_title'] ?? ''),
					'meta_title'       => trim((string)($language_description['meta_title'] ?? $language_description['title'] ?? '')),
					'meta_description' => (string)($language_description['meta_description'] ?? ''),
					'meta_keyword'     => (string)($language_description['meta_keyword'] ?? ''),
					'description'      => (string)($language_description['description'] ?? '')
				];
			} elseif (isset($descriptions[$default_language_id])) {
				// 缺失时使用默认语言兜底
				$default = $descriptions[$default_language_id];
				$normalized_descriptions[$language_id] = [
					'title'            => trim((string)($default['title'] ?? '')),
					'sub_title'        => (string)($default['sub_title'] ?? ''),
					'meta_title'       => trim((string)($default['meta_title'] ?? $default['title'] ?? '')),
					'meta_description' => (string)($default['meta_description'] ?? ''),
					'meta_keyword'     => (string)($default['meta_keyword'] ?? ''),
					'description'      => (string)($default['description'] ?? '')
				];
			}
		}

		$allowed_status = ['draft', 'active', 'suspended'];
		if (!in_array($status, $allowed_status, true)) {
			$status = 'draft';
		}

		$start_time = null;
		if ($start_time_raw !== '') {
			$start_timestamp = strtotime($start_time_raw);
			if ($start_timestamp === false) {
				$json['error']['start_time'] = $this->language->get('error_start_time');
			} else {
				$start_time = date('Y-m-d H:i:s', $start_timestamp);
			}
		}

		$end_time = null;
		if ($end_time_raw !== '') {
			$end_timestamp = strtotime($end_time_raw);
			if ($end_timestamp === false) {
				$json['error']['end_time'] = $this->language->get('error_end_time_invalid');
			} else {
				$end_time = date('Y-m-d H:i:s', $end_timestamp);
			}
		}

		if ($start_time && $end_time && strtotime($start_time) > strtotime($end_time)) {
			$json['error']['end_time'] = $this->language->get('error_end_time_before_start');
		}

		if ($product_id <= 0) {
			$json['error']['product'] = $this->language->get('error_product');
		}

		if ($issue_no < 1) {
			$json['error']['issue_no'] = $this->language->get('error_issue_no');
		}

		if ($total_slots < 1) {
			$json['error']['total_slots'] = $this->language->get('error_total_slots');
		}

		// 验证joined_slots（仅在有值时验证）
		if ($joined_slots !== null && ($joined_slots < 0 || $joined_slots > $total_slots)) {
			$json['error']['joined_slots'] = $this->language->get('error_joined_slots');
		}
		
		// 确保joined_slots不为null
		if ($joined_slots === null) {
			$joined_slots = 0;
		}

		if ($price <= 0) {
			$json['error']['price'] = $this->language->get('error_price');
		}

		// 多语言校验
		foreach ($normalized_descriptions as $language_id => $value) {
			if (!oc_validate_length($value['title'], 3, 255)) {
				$json['error']['title_' . $language_id] = $this->language->get('error_title');
			}

			if (!oc_validate_length($value['meta_title'], 3, 255)) {
				$json['error']['meta_title_' . $language_id] = $this->language->get('error_meta_title');
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('catalog/duobao');

			$data = [
				'product_id'   => $product_id,
				'price'        => $price,
				'status'       => $status,
				'descriptions' => $normalized_descriptions,
				'issue'        => [
					'issue_id'     => $issue_id,
					'issue_no'     => $issue_no,
					'status'       => $status,
					'total_slots'  => $total_slots,
					'joined_slots' => $joined_slots,
					'start_time'   => $start_time,
					'end_time'     => $end_time
				]
			];

			if (!$duobao_id) {
				$duobao_id = $this->model_catalog_duobao->addDuobao($data);
			} else {
				$this->model_catalog_duobao->editDuobao($duobao_id, $data);
			}

			// 保存机器人配置
			$robot_enabled = (int)($this->request->post['robot_enabled'] ?? 0);
			$robot_target_percent = (int)($this->request->post['robot_target_percent'] ?? 80);
			$auto_draw_type = (string)($this->request->post['auto_draw_type'] ?? '');
			$robot_schedules = $this->request->post['robot_schedules'] ?? [];

			// 获取当前期次ID
			$current_issue = $this->model_catalog_duobao->getIssue($duobao_id, $issue_id ?: null);
			if ($current_issue) {
				$current_issue_id = (int)$current_issue['issue_id'];
				
				// 保存机器人配置
				$this->model_catalog_duobao->saveRobotConfig($duobao_id, $current_issue_id, [
					'robot_enabled'       => $robot_enabled,
					'robot_target_percent' => $robot_target_percent
				]);

				// 更新auto_draw_type
				$this->model_catalog_duobao->updateIssueAutoDrawType($current_issue_id, $auto_draw_type);

				// 保存时间段配置
				$this->model_catalog_duobao->saveRobotSchedules($current_issue_id, $robot_schedules);
			}

			$json['success'] = $this->language->get('text_success_save');
			$json['duobao_id'] = $duobao_id;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * 删除夺宝
	 */
	public function delete(): void {
		$this->load->language('catalog/duobao');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/duobao')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$selected = $this->request->post['selected'] ?? [];
		if (!$selected) {
			$json['error'] = $this->language->get('error_selected');
		}

		if (!$json) {
			$this->load->model('catalog/duobao');
			$this->model_catalog_duobao->deleteDuobaos($selected);
			$json['success'] = $this->language->get('text_success_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * 开奖页面
	 */
	public function draw(): void {
		$this->load->language('catalog/duobao');
		$this->document->setTitle($this->language->get('heading_title'));

		$duobao_id = (int)($this->request->get['duobao_id'] ?? 0);
		$issue_id = (int)($this->request->get['issue_id'] ?? 0);

		$this->load->model('catalog/duobao');

		$duobao_info = $this->model_catalog_duobao->getDuobao($duobao_id);
		$issue_info = $this->model_catalog_duobao->getIssue($duobao_id, $issue_id ?: null);

		if (!$duobao_info || !$issue_info) {
			$this->response->redirect($this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_draw'),
			'href' => $this->url->link('catalog/duobao.draw', 'user_token=' . $this->session->data['user_token'] . '&duobao_id=' . $duobao_id . '&issue_id=' . $issue_info['issue_id'])
		];

		$data['action'] = $this->url->link('catalog/duobao.drawSave', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token']);

		$descriptions = $this->model_catalog_duobao->getDescriptions($duobao_id);
		$issue_descriptions = $this->model_catalog_duobao->getIssueDescriptions($issue_info['issue_id']);
		$language_id = (int)$this->config->get('config_language_id');
		$first_key = $descriptions ? array_key_first($descriptions) : null;
		$title = $descriptions[$language_id]['title'] ?? ($first_key !== null ? ($descriptions[$first_key]['title'] ?? '') : '');
		$notes = $issue_descriptions[$language_id]['description'] ?? ($issue_descriptions ? (reset($issue_descriptions)['description'] ?? '') : '');

		// 获取票券统计
		$ticket_stats = $this->model_catalog_duobao->getIssueTicketStats($issue_info['issue_id']);

		$data['duobao'] = [
			'duobao_id'          => $duobao_info['duobao_id'],
			'issue_id'           => $issue_info['issue_id'],
			'title'              => $title,
			'product_id'         => $duobao_info['product_id'],
			'product_name'       => $duobao_info['product_name'] ?? '',
			'issue_no'           => $issue_info['issue_no'],
			'total_slots'        => $issue_info['total_slots'],
			'joined_slots'       => $issue_info['joined_slots'],
			'status'             => $issue_info['status'],
			'winner_customer_id' => $issue_info['winner_customer_id'],
			'winner_order_id'    => $issue_info['winner_order_id'],
			'winner_ticket'      => $issue_info['winner_ticket'],
			'auto_draw_type'     => $issue_info['auto_draw_type'] ?? '',
			'notes'              => $notes,
			'ticket_stats'       => $ticket_stats
		];

		$data['status_options'] = [
			'completed' => $this->language->get('text_status_completed'),
			'cancelled' => $this->language->get('text_status_cancelled')
		];

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/duobao_draw', $data));
	}

	/**
	 * 开奖提交
	 */
	public function drawSave(): void {
		$this->load->language('catalog/duobao');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/duobao')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$duobao_id = (int)($this->request->post['duobao_id'] ?? 0);
		$issue_id = (int)($this->request->post['issue_id'] ?? 0);
		$status = (string)($this->request->post['status'] ?? 'completed');
		$winner_customer_id = $this->request->post['winner_customer_id'] ?? '';
		$winner_order_id = $this->request->post['winner_order_id'] ?? '';
		$winner_ticket = trim((string)($this->request->post['winner_ticket'] ?? ''));
		$notes = (string)($this->request->post['notes'] ?? '');

		$allowed_status = ['completed', 'cancelled'];
		if (!in_array($status, $allowed_status, true)) {
			$status = 'completed';
		}

		if (!$duobao_id || !$issue_id) {
			$json['error']['warning'] = $this->language->get('error_not_found');
		}

		if ($status === 'completed' && $winner_ticket === '') {
			$json['error']['winner_ticket'] = $this->language->get('error_winner_ticket');
		}

		if (!$json) {
			$this->load->model('catalog/duobao');

			$this->model_catalog_duobao->drawDuobao($duobao_id, $issue_id, [
				'status'             => $status,
				'winner_customer_id' => ($winner_customer_id !== '' ? (int)$winner_customer_id : null),
				'winner_order_id'    => ($winner_order_id !== '' ? (int)$winner_order_id : null),
				'winner_ticket'      => $winner_ticket,
				'notes'              => $notes
			]);

			$json['success'] = $this->language->get('text_success_draw');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * 自动选择中奖号码（AJAX）
	 */
	public function autoSelectWinner(): void {
		$this->load->language('catalog/duobao');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/duobao')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$issue_id = (int)($this->request->post['issue_id'] ?? 0);
		$auto_draw_type = (string)($this->request->post['auto_draw_type'] ?? 'random');

		if (!$issue_id) {
			$json['error'] = $this->language->get('error_not_found');
		}

		if (!$json) {
			$this->load->model('catalog/duobao');

			$winner_result = $this->model_catalog_duobao->autoDrawWinner($issue_id, $auto_draw_type);

			if ($winner_result['success']) {
				$json['success'] = true;
				$json['winner_ticket'] = $winner_result['winner_ticket'];
				$json['winner_customer_id'] = $winner_result['winner_customer_id'];
				$json['ticket_type'] = $winner_result['ticket_type'];
				
				$type_text = $winner_result['ticket_type'] === 'real' ? '真人' : '机器人';
				$json['message'] = "已自动选中 {$type_text} 票券：" . $winner_result['winner_ticket'];
				
				if (isset($winner_result['message'])) {
					$json['message'] .= "\n" . $winner_result['message'];
				}
			} else {
				$json['error'] = $winner_result['message'] ?? '自动选择失败';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

			/**
	 * 导出活动配置为JSON
	 */
	public function export(): void {
		// ⚠️ 清除所有之前的输出，确保JSON纯净
		if (ob_get_length()) {
			ob_clean();
		}
		
		try {
			->load->language('catalog/duobao');
			->load->model('catalog/duobao');

			 = (int)(->request->get['duobao_id'] ?? 0);
			 = (int)(->request->get['issue_id'] ?? 0);

			if (! || !) {
				->response->addHeader('HTTP/1.1 400 Bad Request');
				->response->addHeader('Content-Type: application/json; charset=utf-8');
				->response->setOutput(json_encode(['error' => '参数错误'], JSON_UNESCAPED_UNICODE));
				return;
			}

			// 获取活动信息
			 = ->model_catalog_duobao->getDuobao();
			 = ->model_catalog_duobao->getIssue(, );

			if (! || !) {
				->response->addHeader('HTTP/1.1 404 Not Found');
				->response->addHeader('Content-Type: application/json; charset=utf-8');
				->response->setOutput(json_encode(['error' => '活动不存在'], JSON_UNESCAPED_UNICODE));
				return;
			}

			// 获取机器人配置（带默认值）
			 = ->model_catalog_duobao->getRobotConfig();
			if (!) {
				 = ['robot_enabled' => 0, 'robot_target_percent' => 80];
			}
			
			 = ->model_catalog_duobao->getRobotSchedules();
			if (!is_array()) {
				 = [];
			}

			 = '';
			if (!empty(['start_time'])) {
				 = date('Y-m-d\TH:i', strtotime(['start_time']));
			}

			 = '';
			if (!empty(['end_time'])) {
				 = date('Y-m-d\TH:i', strtotime(['end_time']));
			}

			// 构建导出数据（期次规格 + 机器人配置）
			 = [
				'export_version'  => '2.1',
				'export_time'     => date('Y-m-d H:i:s'),
				'export_type'     => 'issue_and_robot_config',
				'source_issue_no' => ['issue_no'] ?? '',
				'source' => [
					'duobao_id' => ,
					'issue_id'  => ,
					'title'     => ['title'] ?? ''
				],
				'product' => [
					'product_id'   => (int)(['product_id'] ?? 0),
					'product_name' => ['product_name'] ?? ''
				],
				'issue' => [
					'issue_no'     => (int)(['issue_no'] ?? 0),
					'status'       => ['status'] ?? '',
					'total_slots'  => (int)(['total_slots'] ?? 0),
					'joined_slots' => (int)(['joined_slots'] ?? 0),
					'price'        => isset(['price']) ? (float)['price'] : 0.0,
					'start_time'   => ,
					'end_time'     => 
				],
				'robot' => [
					'enabled'        => isset(['robot_enabled']) ? (bool)['robot_enabled'] : false,
					'target_percent' => isset(['robot_target_percent']) ? (int)['robot_target_percent'] : 80,
					'auto_draw_type' => ['auto_draw_type'] ?? 'random',
					'schedules'      => []
				]
			];

			// 添加时间段配置（带默认值）
			foreach ( as ) {
				 = ['start_time'] ?? '';
				if () {
					 = date('Y-m-d\TH:i', strtotime());
				}

				 = ['end_time'] ?? '';
				if () {
					 = date('Y-m-d\TH:i', strtotime());
				}

				 = ['quantity_min'] ?? 1;
				 = ['quantity_max'] ?? 5;

				 = ['interval_min'] ?? (['purchase_interval_min'] ?? 30);
				 = ['interval_max'] ?? (['purchase_interval_max'] ?? 120);

				['robot']['schedules'][] = [
					'start_time'     => ,
					'end_time'       => ,
					'quantity_min'   => (int),
					'quantity_max'   => (int),
					'target_percent' => isset(['target_percent']) ? (float)['target_percent'] : 70.0,
					'interval_min'   => (int),
					'interval_max'   => (int),
				];
			}

			 = 'duobao_config_' . date('YmdHis') . '.json';

			// 再次清理输出缓冲，避免意外输出
			if (ob_get_length()) {
				ob_clean();
			}

			// 输出JSON文件
			->response->addHeader('Content-Type: application/json; charset=utf-8');
			->response->addHeader('Content-Disposition: attachment; filename="' .  . '"');
			->response->addHeader('Cache-Control: no-cache, must-revalidate');
			->response->setOutput(json_encode(, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
			
		} catch (Exception ) {
			// 错误处理
			if (ob_get_length()) {
				ob_clean();
			}
			->response->addHeader('HTTP/1.1 500 Internal Server Error');
			->response->addHeader('Content-Type: application/json; charset=utf-8');
			->response->setOutput(json_encode(['error' => '导出失败: ' . ->getMessage()], JSON_UNESCAPED_UNICODE));
		}
	}public function import(): void {
		$this->load->language('catalog/duobao');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/duobao')) {
			$json['error'] = $this->language->get('error_permission');
		}

		// 检查文件上传
		if (!isset($_FILES['config_file']) || $_FILES['config_file']['error'] !== UPLOAD_ERR_OK) {
			$json['error'] = '请选择要导入的配置文件';
		}

		if (!$json) {
			try {
				// 读取文件内容，确保使用UTF-8编码
				$file_content = file_get_contents($_FILES['config_file']['tmp_name']);
				
				// 检测并转换编码（如果不是UTF-8）
				$encoding = mb_detect_encoding($file_content, ['UTF-8', 'GBK', 'GB2312', 'ISO-8859-1'], true);
				if ($encoding && $encoding !== 'UTF-8') {
					$file_content = mb_convert_encoding($file_content, 'UTF-8', $encoding);
				}
				
				// 解码JSON
				$config_data = json_decode($file_content, true);

				if (json_last_error() !== JSON_ERROR_NONE) {
					$json['error'] = 'JSON格式错误：' . json_last_error_msg();
				} elseif (!isset($config_data['export_version'])) {
					$json['error'] = '配置文件格式不正确（缺少版本信息）';
				} elseif (!isset($config_data['robot'])) {
					$json['error'] = '配置文件格式不正确（缺少机器人配置）';
				} else {
					// 验证机器人配置字段
					$robot = $config_data['robot'];
					if (!isset($robot['enabled']) || !isset($robot['target_percent'])) {
						$json['error'] = '机器人配置缺少必要字段';
					} else {
						// 返回配置数据，供前端填充表单
						$json['success'] = true;
						$json['message'] = '机器人配置导入成功，请检查并修改时间后保存';
						$json['config'] = $config_data;
					}
				}
			} catch (Exception $e) {
				$json['error'] = '导入失败：' . $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * 状态映射
	 *
	 * @return array<string, string>
	 */
	private function getStatusMap(): array {
		return [
			'draft'     => $this->language->get('text_status_draft'),
			'active'    => $this->language->get('text_status_active'),
			'suspended' => $this->language->get('text_status_suspended'),
			'completed' => $this->language->get('text_status_completed'),
			'cancelled' => $this->language->get('text_status_cancelled')
		];
	}

	/**
	 * 获取分类列表（用于商品选择器）
	 */
	public function getCategoriesForSelector(): void {
		$this->load->model('catalog/category');
		
		$json = [];
		
		try {
			// 获取所有分类
			$filter_data = [
				'sort'  => 'name',
				'order' => 'ASC'
			];
			
			$categories = $this->model_catalog_category->getCategories($filter_data);
			
			$json['success'] = true;
			$json['categories'] = [];
			
			foreach ($categories as $category) {
				$json['categories'][] = [
					'category_id'   => $category['category_id'],
					'name'          => $category['name'],
					'category_name' => $category['name']
				];
			}
			
		} catch (\Exception $e) {
			$json['success'] = false;
			$json['error'] = '加载分类失败: ' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * 获取商品列表（用于商品选择器）
	 */
	public function getProductsForSelector(): void {
		$this->load->model('catalog/product');
		
		$json = [];
		
		try {
			$filter_data = [
				'sort'  => 'p.product_id',
				'order' => 'DESC'
			];
			
			// 分页参数
			if (isset($this->request->get['start'])) {
				$filter_data['start'] = (int)$this->request->get['start'];
			} else {
				$filter_data['start'] = 0;
			}
			
			if (isset($this->request->get['limit'])) {
				$filter_data['limit'] = (int)$this->request->get['limit'];
			} else {
				$filter_data['limit'] = 20;
			}
			
			// 分类筛选
			if (isset($this->request->get['filter_category_id']) && $this->request->get['filter_category_id'] > 0) {
				$filter_data['filter_category_id'] = (int)$this->request->get['filter_category_id'];
			}
			
			// 名称搜索
			if (isset($this->request->get['filter_name']) && trim($this->request->get['filter_name']) != '') {
				$filter_data['filter_name'] = trim($this->request->get['filter_name']);
			}
			
			// 获取商品列表
			$products = $this->model_catalog_product->getProducts($filter_data);
			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);
			
			$json['success'] = true;
			$json['products'] = [];
			
			foreach ($products as $product) {
				$json['products'][] = [
					'product_id'   => $product['product_id'],
					'name'         => $product['name'],
					'product_name' => $product['name'],
					'model'        => $product['model'],
					'price'        => $this->currency->format($product['price'], $this->config->get('config_currency'))
				];
			}
			
			$json['product_total'] = $product_total;
			
		} catch (\Exception $e) {
			$json['success'] = false;
			$json['error'] = '加载商品失败: ' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * 获取商品详情（用于商品选择器）
	 */
	public function getProductDetailsForSelector(): void {
		$this->load->model('catalog/product');
		
		$json = [];
		
		try {
			$product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
			
			if ($product_id) {
				$product = $this->model_catalog_product->getProduct($product_id);
				
				if ($product) {
					// 获取所有语言的描述
					$product_descriptions = $this->model_catalog_product->getDescriptions($product_id);
					
					$json['success'] = true;
					$json['product'] = [
						'product_id'          => $product['product_id'],
						'name'                => $product['name'],
						'product_name'        => $product['name'],
						'model'               => $product['model'],
						'price'               => $product['price'],
						'description'         => $product['description'],
						'meta_title'          => $product['meta_title'],
						'meta_description'    => $product['meta_description'],
						'meta_keyword'        => $product['meta_keyword'],
						'product_description' => $product_descriptions
					];
				} else {
					$json['success'] = false;
					$json['error'] = '商品不存在';
				}
			} else {
				$json['success'] = false;
				$json['error'] = '商品ID无效';
			}
			
		} catch (\Exception $e) {
			$json['success'] = false;
			$json['error'] = '加载商品详情失败: ' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}



