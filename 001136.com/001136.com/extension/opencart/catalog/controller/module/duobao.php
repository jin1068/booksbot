<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Duobao
 *
 * 首页一元夺宝模块
 */
class Duobao extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $setting
	 *
	 * @return string
	 */
	public function index(array $setting = []): string {
		$this->load->language('extension/opencart/module/duobao');

		$limit = (int)($setting['limit'] ?? 4);
		if ($limit < 1) {
			$limit = 4;
		}

		$width = (int)($setting['width'] ?? 300);
		$height = (int)($setting['height'] ?? 300);

		$this->load->model('duobao/duobao');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$results = $this->model_duobao_duobao->getActiveDuobaos($limit);

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_issue_no'] = $this->language->get('text_issue_no');
		$data['text_joined'] = $this->language->get('text_joined');
		$data['text_remaining'] = $this->language->get('text_remaining');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['button_view'] = $this->language->get('button_view');
		$data['button_view_product'] = $this->language->get('button_view_product');

		$data['duobaos'] = [];

		$language_code = $this->config->get('config_language');

		foreach ($results as $result) {
			$product_info = $this->model_catalog_product->getProduct((int)$result['product_id']);

			if ($product_info && $product_info['image'] && is_file(DIR_IMAGE . $product_info['image'])) {
				$thumb = $this->model_tool_image->resize($product_info['image'], $width, $height);
			} else {
				$thumb = $this->model_tool_image->resize('no_image.png', $width, $height);
			}

			$total_slots = (int)$result['total_slots'];
			$joined_slots = (int)$result['joined_slots'];

			$progress = $total_slots ? min(100, max(0, (int)round($joined_slots / $total_slots * 100))) : 0;
			$remaining = max(0, $total_slots - $joined_slots);

			if ($product_info) {
				$href = $this->url->link('product/product', 'language=' . $language_code . '&product_id=' . $product_info['product_id']);
				$price = $this->currency->format($product_info['price'], $this->session->data['currency']);
			} else {
				$href = '#';
				$price = '';
			}

			$query_string = 'language=' . $language_code . '&duobao_id=' . (int)$result['duobao_id'];

				$data['duobaos'][] = [
					'duobao_id'    => $result['duobao_id'],
					'issue_id'     => $result['issue_id'],
					'title'        => $result['title'] ?: ($product_info['name'] ?? ''),
					'sub_title'    => $result['sub_title'] ?? '',
					'issue_no'     => $result['issue_no'],
					'total_slots'  => $total_slots,
					'joined_slots' => $joined_slots,
					'remaining'    => $remaining,
					'progress'     => $progress,
					'status'       => $result['status'],
					'thumb'        => $thumb,
					'price'        => $price,
					'detail_href'  => $this->url->link('duobao/detail', $query_string),
					'product_href' => $href
				];
		}

		return $this->load->view('extension/opencart/module/duobao', $data);
	}
}
