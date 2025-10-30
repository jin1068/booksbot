<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * 产品SEO直接编辑工具
 * 
 * 功能：在管理后台直接编辑产品的描述、Meta标签等信息
 * 无需导出/导入CSV，直接在线修改
 */
class ProductSeoDirect extends \Opencart\System\Engine\Controller {
	
	/**
	 * 主页面 - 产品列表
	 */
	public function index(): void {
		$this->load->language('catalog/product');
		$this->load->model('catalog/product');
		$this->load->model('localisation/language');
		
		$this->document->setTitle('产品SEO直接编辑');
		
		$data['breadcrumbs'] = [];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['breadcrumbs'][] = [
			'text' => '产品SEO直接编辑',
			'href' => $this->url->link('catalog/product_seo_direct', 'user_token=' . $this->session->data['user_token'])
		];
		
		// 获取所有语言
		$languages = $this->model_localisation_language->getLanguages();
		$data['languages'] = [];
		foreach ($languages as $language) {
			$data['languages'][] = [
				'language_id' => $language['language_id'],
				'name' => $language['name'],
				'code' => $language['code']
			];
		}
		
		// 默认显示中文
		$default_language_id = 1;
		foreach ($languages as $lang) {
			if (strpos($lang['code'], 'zh') !== false) {
				$default_language_id = $lang['language_id'];
				break;
			}
		}
		$data['default_language_id'] = $default_language_id;
		
		// 分页参数
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
		$limit = 20;
		$start = ($page - 1) * $limit;
		
		// 搜索过滤
		$filter_data = [
			'filter_status' => 1,
			'start' => $start,
			'limit' => $limit
		];
		
		if (isset($this->request->get['filter_name'])) {
			$filter_data['filter_name'] = $this->request->get['filter_name'];
			$data['filter_name'] = $this->request->get['filter_name'];
		} else {
			$data['filter_name'] = '';
		}
		
		// 获取产品列表
		$products = $this->model_catalog_product->getProducts($filter_data);
		$total = $this->model_catalog_product->getTotalProducts($filter_data);
		
		$data['products'] = [];
		
		foreach ($products as $product) {
			$descriptions = $this->model_catalog_product->getDescriptions($product['product_id']);
			
			$product_languages = [];
			foreach ($languages as $language) {
				$desc = $descriptions[$language['language_id']] ?? [];
				$product_languages[$language['language_id']] = [
					'name' => $desc['name'] ?? '',
					'has_description' => !empty($desc['description']),
					'has_meta_title' => !empty($desc['meta_title']),
					'has_meta_description' => !empty($desc['meta_description']),
					'has_meta_keyword' => !empty($desc['meta_keyword']),
					'has_tag' => !empty($desc['tag'])
				];
			}
			
			$data['products'][] = [
				'product_id' => $product['product_id'],
				'model' => $product['model'],
				'status' => $product['status'],
				'languages' => $product_languages,
				'edit_url' => $this->url->link('catalog/product_seo_direct.edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'])
			];
		}
		
