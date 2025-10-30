<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * 产品SEO在线编辑器
 * 
 * 功能：直接在网页上批量编辑产品的描述、Meta标签等信息
 * 无需下载CSV，在线即时保存
 */
class ProductSeoOnline extends \Opencart\System\Engine\Controller {
	
	/**
	 * 主页面
	 */
	public function index(): void {
		$this->load->language('catalog/product');
		$this->load->model('localisation/language');
		
		$this->document->setTitle('产品SEO在线编辑');
		
		// 添加必要的CSS和JS
		$this->document->addStyle('view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
		
		$data['breadcrumbs'] = [];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['breadcrumbs'][] = [
			'text' => '产品SEO在线编辑',
			'href' => $this->url->link('catalog/product_seo_online', 'user_token=' . $this->session->data['user_token'])
		];
		
		// 获取所有语言
		$data['languages'] = $this->model_localisation_language->getLanguages();
		
		$data['list_url'] = $this->url->link('catalog/product_seo_online.list', 'user_token=' . $this->session->data['user_token']);
		$data['save_url'] = $this->url->link('catalog/product_seo_online.save', 'user_token=' . $this->session->data['user_token']);
		$data['batch_save_url'] = $this->url->link('catalog/product_seo_online.batchSave', 'user_token=' . $this->session->data['user_token']);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('catalog/product_seo_online', $data));
	}
	
	/**
	 * 获取产品列表（AJAX）
	 */
	public function list(): void {
		$this->load->model('catalog/product');
		$this->load->model('localisation/language');
		
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
		$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;
		$search = isset($this->request->get['search']) ? $this->request->get['search'] : '';
		$start = ($page - 1) * $limit;
		
		$filter_data = [
			'filter_status' => 1,
			'filter_name' => $search,
			'start' => $start,
			'limit' => $limit
		];
		
		// 获取产品列表
		$products = $this->model_catalog_product->getProducts($filter_data);
		$total = $this->model_catalog_product->getTotalProducts($filter_data);
		
		// 获取所有语言
		$languages = $this->model_localisation_language->getLanguages();
		
		$data = [];
		
		foreach ($products as $product) {
			$descriptions = $this->model_catalog_product->getDescriptions($product['product_id']);
			
			$product_data = [
				'product_id' => $product['product_id'],
				'model' => $product['model'],
				'status' => $product['status'],
				'descriptions' => []
			];
			
			foreach ($languages as $language) {
				$desc = $descriptions[$language['language_id']] ?? [];
				
				$product_data['descriptions'][$language['language_id']] = [
					'language_id' => $language['language_id'],
					'language_code' => $language['code'],
					'language_name' => $language['name'],
					'name' => $desc['name'] ?? '',
					'description' => $desc['description'] ?? '',
					'meta_title' => $desc['meta_title'] ?? '',
					'meta_description' => $desc['meta_description'] ?? '',
					'meta_keyword' => $desc['meta_keyword'] ?? '',
					'tag' => $desc['tag'] ?? ''
				];
			}
			
			$data[] = $product_data;
		}
		
		$json = [
			'success' => true,
			'products' => $data,
			'total' => $total,
			'page' => $page,
			'pages' => ceil($total / $limit)
		];
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 保存单个产品（AJAX）
	 */
	public function save(): void {
		$this->load->language('catalog/product');
		$this->load->model('catalog/product');
		
		$json = [];
		
		// 检查权限
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error'] = '您没有权限修改产品';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;
		$descriptions = isset($this->request->post['descriptions']) ? $this->request->post['descriptions'] : [];
		
		if ($product_id <= 0) {
			$json['error'] = '无效的产品ID';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// 检查产品是否存在
		$product = $this->model_catalog_product->getProduct($product_id);
		if (!$product) {
			$json['error'] = '产品不存在';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		try {
			// 更新产品描述
			$this->model_catalog_product->deleteDescriptions($product_id);
			
			foreach ($descriptions as $language_id => $value) {
				$this->model_catalog_product->addDescription($product_id, (int)$language_id, [
					'name' => $value['name'] ?? '',
					'description' => $value['description'] ?? '',
					'meta_title' => $value['meta_title'] ?? '',
					'meta_description' => $value['meta_description'] ?? '',
					'meta_keyword' => $value['meta_keyword'] ?? '',
					'tag' => $value['tag'] ?? ''
				]);
			}
			
			$json['success'] = '产品更新成功';
		} catch (\Exception $e) {
			$json['error'] = '更新失败：' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 批量保存多个产品（AJAX）
	 */
	public function batchSave(): void {
		$this->load->language('catalog/product');
		$this->load->model('catalog/product');
		
		$json = [];
		
		// 检查权限
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error'] = '您没有权限修改产品';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		$products = isset($this->request->post['products']) ? $this->request->post['products'] : [];
		
		if (empty($products)) {
			$json['error'] = '没有要保存的产品';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		$updated_count = 0;
		$errors = [];
		
		foreach ($products as $product_data) {
			$product_id = isset($product_data['product_id']) ? (int)$product_data['product_id'] : 0;
			$descriptions = isset($product_data['descriptions']) ? $product_data['descriptions'] : [];
			
			if ($product_id <= 0) {
				continue;
			}
			
			try {
				// 更新产品描述
				$this->model_catalog_product->deleteDescriptions($product_id);
				
				foreach ($descriptions as $language_id => $value) {
					$this->model_catalog_product->addDescription($product_id, (int)$language_id, [
						'name' => $value['name'] ?? '',
						'description' => $value['description'] ?? '',
						'meta_title' => $value['meta_title'] ?? '',
						'meta_description' => $value['meta_description'] ?? '',
						'meta_keyword' => $value['meta_keyword'] ?? '',
						'tag' => $value['tag'] ?? ''
					]);
				}
				
				$updated_count++;
			} catch (\Exception $e) {
				$errors[] = "产品ID {$product_id}: " . $e->getMessage();
			}
		}
		
		$json['success'] = "成功更新 {$updated_count} 个产品";
		if (!empty($errors)) {
			$json['errors'] = $errors;
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}

