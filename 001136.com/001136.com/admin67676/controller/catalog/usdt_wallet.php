<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * Class UsdtWallet
 */
class UsdtWallet extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('catalog/usdt_wallet');
		
		$this->load->model('tool/image');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_catalog'),
			'href' => $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/usdt_wallet', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('catalog/usdt_wallet.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token']);

		$wallets = $this->config->get('config_usdt_wallets');

		if (!$wallets || !is_array($wallets)) {
			$wallets = [
				[
					'network' => 'TRC20',
					'address' => '',
					'image' => ''
				],
				[
					'network' => 'BEP20',
					'address' => '',
					'image' => ''
				],
				[
					'network' => 'ERC20',
					'address' => '',
					'image' => ''
				]
			];
		}

		// 处理图片缩略图
		foreach ($wallets as $key => $wallet) {
			if (!isset($wallet['image'])) {
				$wallets[$key]['image'] = '';
			}
			
			if (!empty($wallet['image']) && is_file(DIR_IMAGE . $wallet['image'])) {
				$wallets[$key]['thumb'] = $this->model_tool_image->resize($wallet['image'], 100, 100);
			} else {
				$wallets[$key]['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
			}
		}

		$data['wallets'] = $wallets;
		$data['user_token'] = $this->session->data['user_token'];
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_add_row'] = $this->language->get('text_add_row');
		$data['entry_network'] = $this->language->get('entry_network');
		$data['entry_address'] = $this->language->get('entry_address');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_back'] = $this->language->get('button_back');
		$data['button_remove'] = $this->language->get('button_remove');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/usdt_wallet', $data));
	}

	public function upload(): void {
		$this->load->language('catalog/usdt_wallet');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/usdt_wallet')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->files['image']) || !is_uploaded_file($this->request->files['image']['tmp_name'])) {
			$json['error'] = '请选择图片文件';
		}

		if (!$json) {
			$file = $this->request->files['image'];
			$file_extension = '';

			// 检查文件类型
			$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

			if (!in_array($file_extension, $allowed_extensions)) {
				$json['error'] = '不支持的图片格式，仅支持：' . implode(', ', $allowed_extensions);
			}

			// 检查文件大小 (5MB)
			if ($file['size'] > 5 * 1024 * 1024) {
				$json['error'] = '图片大小不能超过5MB';
			}
		}

		if (!$json) {
			$file = $this->request->files['image'];
			$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

			// 创建目录
			$directory = 'catalog/usdt/';
			if (!is_dir(DIR_IMAGE . $directory)) {
				mkdir(DIR_IMAGE . $directory, 0755, true);
			}

			// 生成唯一文件名
			$filename = uniqid() . '_' . time() . '.' . $file_extension;
			$filepath = $directory . $filename;

			// 移动文件
			if (move_uploaded_file($file['tmp_name'], DIR_IMAGE . $filepath)) {
				$this->load->model('tool/image');

				$json['success'] = '图片上传成功';
				$json['path'] = $filepath;
				$json['thumb'] = $this->model_tool_image->resize($filepath, 100, 100);
			} else {
				$json['error'] = '图片上传失败，请检查目录权限';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function save(): void {
		$this->load->language('catalog/usdt_wallet');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/usdt_wallet')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('tool/image');

		$wallets = [];

		$networks = $this->request->post['network'] ?? [];
		$addresses = $this->request->post['address'] ?? [];
		$images = $this->request->post['image'] ?? [];

		foreach ($networks as $index => $network) {
			$network = trim($network);
			$address = trim($addresses[$index] ?? '');
			$image = trim($images[$index] ?? '');

			if ($network === '' && $address === '' && $image === '') {
				continue;
			}

			$row_error = false;

			if ($network === '') {
				$json['error']['network'][$index] = $this->language->get('error_network');
				$row_error = true;
			}

			if ($address === '') {
				$json['error']['address'][$index] = $this->language->get('error_address');
				$row_error = true;
			}

			if (!$row_error) {
				$wallets[] = [
					'network' => $network,
					'address' => $address,
					'image' => $image
				];
			}
		}

		if (!$wallets) {
			$json['error']['warning'] = $this->language->get('error_empty');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('config_usdt', ['config_usdt_wallets' => $wallets]);
			$this->config->set('config_usdt_wallets', $wallets);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
