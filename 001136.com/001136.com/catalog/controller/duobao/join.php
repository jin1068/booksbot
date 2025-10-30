<?php
namespace Opencart\Catalog\Controller\Duobao;
/**
 * Class Join
 *
 * 余额参与一元夺宝
 */
class Join extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('duobao/join');

		$json = [];

		if (!$this->customer->isLogged()) {
			$json['error']['redirect'] = $this->url->link('account/login', 'language=' . $this->config->get('config_language'), true);
			$json['error']['warning'] = $this->language->get('error_login');
		}

		$duobao_id = (int)($this->request->post['duobao_id'] ?? 0);
		$quantity = (int)($this->request->post['quantity'] ?? 1);

		$this->load->model('duobao/duobao');

		$duobao_info = $this->model_duobao_duobao->getDuobao($duobao_id);

		if (!$duobao_info) {
			$json['error']['warning'] = $this->language->get('error_not_found');
		}

		if (!$json && $duobao_info['status'] !== 'active') {
			$json['error']['warning'] = $this->language->get('error_status');
		}

		if (!$json && $quantity < 1) {
			$json['error']['quantity'] = $this->language->get('error_quantity');
		}

		$price = (float)($duobao_info['price'] ?? 0.0);
		$total_cost = $price * $quantity;

		if (!$json && $total_cost <= 0) {
			$json['error']['warning'] = $this->language->get('error_price');
		}

		if (!$json) {
			$remaining = (int)$duobao_info['total_slots'] - (int)$duobao_info['joined_slots'];

			if ($quantity > $remaining) {
				$json['error']['quantity'] = sprintf($this->language->get('error_remaining'), $remaining);
			}
		}

		if (!$json) {
			$balance = $this->customer->getBalance();

			if ($total_cost > $balance) {
				$json['error']['warning'] = sprintf($this->language->get('error_balance'), $this->currency->format($balance, $this->session->data['currency']));
				$token = $this->session->data['customer_token'] ?? '';
				$query = 'language=' . $this->config->get('config_language');

				if ($token) {
					$query .= '&customer_token=' . $token;
				}

				$json['error']['redirect'] = $this->url->link('account/usdt_recharge', $query, true);
			}
		}

		if (!$json) {
            $status_map = [
                'draft'     => 'text_status_draft',
                'active'    => 'text_status_active',
                'suspended' => 'text_status_suspended',
                'completed' => 'text_status_completed',
                'cancelled' => 'text_status_cancelled'
            ];

			$result = $this->model_duobao_duobao->join($duobao_id, $this->customer->getId(), $quantity);

			if (!$result['success']) {
				$json['error']['warning'] = $this->language->get('error_join');
			} else {
				$this->load->model('account/transaction');

				$description = sprintf($this->language->get('text_transaction'), $result['issue_no']);

				$this->model_account_transaction->addTransaction($this->customer->getId(), 0, $description, -$total_cost);

				$balance = $this->customer->getBalance();

				$json['success'] = $this->language->get('text_success');
				$json['tickets'] = $result['tickets'];
				$json['joined_slots'] = $result['joined_slots'];
				$json['total_slots'] = $result['total_slots'];
				$json['progress'] = $result['total_slots'] ? min(100, max(0, (int)round($result['joined_slots'] / $result['total_slots'] * 100))) : 0;
				$json['balance'] = $this->currency->format($balance, $this->session->data['currency']);
				$json['remaining'] = max(0, $result['total_slots'] - $result['joined_slots']);

				$status_key = $status_map[$result['status_code']] ?? 'text_status_active';

				if ($result['joined_slots'] >= $result['total_slots']) {
					$status_key = 'text_status_suspended';
				}

				$json['status'] = $this->language->get($status_key);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
