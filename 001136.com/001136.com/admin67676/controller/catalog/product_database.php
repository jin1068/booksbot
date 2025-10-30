<?php
namespace Opencart\Admin\Controller\Catalog;

/**
 * Class ProductDatabase
 * 产品数据库管理 - 批量导入、启用、下架、删除
 * 
 * @package Opencart\Admin\Controller\Catalog
 */
class ProductDatabase extends \Opencart\System\Engine\Controller {
	
	/**
	 * 主页面
	 */
	public function index(): void {
		$this->load->language('catalog/product_database');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$data['breadcrumbs'] = [];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/product_database', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['upload'] = $this->url->link('catalog/product_database|upload', 'user_token=' . $this->session->data['user_token']);
		$data['list'] = $this->url->link('catalog/product_database|list', 'user_token=' . $this->session->data['user_token']);
		$data['enable'] = $this->url->link('catalog/product_database|enable', 'user_token=' . $this->session->data['user_token']);
		$data['disable'] = $this->url->link('catalog/product_database|disable', 'user_token=' . $this->session->data['user_token']);
		$data['delete'] = $this->url->link('catalog/product_database|delete', 'user_token=' . $this->session->data['user_token']);
		$data['download_template'] = $this->url->link('catalog/product_database|downloadTemplate', 'user_token=' . $this->session->data['user_token']);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		// 获取分类列表
		$this->load->model('catalog/category');
		$data['categories'] = $this->getCategoryTree();
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('catalog/product_database', $data));
	}
	
	/**
	 * 测试方法 - 简单的产品列表
	 */
	public function test(): void {
		header('Content-Type: application/json');
		
		try {
			$db = new \mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
			
			if ($db->connect_error) {
				echo json_encode(['error' => 'Database connection failed: ' . $db->connect_error]);
				exit;
			}
			
			// 初始化变量
			$products = [];
			$total = 0;
			$showing_from = 0;
			$showing_to = 0;
			$total_pages = 0;
			$pagination_html = '';
		
	// 检查是否有筛选条件
	$where = "WHERE 1=1";  // 改为始终为真，不限制language_id
		
		// 按状态筛选
		if (isset($_GET['filter_status']) && $_GET['filter_status'] !== '') {
			$status = (int)$_GET['filter_status'];
			$where .= " AND p.status = " . $status;
		}
		
		// 按名称搜索（搜索任意语言的描述或型号）
		if (isset($_GET['filter_name']) && $_GET['filter_name'] !== '') {
			$name = $db->real_escape_string($_GET['filter_name']);
			$where .= " AND (
			    EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.name LIKE '%" . $name . "%')
			    OR p.model LIKE '%" . $name . "%'
			)";
		}
		
	// 按分类筛选
	if (isset($_GET['filter_category_id']) && $_GET['filter_category_id'] !== '') {
		$category_id = (int)$_GET['filter_category_id'];
		$where .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_to_category ptc WHERE ptc.product_id = p.product_id AND ptc.category_id = " . $category_id . ")";
	}
	
	// 按图片筛选
	if (isset($_GET['filter_image']) && $_GET['filter_image'] !== '') {
		$filter_image = (int)$_GET['filter_image'];
		if ($filter_image == 1) {
			// 有图片（排除占位图和默认图）
			$where .= " AND p.image != '' AND p.image IS NOT NULL";
			$where .= " AND p.image NOT LIKE '%placeholder%'";
			$where .= " AND p.image NOT LIKE '%no_image%'";
			$where .= " AND p.image NOT LIKE 'catalog/demo/%'";
		} elseif ($filter_image == 0) {
			// 无图片（包括空值、NULL和占位图）
			$where .= " AND (p.image = '' OR p.image IS NULL";
			$where .= " OR p.image LIKE '%placeholder%'";
			$where .= " OR p.image LIKE '%no_image%'";
			$where .= " OR p.image LIKE 'catalog/demo/%')";
		}
	}
	
	// 获取页码和每页数量
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100; // 默认显示100个
		$start = ($page - 1) * $limit;
		
		// 获取排序参数
		$sort = isset($_GET['sort']) ? $_GET['sort'] : 'p.product_id';
		$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
		
		// 验证排序字段，防止SQL注入
		$allowed_sort_fields = [
			'p.product_id',
			'name',
			'p.model',
			'categories',
			'p.price',
			'p.quantity',
			'p.status'
		];
		
		if (!in_array($sort, $allowed_sort_fields)) {
			$sort = 'p.product_id';
		}
		
		// 计算总数（考虑筛选条件）
		$count_query = "SELECT COUNT(*) as total 
		                FROM " . DB_PREFIX . "product p 
		                $where";
		$count_result = $db->query($count_query);
		
		if (!$count_result) {
			echo json_encode(['error' => 'Count query failed: ' . $db->error], JSON_UNESCAPED_UNICODE);
			$db->close();
			exit;
		}
		
	$count_row = $count_result->fetch_assoc();
		$total = isset($count_row['total']) ? (int)$count_row['total'] : 0;
		
		// 获取产品列表 - 简化查询，使用PHP逻辑处理分类
		$query = "SELECT 
		              p.product_id, 
		              COALESCE(
		                  (SELECT name FROM " . DB_PREFIX . "product_description WHERE product_id = p.product_id AND language_id = 2 LIMIT 1),
		                  (SELECT name FROM " . DB_PREFIX . "product_description WHERE product_id = p.product_id LIMIT 1),
		                  p.model
		              ) as name,
		              p.model, 
		              p.price, 
		              p.quantity, 
		              p.status,
		              p.image
		          FROM " . DB_PREFIX . "product p 
		          $where 
		          ORDER BY $sort $order
		          LIMIT $start, $limit";
		
		$result = $db->query($query);
		
		if (!$result) {
			echo json_encode(['error' => 'Product query failed: ' . $db->error], JSON_UNESCAPED_UNICODE);
			$db->close();
			exit;
		}
		
		while ($row = $result->fetch_assoc()) {
			// 如果没有中文名称，使用产品型号作为名称
			$product_name = !empty($row['name']) ? $row['name'] : '【未命名】' . $row['model'];
			
			// 处理分类信息 - 获取完整的分类路径（支持多层级）
			$category_paths = [];
			
			// 获取商品的所有分类ID
			$query_cats = $db->query("SELECT DISTINCT ptc.category_id 
			                           FROM " . DB_PREFIX . "product_to_category ptc
			                           WHERE ptc.product_id = " . (int)$row['product_id']);
			
			$all_category_ids = [];
			while ($cat_row = $query_cats->fetch_assoc()) {
				$all_category_ids[] = (int)$cat_row['category_id'];
			}
			
			if (!empty($all_category_ids)) {
				// 排除父分类，只保留最深层级的分类
				$deepest_category_ids = array_filter($all_category_ids, function($cat_id) use ($all_category_ids, $db) {
					// 检查这个分类是否是其他分类的父分类
					if (empty($all_category_ids)) return true;
					
					$ids_str = implode(',', array_map('intval', $all_category_ids));
					$check = $db->query("SELECT COUNT(*) as cnt 
					                     FROM " . DB_PREFIX . "category 
					                     WHERE parent_id = " . (int)$cat_id . " 
					                     AND category_id IN (" . $ids_str . ")");
					
					if ($check && $check->num_rows > 0) {
						$check_row = $check->fetch_assoc();
						return $check_row['cnt'] == 0; // 不是任何分类的父分类
					}
					return true;
				});
				
				// 为每个最深分类构建完整路径
				foreach ($deepest_category_ids as $cat_id) {
					$path = $this->buildCategoryPath($cat_id, $db);
					if ($path) {
						$category_paths[] = $path;
					}
				}
			}
			
			$categories = !empty($category_paths) ? implode(' | ', $category_paths) : '<span class="text-muted">未分类</span>';
			
			// 处理图片路径 - 使用相对路径生成图片URL
			$config_url = rtrim($this->config->get('config_url'), '/');
			$image_url = '';
			if (!empty($row['image'])) {
				$image_url = $config_url . '/image/' . $row['image'];
			} else {
				$image_url = $config_url . '/image/no_image.png';
			}
			
			$products[] = [
				'product_id' => $row['product_id'],
				'name' => $product_name,
				'model' => $row['model'],
				'categories' => $categories,
				'price' => '¥' . number_format($row['price'], 2),
				'quantity' => $row['quantity'],
				'status' => $row['status'],
				'status_text' => $row['status'] ? '启用' : '禁用',
				'image' => $image_url
			];
		}
		
		// 计算显示范围
		$showing_from = $total > 0 ? $start + 1 : 0;
		$showing_to = min($start + count($products), $total);
		
		// 生成分页信息
		$total_pages = $limit > 0 ? ceil($total / $limit) : 1;
		
		// 总是生成分页HTML
		$pagination_html = '<nav><ul class="pagination justify-content-center">';
		
		// 上一页
		if ($page > 1) {
			$pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadProductsPage(' . ($page - 1) . '); return false;">« 上一页</a></li>';
		} else {
			$pagination_html .= '<li class="page-item disabled"><span class="page-link">« 上一页</span></li>';
		}
		
		// 页码（最多显示10页）
		$start_page = max(1, $page - 5);
		$end_page = min($total_pages, $page + 4);
		
		if ($start_page > 1) {
			$pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadProductsPage(1); return false;">1</a></li>';
			if ($start_page > 2) {
				$pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
			}
		}
		
		for ($i = $start_page; $i <= $end_page; $i++) {
			if ($i == $page) {
				$pagination_html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
			} else {
				$pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadProductsPage(' . $i . '); return false;">' . $i . '</a></li>';
			}
		}
		
		if ($end_page < $total_pages) {
			if ($end_page < $total_pages - 1) {
				$pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
			}
			$pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadProductsPage(' . $total_pages . '); return false;">' . $total_pages . '</a></li>';
		}
		
		// 下一页
		if ($page < $total_pages) {
			$pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadProductsPage(' . ($page + 1) . '); return false;">下一页 »</a></li>';
		} else {
			$pagination_html .= '<li class="page-item disabled"><span class="page-link">下一页 »</span></li>';
		}
		
		$pagination_html .= '</ul></nav>';
		
		// 构建结果字符串
		$results_text = sprintf(
			'显示 %d 到 %d，共 %d 个产品（第 %d/%d 页）',
			$showing_from,
			$showing_to,
			$total,
			$page,
			$total_pages
		);
		
		echo json_encode([
			'products' => $products,
			'total' => $total,
			'results' => $results_text,
			'pagination' => $pagination_html
		], JSON_UNESCAPED_UNICODE);
		
		$db->close();
		} catch (\Exception $e) {
			echo json_encode(['error' => '系统错误: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
		}
		exit;
	}
	
	/**
	 * 获取产品列表
	 */
	public function list(): void {
		$this->response->addHeader('Content-Type: application/json');
		
		try {
			$this->load->language('catalog/product_database');
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			
			$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
			$limit = 20;
			$start = ($page - 1) * $limit;
			
			$filter_data = [
				'start' => $start,
				'limit' => $limit
			];
			
			// 搜索过滤
			if (isset($this->request->get['filter_name'])) {
				$filter_data['filter_name'] = $this->request->get['filter_name'];
			}
			
			if (isset($this->request->get['filter_status'])) {
				$filter_data['filter_status'] = $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category_id'])) {
				$filter_data['filter_category_id'] = $this->request->get['filter_category_id'];
			}
			
			$products = $this->model_catalog_product->getProducts($filter_data);
			$total = $this->model_catalog_product->getTotalProducts($filter_data);
			
			$data['products'] = [];
			
			foreach ($products as $product) {
				$data['products'][] = [
					'product_id' => $product['product_id'],
					'name' => $product['name'],
					'model' => $product['model'],
					'price' => $this->currency->format($product['price'], $this->config->get('config_currency')),
					'quantity' => $product['quantity'],
					'status' => $product['status'],
					'status_text' => $product['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'image' => $product['image'] ? $this->model_tool_image->resize($product['image'], 40, 40) : $this->model_tool_image->resize('no_image.png', 40, 40)
				];
			}
			
			$data['pagination'] = '';
			$data['results'] = sprintf('显示 %d 到 %d，共 %d 个产品', ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total);
			
			$this->response->setOutput(json_encode($data));
			
		} catch (\Exception $e) {
			$this->response->setOutput(json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]));
		}
	}
	
	/**
	 * 上传并导入Excel文件
	 */
	public function upload(): void {
		$this->load->language('catalog/product_database');
		
		$json = [];
		
		// 检查权限
		if (!$this->user->hasPermission('modify', 'catalog/product_database')) {
			$json['error'] = $this->language->get('error_permission');
		}
		
		// 检查文件上传
		if (!isset($this->request->files['file']) || $this->request->files['file']['error'] != UPLOAD_ERR_OK) {
			$json['error'] = $this->language->get('error_upload');
		}
		
		// 检查分类ID
		if (!isset($this->request->post['category_id']) || empty($this->request->post['category_id'])) {
			$json['error'] = $this->language->get('error_category');
		}
		
		if (!isset($json['error'])) {
			$file = $this->request->files['file'];
			$category_id = (int)$this->request->post['category_id'];
			
			// 检查文件扩展名
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			
			if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
				$json['error'] = $this->language->get('error_file_type');
			} else {
				try {
					$result = $this->processImport($file['tmp_name'], $ext, $category_id);
					$json['success'] = sprintf($this->language->get('text_import_success'), $result['success'], $result['failed']);
					$json['details'] = $result;
				} catch (\Exception $e) {
					$json['error'] = $this->language->get('error_import') . ': ' . $e->getMessage();
				}
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 处理导入文件（支持所有字段和多图片）
	 */
	private function processImport(string $file_path, string $ext, int $default_category_id): array {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('tool/image');
		$this->load->model('localisation/language');
		
		$success = 0;
		$failed = 0;
		$errors = [];
		
		if ($ext == 'csv') {
			$data = $this->readCSV($file_path);
		} else {
			$data = $this->readExcel($file_path);
		}
		
		// 跳过标题行
		array_shift($data);
		
		$languages = $this->model_localisation_language->getLanguages();
		$default_language_id = (int)$this->config->get('config_language_id');
		
		foreach ($data as $index => $row) {
			$row_num = $index + 2;
			
			try {
				// 验证必填字段（索引0=名称, 1=型号, 9=库存, 25=价格, 39=状态）
				if (empty($row[0]) || empty($row[1])) {
					throw new \Exception("第{$row_num}行：商品名称和型号不能为空");
				}
				
				// 基础数据结构
				$product_data = [
					// 基本字段
					'model' => trim($row[1]),
					'sku' => !empty($row[6]) ? trim($row[6]) : '',
					'upc' => !empty($row[7]) ? trim($row[7]) : '',
					'ean' => !empty($row[8]) ? trim($row[8]) : '',
					'jan' => '',
					'isbn' => '',
					'mpn' => '',
					'location' => '',
					'quantity' => isset($row[9]) && is_numeric($row[9]) ? (int)$row[9] : 0,
					'minimum' => isset($row[10]) && is_numeric($row[10]) ? (int)$row[10] : 1,
					'subtract' => isset($row[11]) ? (int)$row[11] : 1,
					'stock_status_id' => !empty($row[12]) ? $this->getStockStatusId($row[12]) : 7,
					'shipping' => isset($row[13]) ? (int)$row[13] : 1,
					'date_available' => !empty($row[14]) ? date('Y-m-d', strtotime($row[14] . ' days')) : date('Y-m-d'),
					
					// 尺寸重量
					'length' => isset($row[15]) && is_numeric($row[15]) ? (float)$row[15] : 0,
					'width' => isset($row[16]) && is_numeric($row[16]) ? (float)$row[16] : 0,
					'height' => isset($row[17]) && is_numeric($row[17]) ? (float)$row[17] : 0,
					'length_class_id' => $this->getLengthClassId($row[18] ?? 'cm'),
					'weight' => isset($row[19]) && is_numeric($row[19]) ? (float)$row[19] : 0,
					'weight_class_id' => $this->getWeightClassId($row[20] ?? 'kg'),
					
					// 价格营销
					'price' => isset($row[25]) && is_numeric($row[25]) ? (float)$row[25] : 0,
					'tax_class_id' => 0,
					'points' => isset($row[27]) && is_numeric($row[27]) ? (int)$row[27] : 0,
					
					// 图片（主图）
					'image' => !empty($row[23]) ? trim($row[23]) : '',
					
					// 品牌
					'manufacturer_id' => $this->getManufacturerId($row[30] ?? ''),
					'manufacturer' => !empty($row[30]) ? trim($row[30]) : '',
					
					// 排序和状态
					'sort_order' => isset($row[40]) && is_numeric($row[40]) ? (int)$row[40] : 0,
					'status' => isset($row[39]) && $row[39] == '启用' ? 1 : 0,
					
					// 关联数据
					'product_store' => [0],
					'product_description' => [],
					'product_category' => [],
					'product_image' => [],
					'product_special' => [],
					'product_discount' => [],
					'product_reward' => [],
					'product_seo_url' => [],
					'product_related' => [],
					'product_attribute' => [],
					'product_option' => [],
					'product_filter' => [],
					'product_download' => [],
					'product_layout' => []
				];
				
				// 1. 处理产品描述（基本信息）
				foreach ($languages as $language) {
					$product_data['product_description'][$language['language_id']] = [
						'name' => trim($row[0]),
						'description' => !empty($row[2]) ? trim($row[2]) : '',
						'meta_title' => !empty($row[3]) ? trim($row[3]) : trim($row[0]),
						'meta_description' => !empty($row[4]) ? trim($row[4]) : '',
						'meta_keyword' => !empty($row[5]) ? trim($row[5]) : '',
						'tag' => ''
					];
				}
				
				// 2. 处理商品分类
				if (!empty($row[29])) {
					$categories = explode('|', $row[29]);
					foreach ($categories as $cat_name) {
						$cat_id = $this->getCategoryIdByName(trim($cat_name));
						if ($cat_id) {
							$product_data['product_category'][] = $cat_id;
						}
					}
				}
				// 如果没有指定分类，使用默认分类
				if (empty($product_data['product_category'])) {
					$product_data['product_category'][] = $default_category_id;
				}
				
				// 3. 处理附加图片（多张图片用|分隔）
				if (!empty($row[24])) {
					$images = explode('|', $row[24]);
					$sort_order = 0;
					foreach ($images as $image_url) {
						$image_url = trim($image_url);
						if (!empty($image_url)) {
							$product_data['product_image'][] = [
								'image' => $image_url,
								'sort_order' => $sort_order++
							];
						}
					}
				}
				
				// 4. 处理特价
				if (!empty($row[28]) && is_numeric($row[28])) {
					$product_data['product_special'][] = [
						'customer_group_id' => 1,
						'priority' => 1,
						'price' => (float)$row[28],
						'date_start' => !empty($row[29]) ? $row[29] : '0000-00-00',
						'date_end' => !empty($row[30]) ? $row[30] : '0000-00-00'
					];
				}
				
				// 5. 处理奖励积分
				if (!empty($row[31]) && is_numeric($row[31])) {
					foreach ($this->model_localisation_language->getLanguages() as $language) {
						$product_data['product_reward'][] = [
							'customer_group_id' => 1,
							'points' => (int)$row[31]
						];
					}
				}
				
				// 6. 处理属性
				if (!empty($row[34]) && !empty($row[35])) {
					$attr_names = explode(',', $row[34]);
					$attr_values = explode(',', $row[35]);
					
					foreach ($attr_names as $idx => $attr_name) {
						if (isset($attr_values[$idx])) {
							foreach ($languages as $language) {
								$product_data['product_attribute'][] = [
									'attribute_id' => $this->getAttributeIdByName(trim($attr_name)),
									'product_attribute_description' => [
										$language['language_id'] => [
											'text' => trim($attr_values[$idx])
										]
									]
								];
							}
						}
					}
				}
				
				// 7. 处理SEO URL
				if (!empty($row[37])) {
					foreach ($this->model_localisation_language->getLanguages() as $language) {
						$product_data['product_seo_url'][] = [
							'store_id' => 0,
							'language_id' => $language['language_id'],
							'keyword' => trim($row[37])
						];
					}
				}
				
				// 8. 处理关联产品
				if (!empty($row[32])) {
					$related_ids = explode('|', $row[32]);
					foreach ($related_ids as $related_id) {
						if (is_numeric(trim($related_id))) {
							$product_data['product_related'][] = (int)trim($related_id);
						}
					}
				}
				
				// 检查产品是否已存在（通过型号）
				$existing_product = $this->model_catalog_product->getProductByModel($product_data['model']);
				
				if ($existing_product) {
					// 更新现有产品
					$this->model_catalog_product->editProduct($existing_product['product_id'], $product_data);
				} else {
					// 添加新产品
					$this->model_catalog_product->addProduct($product_data);
				}
				
				$success++;
				
			} catch (\Exception $e) {
				$failed++;
				$errors[] = "第{$row_num}行：" . $e->getMessage();
			}
		}
		
		return [
			'success' => $success,
			'failed' => $failed,
			'errors' => $errors
		];
	}
	
	/**
	 * 辅助方法：根据分类名称获取分类ID
	 */
	private function getCategoryIdByName(string $name): int {
		$this->load->model('catalog/category');
		
		// 简单的数据库查询
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
		
		if ($query->num_rows) {
			return (int)$query->row['category_id'];
		}
		
		return 0;
	}
	
	/**
	 * 辅助方法：根据品牌名称获取品牌ID
	 */
	private function getManufacturerId(string $name): int {
		if (empty($name)) {
			return 0;
		}
		
		$query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
		
		if ($query->num_rows) {
			return (int)$query->row['manufacturer_id'];
		}
		
		return 0;
	}
	
	/**
	 * 辅助方法：根据属性名称获取属性ID
	 */
	private function getAttributeIdByName(string $name): int {
		$query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
		
		if ($query->num_rows) {
			return (int)$query->row['attribute_id'];
		}
		
		// 如果不存在，返回0或创建新属性
		return 0;
	}
	
	/**
	 * 辅助方法：获取库存状态ID
	 */
	private function getStockStatusId(string $status): int {
		$status_map = [
			'现货' => 7,
			'预订' => 5,
			'缺货' => 8,
			'2-3天' => 6
		];
		
		return $status_map[$status] ?? 7;
	}
	
	/**
	 * 辅助方法：获取长度单位ID
	 */
	private function getLengthClassId(string $unit): int {
		$unit_map = [
			'cm' => 1,
			'mm' => 2,
			'inch' => 3
		];
		
		return $unit_map[$unit] ?? 1;
	}
	
	/**
	 * 辅助方法：获取重量单位ID
	 */
	private function getWeightClassId(string $unit): int {
		$unit_map = [
			'kg' => 1,
			'g' => 2,
			'lb' => 5,
			'oz' => 6
		];
		
		return $unit_map[$unit] ?? 1;
	}
	
	/**
	 * 读取CSV文件
	 */
	private function readCSV(string $file_path): array {
		$data = [];
		
		if (($handle = fopen($file_path, 'r')) !== false) {
			while (($row = fgetcsv($handle, 1000, ',')) !== false) {
				$data[] = $row;
			}
			fclose($handle);
		}
		
		return $data;
	}
	
	/**
	 * 读取Excel文件（简化版，使用CSV导出或第三方库）
	 */
	private function readExcel(string $file_path): array {
		// 简化版：将Excel文件视为CSV处理
		// 如果需要完整的Excel支持，请安装：composer require phpoffice/phpspreadsheet
		
		// 尝试使用PhpSpreadsheet（如果已安装）
		if (file_exists(DIR_SYSTEM . 'vendor/autoload.php')) {
			require_once(DIR_SYSTEM . 'vendor/autoload.php');
			
			try {
				$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
				$worksheet = $spreadsheet->getActiveSheet();
				$data = [];
				
				foreach ($worksheet->getRowIterator() as $row) {
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(false);
					
					$rowData = [];
					foreach ($cellIterator as $cell) {
						$rowData[] = $cell->getValue();
					}
					$data[] = $rowData;
				}
				
				return $data;
			} catch (\Exception $e) {
				// 如果失败，返回空数组或抛出异常
				throw new \Exception('无法读取Excel文件，请使用CSV格式或联系管理员安装PhpSpreadsheet库');
			}
		} else {
			// 如果没有安装PhpSpreadsheet，提示用户使用CSV格式
			throw new \Exception('系统不支持Excel格式，请将文件另存为CSV格式后重新上传');
		}
	}
	
	/**
	 * 批量启用产品
	 */
	public function enable(): void {
		$this->load->language('catalog/product_database');
		
		$json = [];
		
		// 检查权限
		if (!$this->user->hasPermission('modify', 'catalog/product_database')) {
			$json['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// 检查是否有选中的产品
		if (!isset($this->request->post['selected']) || empty($this->request->post['selected'])) {
			$json['error'] = '请先选择要启用的产品！';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		try {
			$this->load->model('catalog/product');
			
			$count = 0;
			foreach ($this->request->post['selected'] as $product_id) {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET status = 1 WHERE product_id = '" . (int)$product_id . "'");
				$count++;
			}
			
			$json['success'] = '成功启用 ' . $count . ' 个产品！';
		} catch (Exception $e) {
			$json['error'] = '启用失败：' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 批量下架产品
	 */
	public function disable(): void {
		$this->load->language('catalog/product_database');
		
		$json = [];
		
		// 检查权限
		if (!$this->user->hasPermission('modify', 'catalog/product_database')) {
			$json['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// 检查是否有选中的产品
		if (!isset($this->request->post['selected']) || empty($this->request->post['selected'])) {
			$json['error'] = '请先选择要下架的产品！';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		try {
			$this->load->model('catalog/product');
			
			$count = 0;
			foreach ($this->request->post['selected'] as $product_id) {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET status = 0 WHERE product_id = '" . (int)$product_id . "'");
				$count++;
			}
			
			$json['success'] = '成功下架 ' . $count . ' 个产品！前台已不显示这些产品。';
		} catch (Exception $e) {
			$json['error'] = '下架失败：' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 批量删除产品
	 */
	public function delete(): void {
		$this->load->language('catalog/product_database');
		
		$json = [];
		
		// 检查权限
		if (!$this->user->hasPermission('modify', 'catalog/product_database')) {
			$json['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// 检查是否有选中的产品
		if (!isset($this->request->post['selected']) || empty($this->request->post['selected'])) {
			$json['error'] = '请先选择要删除的产品！';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		try {
			$this->load->model('catalog/product');
			
			$count = 0;
			foreach ($this->request->post['selected'] as $product_id) {
				// 使用OpenCart内置方法彻底删除产品（包括所有相关数据）
				$this->model_catalog_product->deleteProduct((int)$product_id);
				$count++;
			}
			
			$json['success'] = '✓ 成功删除 ' . $count . ' 个产品及其所有相关数据（图片、属性、SKU等）！';
		} catch (Exception $e) {
			$json['error'] = '✗ 删除失败：' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 下载完整的Excel导入模板（包含所有字段和多图片支持）
	 */
	public function downloadTemplate(): void {
		$this->load->language('catalog/product_database');
		
		// 简化版CSV模板（如果没有PhpSpreadsheet）
		$vendorPath = DIR_SYSTEM . '../vendor/autoload.php';
		if (!file_exists($vendorPath)) {
			$this->downloadTemplateCSV();
			return;
		}
		
		require_once $vendorPath;
		
		// 直接使用完全限定的类名
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('产品完整导入模板');
		
		// 定义完整的表头（包含所有11个步骤的字段，明确标注功能和多选）
		$headers = [
			// ===== 1. 基本信息 =====
			'【必填】商品名称',
			'【必填】商品型号',
			'商品描述(支持HTML标签)',
			'Meta标题(SEO用)',
			'Meta描述(SEO用)',
			'Meta关键词(SEO用，逗号分隔)',
			'商品标签(逗号分隔，可多个)',
			
			// ===== 2. 数据 =====
			'SKU编码(库存单位)',
			'UPC码(通用商品代码)',
			'EAN码(欧洲商品编号)',
			'JAN码(日本商品编号)',
			'ISBN码(国际标准图书编号)',
			'MPN码(生产商编号)',
			'商品位置(仓库位置)',
			'【必填】库存数量',
			'最小起订量(MOQ)',
			'是否减库存(1=是/0=否)',
			'缺货状态(如:缺货/预订)',
			'需要配送(1=是/0=否，虚拟商品填0)',
			'发货天数(备货时间)',
			'商品税别(如:免税/标准税)',
			'尺寸长度(cm)',
			'尺寸宽度(cm)',
			'尺寸高度(cm)',
			'长度单位(cm/inch/m)',
			'重量(kg)',
			'重量单位(kg/g/lb)',
			'开售日期(YYYY-MM-DD)',
			
			// ===== 3. 选项设置(规格) =====
			'选项名称(如:颜色/尺寸)',
			'选项类型(select/radio/checkbox/input/text/textarea/file/date/time)',
			'选项值(多个用逗号分隔)',
			'选项是否必填(1=是/0=否)',
			
			// ===== 4. SKU列表 =====
			'SKU组合(自动生成，建议留空)',
			'SKU库存',
			'SKU价格',
			'SKU重量',
			'SKU状态(1=启用/0=禁用)',
			
			// ===== 5. 图片(可多个) =====
			'【可多个】主图片URL(相对路径或完整URL)',
			'【可多个】附加图片1(catalog/xxx.jpg)',
			'【可多个】附加图片2(catalog/xxx.jpg)',
			'【可多个】附加图片3(catalog/xxx.jpg)',
			'【可多个】附加图片4(catalog/xxx.jpg)',
			'【可多个】附加图片5(catalog/xxx.jpg)',
			'【可多个】更多图片(用|分隔，如: img1.jpg|img2.jpg|img3.jpg)',
			
			// ===== 6. 营销与价格 =====
			'【必填】销售价格',
			'成本价(用于利润计算)',
			'购买所需积分',
			'特价(促销价格)',
			'特价开始日期(YYYY-MM-DD)',
			'特价结束日期(YYYY-MM-DD)',
			'奖励积分(购买后获得)',
			'优先级(数字)',
			'客户组(默认/批发/零售)',
			'数量折扣(数量>=10,价格-10)',
			'特殊折扣(客户组=VIP,价格-20)',
			
			// ===== 7. 链接(可多个) =====
			'【可多个】商品分类(多个用|分隔，如:电子产品|智能手表)',
			'品牌/制造商',
			'【可多个】关联商品ID(用|分隔，如:1|2|3)',
			'【可多个】筛选标签(用|分隔)',
			'【可多个】可下载文件(用|分隔)',
			
			// ===== 8. 属性(可多个) =====
			'【可多个】属性组',
			'【可多个】属性名1(用逗号分隔)',
			'【可多个】属性值1(用逗号分隔，与属性名对应)',
			'【可多个】属性名2',
			'【可多个】属性值2',
			'【可多个】属性名3',
			'【可多个】属性值3',
			
			// ===== 9. SEO =====
			'SEO关键词(搜索引擎用)',
			'SEO友好URL(如:apple-watch-series-9)',
			'【可多个】SEO多语言URL(英文|中文|法文)',
			
			// ===== 10. 设计 =====
			'自定义布局(layout ID)',
			'自定义主题(theme名称)',
			
			// ===== 11. 报表 =====
			'查看次数(留空自动统计)',
			
			// ===== 通用设置 =====
			'【必填】状态(启用/禁用)',
			'显示顺序(数字，越小越靠前)',
			'所属店铺ID(多店铺用)'
		];
		
		// 写入表头并设置样式
		$col = 'A';
		foreach ($headers as $header) {
			$sheet->setCellValue($col . '1', $header);
			
			// 表头样式
			$sheet->getStyle($col . '1')->applyFromArray([
				'fill' => [
					'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
					'startColor' => ['rgb' => '4472C4']
				],
				'font' => [
					'bold' => true,
					'color' => ['rgb' => 'FFFFFF'],
					'size' => 11
				],
				'alignment' => [
					'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
					'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
					'wrapText' => true
				],
				'borders' => [
					'allBorders' => [
						'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
					]
				]
			]);
			
			$sheet->getColumnDimension($col)->setWidth(20);
			$col++;
		}
		
		$sheet->getRowDimension(1)->setRowHeight(40);
		
		// 写入示例数据（完整示例）
		$exampleData = [
			[
				// ===== 1. 基本信息 =====
				'Apple Watch Series 9 GPS 45mm',  // 商品名称
				'WATCH-APPLE-S9-45MM',  // 商品型号
				'<h3>产品特点</h3><ul><li>S9芯片性能提升</li><li>超亮显示屏</li><li>双击手势控制</li><li>全天候健康监测</li></ul>',  // 商品描述
				'Apple Watch Series 9 - 全新智能手表',  // Meta标题
				'全新Apple Watch Series 9，配备S9芯片，双击手势控制，超亮显示屏，支持心率监测',  // Meta描述
				'Apple Watch,智能手表,健康监测,S9芯片',  // Meta关键词
				'智能手表,Apple,运动健康',  // 商品标签
				
				// ===== 2. 数据 =====
				'APL-WCH-S9-001',  // SKU编码
				'194253912316',  // UPC码
				'',  // EAN码
				'',  // JAN码
				'',  // ISBN码
				'MWVD3CH/A',  // MPN码
				'A1-001',  // 商品位置
				'50',  // 库存数量
				'1',  // 最小起订量
				'1',  // 是否减库存
				'预订',  // 缺货状态
				'1',  // 需要配送
				'2-3',  // 发货天数
				'免税',  // 商品税别
				'4.5',  // 尺寸长度
				'3.8',  // 尺寸宽度
				'1.0',  // 尺寸高度
				'cm',  // 长度单位
				'52.5',  // 重量
				'g',  // 重量单位
				'2025-10-25',  // 开售日期
				
				// ===== 3. 选项设置 =====
				'颜色',  // 选项名称
				'select',  // 选项类型
				'午夜色,星光色,红色,粉色',  // 选项值
				'1',  // 选项是否必填
				
				// ===== 4. SKU列表 =====
				'',  // SKU组合（留空）
				'',  // SKU库存
				'',  // SKU价格
				'',  // SKU重量
				'',  // SKU状态
				
				// ===== 5. 图片 =====
				'catalog/wearables/apple-watch-s9-main.jpg',  // 主图片
				'catalog/wearables/apple-watch-s9-side1.jpg',  // 附加图片1
				'catalog/wearables/apple-watch-s9-side2.jpg',  // 附加图片2
				'catalog/wearables/apple-watch-s9-back.jpg',  // 附加图片3
				'catalog/wearables/apple-watch-s9-box.jpg',  // 附加图片4
				'catalog/wearables/apple-watch-s9-wear.jpg',  // 附加图片5
				'catalog/wearables/s9-detail1.jpg|catalog/wearables/s9-detail2.jpg|catalog/wearables/s9-detail3.jpg',  // 更多图片
				
				// ===== 6. 营销与价格 =====
				'3299.00',  // 销售价格
				'2800.00',  // 成本价
				'0',  // 购买所需积分
				'2999.00',  // 特价
				'2025-10-25',  // 特价开始日期
				'2025-11-30',  // 特价结束日期
				'100',  // 奖励积分
				'1',  // 优先级
				'默认',  // 客户组
				'',  // 数量折扣
				'',  // 特殊折扣
				
				// ===== 7. 链接 =====
				'随身设备|智能手表|Apple产品',  // 商品分类
				'Apple',  // 品牌
				'58|59|60',  // 关联商品ID
				'新品|热卖|推荐',  // 筛选标签
				'',  // 可下载文件
				
				// ===== 8. 属性 =====
				'技术规格',  // 属性组
				'屏幕尺寸,芯片,电池续航,防水等级',  // 属性名1
				'45mm,S9芯片,18小时,50米',  // 属性值1
				'连接性',  // 属性名2
				'GPS,蓝牙5.3,Wi-Fi',  // 属性值2
				'传感器',  // 属性名3
				'心率传感器,血氧传感器,温度传感器',  // 属性值3
				
				// ===== 9. SEO =====
				'apple watch series 9 gps 45mm',  // SEO关键词
				'apple-watch-series-9-45mm-gps',  // SEO友好URL
				'apple-watch-series-9|苹果手表第九代|montre-apple-series-9',  // SEO多语言URL
				
				// ===== 10. 设计 =====
				'',  // 自定义布局
				'',  // 自定义主题
				
				// ===== 11. 报表 =====
				'',  // 查看次数
				
				// ===== 通用设置 =====
				'启用',  // 状态
				'1',  // 显示顺序
				'0'  // 所属店铺ID
			]
		];
		
		// 写入示例数据
		$row = 2;
		foreach ($exampleData as $data) {
			$col = 'A';
			foreach ($data as $value) {
				$sheet->setCellValue($col . $row, $value);
				$sheet->getStyle($col . $row)->applyFromArray([
					'alignment' => [
						'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
						'wrapText' => true
					],
					'borders' => [
						'allBorders' => [
							'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
							'color' => ['rgb' => 'D0D0D0']
						]
					]
				]);
				$col++;
			}
			$row++;
		}
		
		// 添加说明工作表
		$instructionSheet = $spreadsheet->createSheet();
		$instructionSheet->setTitle('使用说明');
		
		$instructions = [
			['完整产品导入模板 - 使用说明（包含商品管理全部11个步骤）'],
			[''],
			['重要提示：'],
			['1. 【必填】= 必填字段，不能为空'],
			['2. 【可多个】= 支持多个值，使用指定分隔符'],
			['3. 导入后将立即保存并全站同步生效'],
			['4. 图片URL必须是相对路径（相对于image目录）或完整URL'],
			[''],
			['字段标注说明：'],
			['  【必填】- 这个字段是必填的，不能留空'],
			['  【可多个】- 这个字段支持添加多个值'],
			['  (多个用|分隔) - 多个值之间用竖线|分隔'],
			['  (用逗号分隔) - 多个值之间用逗号,分隔'],
			['  (catalog/xxx.jpg) - 填写示例格式'],
			[''],
			['一、图片导入说明（重点）'],
			['  主图片URL：填写一张主图的路径'],
			['    示例：catalog/products/product-01.jpg'],
			['  附加图片URL：多张图片用竖线|分隔'],
			['    示例：catalog/products/product-02.jpg|catalog/products/product-03.jpg|catalog/products/product-04.jpg'],
			['  支持格式：'],
			['    - 相对路径：catalog/products/image.jpg'],
			['    - 完整URL：https://example.com/image.jpg'],
			['  图片会自动处理并同步到全站'],
			[''],
			['二、基本信息字段'],
			['  商品名称：产品显示名称（必填）'],
			['  商品型号：唯一标识符（必填）'],
			['  商品描述：支持HTML格式，建议包含<h3>、<ul>、<li>等标签'],
			['  Meta标题/描述/关键词：用于SEO优化'],
			[''],
			['三、数据字段'],
			['  SKU/UPC/EAN：产品条码信息'],
			['  库存数量：当前库存（必填）'],
			['  是否减库存：1表示下单自动减库存，0表示不减'],
			['  需要配送：1表示需要物流，0表示虚拟商品'],
			['  尺寸/重量：用于运费计算'],
			[''],
			['四、选项设置'],
			['  选项名称：如"颜色"、"尺寸"'],
			['  选项值：多个值用逗号分隔，如"红色,蓝色,黑色"'],
			[''],
			['五、图片（支持批量导入）'],
			['  主图片：产品主图'],
			['  附加图片：最多可添加数十张，用|分隔'],
			['  重要：图片需要预先上传到服务器的image目录'],
			[''],
			['六、营销与价格'],
			['  销售价格：产品售价（必填）'],
			['  成本价：用于利润计算'],
			['  特价：促销价格'],
			['  特价日期：设置促销时间段，格式YYYY-MM-DD'],
			['  奖励积分：购买后获得的积分'],
			[''],
			['七、链接'],
			['  商品分类：多个分类用|分隔，如"随身设备|智能手表"'],
			['  品牌：产品品牌名称'],
			['  关联产品ID：相关产品ID，用|分隔'],
			[''],
			['八、属性'],
			['  属性名：多个属性用逗号分隔'],
			['  属性值：对应属性的值，用逗号分隔，顺序要对应'],
			[''],
			['九、SEO'],
			['  SEO关键词：搜索引擎关键词'],
			['  SEO友好URL：自定义URL，如product-name'],
			[''],
			['十、状态管理'],
			['  状态：填写"启用"或"禁用"'],
			['  显示顺序：数字越小越靠前'],
			[''],
			['十一、导入流程'],
			['  1. 填写本模板（参考示例数据）'],
			['  2. 保存为Excel或CSV文件'],
			['  3. 在"产品数据库管理"页面点击"选择文件"'],
			['  4. 选择填好的文件并点击"导入"'],
			['  5. 系统自动处理并立即保存'],
			['  6. 导入成功后，全站（前台+后台）立即同步生效'],
			[''],
			['十二、AI辅助提示'],
			['  您可以使用AI（如ChatGPT）快速生成导入数据：'],
			['  示例提示词："帮我生成20个智能手表产品的Excel数据，包含产品名称、型号、描述、图片链接等"'],
			['  然后将AI生成的数据复制到本模板即可批量导入'],
			[''],
			['十三、完整字段列表（按商品管理步骤分类）'],
			[''],
			['【步骤1：基本信息】7个字段'],
			['  - 商品名称 (必填)'],
			['  - 商品型号 (必填)'],
			['  - 商品描述 (支持HTML)'],
			['  - Meta标题/描述/关键词 (SEO优化)'],
			['  - 商品标签 (可多个，逗号分隔)'],
			[''],
			['【步骤2：数据】21个字段'],
			['  - SKU/UPC/EAN/JAN/ISBN/MPN码 (条码信息)'],
			['  - 商品位置 (仓库位置)'],
			['  - 库存数量 (必填)'],
			['  - 最小起订量/是否减库存/缺货状态'],
			['  - 需要配送/发货天数/商品税别'],
			['  - 尺寸(长宽高)/长度单位'],
			['  - 重量/重量单位'],
			['  - 开售日期'],
			[''],
			['【步骤3：选项设置】4个字段'],
			['  - 选项名称 (如颜色/尺寸)'],
			['  - 选项类型 (select/radio/checkbox等)'],
			['  - 选项值 (可多个，逗号分隔)'],
			['  - 选项是否必填'],
			[''],
			['【步骤4：SKU列表】5个字段'],
			['  - SKU组合/库存/价格/重量/状态'],
			['  - 注：建议留空，由系统自动生成'],
			[''],
			['【步骤5：图片】7个字段 【重点：可多个】'],
			['  - 主图片URL (必填)'],
			['  - 附加图片1-5 (每个都可单独填写)'],
			['  - 更多图片 (用|分隔，支持无限多张)'],
			['  - 示例：img1.jpg|img2.jpg|img3.jpg|img4.jpg...'],
			[''],
			['【步骤6：营销与价格】11个字段'],
			['  - 销售价格 (必填)'],
			['  - 成本价/购买积分/奖励积分'],
			['  - 特价/特价日期 (促销活动)'],
			['  - 优先级/客户组'],
			['  - 数量折扣/特殊折扣'],
			[''],
			['【步骤7：链接】5个字段 【可多个】'],
			['  - 商品分类 (可多个，用|分隔)'],
			['  - 品牌/制造商'],
			['  - 关联商品ID (可多个，用|分隔)'],
			['  - 筛选标签 (可多个，用|分隔)'],
			['  - 可下载文件 (可多个，用|分隔)'],
			[''],
			['【步骤8：属性】7个字段 【可多个】'],
			['  - 属性组'],
			['  - 属性名1/属性值1'],
			['  - 属性名2/属性值2'],
			['  - 属性名3/属性值3'],
			['  - 注：属性名和属性值要一一对应'],
			[''],
			['【步骤9：SEO】3个字段'],
			['  - SEO关键词'],
			['  - SEO友好URL'],
			['  - SEO多语言URL (可多个，用|分隔)'],
			[''],
			['【步骤10：设计】2个字段'],
			['  - 自定义布局 (layout ID)'],
			['  - 自定义主题 (theme名称)'],
			[''],
			['【步骤11：报表】1个字段'],
			['  - 查看次数 (留空自动统计)'],
			[''],
			['【通用设置】3个字段'],
			['  - 状态 (必填：启用/禁用)'],
			['  - 显示顺序 (数字)'],
			['  - 所属店铺ID (多店铺)'],
			[''],
			['合计：80+个字段，涵盖OpenCart商品管理的所有功能！'],
		];
		
		$row = 1;
		foreach ($instructions as $instruction) {
			$instructionSheet->setCellValue('A' . $row, $instruction[0]);
			
			if ($row == 1) {
				$instructionSheet->getStyle('A' . $row)->applyFromArray([
					'font' => [
						'bold' => true,
						'size' => 16,
						'color' => ['rgb' => '4472C4']
					],
					'alignment' => [
						'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
					]
				]);
			} elseif (strpos($instruction[0], '、') !== false || strpos($instruction[0], '：') == (mb_strlen($instruction[0]) - 1)) {
				$instructionSheet->getStyle('A' . $row)->applyFromArray([
					'font' => [
						'bold' => true,
						'size' => 12,
						'color' => ['rgb' => '2E5090']
					]
				]);
			}
			
			$row++;
		}
		
		$instructionSheet->getColumnDimension('A')->setWidth(100);
		
		// 设置默认工作表为模板
		$spreadsheet->setActiveSheetIndex(0);
		
		// 输出文件
		$filename = '产品完整导入模板_' . date('Ymd_His') . '.xlsx';
		
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$writer->save('php://output');
		
		exit;
	}
	
	/**
	 * 简化CSV模板（备用）
	 */
	private function downloadTemplateCSV(): void {
		$filename = '产品完整导入模板_' . date('Ymd_His') . '.csv';
		
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		
		$output = fopen('php://output', 'w');
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 完整的80+字段表头（与Excel版本一致）
		fputcsv($output, [
			// ===== 1. 基本信息 =====
			'【必填】商品名称',
			'【必填】商品型号',
			'商品描述(支持HTML标签)',
			'Meta标题(SEO用)',
			'Meta描述(SEO用)',
			'Meta关键词(SEO用，逗号分隔)',
			'商品标签(逗号分隔，可多个)',
			
			// ===== 2. 数据 =====
			'SKU编码(库存单位)',
			'UPC码(通用商品代码)',
			'EAN码(欧洲商品编号)',
			'JAN码(日本商品编号)',
			'ISBN码(国际标准图书编号)',
			'MPN码(生产商编号)',
			'商品位置(仓库位置)',
			'【必填】库存数量',
			'最小起订量(MOQ)',
			'是否减库存(1=是/0=否)',
			'缺货状态(如:缺货/预订)',
			'需要配送(1=是/0=否，虚拟商品填0)',
			'发货天数(备货时间)',
			'商品税别(如:免税/标准税)',
			'尺寸长度(cm)',
			'尺寸宽度(cm)',
			'尺寸高度(cm)',
			'长度单位(cm/inch/m)',
			'重量(kg)',
			'重量单位(kg/g/lb)',
			'开售日期(YYYY-MM-DD)',
			
			// ===== 3. 选项设置(规格) =====
			'选项名称(如:颜色/尺寸)',
			'选项类型(select/radio/checkbox/input/text/textarea/file/date/time)',
			'选项值(多个用逗号分隔)',
			'选项是否必填(1=是/0=否)',
			
			// ===== 4. SKU列表 =====
			'SKU组合(自动生成，建议留空)',
			'SKU库存',
			'SKU价格',
			'SKU重量',
			'SKU状态(1=启用/0=禁用)',
			
			// ===== 5. 图片(可多个) =====
			'【可多个】主图片URL(相对路径或完整URL)',
			'【可多个】附加图片1(catalog/xxx.jpg)',
			'【可多个】附加图片2(catalog/xxx.jpg)',
			'【可多个】附加图片3(catalog/xxx.jpg)',
			'【可多个】附加图片4(catalog/xxx.jpg)',
			'【可多个】附加图片5(catalog/xxx.jpg)',
			'【可多个】更多图片(用|分隔，如: img1.jpg|img2.jpg|img3.jpg)',
			
			// ===== 6. 营销与价格 =====
			'【必填】销售价格',
			'成本价(用于利润计算)',
			'购买所需积分',
			'特价(促销价格)',
			'特价开始日期(YYYY-MM-DD)',
			'特价结束日期(YYYY-MM-DD)',
			'奖励积分(购买后获得)',
			'优先级(数字)',
			'客户组(默认/批发/零售)',
			'数量折扣(数量>=10,价格-10)',
			'特殊折扣(客户组=VIP,价格-20)',
			
			// ===== 7. 链接(可多个) =====
			'【可多个】商品分类(多个用|分隔，如:电子产品|智能手表)',
			'品牌/制造商',
			'【可多个】关联商品ID(用|分隔，如:1|2|3)',
			'【可多个】筛选标签(用|分隔)',
			'【可多个】可下载文件(用|分隔)',
			
			// ===== 8. 属性(可多个) =====
			'【可多个】属性组',
			'【可多个】属性名1(用逗号分隔)',
			'【可多个】属性值1(用逗号分隔，与属性名对应)',
			'【可多个】属性名2',
			'【可多个】属性值2',
			'【可多个】属性名3',
			'【可多个】属性值3',
			
			// ===== 9. SEO =====
			'SEO关键词(搜索引擎用)',
			'SEO友好URL(如:apple-watch-series-9)',
			'【可多个】SEO多语言URL(英文|中文|法文)',
			
			// ===== 10. 设计 =====
			'自定义布局(layout ID)',
			'自定义主题(theme名称)',
			
			// ===== 11. 报表 =====
			'查看次数(留空自动统计)',
			
			// ===== 通用设置 =====
			'【必填】状态(启用/禁用)',
			'显示顺序(数字，越小越靠前)',
			'所属店铺ID(多店铺用)'
		]);
		
		// 完整的示例数据
		fputcsv($output, [
			// 1. 基本信息
			'Apple Watch Series 9 GPS 45mm',
			'WATCH-APPLE-S9-45MM',
			'<h3>产品特点</h3><ul><li>S9芯片性能提升</li><li>超亮显示屏</li><li>双击手势控制</li><li>全天候健康监测</li></ul>',
			'Apple Watch Series 9 - 全新智能手表',
			'全新Apple Watch Series 9，配备S9芯片，双击手势控制，超亮显示屏，支持心率监测',
			'Apple Watch,智能手表,健康监测,S9芯片',
			'智能手表,Apple,运动健康',
			
			// 2. 数据
			'APL-WCH-S9-001',
			'194253912316',
			'',
			'',
			'',
			'MWVD3CH/A',
			'A1-001',
			'50',
			'1',
			'1',
			'预订',
			'1',
			'2-3',
			'免税',
			'4.5',
			'3.8',
			'1.0',
			'cm',
			'52.5',
			'g',
			'2025-10-25',
			
			// 3. 选项设置
			'颜色',
			'select',
			'午夜色,星光色,红色,粉色',
			'1',
			
			// 4. SKU列表
			'',
			'',
			'',
			'',
			'',
			
			// 5. 图片
			'catalog/wearables/apple-watch-s9-main.jpg',
			'catalog/wearables/apple-watch-s9-side1.jpg',
			'catalog/wearables/apple-watch-s9-side2.jpg',
			'catalog/wearables/apple-watch-s9-back.jpg',
			'catalog/wearables/apple-watch-s9-box.jpg',
			'catalog/wearables/apple-watch-s9-wear.jpg',
			'catalog/wearables/s9-detail1.jpg|catalog/wearables/s9-detail2.jpg|catalog/wearables/s9-detail3.jpg',
			
			// 6. 营销与价格
			'3299.00',
			'2800.00',
			'0',
			'2999.00',
			'2025-10-25',
			'2025-11-30',
			'100',
			'1',
			'默认',
			'',
			'',
			
			// 7. 链接
			'随身设备|智能手表|Apple产品',
			'Apple',
			'58|59|60',
			'新品|热卖|推荐',
			'',
			
			// 8. 属性
			'技术规格',
			'屏幕尺寸,芯片,电池续航,防水等级',
			'45mm,S9芯片,18小时,50米',
			'连接性',
			'GPS,蓝牙5.3,Wi-Fi',
			'传感器',
			'心率传感器,血氧传感器,温度传感器',
			
			// 9. SEO
			'apple watch series 9 gps 45mm',
			'apple-watch-series-9-45mm-gps',
			'apple-watch-series-9|苹果手表第九代|montre-apple-series-9',
			
			// 10. 设计
			'',
			'',
			
			// 11. 报表
			'',
			
			// 通用设置
			'启用',
			'1',
			'0'
		]);
		
		fclose($output);
		exit;
	}
	
	/**
	 * 构建分类的完整路径（递归，从根到叶）
	 * @param int $category_id 分类ID
	 * @param mysqli $db 数据库连接
	 * @return string 完整的分类路径，如"手机与平板 > iPhone"
	 */
	private function buildCategoryPath(int $category_id, $db): string {
		$path = [];
		$current_id = $category_id;
		
		// 递归获取所有父分类
		while ($current_id > 0) {
			$query = $db->query("SELECT c.category_id, c.parent_id, cd.name 
			                     FROM " . DB_PREFIX . "category c
			                     LEFT JOIN " . DB_PREFIX . "category_description cd 
			                         ON c.category_id = cd.category_id AND cd.language_id = 2
			                     WHERE c.category_id = " . (int)$current_id . "
			                     LIMIT 1");
			
			if ($query && $query->num_rows > 0) {
				$row = $query->fetch_assoc();
				array_unshift($path, $row['name']); // 添加到数组开头
				$current_id = (int)$row['parent_id'];
			} else {
				break;
			}
		}
		
		return implode(' > ', $path);
	}
	
	/**
	 * 获取分类的所有父级分类ID（包括自己）
	 * 例如：选择"音频设备 > 真无线耳机"，返回 [60, 66]（音频设备ID和真无线耳机ID）
	 * @param int $category_id 分类ID
	 * @return array 分类ID数组（从父到子）
	 */
	private function getAllParentCategoryIds(int $category_id): array {
		$category_ids = [];
		$current_id = $category_id;
		
		// 递归获取所有父分类ID
		while ($current_id > 0) {
			$category_ids[] = $current_id;
			
			$query = $this->db->query("SELECT parent_id FROM `" . DB_PREFIX . "category` 
			                           WHERE category_id = '" . (int)$current_id . "' 
			                           LIMIT 1");
			
			if ($query->num_rows > 0) {
				$current_id = (int)$query->row['parent_id'];
			} else {
				break;
			}
		}
		
		// 反转数组，使其从父到子排序
		return array_reverse($category_ids);
	}
	
	/**
	 * 获取分类树（包含完整的分类路径）
	 * 显示格式：顶级分类、子分类（完整路径）、孙分类（完整路径）
	 */
	private function getCategoryTree(int $parent_id = 0, string $prefix = '', string $path = ''): array {
		$this->load->model('catalog/category');
		
		$categories = [];
		
		$results = $this->model_catalog_category->getCategories(['filter_parent_id' => $parent_id]);
		
		foreach ($results as $result) {
			// 构建完整的分类路径（父级 > 子级）
			$current_path = $path ? $path . ' > ' . $result['name'] : $result['name'];
			
			// 根据层级添加视觉缩进（├── 符号）
			$visual_prefix = '';
			if ($prefix) {
				$visual_prefix = $prefix . '├─ ';
			}
			
			$categories[] = [
				'category_id' => $result['category_id'],
				'name' => $visual_prefix . $current_path  // 显示: ├─ 完整路径
			];
			
			// 递归获取子分类
			$children = $this->getCategoryTree(
				$result['category_id'], 
				$prefix . '│　',  // 使用树形符号增加缩进
				$current_path  // 传递当前路径
			);
			
			if ($children) {
				$categories = array_merge($categories, $children);
			}
		}
		
		return $categories;
	}
	
	/**
	 * 获取单个产品信息（用于快速编辑）
	 */
	public function getProductInfo(): void {
		$this->load->model('catalog/product');
		
		$product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
		
		if ($product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);
			
			if ($product_info) {
				// 获取产品分类
				$categories = $this->model_catalog_product->getCategories($product_id);
				$category_id = !empty($categories) ? $categories[0] : 0;
				
				// 简化返回数据
				$json = [
					'product_id' => $product_info['product_id'],
					'name' => $product_info['name'] ?? '',
					'category_id' => $category_id,
					'model' => $product_info['model'] ?? '',
					'price' => $product_info['price'] ?? '0',
					'quantity' => $product_info['quantity'] ?? '0',
					'status' => $product_info['status'] ?? '0'
				];
			} else {
				$json = ['error' => '产品不存在'];
			}
		} else {
			$json = ['error' => '无效的产品ID'];
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 快速保存产品信息
	 */
	public function quickSave(): void {
		$this->load->language('catalog/product_database');
		
		$json = [];
		
		if (!$this->user->hasPermission('modify', 'catalog/product_database')) {
			$json['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		$this->load->model('catalog/product');
		
		$product_id = (int)$this->request->post['product_id'];
		
		if (!$product_id) {
			$json['error'] = '无效的产品ID';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		try {
			// 使用直接的SQL UPDATE语句，避免数据结构问题
			$language_id = (int)$this->config->get('config_language_id');
			
			// 1. 更新产品基本信息（product表）
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET 
				`model` = '" . $this->db->escape($this->request->post['model']) . "',
				`price` = '" . (float)$this->request->post['price'] . "',
				`quantity` = '" . (int)$this->request->post['quantity'] . "',
				`status` = '" . (int)$this->request->post['status'] . "',
				`date_modified` = NOW()
				WHERE `product_id` = '" . (int)$product_id . "'");
			
			// 2. 更新产品名称（product_description表，只更新当前语言）
			$this->db->query("UPDATE `" . DB_PREFIX . "product_description` SET 
				`name` = '" . $this->db->escape($this->request->post['name']) . "'
				WHERE `product_id` = '" . (int)$product_id . "' 
				AND `language_id` = '" . (int)$language_id . "'");
			
			// 3. 更新产品分类（如果提供了分类ID）
			if (isset($this->request->post['category_id'])) {
				$category_id = (int)$this->request->post['category_id'];
				
				// 只有当分类ID有效时才更新分类关联
				if ($category_id > 0) {
					// 先删除所有分类关联
					$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . (int)$product_id . "'");
					
					// 获取该分类的所有父级分类ID（包括自己）
					$category_ids = $this->getAllParentCategoryIds($category_id);
					
					// 添加所有分类关联（父级 + 子级）
					foreach ($category_ids as $cat_id) {
						$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET 
							`product_id` = '" . (int)$product_id . "',
							`category_id` = '" . (int)$cat_id . "'");
					}
				}
				// 如果分类ID为0，保持原有的分类关联不变
			}
			
			$json['success'] = '✓ 产品更新成功！修改已在全站同步（包括商品分类）。';
		} catch (Exception $e) {
			$json['error'] = '✗ 更新失败：' . $e->getMessage();
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 批量编辑产品
	 */
	public function batchEdit(): void {
		$this->load->language('catalog/product_database');
		
		$json = [];
		
		if (!$this->user->hasPermission('modify', 'catalog/product_database')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('catalog/product');
			
			$selected = isset($this->request->post['selected']) ? $this->request->post['selected'] : [];
			$modifications = isset($this->request->post['modifications']) ? $this->request->post['modifications'] : [];
			
			if (empty($selected)) {
				$json['error'] = '未选择任何产品';
			} elseif (empty($modifications)) {
				$json['error'] = '未指定要修改的字段';
			} else {
				$success_count = 0;
				$error_count = 0;
				
				foreach ($selected as $product_id) {
					$product_id = (int)$product_id;
					
					// 获取现有产品数据
					$product_info = $this->model_catalog_product->getProduct($product_id);
					
					if ($product_info) {
						$data = $product_info;
						
						// 应用修改
						if (isset($modifications['category_id'])) {
							$category_id = (int)$modifications['category_id'];
							// 获取该分类的所有父级分类ID（包括自己）
							$category_ids = $this->getAllParentCategoryIds($category_id);
							$data['product_category'] = $category_ids;
						}
						
						if (isset($modifications['status'])) {
							$data['status'] = (int)$modifications['status'];
						}
						
						if (isset($modifications['price'])) {
							$price_mod = $modifications['price'];
							$current_price = (float)$product_info['price'];
							$value = (float)$price_mod['value'];
							
							switch ($price_mod['operation']) {
								case 'set':
									$data['price'] = $value;
									break;
								case 'increase':
									$data['price'] = $current_price + $value;
									break;
								case 'decrease':
									$data['price'] = max(0, $current_price - $value);
									break;
								case 'percent_increase':
									$data['price'] = $current_price * (1 + $value / 100);
									break;
								case 'percent_decrease':
									$data['price'] = max(0, $current_price * (1 - $value / 100));
									break;
							}
						}
						
						if (isset($modifications['quantity'])) {
							$quantity_mod = $modifications['quantity'];
							$current_quantity = (int)$product_info['quantity'];
							$value = (int)$quantity_mod['value'];
							
							switch ($quantity_mod['operation']) {
								case 'set':
									$data['quantity'] = $value;
									break;
								case 'increase':
									$data['quantity'] = $current_quantity + $value;
									break;
								case 'decrease':
									$data['quantity'] = max(0, $current_quantity - $value);
									break;
							}
						}
						
						// 更新产品
						$this->model_catalog_product->editProduct($product_id, $data);
						$success_count++;
					} else {
						$error_count++;
					}
				}
				
				if ($success_count > 0) {
					$json['success'] = "批量编辑成功！共更新 {$success_count} 个产品";
					if ($error_count > 0) {
						$json['success'] .= "，{$error_count} 个产品失败（不存在）";
					}
					$json['success'] .= "。修改已在全站同步生效。";
				} else {
					$json['error'] = '批量编辑失败：所有选中的产品都不存在';
				}
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}