		// 分页
		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('catalog/product_seo_direct', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);
		
		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));
		
		$data['user_token'] = $this->session->data['user_token'];
		$data['auto_generate_url'] = $this->url->link('catalog/product_seo_direct.autoGenerate', 'user_token=' . $this->session->data['user_token']);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('catalog/product_seo_direct_list', $data));
	}
	
	/**
	 * 编辑单个产品
	 */
	public function edit(): void {
		$this->load->language('catalog/product');
		$this->load->model('catalog/product');
		$this->load->model('localisation/language');
		
		$product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
		
		if (!$product_id) {
			$this->response->redirect($this->url->link('catalog/product_seo_direct', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		
		$product = $this->model_catalog_product->getProduct($product_id);
		if (!$product) {
			$this->response->redirect($this->url->link('catalog/product_seo_direct', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		
		$this->document->setTitle('编辑产品SEO信息');
		
		$data['breadcrumbs'] = [];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['breadcrumbs'][] = [
			'text' => '产品SEO直接编辑',
			'href' => $this->url->link('catalog/product_seo_direct', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['breadcrumbs'][] = [
			'text' => '编辑: ' . $product['model'],
			'href' => $this->url->link('catalog/product_seo_direct.edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product_id)
		];
		
		// 获取所有语言
		$languages = $this->model_localisation_language->getLanguages();
		$descriptions = $this->model_catalog_product->getDescriptions($product_id);
		
		$data['languages'] = [];
		foreach ($languages as $language) {
			$desc = $descriptions[$language['language_id']] ?? [];
			
			$data['languages'][] = [
				'language_id' => $language['language_id'],
				'name' => $language['name'],
				'code' => $language['code'],
				'image' => $language['image'],
				'description' => [
					'name' => $desc['name'] ?? '',
					'description' => $desc['description'] ?? '',
					'meta_title' => $desc['meta_title'] ?? '',
					'meta_description' => $desc['meta_description'] ?? '',
					'meta_keyword' => $desc['meta_keyword'] ?? '',
					'tag' => $desc['tag'] ?? ''
				]
			];
		}
		
		$data['product_id'] = $product_id;
		$data['model'] = $product['model'];
		$data['product_name'] = $descriptions[1]['name'] ?? $product['model'];
		
		$data['save_url'] = $this->url->link('catalog/product_seo_direct.save', 'user_token=' . $this->session->data['user_token']);
		$data['back_url'] = $this->url->link('catalog/product_seo_direct', 'user_token=' . $this->session->data['user_token']);
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('catalog/product_seo_direct_form', $data));
	}
	
	/**
	 * 保存产品SEO信息
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
		
		if (!$product_id) {
			$json['error'] = '无效的产品ID';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// 获取提交的数据
		$descriptions = $this->request->post['product_description'] ?? [];
		
		// 更新产品描述
		try {
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
			
			$json['success'] = '产品SEO信息已成功更新！';
			$json['redirect'] = $this->url->link('catalog/product_seo_direct', 'user_token=' . $this->session->data['user_token']);
		} catch (\Exception $e) {
			$json['error'] = '保存失败：' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 自动生成Meta标签（批量）
	 */
	public function autoGenerate(): void {
		$this->load->model('catalog/product');
		$this->load->model('localisation/language');
		
		$json = [];
		
		// 检查权限
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error'] = '您没有权限修改产品';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		$languages = $this->model_localisation_language->getLanguages();
		
		// 获取所有启用的产品
		$filter_data = [
			'filter_status' => 1,
			'start' => 0,
			'limit' => 1000
		];
		
		$products = $this->model_catalog_product->getProducts($filter_data);
		
		$updated_count = 0;
		
		foreach ($products as $product) {
			$descriptions = $this->model_catalog_product->getDescriptions($product['product_id']);
			
			foreach ($languages as $language) {
				$lang_id = $language['language_id'];
				$desc = $descriptions[$lang_id] ?? [];
				
				$name = $desc['name'] ?? '';
				if (empty($name)) continue;
				
				$updated = false;
				
				// 如果没有Meta标题，自动生成
				if (empty($desc['meta_title'])) {
					$desc['meta_title'] = $name . ' | 官方商城';
					$updated = true;
				}
				
				// 如果没有Meta描述，从描述中提取或生成
				if (empty($desc['meta_description'])) {
					if (!empty($desc['description'])) {
						// 从HTML描述中提取纯文本
						$text = strip_tags($desc['description']);
						$text = preg_replace('/\s+/', ' ', $text);
						$desc['meta_description'] = mb_substr(trim($text), 0, 150) . '...';
					} else {
						$desc['meta_description'] = $name . ' - 高品质产品，立即购买享受优惠。';
					}
					$updated = true;
				}
				
				// 如果有更新，保存
				if ($updated) {
					$this->model_catalog_product->deleteDescriptions($product['product_id']);
					foreach ($descriptions as $lid => $d) {
						if ($lid == $lang_id) {
							$d = $desc;
						}
						$this->model_catalog_product->addDescription($product['product_id'], $lid, $d);
					}
					$updated_count++;
					break; // 每个产品只计数一次
				}
			}
		}
		
		$json['success'] = "已为 {$updated_count} 个产品自动生成Meta标签";
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}

