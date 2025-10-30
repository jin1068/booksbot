<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Menu
 *
 * Can be called from $this->load->controller('common/menu');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Menu extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/menu');

		// Category
		$this->load->model('catalog/category');

		// Product
		$this->load->model('catalog/product');

		$categories = $this->buildTree();

		$route = $this->request->get['route'] ?? '';

		if (!$route && isset($this->request->get['_route_'])) {
			$route = $this->request->get['_route_'];
		}

		$route = trim((string)$route);

		$is_home = !$route || $route === 'common/home';

		$data['categories'] = $categories;
		$data['categories_json'] = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$data['is_home'] = $is_home;

	$language_code = (string)$this->config->get('config_language');
	$lucky_purchase_base = html_entity_decode($this->url->link('common/home', 'language=' . $language_code), ENT_QUOTES, 'UTF-8');
	// 移除hash，避免语言切换时跳转到Lucky Purchase位置
	$data['lucky_purchase_href'] = $lucky_purchase_base;
	$data['home'] = $lucky_purchase_base;
		
		// Language variables
		$data['text_category'] = $this->language->get('text_category');
		$data['text_all'] = $this->language->get('text_all');
		$data['text_all_categories'] = $this->language->get('text_all_categories');
		$data['text_drawer_title'] = $this->language->get('text_drawer_title');
		$data['text_drawer_back'] = $this->language->get('text_drawer_back');
		$data['text_drawer_close'] = $this->language->get('text_drawer_close');
		$data['text_drawer_view_all'] = $this->language->get('text_drawer_view_all');
		$data['text_lucky_purchase'] = $this->language->get('text_lucky_purchase');
		$data['text_toggle_menu'] = $this->language->get('text_toggle_menu');

		return $this->load->view('common/menu', $data);
	}

	/**
	 * Build nested category tree for menu
	 *
	 * @param int    $parent_id
	 * @param string $path
	 * @param int    $depth
	 * @param int    $max_depth
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function buildTree(int $parent_id = 0, string $path = '', int $depth = 0, int $max_depth = 3): array {
		$categories = $this->model_catalog_category->getCategories($parent_id);
		$tree = [];

		foreach ($categories as $category) {
			$current_path = $path === '' ? (string)$category['category_id'] : $path . '_' . $category['category_id'];

			$count = 0;

			if ($this->config->get('config_product_count')) {
				$count = $this->model_catalog_product->getTotalProducts([
					'filter_category_id'  => $category['category_id'],
					'filter_sub_category' => true
				]);
			}

			$children = [];

			if ($depth < $max_depth) {
				$children = $this->buildTree($category['category_id'], $current_path, $depth + 1, $max_depth);
			}

			$tree[] = [
				'category_id'  => $category['category_id'],
				'name'         => $category['name'],
				'display_name' => $count ? $category['name'] . ' (' . $count . ')' : $category['name'],
				'href'         => html_entity_decode($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $current_path), ENT_QUOTES, 'UTF-8'),
				'children'     => $children,
				'path'         => $current_path,
				'has_children' => !empty($children),
				'count'        => $count
			];
		}

		return $tree;
	}
}
