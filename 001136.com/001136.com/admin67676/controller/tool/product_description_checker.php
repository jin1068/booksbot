<?php
namespace Opencart\Admin\Controller\Tool;
/**
 * 产品描述检查和补充工具
 */
class ProductDescriptionChecker extends \Opencart\System\Engine\Controller {
	
	public function index(): void {
		$this->load->language('tool/product_description_checker');
		
		$this->document->setTitle('产品描述检查工具');
		
		$data['breadcrumbs'] = [];
		
		$data['breadcrumbs'][] = [
			'text' => '首页',
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['breadcrumbs'][] = [
			'text' => '产品描述检查',
			'href' => $this->url->link('tool/product_description_checker', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['user_token'] = $this->session->data['user_token'];
		$data['check_url'] = $this->url->link('tool/product_description_checker.check', 'user_token=' . $this->session->data['user_token']);
		$data['update_url'] = $this->url->link('tool/product_description_checker.update', 'user_token=' . $this->session->data['user_token']);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('tool/product_description_checker', $data));
	}
	
	/**
	 * 检查所有产品描述
	 */
	public function check(): void {
		$json = [];
		
		// 获取所有产品
		$query = $this->db->query("
			SELECT 
				p.product_id,
				p.model,
				p.price,
				p.status,
				pd_zh.name as name_zh,
				pd_zh.description as description_zh,
				pd_en.name as name_en,
				pd_en.description as description_en,
				(SELECT COUNT(*) FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id) as attribute_count
			FROM " . DB_PREFIX . "product p
			LEFT JOIN " . DB_PREFIX . "product_description pd_zh ON (p.product_id = pd_zh.product_id AND pd_zh.language_id = 2)
			LEFT JOIN " . DB_PREFIX . "product_description pd_en ON (p.product_id = pd_en.product_id AND pd_en.language_id = 1)
			WHERE p.status = 1
			ORDER BY p.product_id
			LIMIT 100
		");
		
		$products_needing_improvement = [];
		
		foreach ($query->rows as $row) {
			$issues = [];
			
			// 检查中文描述
			$desc_zh_length = mb_strlen(strip_tags($row['description_zh'] ?? ''));
			if ($desc_zh_length < 50) {
				$issues[] = "中文描述过短 ({$desc_zh_length}字)";
			}
			
			// 检查英文描述
			$desc_en_length = strlen(strip_tags($row['description_en'] ?? ''));
			if ($desc_en_length < 50) {
				$issues[] = "英文描述过短 ({$desc_en_length}字)";
			}
			
			// 检查是否有属性
			if ($row['attribute_count'] == 0) {
				$issues[] = "缺少产品参数";
			}
			
			if (!empty($issues)) {
				// 获取产品属性
				$attributes_query = $this->db->query("
					SELECT ad.name, pa.text
					FROM " . DB_PREFIX . "product_attribute pa
					LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = 2)
					WHERE pa.product_id = '" . (int)$row['product_id'] . "' AND pa.language_id = 2
				");
				
				$attributes = [];
				foreach ($attributes_query->rows as $attr) {
					$attributes[] = $attr['name'] . ': ' . $attr['text'];
				}
				
				$products_needing_improvement[] = [
					'product_id' => $row['product_id'],
					'model' => $row['model'],
					'name_zh' => $row['name_zh'],
					'name_en' => $row['name_en'],
					'attribute_count' => $row['attribute_count'],
					'attributes' => $attributes,
					'description_zh_length' => $desc_zh_length,
					'description_en_length' => $desc_en_length,
					'current_description_zh' => substr(strip_tags($row['description_zh']), 0, 200),
					'current_description_en' => substr(strip_tags($row['description_en']), 0, 200),
					'issues' => $issues
				];
			}
		}
		
		$json['total'] = count($query->rows);
		$json['needs_improvement'] = count($products_needing_improvement);
		$json['products'] = $products_needing_improvement;
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 更新单个产品描述
	 */
	public function update(): void {
		$json = [];
		
		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
			
			// 获取产品信息
			$product_query = $this->db->query("
				SELECT 
					p.*,
					pd_zh.name as name_zh,
					pd_zh.description as description_zh,
					pd_en.name as name_en,
					pd_en.description as description_en
				FROM " . DB_PREFIX . "product p
				LEFT JOIN " . DB_PREFIX . "product_description pd_zh ON (p.product_id = pd_zh.product_id AND pd_zh.language_id = 2)
				LEFT JOIN " . DB_PREFIX . "product_description pd_en ON (p.product_id = pd_en.product_id AND pd_en.language_id = 1)
				WHERE p.product_id = '" . $product_id . "'
			");
			
			if ($product_query->num_rows) {
				$product = $product_query->row;
				
				// 获取产品属性
				$attributes_query = $this->db->query("
					SELECT ad.name, pa.text, ad_en.name as name_en, pa_en.text as text_en
					FROM " . DB_PREFIX . "product_attribute pa
					LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (pa.attribute_id = ad.attribute_id AND ad.language_id = 2)
					LEFT JOIN " . DB_PREFIX . "product_attribute pa_en ON (pa.product_id = pa_en.product_id AND pa.attribute_id = pa_en.attribute_id AND pa_en.language_id = 1)
					LEFT JOIN " . DB_PREFIX . "attribute_description ad_en ON (pa.attribute_id = ad_en.attribute_id AND ad_en.language_id = 1)
					WHERE pa.product_id = '" . $product_id . "' AND pa.language_id = 2
				");
				
				// 生成中文描述
				$description_zh = $this->generateDescription($product, $attributes_query->rows, 'zh');
				
				// 生成英文描述
				$description_en = $this->generateDescription($product, $attributes_query->rows, 'en');
				
				// 更新中文描述
				if (mb_strlen(strip_tags($product['description_zh'])) < 50) {
					$this->db->query("
						UPDATE " . DB_PREFIX . "product_description 
						SET description = '" . $this->db->escape($description_zh) . "'
						WHERE product_id = '" . $product_id . "' AND language_id = 2
					");
				}
				
				// 更新英文描述
				if (strlen(strip_tags($product['description_en'])) < 50) {
					$this->db->query("
						UPDATE " . DB_PREFIX . "product_description 
						SET description = '" . $this->db->escape($description_en) . "'
						WHERE product_id = '" . $product_id . "' AND language_id = 1
					");
				}
				
				$json['success'] = '产品描述已更新';
				$json['description_zh'] = $description_zh;
				$json['description_en'] = $description_en;
			} else {
				$json['error'] = '产品不存在';
			}
		} else {
			$json['error'] = '缺少产品ID';
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 批量更新所有需要改进的产品
	 */
	public function updateAll(): void {
		$json = [];
		$updated_count = 0;
		
		// 获取所有需要更新的产品
		$query = $this->db->query("
			SELECT 
				p.product_id,
				p.model,
				pd_zh.name as name_zh,
				pd_zh.description as description_zh,
				pd_en.description as description_en
			FROM " . DB_PREFIX . "product p
			LEFT JOIN " . DB_PREFIX . "product_description pd_zh ON (p.product_id = pd_zh.product_id AND pd_zh.language_id = 2)
			LEFT JOIN " . DB_PREFIX . "product_description pd_en ON (p.product_id = pd_en.product_id AND pd_en.language_id = 1)
			WHERE p.status = 1 
			AND (LENGTH(pd_zh.description) < 50 OR LENGTH(pd_en.description) < 50)
			LIMIT 50
		");
		
		foreach ($query->rows as $row) {
			// 调用update方法更新每个产品
			$this->request->post['product_id'] = $row['product_id'];
			$this->update();
			$updated_count++;
		}
		
		$json['success'] = "成功更新 {$updated_count} 个产品";
		$json['updated_count'] = $updated_count;
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	/**
	 * 生成产品描述
	 */
	private function generateDescription($product, $attributes, $lang = 'zh'): string {
		$html = '';
		
		if ($lang == 'zh') {
			$html .= '<div class="product-description">';
			$html .= '<h3>产品介绍</h3>';
			$html .= '<p>' . htmlspecialchars($product['name_zh']) . ' 是一款高品质的电子产品，采用先进的技术和优质的材料制造。</p>';
			
			if (!empty($attributes)) {
				$html .= '<h3>产品参数</h3>';
				$html .= '<table class="table table-bordered">';
				$html .= '<tbody>';
				
				foreach ($attributes as $attr) {
					$html .= '<tr>';
					$html .= '<td><strong>' . htmlspecialchars($attr['name']) . '</strong></td>';
					$html .= '<td>' . htmlspecialchars($attr['text']) . '</td>';
					$html .= '</tr>';
				}
				
				$html .= '</tbody>';
				$html .= '</table>';
			}
			
			$html .= '<h3>产品特点</h3>';
			$html .= '<ul>';
			$html .= '<li>高品质材料，经久耐用</li>';
			$html .= '<li>先进技术，性能卓越</li>';
			$html .= '<li>人性化设计，使用便捷</li>';
			$html .= '<li>完善的售后服务保障</li>';
			$html .= '</ul>';
			$html .= '</div>';
		} else {
			$html .= '<div class="product-description">';
			$html .= '<h3>Product Introduction</h3>';
			$html .= '<p>' . htmlspecialchars($product['name_en']) . ' is a high-quality electronic product manufactured with advanced technology and premium materials.</p>';
			
			if (!empty($attributes)) {
				$html .= '<h3>Specifications</h3>';
				$html .= '<table class="table table-bordered">';
				$html .= '<tbody>';
				
				foreach ($attributes as $attr) {
					$name_en = $attr['name_en'] ?? $attr['name'];
					$text_en = $attr['text_en'] ?? $attr['text'];
					$html .= '<tr>';
					$html .= '<td><strong>' . htmlspecialchars($name_en) . '</strong></td>';
					$html .= '<td>' . htmlspecialchars($text_en) . '</td>';
					$html .= '</tr>';
				}
				
				$html .= '</tbody>';
				$html .= '</table>';
			}
			
			$html .= '<h3>Key Features</h3>';
			$html .= '<ul>';
			$html .= '<li>High-quality materials for durability</li>';
			$html .= '<li>Advanced technology for excellent performance</li>';
			$html .= '<li>User-friendly design for convenience</li>';
			$html .= '<li>Comprehensive after-sales service</li>';
			$html .= '</ul>';
			$html .= '</div>';
		}
		
		return $html;
	}
}

