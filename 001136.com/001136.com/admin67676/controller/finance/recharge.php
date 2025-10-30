<?php
namespace Opencart\Admin\Controller\Finance;
/**
 * Class Recharge
 */
class Recharge extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 */
	public function index(): void {
		$this->load->language('finance/recharge');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_finance'),
			'href' => $this->url->link('finance/recharge', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('finance/recharge', 'user_token=' . $this->session->data['user_token'])
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['column_customer'] = $this->language->get('column_customer');
		$data['column_network'] = $this->language->get('column_network');
		$data['column_amount'] = $this->language->get('column_amount');
		$data['column_txhash'] = $this->language->get('column_txhash');
		$data['column_receipt'] = $this->language->get('column_receipt');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_action'] = $this->language->get('column_action');
		$data['button_approve'] = $this->language->get('button_approve');
		$data['button_reject'] = $this->language->get('button_reject');
		$data['text_processed'] = $this->language->get('text_processed');
		$data['text_view_receipt'] = $this->language->get('text_view_receipt');
		$data['text_no_receipt'] = $this->language->get('text_no_receipt');

		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		if ($page < 1) {
			$page = 1;
		}

		$limit = (int)$this->config->get('config_pagination_admin');

		if ($limit < 1) {
			$limit = 20;
		}
		$start = ($page - 1) * $limit;

		$this->load->model('finance/recharge');

		$recharge_data = [
			'order' => 'DESC',
			'start' => $start,
			'limit' => $limit
		];

		$results = $this->model_finance_recharge->getRecharges($recharge_data);
		$total = $this->model_finance_recharge->getTotalRecharges();

		$status_map = [
			0 => $this->language->get('text_status_pending'),
			1 => $this->language->get('text_status_completed'),
			2 => $this->language->get('text_status_rejected')
		];

		$data['recharges'] = [];

		$receipt_base_url = (defined('HTTP_CATALOG') ? HTTP_CATALOG : $this->config->get('config_url')) . 'image/';
		$data['receipt_base_url'] = $receipt_base_url;

		foreach ($results as $result) {
			$customer_name = trim(($result['firstname'] ?? '') . ' ' . ($result['lastname'] ?? ''));

			if ($customer_name === '') {
				$customer_name = $result['email'] ?? '';
			}

			if ($customer_name === '') {
				$customer_name = '#'.$result['customer_id'];
			}

			$receipt_path = (string)($result['note'] ?? '');
			$normalized_path = str_replace('\\', '/', $receipt_path);
			$is_receipt = $normalized_path && str_contains($normalized_path, 'usdt_receipts/');
			$receipt_url = $is_receipt ? $receipt_base_url . ltrim($normalized_path, '/') : '';

			$data['recharges'][] = [
				'recharge_id' => $result['recharge_id'],
				'customer'    => $customer_name,
				'email'       => $result['email'] ?? '',
				'network'     => $result['network'],
				'amount'      => $result['amount'],
				'txhash'      => $result['txhash'],
				'receipt_url' => $receipt_url,
				'receipt_raw' => $is_receipt ? '' : $receipt_path,
				'status'      => $status_map[(int)$result['status']] ?? $result['status'],
				'transaction_id' => (int)$result['transaction_id'],
				'status_code' => (int)$result['status'],
				'date_added'  => $result['date_added']
			];
		}

		$pagination = new \Opencart\System\Library\Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('finance/recharge', 'user_token=' . $this->session->data['user_token'] . '&page={page}');

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		$data['user_token'] = $this->session->data['user_token'];
		$data['approve'] = $this->url->link('finance/recharge.approve', 'user_token=' . $this->session->data['user_token']);
		$data['reject'] = $this->url->link('finance/recharge.reject', 'user_token=' . $this->session->data['user_token']);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('finance/recharge_list', $data));
	}

	/**
	 * Approve recharge
	 */
	public function approve(): void {
		$this->load->language('finance/recharge');

		$json = [];

		if (!$this->user->hasPermission('modify', 'finance/recharge')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$recharge_id = (int)($this->request->get['recharge_id'] ?? 0);

		if (!$recharge_id && !isset($json['error'])) {
			$json['error'] = $this->language->get('error_recharge_not_found');
		}

		if (!$json) {
			$this->load->model('finance/recharge');

			$recharge = $this->model_finance_recharge->getRecharge($recharge_id);

			if (!$recharge) {
				$json['error'] = $this->language->get('error_recharge_not_found');
			} elseif ((int)$recharge['status'] !== 0) {
				$json['error'] = $this->language->get('error_recharge_processed');
			} elseif ((float)$recharge['amount'] <= 0) {
				$json['error'] = $this->language->get('error_amount_invalid');
			} else {
				$description = sprintf($this->language->get('text_transaction_description'), $recharge_id);
				$transaction_id = $this->model_finance_recharge->addTransaction((int)$recharge['customer_id'], $description, (float)$recharge['amount']);

				$this->model_finance_recharge->updateStatus($recharge_id, 1, $transaction_id);

				$json['success'] = $this->language->get('text_success_approve');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Reject recharge
	 */
	public function reject(): void {
		$this->load->language('finance/recharge');

		$json = [];

		if (!$this->user->hasPermission('modify', 'finance/recharge')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$recharge_id = (int)($this->request->get['recharge_id'] ?? 0);

		if (!$recharge_id && !isset($json['error'])) {
			$json['error'] = $this->language->get('error_recharge_not_found');
		}

		if (!$json) {
			$this->load->model('finance/recharge');

			$recharge = $this->model_finance_recharge->getRecharge($recharge_id);

			if (!$recharge) {
				$json['error'] = $this->language->get('error_recharge_not_found');
			} elseif ((int)$recharge['status'] !== 0) {
				$json['error'] = $this->language->get('error_recharge_processed');
			} else {
				$this->model_finance_recharge->updateStatus($recharge_id, 2);

				$json['success'] = $this->language->get('text_success_reject');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
