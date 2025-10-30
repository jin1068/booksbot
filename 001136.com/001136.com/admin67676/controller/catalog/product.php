<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * Class Product
 *
 * Can be loaded using $this->load->controller('catalog/product');
 *
 * @package Opencart\Admin\Controller\Catalog
 */
class Product extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('catalog/product');

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = '';
		}

		$category_path = (string)($this->request->get['category_path'] ?? '');
		$category_path_ids = array_values(array_filter(array_map('intval', explode('_', $category_path))));
		$selected_category_id = $category_path_ids ? (int)end($category_path_ids) : 0;

		if ($category_path === '' && isset($this->request->get['filter_category_id'])) {
			$filter_category_id = $this->request->get['filter_category_id'];
		} else {
			$filter_category_id = '';
		}
	
		if ($selected_category_id) {
			$filter_category_id = (string)$selected_category_id;
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$filter_manufacturer_id = $this->request->get['filter_manufacturer_id'];
		} else {
			$filter_manufacturer_id = '';
		}

		if (isset($this->request->get['filter_price_from'])) {
			$filter_price_from = $this->request->get['filter_price_from'];
		} else {
			$filter_price_from = '';
		}

		if (isset($this->request->get['filter_price_to'])) {
			$filter_price_to = $this->request->get['filter_price_to'];
		} else {
			$filter_price_to = '';
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$filter_quantity_from = $this->request->get['filter_quantity_from'];
		} else {
			$filter_quantity_from = '';
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$filter_quantity_to = $this->request->get['filter_quantity_to'];
		} else {
			$filter_quantity_to = '';
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$filter_quantity_from = $this->request->get['filter_quantity_from'];
		} else {
			$filter_quantity_from = '';
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$filter_quantity_to = $this->request->get['filter_quantity_to'];
		} else {
			$filter_quantity_to = '';
		}

	if (isset($this->request->get['filter_status'])) {
		$filter_status = $this->request->get['filter_status'];
	} else {
		$filter_status = '';
	}

	if (isset($this->request->get['filter_image'])) {
		$filter_image = $this->request->get['filter_image'];
	} else {
		$filter_image = '';
	}

	$this->document->setTitle($this->language->get('heading_title'));

		$preserve_query = '';

		if ($filter_name !== '') {
			$preserve_query .= '&filter_name=' . urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_model !== '') {
			$preserve_query .= '&filter_model=' . urlencode(html_entity_decode($filter_model, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_manufacturer_id !== '') {
			$preserve_query .= '&filter_manufacturer_id=' . (int)$filter_manufacturer_id;
		}

		if ($filter_price_from !== '') {
			$preserve_query .= '&filter_price_from=' . $filter_price_from;
		}

		if ($filter_price_to !== '') {
			$preserve_query .= '&filter_price_to=' . $filter_price_to;
		}

		if ($filter_quantity_from !== '') {
			$preserve_query .= '&filter_quantity_from=' . $filter_quantity_from;
		}

		if ($filter_quantity_to !== '') {
			$preserve_query .= '&filter_quantity_to=' . $filter_quantity_to;
		}

	if ($filter_status !== '') {
		$preserve_query .= '&filter_status=' . $filter_status;
	}

	if ($filter_image !== '') {
		$preserve_query .= '&filter_image=' . $filter_image;
	}

	if (isset($this->request->get['sort'])) {
			$preserve_query .= '&sort=' . (string)$this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$preserve_query .= '&order=' . (string)$this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$preserve_query .= '&page=' . (int)$this->request->get['page'];
		}

		if ($category_path === '' && $filter_category_id !== '') {
			$preserve_query .= '&filter_category_id=' . $filter_category_id;
		}

		$this->load->model('catalog/category');

		$data['category_tree'] = $this->buildCategoryTree($category_path_ids, 0, 0, 3, [], $preserve_query);
		$data['category_path'] = $category_path;
		$data['category_all_link'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $preserve_query);
		$data['selected_category_id'] = $selected_category_id;
		$data['category_has_selection'] = $selected_category_id > 0;
		$data['filter_query'] = $preserve_query;

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if ($category_path === '' && isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if ($category_path !== '') {
			$url .= '&category_path=' . $category_path;
		}

		if ($category_path !== '') {
			$url .= '&category_path=' . $category_path;
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
			'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url)
		];

		$category_query = $category_path !== '' ? '&category_path=' . $category_path : '';

		$data['add_product'] = $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . $preserve_query . $category_query);
		$data['add_category_top'] = $this->url->link('catalog/category.form', 'user_token=' . $this->session->data['user_token']);
		$data['add_category_child'] = $selected_category_id ? $this->url->link('catalog/category.form', 'user_token=' . $this->session->data['user_token'] . '&parent_id=' . $selected_category_id) : '';
		$data['copy'] = $this->url->link('catalog/product.copy', 'user_token=' . $this->session->data['user_token'] . $preserve_query . $category_query);
		$data['delete'] = $this->url->link('catalog/product.delete', 'user_token=' . $this->session->data['user_token'] . $preserve_query . $category_query);

		$data['list'] = $this->load->controller('catalog/product.getList');

		$data['filter_name'] = $filter_name;
		$data['filter_model'] = $filter_model;
		$data['filter_category_id'] = $filter_category_id ? (int)$filter_category_id : '';
		$data['filter_manufacturer_id'] = $filter_manufacturer_id;
		$data['filter_price_from'] = $filter_price_from;
		$data['filter_price_to'] = $filter_price_to;
	$data['filter_quantity_from'] = $filter_quantity_from;
	$data['filter_quantity_to'] = $filter_quantity_to;
	$data['filter_status'] = $filter_status;
	$data['filter_image'] = $filter_image;
	$data['filter_manufacturer'] = '';

		// Category
		if (!empty($category_path_ids)) {
			$this->load->model('catalog/category');

			$names = [];

			foreach ($category_path_ids as $path_category_id) {
				$category_info = $this->model_catalog_category->getCategory($path_category_id);

				if (!empty($category_info['name'])) {
					$names[] = $category_info['name'];
				}
			}

			$data['filter_category'] = $names ? implode(' / ', $names) : '';
		} elseif (!empty($filter_category_id)) {
			$this->load->model('catalog/category');

			$category_info = $this->model_catalog_category->getCategory($filter_category_id);

			$data['filter_category'] = !empty($category_info['name']) ? (!empty($category_info['path']) ? implode(' > ', [$category_info['path'], $category_info['name']]) : $category_info['name']) : '';
		} else {
			$data['filter_category'] = '';
		}

		// Manufacturer
		if (!empty($filter_manufacturer_id)) {
			$this->load->model('catalog/manufacturer');

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($filter_manufacturer_id);

			$data['filter_manufacturer'] = !empty($manufacturer_info['name']) ? $manufacturer_info['name'] : '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product', $data));
	}

	protected function buildCategoryTree(array $path_ids, int $parent_id = 0, int $depth = 0, int $max_depth = 3, array $trail = [], string $query = ''): array {
		$nodes = [];

		$children = $this->model_catalog_category->getChildren($parent_id);

		foreach ($children as $child) {
			$current_trail = array_merge($trail, [(int)$child['category_id']]);
			$path = implode('_', $current_trail);

			$is_active = in_array((int)$child['category_id'], $path_ids, true);
			$is_current = $path_ids ? ((int)end($path_ids) === (int)$child['category_id']) : false;

			$href = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $query . '&category_path=' . $path);

			$node = [
				'category_id'   => (int)$child['category_id'],
				'name'          => $child['name'],
				'path'          => $path,
				'href'          => $href,
				'active'        => $is_active,
				'current'       => $is_current,
				'has_children'  => (int)$child['children'] > 0,
				'children'      => []
			];

			if ($node['has_children'] && ($depth + 1 < $max_depth || $is_active)) {
				$node['children'] = $this->buildCategoryTree($path_ids, (int)$child['category_id'], $depth + 1, $max_depth, $current_trail, $query);
			}

			$nodes[] = $node;
		}

		return $nodes;
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('catalog/product');

		$this->response->setOutput($this->load->controller('catalog/product.getList'));
	}

	/**
	 * @return string
	 */
	public function getList(): string {
		if (isset($this->request->get['category_path'])) {
			$category_path = (string)$this->request->get['category_path'];
		} else {
			$category_path = '';
		}

		$category_path_ids = array_values(array_filter(array_map('intval', explode('_', $category_path))));
		$selected_category_id = $category_path_ids ? (int)end($category_path_ids) : 0;

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = '';
		}

		if ($category_path === '' && isset($this->request->get['filter_category_id'])) {
			$filter_category_id = $this->request->get['filter_category_id'];
		} else {
			$filter_category_id = '';
		}

		if ($selected_category_id) {
			$filter_category_id = $selected_category_id;
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$filter_manufacturer_id = $this->request->get['filter_manufacturer_id'];
		} else {
			$filter_manufacturer_id = '';
		}

		if (isset($this->request->get['filter_price_from'])) {
			$filter_price_from = $this->request->get['filter_price_from'];
		} else {
			$filter_price_from = '';
		}

		if (isset($this->request->get['filter_price_to'])) {
			$filter_price_to = $this->request->get['filter_price_to'];
		} else {
			$filter_price_to = '';
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$filter_quantity_from = $this->request->get['filter_quantity_from'];
		} else {
			$filter_quantity_from = '';
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$filter_quantity_to = $this->request->get['filter_quantity_to'];
		} else {
			$filter_quantity_to = '';
		}

	if (isset($this->request->get['filter_status'])) {
		$filter_status = $this->request->get['filter_status'];
	} else {
		$filter_status = '';
	}

	if (isset($this->request->get['filter_image'])) {
		$filter_image = $this->request->get['filter_image'];
	} else {
		$filter_image = '';
	}

	if (isset($this->request->get['sort'])) {
		$sort = (string)$this->request->get['sort'];
	} else {
		$sort = 'pd.name';
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

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

	if (isset($this->request->get['filter_quantity_to'])) {
		$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
	}

	if (isset($this->request->get['filter_status'])) {
		$url .= '&filter_status=' . $this->request->get['filter_status'];
	}

	if (isset($this->request->get['filter_image'])) {
		$url .= '&filter_image=' . $this->request->get['filter_image'];
	}

	if ($category_path !== '') {
		$url .= '&category_path=' . $category_path;
	}

	if (isset($this->request->get['page'])) {
		$url .= '&page=' . $this->request->get['page'];
	}

	$data['action'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . $url);

		// Product
		$data['products'] = [];

	$filter_data = [
		'filter_name'            => $filter_name,
		'filter_model'           => $filter_model,
		'filter_category_id'     => (int)$filter_category_id,
		'filter_manufacturer_id' => $filter_manufacturer_id,
		'filter_price_from'      => $filter_price_from,
		'filter_price_to'        => $filter_price_to,
		'filter_quantity_from'   => $filter_quantity_from,
		'filter_quantity_to'     => $filter_quantity_to,
		'filter_status'          => $filter_status,
		'filter_image'           => $filter_image,
		'sort'                   => $sort,
		'order'                  => $order,
		'start'                  => ($page - 1) * $this->config->get('config_pagination_admin'),
		'limit'                  => $this->config->get('config_pagination_admin')
	];

		$this->load->model('catalog/product');

		// Image
		$this->load->model('tool/image');

		$results = $this->model_catalog_product->getProducts($filter_data);

		foreach ($results as $result) {
			if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $result['image'];
			} else {
				$image = 'no_image.png';
			}

			$special = '';

			$product_discounts = $this->model_catalog_product->getDiscounts($result['product_id']);

			foreach ($product_discounts as $product_discount) {
				if (($product_discount['date_start'] == '0000-00-00' || strtotime($product_discount['date_start']) < time()) && ($product_discount['date_end'] == '0000-00-00' || strtotime($product_discount['date_end']) > time())) {
					$special = $this->currency->format($product_discount['price'], $this->config->get('config_currency'));

					break;
				}
			}

			$data['products'][] = [
				'image'   => $this->model_tool_image->resize($image, 40, 40),
				'price'   => $this->currency->format($result['price'], $this->config->get('config_currency')),
				'special' => $special,
				'edit'    => $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . ($result['master_id'] ? '&master_id=' . $result['master_id'] : '') . $url),
				'variant' => (!$result['master_id'] ? $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&master_id=' . $result['product_id'] . $url) : '')
			] + $result;
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_product_id'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . '&sort=p.product_id' . $url);
		$data['sort_name'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url);
		$data['sort_model'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url);
		$data['sort_price'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url);
		$data['sort_quantity'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url);
		$data['sort_sort_order'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url);
		$data['sort_order'] = $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url);

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if ($category_path !== '') {
			$url .= '&category_path=' . $category_path;
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $product_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('catalog/product.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($product_total - $this->config->get('config_pagination_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $product_total, ceil($product_total / $this->config->get('config_pagination_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		return $this->load->view('catalog/product_list', $data);
	}

	/**
	 * Form
	 *
	 * @return void
	 */
	public function form(): void {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');

		$data['text_form'] = !isset($this->request->get['product_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$data['error_upload_size'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size'));

		$data['config_file_max_size'] = ((int)$this->config->get('config_file_max_size') * 1024 * 1024);

		if (isset($this->request->get['master_id'])) {
			$this->load->model('catalog/product');

			$url = $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $this->request->get['master_id']);

			$data['text_variant'] = sprintf($this->language->get('text_variant'), $url, $url);
		} else {
			$data['text_variant'] = '';
		}

		$url = '';

		if (isset($this->request->get['master_id'])) {
			$url .= '&master_id=' . $this->request->get['master_id'];
		}

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
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
			'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url)
		];

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$url .= '&filter_quantity_from=' . $this->request->get['filter_quantity_from'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
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

		$data['save'] = $this->url->link('catalog/product.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['upload'] = $this->url->link('tool/upload.upload', 'user_token=' . $this->session->data['user_token']);

		$autocomplete_limit = (int)$this->config->get('config_autocomplete_limit');

		if ($autocomplete_limit < 20) {
			$autocomplete_limit = 200;  // 增加到200，显示更多分类
		}

		$data['autocomplete_limit'] = $autocomplete_limit;

		if (isset($this->request->get['product_id'])) {
			$data['product_id'] = (int)$this->request->get['product_id'];
		} else {
			$data['product_id'] = 0;
		}

		// If the product_id is the master_id, we need to get the variant info
		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} elseif (isset($this->request->get['master_id'])) {
			$product_id = (int)$this->request->get['master_id'];
		} else {
			$product_id = 0;
		}

		if ($product_id) {
			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct($product_id);
		}

		if (isset($this->request->get['master_id'])) {
			$data['master_id'] = (int)$this->request->get['master_id'];
		} elseif (!empty($product_info)) {
			$data['master_id'] = $product_info['master_id'];
		} else {
			$data['master_id'] = 0;
		}

		// Language
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (!empty($product_info)) {
			$data['product_description'] = $this->model_catalog_product->getDescriptions($product_id);
		} else {
			$data['product_description'] = [];
		}

		if (!empty($product_info)) {
			$data['model'] = $product_info['model'];
		} else {
			$data['model'] = '';
		}

		// Product Identifiers
		$this->load->model('catalog/identifier');

		$data['identifiers'] = $this->model_catalog_identifier->getIdentifiers();

		// Filter
		if (!empty($product_info)) {
			$data['product_codes'] = $this->model_catalog_product->getCodes($product_id);
		} else {
			$data['product_codes'] = [];
		}

		if (!empty($product_info)) {
			$data['price'] = $product_info['price'];
		} else {
			$data['price'] = '';
		}

		// Tax Class
		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->ensureDefaultTaxClasses($this->model_localisation_tax_class->getTaxClasses());

		if (!empty($product_info)) {
			$data['tax_class_id'] = $product_info['tax_class_id'];
		} else {
			$data['tax_class_id'] = 0;
		}

		if (!empty($product_info)) {
			$data['quantity'] = $product_info['quantity'];
		} else {
			$data['quantity'] = 1;
		}

		if (!empty($product_info)) {
			$data['minimum'] = $product_info['minimum'];
		} else {
			$data['minimum'] = 1;
		}

		if (!empty($product_info)) {
			$data['subtract'] = $product_info['subtract'];
		} else {
			$data['subtract'] = 1;
		}

		// Stock Status
		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		if (!empty($product_info)) {
			$data['stock_status_id'] = $product_info['stock_status_id'];
		} else {
			$data['stock_status_id'] = 0;
		}

		if (!empty($product_info)) {
			$data['location'] = $product_info['location'];
		} else {
			$data['location'] = '';
		}

		if (!empty($product_info)) {
			$data['date_available'] = ($product_info['date_available'] != '0000-00-00') ? $product_info['date_available'] : '';
		} else {
			$data['date_available'] = date('Y-m-d');
		}

		if (!empty($product_info)) {
			$data['shipping'] = $product_info['shipping'];
		} else {
			$data['shipping'] = 1;
		}

		if (!empty($product_info)) {
			$data['length'] = $product_info['length'];
		} else {
			$data['length'] = '';
		}

		if (!empty($product_info)) {
			$data['width'] = $product_info['width'];
		} else {
			$data['width'] = '';
		}

		if (!empty($product_info)) {
			$data['height'] = $product_info['height'];
		} else {
			$data['height'] = '';
		}

		// Length Class
		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		if (!empty($product_info)) {
			$data['length_class_id'] = $product_info['length_class_id'];
		} else {
			$data['length_class_id'] = (int)$this->config->get('config_length_class_id');
		}

		// Weight Class
		if (!empty($product_info)) {
			$data['weight'] = $product_info['weight'];
		} else {
			$data['weight'] = '';
		}

		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

		if (!empty($product_info)) {
			$data['weight_class_id'] = $product_info['weight_class_id'];
		} else {
			$data['weight_class_id'] = (int)$this->config->get('config_weight_class_id');
		}

		if (!empty($product_info)) {
			$data['status'] = $product_info['status'];
		} else {
			$data['status'] = true;
		}

		if (!empty($product_info)) {
			$data['sort_order'] = $product_info['sort_order'];
		} else {
			$data['sort_order'] = 1;
		}

		// Manufacturer
		$this->load->model('catalog/manufacturer');

		if (!empty($product_info)) {
			$data['manufacturer_id'] = $product_info['manufacturer_id'];
		} else {
			$data['manufacturer_id'] = 0;
		}

		if (!empty($product_info)) {
			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

			if ($manufacturer_info) {
				$data['manufacturer'] = $manufacturer_info['name'];
			} else {
				$data['manufacturer'] = '';
			}
		} else {
			$data['manufacturer'] = '';
		}

		// Category
		$this->load->model('catalog/category');

		if ($product_id) {
			$categories = $this->model_catalog_product->getCategories($product_id);
		} else {
			$categories = [];
		}

		$data['product_categories'] = [];

		foreach ($categories as $category_id) {
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$data['product_categories'][] = ['name' => ($category_info['path'] ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name'])] + $category_info;
			}
		}

		// Filter
		$this->load->model('catalog/filter');

		if (!empty($product_info)) {
			$filters = $this->model_catalog_product->getFilters($product_id);
		} else {
			$filters = [];
		}

		$data['product_filters'] = [];

		foreach ($filters as $filter_id) {
			$filter_info = $this->model_catalog_filter->getFilter($filter_id);

			if ($filter_info) {
				$data['product_filters'][] = ['name' => $filter_info['group'] . ' &gt; ' . $filter_info['name']] + $filter_info;
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

		if ($product_id) {
			$data['product_store'] = $this->model_catalog_product->getStores($product_id);
		} else {
			$data['product_store'] = [0];
		}

		// Download
		$this->load->model('catalog/download');

		if ($product_id) {
			$product_downloads = $this->model_catalog_product->getDownloads($product_id);
		} else {
			$product_downloads = [];
		}

		$data['product_downloads'] = [];

		foreach ($product_downloads as $download_id) {
			$download_info = $this->model_catalog_download->getDownload($download_id);

			if ($download_info) {
				$data['product_downloads'][] = $download_info;
			}
		}

		// Related
		if ($product_id) {
			$product_relateds = $this->model_catalog_product->getRelated($product_id);
		} else {
			$product_relateds = [];
		}

		$data['product_relateds'] = [];

		foreach ($product_relateds as $related_id) {
			$related_info = $this->model_catalog_product->getProduct($related_id);

			if ($related_info) {
				$data['product_relateds'][] = $related_info;
			}
		}

		// Attribute
		$this->load->model('catalog/attribute');

		if ($product_id) {
			$product_attributes = $this->model_catalog_product->getAttributes($product_id);
		} else {
			$product_attributes = [];
		}

		$data['product_attributes'] = [];

		foreach ($product_attributes as $product_attribute) {
			$attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);

			if ($attribute_info) {
				$data['product_attributes'][] = ['name' => $attribute_info['name']] + $product_attribute;
			}
		}

		// Customer Group
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		// Option
		$this->load->model('catalog/option');

		if ($product_id) {
			$product_options = $this->model_catalog_product->getOptions($product_id);
		} else {
			$product_options = [];
		}

		$data['product_options'] = [];

		foreach ($product_options as $product_option) {
			$product_option_value_data = [];

			if (isset($product_option['product_option_value'])) {
				foreach ($product_option['product_option_value'] as $product_option_value) {
					$option_value_info = $this->model_catalog_option->getValue($product_option_value['option_value_id']);

					if ($option_value_info) {
						$product_option_value_data[] = [
							'name'   => $option_value_info['name'],
							'points' => round($product_option_value['points']),
							'weight' => round($product_option_value['weight']),
						] + $product_option_value;
					}
				}
			}

			$data['product_options'][] = [
				'product_option_value' => $product_option_value_data,
				'value'                => $product_option['value'] ?? '',
			] + $product_option;
		}

		$data['option_values'] = [];

		foreach ($data['product_options'] as $product_option) {
			if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
				if (!isset($data['option_values'][$product_option['option_id']])) {
					$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getValues($product_option['option_id']);
				}
			}
		}

		$this->load->model('tool/image');

		$data['spec_options'] = [];
		$option_value_lookup = [];

		foreach ($data['product_options'] as $product_option) {
			if (!in_array($product_option['type'], ['select', 'radio', 'checkbox', 'image'], true)) {
				continue;
			}

			$values = [];

			if (!empty($product_option['product_option_value'])) {
				foreach ($product_option['product_option_value'] as $product_option_value) {
					$value_image = $product_option_value['image'] ?? '';

					if ($value_image && is_file(DIR_IMAGE . html_entity_decode($value_image, ENT_QUOTES, 'UTF-8'))) {
						$thumb_path = $value_image;
					} else {
						$thumb_path = 'no_image.png';
					}

					$values[] = [
						'product_option_value_id' => (int)$product_option_value['product_option_value_id'],
						'option_value_id'         => (int)$product_option_value['option_value_id'],
						'name'                    => $product_option_value['name'] ?? '',
						'image'                   => $value_image,
						'thumb'                   => $this->model_tool_image->resize($thumb_path, 60, 60),
						'sort_order'              => (int)($product_option_value['sort_order'] ?? 0),
						'price'                   => (float)($product_option_value['price'] ?? 0.0),
						'price_prefix'            => $product_option_value['price_prefix'] ?? '+',
						'quantity'                => (int)($product_option_value['quantity'] ?? 0),
						'subtract'                => (int)($product_option_value['subtract'] ?? 0),
						'weight'                  => (float)($product_option_value['weight'] ?? 0.0),
						'weight_prefix'           => $product_option_value['weight_prefix'] ?? '+'
					];

					$option_value_lookup[(int)$product_option_value['product_option_value_id']] = [
						'option_id'   => (int)$product_option['option_id'],
						'option_name' => $product_option['name'] ?? '',
						'value_name'  => $product_option_value['name'] ?? ''
					];
				}
			}

			$data['spec_options'][] = [
				'product_option_id' => (int)$product_option['product_option_id'],
				'option_id'         => (int)$product_option['option_id'],
				'name'              => $product_option['name'] ?? '',
				'type'              => $product_option['type'],
				'required'          => (int)($product_option['required'] ?? 0),
				'sort_order'        => (int)($product_option['sort_order'] ?? 0),
				'values'            => $values
			];
		}

		$data['option_value_lookup'] = $option_value_lookup;

		// Variants
		if (!empty($product_info)) {
			$data['variant'] = $product_info['variant'];
		} else {
			$data['variant'] = [];
		}

		// Overrides
		if (!empty($product_info)) {
			$data['override'] = $product_info['override'];
		} else {
			$data['override'] = [];
		}

		$data['options'] = [];

		if (isset($this->request->get['master_id'])) {
			$product_options = $this->model_catalog_product->getOptions($this->request->get['master_id']);

			foreach ($product_options as $product_option) {
				$product_option_value_data = [];

				foreach ($product_option['product_option_value'] as $product_option_value) {
					$option_value_info = $this->model_catalog_option->getValue($product_option_value['option_value_id']);

					if ($option_value_info) {
						$product_option_value_data[] = [
							'name'  => $option_value_info['name'],
							'price' => (float)$product_option_value['price'] ? $product_option_value['price'] : false,
						] + $product_option_value;
					}
				}

				$option_info = $this->model_catalog_option->getOption($product_option['option_id']);

				$data['options'][] = [
					'product_option_value' => $product_option_value_data,
					'name'                 => $option_info['name'],
					'type'                 => $option_info['type'],
					'value'                => !empty($data['variant'][$product_option['product_option_id']]) ? $product_option['value'] : ''
				] + $product_option;
			}
		}

		// Subscription Plan
		$this->load->model('catalog/subscription_plan');

		$data['subscription_plans'] = $this->model_catalog_subscription_plan->getSubscriptionPlans();

		if ($product_id) {
			$data['product_subscriptions'] = $this->model_catalog_product->getSubscriptions($product_id);
		} else {
			$data['product_subscriptions'] = [];
		}

		// Discount
		if ($product_id) {
			$product_discounts = $this->model_catalog_product->getDiscounts($product_id);
		} else {
			$product_discounts = [];
		}

		$data['product_discounts'] = [];

		foreach ($product_discounts as $product_discount) {
			$data['product_discounts'][] = [
				'date_start' => ($product_discount['date_start'] != '0000-00-00' ? $product_discount['date_start'] : ''),
				'date_end'   => ($product_discount['date_end'] != '0000-00-00' ? $product_discount['date_end'] : '')
			] + $product_discount;
		}

		// Image
		if (!empty($product_info)) {
			$data['image'] = $product_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'));

		if ($data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb'] = $this->model_tool_image->resize($data['image'], (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'));
		} else {
			$data['thumb'] = $data['placeholder'];
		}

		// Images
		if ($product_id) {
			$product_images = $this->model_catalog_product->getImages($product_id);
		} else {
			$product_images = [];
		}

		$data['product_images'] = [];

		foreach ($product_images as $product_image) {
			if ($product_image['image'] && is_file(DIR_IMAGE . html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $product_image['image'];
				$thumb = $product_image['image'];
			} else {
				$image = '';
				$thumb = 'no_image.png';
			}

			$data['product_images'][] = [
				'image' => $image,
				'thumb' => $this->model_tool_image->resize($thumb, (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height')),
			] + $product_image;
		}

		if ($product_id) {
			$product_variants = $this->model_catalog_product->getProductVariants($product_id);
		} else {
			$product_variants = [];
		}

		$data['variant_placeholder'] = $this->model_tool_image->resize('no_image.png', (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'));
		$data['product_variants'] = [];
		$selected_option_value_ids = [];

		foreach ($product_variants as $product_variant) {
			if ($product_variant['image'] && is_file(DIR_IMAGE . html_entity_decode($product_variant['image'], ENT_QUOTES, 'UTF-8'))) {
				$variant_thumb = $product_variant['image'];
			} else {
				$variant_thumb = 'no_image.png';
			}

			$variant_images = [];

			if (!empty($product_variant['images'])) {
				foreach ($product_variant['images'] as $variant_image) {
					if ($variant_image['image'] && is_file(DIR_IMAGE . html_entity_decode($variant_image['image'], ENT_QUOTES, 'UTF-8'))) {
						$image_path = $variant_image['image'];
					} else {
						$image_path = '';
					}

					if ($image_path) {
						$image_thumb = $variant_image['image'];
					} else {
						$image_thumb = 'no_image.png';
					}

					$variant_images[] = [
						'variant_image_id' => $variant_image['variant_image_id'] ?? 0,
						'image'            => $image_path,
						'thumb'            => $this->model_tool_image->resize($image_thumb, (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height')),
						'sort_order'       => (int)($variant_image['sort_order'] ?? 0)
					];
				}
			}

			$option_ids = array_map('intval', (array)$product_variant['options']);
			$option_labels = [];
			$option_value_names = [];

			foreach ($option_ids as $option_value_id) {
				if (isset($option_value_lookup[$option_value_id])) {
					$lookup = $option_value_lookup[$option_value_id];

					if ($lookup['option_name']) {
						$option_labels[] = $lookup['option_name'] . ': ' . $lookup['value_name'];
					} else {
						$option_labels[] = $lookup['value_name'];
					}

					$option_value_names[] = $lookup['value_name'];
					$selected_option_value_ids[] = $option_value_id;
				}
			}

			$data['product_variants'][] = [
				'variant_id' => (int)$product_variant['variant_id'],
				'sku'        => $product_variant['sku'],
				'model'      => $product_variant['model'],
				'price'      => (float)$product_variant['price'],
				'quantity'   => (int)$product_variant['quantity'],
				'status'     => (int)$product_variant['status'],
				'weight'     => (float)$product_variant['weight'],
				'sort_order' => (int)$product_variant['sort_order'],
				'image'      => $product_variant['image'],
				'thumb'      => $this->model_tool_image->resize($variant_thumb, (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height')),
				'options'    => $option_ids,
				'option_labels' => $option_labels,
				'option_value_names' => $option_value_names,
				'images'     => $variant_images
			];
		}

		$data['selected_option_value_ids'] = array_values(array_unique($selected_option_value_ids));

		// Points
		if (!empty($product_info)) {
			$data['points'] = $product_info['points'];
		} else {
			$data['points'] = '';
		}

		// Rewards
		if ($product_id) {
			$data['product_reward'] = $this->model_catalog_product->getRewards($product_id);
		} else {
			$data['product_reward'] = [];
		}

		// SEO
		if ($product_id) {
			$this->load->model('design/seo_url');

			$data['product_seo_url'] = $this->model_design_seo_url->getSeoUrlsByKeyValue('product_id', $product_id);
		} else {
			$data['product_seo_url'] = [];
		}

		// Layout
		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		if ($product_id) {
			$data['product_layout'] = $this->model_catalog_product->getLayouts($product_id);
		} else {
			$data['product_layout'] = [];
		}

		$data['report'] = $this->getReport();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_form', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('catalog/product');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$required = [
			'product_id'          => 0,
			'master_id'           => 0,
			'product_description' => [],
			'model'               => '',
			'product_code'        => [],
			'location'            => '',
			'variant'             => [],
			'override'            => [],
			'product_variant'     => [],
			'quantity'            => 0,
			'minimum'             => 0,
			'subtract'            => 0,
			'stock_status_id'     => 0,
			'date_available'      => '',
			'manufacturer_id'     => 0,
			'shipping'            => 0,
			'price'               => 0.0,
			'points'              => 0,
			'weight'              => 0.0,
			'weight_class_id'     => 0,
			'length'              => 0.0,
			'length_class_id'     => 0,
			'status'              => 0,
			'tax_class_id'        => 0,
			'sort_order'          => 0
		];

		$post_info = $this->request->post + $required;

		foreach ($post_info['product_description'] as $language_id => $value) {
			if (!oc_validate_length($value['name'], 1, 255)) {
				$json['error']['name_' . $language_id] = $this->language->get('error_name');
			}

			if (!oc_validate_length($value['meta_title'], 1, 255)) {
				$json['error']['meta_title_' . $language_id] = $this->language->get('error_meta_title');
			}
		}

		if (!oc_validate_length($post_info['model'], 1, 64)) {
			$json['error']['model'] = $this->language->get('error_model');
		}

		// Identifier
		$this->load->model('catalog/identifier');

		foreach ($post_info['product_code'] as $key => $product_code) {
			$identifier_info = $this->model_catalog_identifier->getIdentifierByCode($product_code['code']);

			if ($identifier_info && $identifier_info['validation'] && !oc_validate_regex($product_code['value'], $identifier_info['validation'])) {
				$json['error']['code_' . $key] = sprintf($this->language->get('error_regex'), $product_code['code']);
			}
		}

		$this->load->model('catalog/product');

		if ($post_info['master_id']) {
			$product_options = $this->model_catalog_product->getOptions($post_info['master_id']);

			foreach ($product_options as $product_option) {
				if (isset($post_info['override']['variant'][$product_option['product_option_id']]) && $product_option['required'] && empty($post_info['variant'][$product_option['product_option_id']])) {
					$json['error']['option_' . $product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
				}
			}
		}

		// SEO
		if ($post_info['product_seo_url']) {
			$this->load->model('design/seo_url');

			foreach ($post_info['product_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!oc_validate_length($keyword, 1, 64)) {
						$json['error']['keyword_' . $store_id . '_' . $language_id] = $this->language->get('error_keyword');
					}

					if (!oc_validate_path($keyword)) {
						$json['error']['keyword_' . $store_id . '_' . $language_id] = $this->language->get('error_keyword_character');
					}

					$seo_url_info = $this->model_design_seo_url->getSeoUrlByKeyword($keyword, $store_id);

					if ($seo_url_info && ($seo_url_info['key'] != 'product_id' || !isset($post_info['product_id']) || $seo_url_info['value'] != (int)$post_info['product_id'])) {
						$json['error']['keyword_' . $store_id . '_' . $language_id] = $this->language->get('error_keyword_exists');
					}
				}
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$sanitized_variants = [];

			if (!empty($post_info['product_variant']) && is_array($post_info['product_variant'])) {
				foreach ($post_info['product_variant'] as $variant) {
					if (!is_array($variant)) {
						continue;
					}

					$options = [];

					if (isset($variant['options'])) {
						$raw_options = is_array($variant['options']) ? $variant['options'] : explode(',', (string)$variant['options']);

						foreach ($raw_options as $option_value) {
							if (is_array($option_value)) {
								$option_value_id = $option_value['product_option_value_id'] ?? $option_value['option_value_id'] ?? null;
							} else {
								$option_value_id = $option_value;
							}

							if ($option_value_id === '' || $option_value_id === null) {
								continue;
							}

							$options[] = (int)$option_value_id;
						}
					}

					$options = array_values(array_unique(array_map('intval', $options)));

					$images = [];

					if (!empty($variant['images']) && is_array($variant['images'])) {
						foreach ($variant['images'] as $variant_image) {
							if (!is_array($variant_image)) {
								continue;
							}

							$image_path = trim((string)($variant_image['image'] ?? ''));

							if ($image_path === '') {
								continue;
							}

							$images[] = [
								'image'      => $image_path,
								'sort_order' => (int)($variant_image['sort_order'] ?? 0)
							];
						}
					}

					$image = trim((string)($variant['image'] ?? ''));

					if ($image === '' && isset($images[0])) {
						$image = $images[0]['image'];
					}

					$sanitized_variants[] = [
						'variant_id' => (int)($variant['variant_id'] ?? 0),
						'sku'        => trim((string)($variant['sku'] ?? '')),
						'model'      => trim((string)($variant['model'] ?? '')),
						'price'      => (float)($variant['price'] ?? 0.0),
						'quantity'   => (int)($variant['quantity'] ?? 0),
						'weight'     => (float)($variant['weight'] ?? 0.0),
						'status'     => !empty($variant['status']) ? 1 : 0,
						'sort_order' => (int)($variant['sort_order'] ?? 0),
						'image'      => $image,
						'options'    => $options,
						'images'     => $images
					];
				}
			}

			$post_info['product_variant'] = $sanitized_variants;

			if (!$post_info['product_id']) {
				if (!$post_info['master_id']) {
					// Normal product add
					$json['product_id'] = $this->model_catalog_product->addProduct($post_info);
				} else {
					// Variant product add
					$json['product_id'] = $this->model_catalog_product->addVariant($post_info['master_id'], $post_info);
				}
			} else {
				if (!$post_info['master_id']) {
					// Normal product edit
					$this->model_catalog_product->editProduct($post_info['product_id'], $post_info);
				} else {
					// Variant product edit
					$this->model_catalog_product->editVariant($post_info['master_id'], $post_info['product_id'], $post_info);
				}

				// Variant products edit if master product is edited
				$this->model_catalog_product->editVariants($post_info['product_id'], $post_info);
			}

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
		$this->load->language('catalog/product');

		$json = [];

		if (isset($this->request->post['selected'])) {
			$selected = (array)$this->request->post['selected'];
		} else {
			$selected = [];
		}

		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			// Product
			$this->load->model('catalog/product');

			foreach ($selected as $product_id) {
				$this->model_catalog_product->deleteProduct($product_id);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Copy
	 *
	 * @return void
	 */
	public function copy(): void {
		$this->load->language('catalog/product');

		$json = [];

		if (isset($this->request->post['selected'])) {
			$selected = (array)$this->request->post['selected'];
		} else {
			$selected = [];
		}

		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('catalog/product');

			foreach ($selected as $product_id) {
				$this->model_catalog_product->copyProduct($product_id);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Report
	 *
	 * @return void
	 */
	public function report(): void {
		$this->load->language('catalog/product');

		$this->response->setOutput($this->getReport());
	}

	/**
	 * Get Report
	 *
	 * @return string
	 */
	public function getReport(): string {
		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->get['page']) && $this->request->get['route'] == 'catalog/product.report') {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$limit = 10;

		$data['reports'] = [];

		// Product
		$this->load->model('catalog/product');

		// Store
		$this->load->model('setting/store');

		$results = $this->model_catalog_product->getReports($product_id, ($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$store_info = $this->model_setting_store->getStore($result['store_id']);

			if ($store_info) {
				$store = $store_info['name'];
			} elseif (!$result['store_id']) {
				$store = $this->config->get('config_name');
			} else {
				$store = '';
			}

			$data['reports'][] = [
				'ip'         => $result['ip'],
				'store'      => $store,
				'country'    => $result['country'],
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			];
		}

		$report_total = $this->model_catalog_product->getTotalReports($product_id);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $report_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('catalog/product.report', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product_id . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($report_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($report_total - $limit)) ? $report_total : ((($page - 1) * $limit) + $limit), $report_total, ceil($report_total / $limit));

		return $this->load->view('catalog/product_report', $data);
	}

	/**
	 * Autocomplete
	 *
	 * @return void
	 */
	public function autocomplete(): void {
		$this->load->language('catalog/product');

		$json = [];

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = '';
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = (int)$this->config->get('config_autocomplete_limit');
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$filter_data = [
			'filter_name'  => $filter_name,
			'filter_model' => $filter_model,
			'start'        => 0,
			'limit'        => $limit
		];

		// Product
		$this->load->model('catalog/product');

		// Option
		$this->load->model('catalog/option');

		// Subscription Plan
		$this->load->model('catalog/subscription_plan');

		$results = $this->model_catalog_product->getProducts($filter_data);

		foreach ($results as $result) {
			$option_data = [];

			$product_options = $this->model_catalog_product->getOptions($result['product_id']);

			foreach ($product_options as $product_option) {
				$option_info = $this->model_catalog_option->getOption($product_option['option_id']);

				if ($option_info) {
					$product_option_value_data = [];

					foreach ($product_option['product_option_value'] as $product_option_value) {
						$option_value_info = $this->model_catalog_option->getValue($product_option_value['option_value_id']);

						if ($option_value_info) {
							$product_option_value_data[] = [
								'product_option_value_id' => $product_option_value['product_option_value_id'],
								'option_value_id'         => $product_option_value['option_value_id'],
								'name'                    => $option_value_info['name'],
								'price'                   => (float)$product_option_value['price'] ? $this->currency->format($product_option_value['price'], $this->config->get('config_currency')) : false,
								'price_prefix'            => $product_option_value['price_prefix']
							];
						}
					}

					$option_data[] = [
						'product_option_id'    => $product_option['product_option_id'],
						'product_option_value' => $product_option_value_data,
						'option_id'            => $product_option['option_id'],
						'name'                 => $option_info['name'],
						'type'                 => $option_info['type'],
						'value'                => $product_option['value'],
						'required'             => $product_option['required']
					];
				}
			}

			$subscription_plan_data = [];

			$product_subscriptions = $this->model_catalog_product->getSubscriptions($result['product_id']);

			foreach ($product_subscriptions as $product_subscription) {
				$subscription_plan_info = $this->model_catalog_subscription_plan->getSubscriptionPlan($product_subscription['subscription_plan_id']);

				if ($subscription_plan_info) {
					$price = $this->currency->format($product_subscription['price'], $this->config->get('config_currency'));
					$cycle = $subscription_plan_info['cycle'];
					$frequency = $this->language->get('text_' . $subscription_plan_info['frequency']);
					$duration = $subscription_plan_info['duration'];

					if ($subscription_plan_info['duration']) {
						$description = sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
					} else {
						$description = sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
					}

					$subscription_plan_data[] = [
						'subscription_plan_id' => $subscription_plan_info['subscription_plan_id'],
						'customer_group_id'    => $product_subscription['customer_group_id'],
						'name'                 => $subscription_plan_info['name'],
						'description'          => $description
					];
				}
			}

			$json[] = [
				'product_id'   => $result['product_id'],
				'name'         => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
				'model'        => $result['model'],
				'option'       => $option_data,
				'subscription' => $subscription_plan_data,
				'price'        => $result['price']
			];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Make sure the default tax classes exist and return the updated list.
	 *
	 * @param array<int, array<string, mixed>> $tax_classes
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function ensureDefaultTaxClasses(array $tax_classes): array {
		$required = [
			[
				'title'       => '宸插惈绋庢敹 / Tax Included',
				'description' => '浠锋牸宸插惈绋?(Price includes applicable taxes).'
			],
			[
				'title'       => '鏈惈绋庢敹 / Tax Excluded',
				'description' => '浠锋牸鏈惈绋?(Taxes calculated separately).'
			]
		];

		$lookup = [];

		$lower = static function (string $value): string {
			$value = trim($value);

			return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
		};

		foreach ($tax_classes as $tax_class) {
			$lookup[$lower((string)$tax_class['title'])] = true;
		}

		foreach ($required as $defaults) {
			$key = $lower($defaults['title']);

			if (!isset($lookup[$key])) {
				$tax_class_id = $this->model_localisation_tax_class->addTaxClass([
					'title'       => $defaults['title'],
					'description' => $defaults['description'],
					'tax_rule'    => []
				]);

				$tax_classes[] = [
					'tax_class_id' => $tax_class_id,
					'title'        => $defaults['title'],
					'description'  => $defaults['description']
				];
			}
		}

		return $tax_classes;
	}

	/**
	 * Export products to CSV
	 *
	 * @return void
	 */
	public function export(): void {
		$this->load->language('catalog/product');
		$this->load->model('catalog/product');
		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		
		// 获取所有产品（不分页）
		$products = $this->model_catalog_product->getProducts([]);
		
		// 设置CSV响应头
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="products_' . date('Y-m-d_His') . '.csv"');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		$output = fopen('php://output', 'w');
		
		// 添加BOM以便Excel正确识别UTF-8
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 构建表头（字段名）
		$headers = ['product_id', 'model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn', 'location', 'quantity', 'minimum', 'subtract', 'stock_status_id', 'price', 'tax_class_id', 'manufacturer_id', 'status', 'sort_order', 'weight', 'weight_class_id', 'length', 'width', 'height', 'length_class_id', 'shipping', 'points', 'date_available'];
		
		foreach ($languages as $language) {
			$headers[] = 'name_' . $language['code'];
			$headers[] = 'description_' . $language['code'];
			$headers[] = 'tag_' . $language['code'];
			$headers[] = 'meta_title_' . $language['code'];
			$headers[] = 'meta_description_' . $language['code'];
			$headers[] = 'meta_keyword_' . $language['code'];
			$headers[] = 'seo_url_' . $language['code'];
		}
		
		$headers[] = 'image';
		$headers[] = 'categories';
		
		fputcsv($output, $headers);
		
		// 添加中文说明行（第二行）
		$descriptions = [
			'【必填】产品ID(留空=新增)',
			'【必填】产品型号',
			'SKU编码',
			'UPC条码',
			'EAN条码',
			'JAN条码',
			'ISBN编码',
			'MPN编码',
			'库存位置',
			'【必填】库存数量',
			'最小订购量',
			'减少库存(1=是/0=否)',
			'缺货状态ID(7=缺货)',
			'【必填】价格',
			'税率分类ID',
			'制造商ID',
			'【必填】状态(1=启用/0=禁用)',
			'排序值',
			'重量',
			'重量单位ID(1=千克)',
			'长度',
			'宽度',
			'高度',
			'长度单位ID(1=厘米)',
			'需要配送(1=是/0=否)',
			'积分',
			'上架日期(YYYY-MM-DD)'
		];
		
		foreach ($languages as $language) {
			$lang_name = $language['name'];
			$descriptions[] = "【必填】{$lang_name}产品名称";
			$descriptions[] = "{$lang_name}产品描述(支持HTML)";
			$descriptions[] = "{$lang_name}标签(逗号分隔)";
			$descriptions[] = "{$lang_name}SEO标题";
			$descriptions[] = "{$lang_name}SEO描述";
			$descriptions[] = "{$lang_name}关键词(逗号分隔)";
			$descriptions[] = "{$lang_name}SEO URL(如: iphone-15-pro)";
		}
		
		$descriptions[] = '主图路径';
		$descriptions[] = '所属分类ID(逗号分隔,如:20,25)';
		
		fputcsv($output, $descriptions);
		
		// 如果没有产品，输出一行示例数据
		if (empty($products)) {
			$example = ['', 'IPHONE-15-PRO', 'SKU-IP15P', '123456789012', '1234567890123', '', '', 'MPN123', 'A区货架1', '100', '1', '1', '7', '6999.00', '0', '', '1', '0', '0.20', '1', '14.76', '7.15', '0.81', '2', '1', '0', date('Y-m-d')];
			
			foreach ($languages as $language) {
				$example[] = 'iPhone 15 Pro 256GB 黑色钛金属';
				$example[] = '全新iPhone 15 Pro，搭载A17 Pro芯片，钛金属设计，4800万像素主摄像头，支持5G网络';
				$example[] = 'iPhone,苹果,手机,5G';
				$example[] = 'iPhone 15 Pro 256GB - 官方正品';
				$example[] = '购买全新iPhone 15 Pro，享受卓越性能和拍摄体验';
				$example[] = 'iPhone 15 Pro,苹果手机,A17 Pro,钛金属';
				$example[] = 'iphone-15-pro-256gb-black';
			}
			
			$example[] = 'catalog/demo/iphone-15-pro.jpg';
			$example[] = '20,25'; // 示例：分类ID 20和25
			
			fputcsv($output, $example);
		} else {
			// 输出所有产品数据
			foreach ($products as $product) {
				$product_data = $this->model_catalog_product->getProduct($product['product_id']);
				
				$row = [
					$product_data['product_id'],
					$product_data['model'],
					$product_data['sku'],
					$product_data['upc'],
					$product_data['ean'],
					$product_data['jan'],
					$product_data['isbn'],
					$product_data['mpn'],
					$product_data['location'],
					$product_data['quantity'],
					$product_data['minimum'],
					$product_data['subtract'] ? '1' : '0',
					$product_data['stock_status_id'],
					$product_data['price'],
					$product_data['tax_class_id'],
					$product_data['manufacturer_id'],
					$product_data['status'] ? '1' : '0',
					$product_data['sort_order'],
					$product_data['weight'],
					$product_data['weight_class_id'],
					$product_data['length'],
					$product_data['width'],
					$product_data['height'],
					$product_data['length_class_id'],
					$product_data['shipping'] ? '1' : '0',
					$product_data['points'],
					$product_data['date_available']
				];
				
				foreach ($languages as $language) {
					$description = $this->model_catalog_product->getDescriptions($product_data['product_id']);
					$lang_data = $description[$language['language_id']] ?? [];
					
					$row[] = $lang_data['name'] ?? '';
					$row[] = strip_tags($lang_data['description'] ?? '');
					$row[] = $lang_data['tag'] ?? '';
					$row[] = $lang_data['meta_title'] ?? '';
					$row[] = $lang_data['meta_description'] ?? '';
					$row[] = $lang_data['meta_keyword'] ?? '';
					
					// 获取SEO URL
					$seo_urls = $this->model_catalog_product->getSeoUrls($product_data['product_id']);
					$seo_url = '';
					foreach ($seo_urls as $seo) {
						if ($seo['language_id'] == $language['language_id'] && $seo['store_id'] == 0) {
							$seo_url = $seo['keyword'];
							break;
						}
					}
					$row[] = $seo_url;
				}
				
				$row[] = $product_data['image'] ?? '';
				
				// 获取产品分类
				$categories = $this->model_catalog_product->getCategories($product_data['product_id']);
				$category_ids = array_column($categories, 'category_id');
				$row[] = implode(',', $category_ids);
				
				fputcsv($output, $row);
			}
		}
		
		fclose($output);
		exit;
	}

	/**
	 * Import products from CSV
	 *
	 * @return void
	 */
	public function import(): void {
		$this->load->language('catalog/product');
		$this->load->model('catalog/product');
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
							$this->processProduct($first_data_row, $headers, $language_codes, $imported, $updated, $errors, $row_num);
						}
						$row_num++;
					}
				}
				
				while (($row = fgetcsv($handle)) !== false) {
					$row_num++;
					
					if (empty(array_filter($row))) {
						continue; // 跳过空行
					}
					
					$this->processProduct($row, $headers, $language_codes, $imported, $updated, $errors, $row_num);
				}
				
				fclose($handle);
				
				if ($errors) {
					$json['warning'] = implode('<br>', $errors);
				}
				
				$json['success'] = sprintf('成功导入 %d 个产品，更新 %d 个产品', $imported, $updated);
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
	 * Process a single product row from CSV
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
	private function processProduct(array $row, array $headers, array $language_codes, int &$imported, int &$updated, array &$errors, int $row_num): void {
		$data = array_combine($headers, $row);
		
		// 准备产品数据
		$product_data = [
			'model' => $data['model'] ?? '',
			'sku' => $data['sku'] ?? '',
			'upc' => $data['upc'] ?? '',
			'ean' => $data['ean'] ?? '',
			'jan' => $data['jan'] ?? '',
			'isbn' => $data['isbn'] ?? '',
			'mpn' => $data['mpn'] ?? '',
			'location' => $data['location'] ?? '',
			'quantity' => (int)($data['quantity'] ?? 0),
			'minimum' => (int)($data['minimum'] ?? 1),
			'subtract' => isset($data['subtract']) && $data['subtract'] == '1' ? 1 : 0,
			'stock_status_id' => (int)($data['stock_status_id'] ?? 7),
			'price' => (float)($data['price'] ?? 0),
			'tax_class_id' => (int)($data['tax_class_id'] ?? 0),
			'manufacturer_id' => (int)($data['manufacturer_id'] ?? 0),
			'status' => isset($data['status']) && $data['status'] == '1' ? 1 : 0,
			'sort_order' => (int)($data['sort_order'] ?? 0),
			'weight' => (float)($data['weight'] ?? 0),
			'weight_class_id' => (int)($data['weight_class_id'] ?? 1),
			'length' => (float)($data['length'] ?? 0),
			'width' => (float)($data['width'] ?? 0),
			'height' => (float)($data['height'] ?? 0),
			'length_class_id' => (int)($data['length_class_id'] ?? 1),
			'shipping' => isset($data['shipping']) && $data['shipping'] == '1' ? 1 : 0,
			'points' => (int)($data['points'] ?? 0),
			'date_available' => $data['date_available'] ?? date('Y-m-d'),
			'image' => $data['image'] ?? '',
			'product_description' => []
		];
		
		// 处理多语言数据和SEO URL
		foreach ($language_codes as $code => $language_id) {
			$product_data['product_description'][$language_id] = [
				'name' => $data['name_' . $code] ?? '',
				'description' => $data['description_' . $code] ?? '',
				'tag' => $data['tag_' . $code] ?? '',
				'meta_title' => $data['meta_title_' . $code] ?? '',
				'meta_description' => $data['meta_description_' . $code] ?? '',
				'meta_keyword' => $data['meta_keyword_' . $code] ?? ''
			];
			
			// 处理SEO URL
			$seo_url = $data['seo_url_' . $code] ?? '';
			if (!empty($seo_url)) {
				$product_data['product_seo_url'][0][$language_id] = $seo_url;
			}
		}
		
		// 处理分类
		$product_data['product_category'] = [];
		if (!empty($data['categories'])) {
			$category_ids = array_map('trim', explode(',', $data['categories']));
			$product_data['product_category'] = array_filter(array_map('intval', $category_ids));
		}
		
		// 其他必需字段
		$product_data['product_store'] = [0]; // 默认商店
		$product_data['product_filter'] = [];
		$product_data['product_attribute'] = [];
		$product_data['product_option'] = [];
		$product_data['product_recurring'] = [];
		$product_data['product_discount'] = [];
		$product_data['product_special'] = [];
		$product_data['product_image'] = [];
		$product_data['product_download'] = [];
		$product_data['product_related'] = [];
		$product_data['product_reward'] = [];
		$product_data['product_layout'] = [];
		
		// 如果没有设置SEO URL，初始化为空数组
		if (!isset($product_data['product_seo_url'])) {
			$product_data['product_seo_url'] = [];
		}
		
		try {
			if (!empty($data['product_id']) && $data['product_id'] > 0) {
				// 更新现有产品
				$product_data['product_id'] = (int)$data['product_id'];
				$this->model_catalog_product->editProduct($product_data['product_id'], $product_data);
				$updated++;
			} else {
				// 添加新产品
				$this->model_catalog_product->addProduct($product_data);
				$imported++;
			}
		} catch (\Exception $e) {
			$errors[] = "第 {$row_num} 行错误: " . $e->getMessage();
		}
	}
}
