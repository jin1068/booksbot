<?php
namespace Opencart\Catalog\Controller\Duobao;
/**
 * Class Detail
 *
 * 前台一元夺宝详情页
 */
class Detail extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
        $this->load->language('duobao/detail');
        $this->load->language('duobao/join');
        $this->load->model('duobao/duobao');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$language_code = $this->config->get('config_language');
		$duobao_id = (int)($this->request->get['duobao_id'] ?? 0);

		$duobao_info = $this->model_duobao_duobao->getDuobao($duobao_id);

		if (!$duobao_info) {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
			$this->response->setOutput($this->load->controller('error/not_found'));
			return;
		}

		$issue_description = [];
		if (!empty($duobao_info['issue_id'])) {
			$issue_description = $this->model_duobao_duobao->getIssueDescription((int)$duobao_info['issue_id']);
		}

		$product_info = [];
		$thumb = '';
		$price_formatted = '';
		$special_formatted = '';
		$tax_formatted = '';
		$rating = null;
		$product_href = '';

		if (!empty($duobao_info['product_id'])) {
			$product_info = $this->model_catalog_product->getProduct((int)$duobao_info['product_id']);

			if ($product_info) {
				if ($product_info['image'] && is_file(DIR_IMAGE . $product_info['image'])) {
					$thumb = $this->model_tool_image->resize($product_info['image'], 600, 600);
				} else {
					$thumb = $this->model_tool_image->resize('no_image.png', 600, 600);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price_formatted = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				}

				if ((float)$product_info['special']) {
					$special_formatted = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				}

				if ($this->config->get('config_tax')) {
					$tax_value = (float)$product_info['special'] ? $product_info['special'] : $product_info['price'];
					$tax_formatted = $this->currency->format($tax_value, $this->session->data['currency']);
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$product_info['rating'];
				}

				$product_href = $this->url->link('product/product', 'language=' . $language_code . '&product_id=' . $product_info['product_id']);
			}
		}

		$meta_title = $duobao_info['meta_title'] ?: ($duobao_info['title'] ?? $this->language->get('heading_title'));

		$this->document->setTitle($meta_title);

		if (!empty($duobao_info['meta_description'])) {
			$this->document->setDescription($duobao_info['meta_description']);
		}

		if (!empty($duobao_info['meta_keyword'])) {
			$this->document->setKeywords($duobao_info['meta_keyword']);
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $language_code)
		];

		$data['breadcrumbs'][] = [
			'text' => $duobao_info['title'] ?? $this->language->get('heading_title'),
			'href' => $this->url->link('duobao/detail', 'language=' . $language_code . '&duobao_id=' . $duobao_id)
		];

		$date_format = $this->language->get('datetime_format') ?? 'Y-m-d H:i:s';

		$total_slots = (int)$duobao_info['total_slots'];
		$joined_slots = (int)$duobao_info['joined_slots'];
		$progress = $total_slots ? min(100, max(0, (int)round($joined_slots / $total_slots * 100))) : 0;
		$remaining = max(0, $total_slots - $joined_slots);
		$start_time_formatted = $duobao_info['start_time'] ? date($date_format, strtotime($duobao_info['start_time'])) : '';
		$end_time_formatted = $duobao_info['end_time'] ? date($date_format, strtotime($duobao_info['end_time'])) : '';

		$status_map = [
			'draft'     => $this->language->get('text_status_draft'),
			'active'    => $this->language->get('text_status_active'),
			'suspended' => $this->language->get('text_status_suspended'),
			'completed' => $this->language->get('text_status_completed'),
			'cancelled' => $this->language->get('text_status_cancelled')
		];

		$status_text = $status_map[$duobao_info['status']] ?? $duobao_info['status'];

		$data['heading_title'] = $duobao_info['title'] ?? $this->language->get('heading_title');
		$data['sub_title'] = $duobao_info['sub_title'] ?? '';
		$normalizeContent = function (string $value): string {
			$value = trim($value);
			$value = preg_replace('/\s+/', ' ', strip_tags($value));

			return is_string($value) ? $value : '';
		};
		// 解码HTML实体（支持双重编码）
		$description = $duobao_info['description'] ?? '';
		$description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
		// 如果还有HTML实体，再解码一次
		if (strpos($description, '&lt;') !== false || strpos($description, '&gt;') !== false) {
			$description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
		}
		$data['description'] = $description;
		$data['issue_no'] = $duobao_info['issue_no'];
		$data['total_slots'] = $total_slots;
		$data['joined_slots'] = $joined_slots;
		$data['remaining'] = $remaining;
		$data['progress'] = $progress;
		$data['status'] = $status_text;
		$data['start_time'] = $start_time_formatted;
		$data['end_time'] = $end_time_formatted;

		$issue_notes = $issue_description['description'] ?? '';
		if ($issue_notes !== '') {
			$issue_notes = html_entity_decode($issue_notes, ENT_QUOTES, 'UTF-8');
			if (strpos($issue_notes, '&lt;') !== false || strpos($issue_notes, '&gt;') !== false) {
				$issue_notes = html_entity_decode($issue_notes, ENT_QUOTES, 'UTF-8');
			}
			if ($normalizeContent($issue_notes) === $normalizeContent($description)) {
				$issue_notes = '';
			}
		}
		$data['issue_notes'] = $issue_notes;
		$data['per_price'] = $this->currency->format($duobao_info['price'], $this->session->data['currency']);
		$data['text_summary'] = $this->language->get('text_summary');
		$data['text_issue_no'] = $this->language->get('text_issue_no');
		$data['text_total_slots'] = $this->language->get('text_total_slots');
		$data['text_joined_slots'] = $this->language->get('text_joined_slots');
		$data['text_remaining_slots'] = $this->language->get('text_remaining_slots');
		$data['text_per_price'] = $this->language->get('text_per_price');
		$data['text_start_time'] = $this->language->get('text_start_time');
		$data['text_end_time'] = $this->language->get('text_end_time');
		$data['text_related_product'] = $this->language->get('text_related_product');
		$data['text_progress'] = $this->language->get('text_progress');
		$data['text_suggestions'] = $this->language->get('text_suggestions');
		$data['text_no_suggestions'] = $this->language->get('text_no_suggestions');
		$data['text_no_description'] = $this->language->get('text_no_description');
		$data['text_status'] = $this->language->get('text_status');
		$data['text_my_tickets'] = $this->language->get('text_my_tickets');
		$data['text_login_notice'] = $this->language->get('text_login_notice');
		$data['text_login'] = $this->language->get('text_login');
        $data['text_history'] = $this->language->get('text_history');
		$data['button_view_product'] = $this->language->get('button_view_product');
		$data['button_continue'] = $this->language->get('button_continue');
        $data['button_join'] = $this->language->get('button_join');
        $data['button_view_activity'] = $this->language->get('button_view_activity');
        $data['button_history'] = $this->language->get('button_history');
		$data['error_join'] = $this->language->get('error_join');

		$data['product_thumb'] = $thumb;
		$data['product_href'] = $product_href;
		$data['product_price'] = $price_formatted;
		$data['product_special'] = $special_formatted;
		$data['product_tax'] = $tax_formatted;
		$data['rating'] = $rating;
		$data['is_logged'] = $this->customer->isLogged();
		$data['customer_balance'] = $data['is_logged'] ? $this->currency->format($this->customer->getBalance(), $this->session->data['currency']) : '';
		$data['join_action'] = $this->url->link('duobao/join', 'language=' . $language_code, true);
		$data['duobao_id'] = $duobao_id;
		$data['login_href'] = $this->url->link('account/login', 'language=' . $language_code, true);
        $data['history_href'] = $this->url->link('duobao/history', 'language=' . $language_code);
		$data['text_balance'] = $this->language->get('text_balance');
		$data['entry_quantity'] = $this->language->get('entry_quantity');

		if ($data['is_logged'] && !empty($duobao_info['issue_id'])) {
			$data['my_tickets'] = $this->model_duobao_duobao->getTicketsByCustomer((int)$duobao_info['issue_id'], $this->customer->getId());
		} else {
			$data['my_tickets'] = [];
		}

		// 推荐其他活动
		$data['suggestions'] = [];
		$suggestions = $this->model_duobao_duobao->getActiveDuobaos(4);
		foreach ($suggestions as $suggestion) {
			if ((int)$suggestion['duobao_id'] === $duobao_id) {
				continue;
			}

			$s_thumb = $thumb;
			if (!empty($suggestion['product_id'])) {
				$s_product = $this->model_catalog_product->getProduct((int)$suggestion['product_id']);
				if ($s_product && $s_product['image'] && is_file(DIR_IMAGE . $s_product['image'])) {
					$s_thumb = $this->model_tool_image->resize($s_product['image'], 240, 240);
				} else {
					$s_thumb = $this->model_tool_image->resize('no_image.png', 240, 240);
				}
			}

			$s_total = (int)$suggestion['total_slots'];
			$s_joined = (int)$suggestion['joined_slots'];
			$s_progress = $s_total ? min(100, max(0, (int)round($s_joined / $s_total * 100))) : 0;

			$data['suggestions'][] = [
				'duobao_id' => $suggestion['duobao_id'],
				'title'     => $suggestion['title'],
				'thumb'     => $s_thumb,
				'progress'  => $s_progress,
				'href'      => $this->url->link('duobao/detail', 'language=' . $language_code . '&duobao_id=' . (int)$suggestion['duobao_id'])
			];
		}

		$data['continue'] = $this->url->link('common/home', 'language=' . $language_code);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('duobao/detail', $data));
	}
}
