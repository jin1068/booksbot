<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * 产品SEO批量管理工具
 * 
 * 功能：批量导出/导入产品的描述、Meta标签等信息
 * 确保中英文版本分离
 */
class ProductSeoBatch extends \Opencart\System\Engine\Controller {
	
	/**
	 * 主页面
	 */
	public function index(): void {
		$this->load->language('catalog/product');
		
		$this->document->setTitle('产品SEO批量管理');
		
		$data['breadcrumbs'] = [];
		
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['breadcrumbs'][] = [
			'text' => '产品SEO批量管理',
			'href' => $this->url->link('catalog/product_seo_batch', 'user_token=' . $this->session->data['user_token'])
		];
		
		$data['export_url'] = $this->url->link('catalog/product_seo_batch.export', 'user_token=' . $this->session->data['user_token']);
		$data['import_url'] = $this->url->link('catalog/product_seo_batch.import', 'user_token=' . $this->session->data['user_token']);
		$data['template_url'] = $this->url->link('catalog/product_seo_batch.downloadTemplate', 'user_token=' . $this->session->data['user_token']);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('catalog/product_seo_batch', $data));
	}
	
	/**
	 * 导出当前所有产品的SEO信息
	 */
	public function export(): void {
		$this->load->model('catalog/product');
		$this->load->model('localisation/language');
		
		// 获取所有语言
		$languages = $this->model_localisation_language->getLanguages();
		
		// 获取所有启用的产品
		$filter_data = [
			'filter_status' => 1,
			'start' => 0,
			'limit' => 10000
		];
		
		$products = $this->model_catalog_product->getProducts($filter_data);
		
		// 创建CSV文件
		$filename = 'product_seo_export_' . date('Y-m-d_H-i-s') . '.csv';
		
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		$output = fopen('php://output', 'w');
		
		// 输出BOM以支持Excel正确显示UTF-8
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 构建表头
		$headers = [
			'产品ID',
			'产品型号',
			'状态'
		];
		
		foreach ($languages as $language) {
			$lang_code = strtoupper($language['code']);
			$headers[] = "[{$lang_code}]产品名称";
			$headers[] = "[{$lang_code}]产品描述";
			$headers[] = "[{$lang_code}]Meta标题";
			$headers[] = "[{$lang_code}]Meta描述";
			$headers[] = "[{$lang_code}]Meta关键字";
			$headers[] = "[{$lang_code}]标签";
		}
		
		fputcsv($output, $headers);
		
		// 输出产品数据
		foreach ($products as $product) {
			$product_info = $this->model_catalog_product->getProduct($product['product_id']);
			$descriptions = $this->model_catalog_product->getDescriptions($product['product_id']);
			
			$row = [
				$product['product_id'],
				$product['model'],
				$product['status'] ? '启用' : '禁用'
			];
			
			foreach ($languages as $language) {
				$desc = $descriptions[$language['language_id']] ?? [];
				
				$row[] = $desc['name'] ?? '';
				$row[] = $desc['description'] ?? '';
				$row[] = $desc['meta_title'] ?? '';
				$row[] = $desc['meta_description'] ?? '';
				$row[] = $desc['meta_keyword'] ?? '';
				$row[] = $desc['tag'] ?? '';
			}
			
			fputcsv($output, $row);
		}
		
		fclose($output);
		exit;
	}
	
	/**
	 * 下载填写模板
	 */
	public function downloadTemplate(): void {
		$this->load->model('localisation/language');
		
		// 获取所有语言
		$languages = $this->model_localisation_language->getLanguages();
		
		// 创建CSV文件
		$filename = 'product_seo_template_' . date('Y-m-d') . '.csv';
		
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		$output = fopen('php://output', 'w');
		
		// 输出BOM
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 构建表头
		$headers = [
			'产品ID',
			'产品型号',
			'状态'
		];
		
		foreach ($languages as $language) {
			$lang_code = strtoupper($language['code']);
			$lang_name = $language['name'];
			$headers[] = "[{$lang_code}]产品名称";
			$headers[] = "[{$lang_code}]产品描述(HTML)";
			$headers[] = "[{$lang_code}]Meta标题";
			$headers[] = "[{$lang_code}]Meta描述";
			$headers[] = "[{$lang_code}]Meta关键字";
			$headers[] = "[{$lang_code}]标签(逗号分隔)";
		}
		
		fputcsv($output, $headers);
		
		// 添加说明行
		$notes = ['说明', '留空=不修改', '启用/禁用'];
		foreach ($languages as $language) {
			$lang_name = $language['name'];
			$notes[] = "{$lang_name}版本产品名称";
			$notes[] = "{$lang_name}版本详细描述（支持HTML）";
			$notes[] = "{$lang_name}版本SEO标题（建议50-60字符）";
			$notes[] = "{$lang_name}版本SEO描述（建议150-160字符）";
			$notes[] = "{$lang_name}版本SEO关键字（逗号分隔）";
			$notes[] = "{$lang_name}版本产品标签";
		}
		fputcsv($output, $notes);
		
		// 添加示例数据
		$example = ['123', 'SAMPLE-001', '启用'];
		foreach ($languages as $language) {
			$is_chinese = (strpos($language['code'], 'zh') !== false);
			if ($is_chinese) {
				$example[] = '示例产品名称';
				$example[] = '<p>这是产品的详细描述</p>';
				$example[] = '示例产品 - 高质量产品';
				$example[] = '这是一个示例产品，展示如何填写产品信息。';
				$example[] = '示例,产品,关键字';
				$example[] = '电子产品,智能设备';
			} else {
				$example[] = 'Sample Product Name';
				$example[] = '<p>This is the detailed product description</p>';
				$example[] = 'Sample Product - High Quality';
				$example[] = 'This is a sample product showing how to fill in product information.';
				$example[] = 'sample,product,keywords';
				$example[] = 'electronics,smart device';
			}
		}
		fputcsv($output, $example);
		
		fclose($output);
		exit;
	}
	
	/**
	 * 导入产品SEO信息
	 */
	public function import(): void {
		$this->load->language('catalog/product');
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
		
		// 检查文件
		if (!isset($this->request->files['file'])) {
			$json['error'] = '请选择要上传的CSV文件';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		$file = $this->request->files['file'];
		
		if ($file['error'] != UPLOAD_ERR_OK) {
			$json['error'] = '文件上传失败';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// 读取CSV文件
		$handle = fopen($file['tmp_name'], 'r');
		
		if ($handle === false) {
			$json['error'] = '无法读取CSV文件';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// 获取所有语言
		$languages = $this->model_localisation_language->getLanguages();
		$lang_map = [];
		foreach ($languages as $lang) {
			$lang_map[$lang['language_id']] = $lang;
		}
		
		// 读取表头
		$headers = fgetcsv($handle);
		
		// 跳过说明行
		fgetcsv($handle);
		
		$updated_count = 0;
		$error_count = 0;
		$errors = [];
		
		$row_num = 3; // 从第3行开始（1=表头，2=说明）
		
		// 处理数据行
		while (($data = fgetcsv($handle)) !== false) {
			$row_num++;
			
			if (count($data) < 3) {
				continue; // 跳过空行
			}
			
			$product_id = (int)$data[0];
			
			if ($product_id <= 0) {
				continue; // 跳过无效ID
			}
			
			// 检查产品是否存在
			$product = $this->model_catalog_product->getProduct($product_id);
			if (!$product) {
				$errors[] = "行 {$row_num}: 产品ID {$product_id} 不存在";
				$error_count++;
				continue;
			}
			
			// 准备更新数据
			$product_data = [
				'model' => $product['model'],
				'product_description' => []
			];
			
			// 解析各语言的数据
			$col_index = 3; // 从第4列开始（跳过ID、型号、状态）
			
			foreach ($languages as $language) {
				$lang_id = $language['language_id'];
				
				// 获取当前产品的描述
				$descriptions = $this->model_catalog_product->getDescriptions($product_id);
				$current_desc = $descriptions[$lang_id] ?? [];
				
				// 读取新数据（如果为空则保持原值）
				$name = isset($data[$col_index]) && trim($data[$col_index]) !== '' ? trim($data[$col_index]) : ($current_desc['name'] ?? '');
				$description = isset($data[$col_index + 1]) && trim($data[$col_index + 1]) !== '' ? trim($data[$col_index + 1]) : ($current_desc['description'] ?? '');
				$meta_title = isset($data[$col_index + 2]) && trim($data[$col_index + 2]) !== '' ? trim($data[$col_index + 2]) : ($current_desc['meta_title'] ?? $name);
				$meta_description = isset($data[$col_index + 3]) && trim($data[$col_index + 3]) !== '' ? trim($data[$col_index + 3]) : ($current_desc['meta_description'] ?? '');
				$meta_keyword = isset($data[$col_index + 4]) && trim($data[$col_index + 4]) !== '' ? trim($data[$col_index + 4]) : ($current_desc['meta_keyword'] ?? '');
				$tag = isset($data[$col_index + 5]) && trim($data[$col_index + 5]) !== '' ? trim($data[$col_index + 5]) : ($current_desc['tag'] ?? '');
				
				$product_data['product_description'][$lang_id] = [
					'name' => $name,
					'description' => $description,
					'meta_title' => $meta_title,
					'meta_description' => $meta_description,
					'meta_keyword' => $meta_keyword,
					'tag' => $tag
				];
				
				$col_index += 6; // 每个语言6个字段
			}
			
			// 更新产品描述
			try {
				$this->model_catalog_product->deleteDescriptions($product_id);
				foreach ($product_data['product_description'] as $language_id => $value) {
					$this->model_catalog_product->addDescription($product_id, $language_id, $value);
				}
				$updated_count++;
			} catch (\Exception $e) {
				$errors[] = "行 {$row_num}: 更新失败 - " . $e->getMessage();
				$error_count++;
			}
		}
		
		fclose($handle);
		
		// 返回结果
		$json['success'] = "成功更新 {$updated_count} 个产品";
		if ($error_count > 0) {
			$json['warning'] = "有 {$error_count} 个产品更新失败";
			$json['errors'] = $errors;
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}

