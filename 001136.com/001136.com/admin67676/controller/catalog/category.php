<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * Class Category
 *
 * Can be loaded using $this->load->controller('catalog/category');
 *
 * @package Opencart\Admin\Controller\Catalog
 */
class Category extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('catalog/category');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url)
		];

		$data['repair'] = $this->url->link('catalog/category.repair', 'user_token=' . $this->session->data['user_token']);
		$data['add'] = $this->url->link('catalog/category.form', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('catalog/category.delete', 'user_token=' . $this->session->data['user_token']);

		$data['list'] = $this->load->controller('catalog/category.getList');

		$data['filter_name'] = $filter_name;
		$data['filter_status'] = $filter_status;

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/category', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('catalog/category');

		$this->response->setOutput($this->load->controller('catalog/category.getList'));
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = (string)$this->request->get['sort'];
		} else {
			$sort = 'sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = (string)$this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['action'] = $this->url->link('catalog/category.list', 'user_token=' . $this->session->data['user_token'] . $url);

		// Category
		$data['categories'] = [];

		$filter_data = [
			'filter_name'   => $filter_name,
			'filter_status' => $filter_status,
			'sort'          => $sort,
			'order'         => $order,
			'start'         => ($page - 1) * $this->config->get('config_pagination_admin'),
			'limit'         => $this->config->get('config_pagination_admin')
		];

		// Image
		$this->load->model('tool/image');

		$this->load->model('catalog/category');

		$results = $this->model_catalog_category->getCategories($filter_data);

		foreach ($results as $result) {
			$image = $result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))
				? $result['image']
				: 'no_image.png';

			$data['categories'][] = [
				'image' => $this->model_tool_image->resize($image, 40, 40),
				'edit'  => $this->url->link('catalog/category.form', 'user_token=' . $this->session->data['user_token'] . '&category_id=' . $result['category_id'] . $url)
			] + $result;
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_name'] = $this->url->link('catalog/category.list', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url);
		$data['sort_sort_order'] = $this->url->link('catalog/category.list', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url);

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$category_total = $this->model_catalog_category->getTotalCategories($filter_data);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $category_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('catalog/category.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($category_total - $this->config->get('config_pagination_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $category_total, ceil($category_total / $this->config->get('config_pagination_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		return $this->load->view('catalog/category_list', $data);
	}

	/**
	 * Form
	 *
	 * @return void
	 */
	public function form(): void {
		$this->load->language('catalog/category');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');

		$data['text_form'] = !isset($this->request->get['category_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url)
		];

		$data['save'] = $this->url->link('catalog/category.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url);

		if (isset($this->request->get['category_id'])) {
			$this->load->model('catalog/category');

			$category_info = $this->model_catalog_category->getCategory((int)$this->request->get['category_id']);
		}

		if (!empty($category_info)) {
			$data['category_id'] = $category_info['category_id'];
		} else {
			$data['category_id'] = 0;
		}

		// Language
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (!empty($category_info)) {
			$data['category_description'] = $this->model_catalog_category->getDescriptions($category_info['category_id']);
		} else {
			$data['category_description'] = [];
		}

		if (!empty($category_info)) {
			$data['path'] = $category_info['path'];
		} else {
			$data['path'] = '';
		}

		if (!empty($category_info)) {
			$data['parent_id'] = $category_info['parent_id'];
		} else {
			$data['parent_id'] = 0;
		}

		// Filter
		$this->load->model('catalog/filter');

		if (!empty($category_info)) {
			$filters = $this->model_catalog_category->getFilters($category_info['category_id']);
		} else {
			$filters = [];
		}

		$data['category_filters'] = [];

		foreach ($filters as $filter_id) {
			$filter_info = $this->model_catalog_filter->getFilter($filter_id);

			if ($filter_info) {
				$data['category_filters'][] = [
					'filter_id' => $filter_info['filter_id'],
					'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
				];
			}
		}

		// Store
		$data['stores'] = [];

		$data['stores'][] = [
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		];

		$this->load->model('setting/store');

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = $result;
		}

		if (!empty($category_info)) {
			$data['category_store'] = $this->model_catalog_category->getStores($category_info['category_id']);
		} else {
			$data['category_store'] = [0];
		}

		// Image
		if (!empty($category_info)) {
			$data['image'] = $category_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', $this->config->get('config_image_default_width'), $this->config->get('config_image_default_height'));

		if ($data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb'] = $this->model_tool_image->resize($data['image'], $this->config->get('config_image_default_width'), $this->config->get('config_image_default_height'));
		} else {
			$data['thumb'] = $data['placeholder'];
		}

		if (!empty($category_info)) {
			$data['sort_order'] = $category_info['sort_order'];
		} else {
			$data['sort_order'] = 0;
		}

		if (!empty($category_info)) {
			$data['status'] = $category_info['status'];
		} else {
			$data['status'] = true;
		}

		// SEO
		$data['category_seo_url'] = [];

		if (!empty($category_info)) {
			$this->load->model('design/seo_url');

			$results = $this->model_design_seo_url->getSeoUrlsByKeyValue('path', $this->model_catalog_category->getPath($category_info['category_id']));

			foreach ($results as $store_id => $languages) {
				foreach ($languages as $language_id => $keyword) {
					$pos = strrpos($keyword, '/');

					if ($pos !== false) {
						$keyword = substr($keyword, $pos + 1);
					}

					$data['category_seo_url'][$store_id][$language_id] = $keyword;
				}
			}
		}

		// Layout
		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		if (!empty($category_info)) {
			$data['category_layout'] = $this->model_catalog_category->getLayouts($category_info['category_id']);
		} else {
			$data['category_layout'] = [];
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/category_form', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('catalog/category');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/category')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$required = [
			'category_id'          => 0,
			'category_description' => [],
			'image'                => '',
			'parent_id'            => 0,
			'sort_order'           => 0,
			'status'               => 0
		];

		$post_info = $this->request->post + $required;

		foreach ((array)$post_info['category_description'] as $language_id => $value) {
			if (!oc_validate_length((string)$value['name'], 1, 255)) {
				$json['error']['name_' . $language_id] = $this->language->get('error_name');
			}

			if (!oc_validate_length((string)$value['meta_title'], 1, 255)) {
				$json['error']['meta_title_' . $language_id] = $this->language->get('error_meta_title');
			}
		}

		// Category
		$this->load->model('catalog/category');

		if (isset($post_info['category_id']) && $post_info['parent_id']) {
			$results = $this->model_catalog_category->getPaths((int)$post_info['parent_id']);

			foreach ($results as $result) {
				if ($result['path_id'] == $post_info['category_id']) {
					$json['error']['parent'] = $this->language->get('error_parent');
					break;
				}
			}
		}

		// SEO
		if ($post_info['category_seo_url']) {
			$this->load->model('design/seo_url');

			foreach ($post_info['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!oc_validate_length($keyword, 1, 64)) {
						$json['error']['keyword_' . $store_id . '_' . $language_id] = $this->language->get('error_keyword');
					}

					if (!oc_validate_path($keyword)) {
						$json['error']['keyword_' . $store_id . '_' . $language_id] = $this->language->get('error_keyword_character');
					}

					$seo_url_info = $this->model_design_seo_url->getSeoUrlByKeyword($keyword, $store_id);

					if ($seo_url_info && (!isset($post_info['category_id']) || $seo_url_info['key'] != 'path' || $seo_url_info['value'] != $this->model_catalog_category->getPath($post_info['category_id']))) {
						$json['error']['keyword_' . $store_id . '_' . $language_id] = $this->language->get('error_keyword_exists');
					}
				}
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			if (!$post_info['category_id']) {
				$json['category_id'] = $this->model_catalog_category->addCategory($post_info);
			} else {
				$this->model_catalog_category->editCategory($post_info['category_id'], $post_info);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Repair
	 *
	 * @return void
	 */
	public function repair(): void {
		$this->load->language('catalog/category');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/category')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('catalog/category');

			$this->model_catalog_category->repairCategories();

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('catalog/category');

		$json = [];

		if (isset($this->request->post['selected'])) {
			$selected = (array)$this->request->post['selected'];
		} else {
			$selected = [];
		}

		if (!$this->user->hasPermission('modify', 'catalog/category')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('catalog/category');

			foreach ($selected as $category_id) {
				$this->model_catalog_category->deleteCategory($category_id);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Autocomplete
	 *
	 * @return void
	 */
	public function autocomplete(): void {
		$json = [];

		$this->load->model('catalog/category');

	$filter_name = isset($this->request->get['filter_name']) ? trim((string)$this->request->get['filter_name']) : '';
	$filter_parent = !empty($this->request->get['filter_parent']);
	$limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : (int)$this->config->get('config_autocomplete_limit');

	if ($limit < 1) {
		$limit = 9999;  // 不限制，显示所有分类
	}

		if ($filter_parent) {
			$parent_id = isset($this->request->get['parent_id']) ? (int)$this->request->get['parent_id'] : 0;

			$filter_data = [
				'sort'             => 'sort_order',
				'order'            => 'ASC',
				'filter_parent_id' => $parent_id,
				'filter_status'    => 1,
				'start'            => 0,
				'limit'            => $limit
			];

			if ($filter_name !== '') {
				$filter_data['filter_name'] = $filter_name;
			}

			$results = $this->model_catalog_category->getCategories($filter_data);

			foreach ($results as $result) {
				$full_name = html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8');
				$parts = array_filter(preg_split('/\s*>\s*/', $full_name), 'strlen');
				$label = array_pop($parts) ?? $full_name;
				$path = implode(' > ', $parts);

				$has_children = $this->model_catalog_category->getTotalCategories([
					'filter_parent_id' => $result['category_id']
				]) > 0;

				$json[] = [
					'category_id'  => (int)$result['category_id'],
					'name'         => $full_name,
					'label'        => $label,
					'path'         => $path,
					'has_children' => $has_children
				];
			}
		} elseif (isset($this->request->get['filter_name'])) {
			$filter_data = [
				'filter_name'   => $filter_name,
				'filter_status' => 1,
				'sort'          => 'name',
				'order'         => 'ASC',
				'start'         => 0,
				'limit'         => $limit
			];

			$results = $this->model_catalog_category->getCategories($filter_data);

			foreach ($results as $result) {
				$full_name = html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8');
				$parts = array_filter(preg_split('/\s*>\s*/', $full_name), 'strlen');
				$label = array_pop($parts) ?? $full_name;
				$path = implode(' > ', $parts);

				$json[] = [
					'category_id'  => (int)$result['category_id'],
					'name'         => $full_name,
					'label'        => $label,
					'path'         => $path,
					'has_children' => $this->model_catalog_category->getTotalCategories([
						'filter_parent_id' => $result['category_id']
					]) > 0
				];
			}

			$sort_order = [];

			foreach ($json as $key => $value) {
				$sort_order[$key] = $value['name'];
			}

			array_multisort($sort_order, SORT_ASC, $json);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Export categories to CSV
	 *
	 * @return void
	 */
	public function export(): void {
		$this->load->language('catalog/category');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		
		// 获取所有分类
		$categories = $this->model_catalog_category->getCategories();
		
		// 设置CSV响应头
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="categories_' . date('Y-m-d_His') . '.csv"');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		$output = fopen('php://output', 'w');
		
		// 添加BOM以便Excel正确识别UTF-8
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 构建表头（字段名）
		$headers = ['category_id', 'parent_id', 'top', 'status', 'sort_order'];
		foreach ($languages as $language) {
			$headers[] = 'name_' . $language['code'];
			$headers[] = 'description_' . $language['code'];
			$headers[] = 'meta_title_' . $language['code'];
			$headers[] = 'meta_description_' . $language['code'];
			$headers[] = 'meta_keyword_' . $language['code'];
			$headers[] = 'seo_url_' . $language['code'];
		}
		$headers[] = 'image';
		$headers[] = 'column';
		
		fputcsv($output, $headers);
		
		// 添加中文说明行（第二行）
		$descriptions = ['【必填】分类ID(留空=新增)', '【必填】父分类ID(0=顶级)', '【必填】顶部显示(1=是/0=否)', '【必填】状态(1=启用/0=禁用)', '排序值'];
		foreach ($languages as $language) {
			$lang_name = $language['name'];
			$descriptions[] = "【必填】{$lang_name}分类名称";
			$descriptions[] = "{$lang_name}商品描述(支持HTML标签)";
			$descriptions[] = "{$lang_name}SEO标题";
			$descriptions[] = "{$lang_name}SEO描述";
			$descriptions[] = "{$lang_name}关键词(逗号分隔)";
			$descriptions[] = "{$lang_name}SEO URL(如: electronics/phones)";
		}
		$descriptions[] = '分类图片路径';
		$descriptions[] = '显示列数';
		
		fputcsv($output, $descriptions);
		
		// 如果没有分类,输出一行示例数据
		if (empty($categories)) {
			$example = ['', '0', '1', '1', '0'];
			foreach ($languages as $language) {
				$example[] = '电子产品';
				$example[] = '各类电子产品，包括手机、电脑、平板等';
				$example[] = '电子产品_SEO标题';
				$example[] = '优质电子产品，价格实惠，品质保证';
				$example[] = '电子产品,手机,电脑,数码';
				$example[] = 'electronics';
			}
			$example[] = 'catalog/demo/category.jpg';
			$example[] = '1';
			fputcsv($output, $example);
		} else {
			// 输出所有分类数据
			foreach ($categories as $category) {
				$category_data = $this->model_catalog_category->getCategory($category['category_id']);
				
				$row = [
					$category_data['category_id'],
					$category_data['parent_id'],
					$category_data['top'] ? '1' : '0',
					$category_data['status'] ? '1' : '0',
					$category_data['sort_order']
				];
				
				foreach ($languages as $language) {
					$description = $this->model_catalog_category->getDescriptions($category_data['category_id']);
					$lang_data = $description[$language['language_id']] ?? [];
					
					$row[] = $lang_data['name'] ?? '';
					$row[] = strip_tags($lang_data['description'] ?? '');
					$row[] = $lang_data['meta_title'] ?? '';
					$row[] = $lang_data['meta_description'] ?? '';
					$row[] = $lang_data['meta_keyword'] ?? '';
					
					// 获取SEO URL
					$seo_urls = $this->model_catalog_category->getSeoUrls($category_data['category_id']);
					$seo_url = '';
					foreach ($seo_urls as $seo) {
						if ($seo['language_id'] == $language['language_id'] && $seo['store_id'] == 0) {
							$seo_url = $seo['keyword'];
							break;
						}
					}
					$row[] = $seo_url;
				}
				
				$row[] = $category_data['image'] ?? '';
				$row[] = $category_data['column'] ?? '1';
				
				fputcsv($output, $row);
			}
		}
		
		fclose($output);
		exit;
	}

	/**
	 * Import categories from CSV
	 *
	 * @return void
	 */
	public function import(): void {
		$this->load->language('catalog/category');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');

		$json = [];

		if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {
			$filename = $this->request->files['file']['tmp_name'];
			
			$languages = $this->model_localisation_language->getLanguages();
			$language_codes = [];
			foreach ($languages as $language) {
				$language_codes[$language['code']] = $language['language_id'];
			}
			
			if (($handle = fopen($filename, 'r')) !== false) {
				// 跳过BOM
				$bom = fread($handle, 3);
				if ($bom != chr(0xEF).chr(0xBB).chr(0xBF)) {
					rewind($handle);
				}
				
				$headers = fgetcsv($handle);
				$row_num = 1;
				$imported = 0;
				$updated = 0;
				$errors = [];
				
				// 检查并跳过第2行的中文说明行
				$first_data_row = fgetcsv($handle);
				if ($first_data_row !== false) {
					// 检测是否为说明行（包含【必填】等中文字符）
					$first_cell = $first_data_row[0] ?? '';
					if (strpos($first_cell, '【必填】') !== false || strpos($first_cell, '必填') !== false) {
						// 这是说明行，跳过它
						$row_num++;
					} else {
						// 这是数据行，需要处理
						if (!empty(array_filter($first_data_row))) {
							$this->processCategory($first_data_row, $headers, $language_codes, $imported, $updated, $errors, $row_num);
						}
						$row_num++;
					}
				}
				
				while (($row = fgetcsv($handle)) !== false) {
					$row_num++;
					
					if (empty(array_filter($row))) {
						continue; // 跳过空行
					}
					
					$this->processCategory($row, $headers, $language_codes, $imported, $updated, $errors, $row_num);
				}
				
				fclose($handle);
				
				if ($errors) {
					$json['warning'] = implode('<br>', $errors);
				}
				
				$json['success'] = sprintf('成功导入 %d 个分类，更新 %d 个分类', $imported, $updated);
			} else {
				$json['error'] = '无法读取CSV文件';
			}
		} else {
			$json['error'] = '请选择要导入的CSV文件';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Process a single category row from CSV
	 *
	 * @param array $row
	 * @param array $headers
	 * @param array $language_codes
	 * @param int &$imported
	 * @param int &$updated
	 * @param array &$errors
	 * @param int $row_num
	 * @return void
	 */
	private function processCategory(array $row, array $headers, array $language_codes, int &$imported, int &$updated, array &$errors, int $row_num): void {
		$data = array_combine($headers, $row);
		
		// 准备分类数据
		$category_data = [
			'parent_id' => (int)($data['parent_id'] ?? 0),
			'top' => isset($data['top']) && $data['top'] == '1' ? 1 : 0,
			'status' => isset($data['status']) && $data['status'] == '1' ? 1 : 0,
			'sort_order' => (int)($data['sort_order'] ?? 0),
			'image' => $data['image'] ?? '',
			'column' => (int)($data['column'] ?? 1),
			'category_description' => [],
			'category_seo_url' => []
		];
		
		// 处理多语言数据和SEO URL
		foreach ($language_codes as $code => $language_id) {
			$category_data['category_description'][$language_id] = [
				'name' => $data['name_' . $code] ?? '',
				'description' => $data['description_' . $code] ?? '',
				'meta_title' => $data['meta_title_' . $code] ?? '',
				'meta_description' => $data['meta_description_' . $code] ?? '',
				'meta_keyword' => $data['meta_keyword_' . $code] ?? ''
			];
			
			// 处理SEO URL
			$seo_url = $data['seo_url_' . $code] ?? '';
			if (!empty($seo_url)) {
				$category_data['category_seo_url'][0][$language_id] = $seo_url;
			}
		}
		
		// 其他字段
		$category_data['category_store'] = [0]; // 默认商店
		$category_data['category_filter'] = [];
		$category_data['category_layout'] = [];
		
		try {
			if (!empty($data['category_id']) && $data['category_id'] > 0) {
				// 更新现有分类
				$category_data['category_id'] = (int)$data['category_id'];
				$this->model_catalog_category->editCategory($category_data['category_id'], $category_data);
				$updated++;
			} else {
				// 添加新分类
				$this->model_catalog_category->addCategory($category_data);
				$imported++;
			}
		} catch (\Exception $e) {
			$errors[] = "第 {$row_num} 行错误: " . $e->getMessage();
		}
	}
}
