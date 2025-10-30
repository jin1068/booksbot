<?php
namespace Opencart\Admin\Controller\Extension\Opencart\Module;
/**
 * Class Duobao
 *
 * 后台一元夺宝首页模块配置
 */
class Duobao extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/opencart/module/duobao');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/opencart/module/duobao', 'user_token=' . $this->session->data['user_token'])
			];
		} else {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/opencart/module/duobao', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'])
			];
		}

		if (!isset($this->request->get['module_id'])) {
			$data['save'] = $this->url->link('extension/opencart/module/duobao.save', 'user_token=' . $this->session->data['user_token']);
		} else {
			$data['save'] = $this->url->link('extension/opencart/module/duobao.save', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . (int)$this->request->get['module_id']);
		}

		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		// Load existing settings
		if (isset($this->request->get['module_id'])) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule((int)$this->request->get['module_id']);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['limit'] = $module_info['limit'] ?? 4;
		$data['width'] = $module_info['width'] ?? 300;
		$data['height'] = $module_info['height'] ?? 300;
		$data['status'] = $module_info['status'] ?? 1;

		$data['module_id'] = (int)($this->request->get['module_id'] ?? 0);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/opencart/module/duobao', $data));
	}

	/**
	 * 保存配置
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/opencart/module/duobao');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/opencart/module/duobao')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$module_id = (int)($this->request->get['module_id'] ?? 0);
		$name = trim((string)($this->request->post['name'] ?? ''));
		$limit = (int)($this->request->post['limit'] ?? 4);
		$width = (int)($this->request->post['width'] ?? 0);
		$height = (int)($this->request->post['height'] ?? 0);

		if (!oc_validate_length($name, 3, 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if ($limit < 1) {
			$json['error']['limit'] = $this->language->get('error_limit');
		}

		if ($width < 1) {
			$json['error']['width'] = $this->language->get('error_width');
		}

		if ($height < 1) {
			$json['error']['height'] = $this->language->get('error_height');
		}

		if (!$json) {
			$this->load->model('setting/module');

			$module_data = [
				'name'   => $name,
				'limit'  => $limit,
				'width'  => $width,
				'height' => $height,
				'status' => (int)($this->request->post['status'] ?? 0)
			];

			if (!$module_id) {
				$module_id = $this->model_setting_module->addModule('opencart.duobao', $module_data);
			} else {
				$this->model_setting_module->editModule($module_id, $module_data);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
