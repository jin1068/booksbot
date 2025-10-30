<?php
namespace Opencart\Admin\Controller\Finance;
/**
 * Class Transaction
 */
class Transaction extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 */
	public function index(): void {
		$this->load->language('finance/transaction');

		$this->document->setTitle($this->language->get('heading_title'));

		$filter_customer = trim((string)($this->request->get['filter_customer'] ?? ''));
		$filter_customer_id = (int)($this->request->get['filter_customer_id'] ?? 0);
		$filter_description = trim((string)($this->request->get['filter_description'] ?? ''));
		$filter_amount_min = (string)($this->request->get['filter_amount_min'] ?? '');
		$filter_amount_max = (string)($this->request->get['filter_amount_max'] ?? '');
		$filter_date_start = (string)($this->request->get['filter_date_start'] ?? '');
		$filter_date_end = (string)($this->request->get['filter_date_end'] ?? '');
		$filter_type = (string)($this->request->get['filter_type'] ?? '');

		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		if ($page < 1) {
			$page = 1;
		}

		$limit = (int)$this->config->get('config_pagination_admin');

		if ($limit < 1) {
			$limit = 20;
		}

		$url = '';

		if ($filter_customer !== '') {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($filter_customer, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_customer_id) {
			$url .= '&filter_customer_id=' . $filter_customer_id;
		}

		if ($filter_description !== '') {
			$url .= '&filter_description=' . urlencode(html_entity_decode($filter_description, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_amount_min !== '') {
			$url .= '&filter_amount_min=' . $filter_amount_min;
		}

		if ($filter_amount_max !== '') {
			$url .= '&filter_amount_max=' . $filter_amount_max;
		}

		if ($filter_date_start !== '') {
			$url .= '&filter_date_start=' . $filter_date_start;
		}

		if ($filter_date_end !== '') {
			$url .= '&filter_date_end=' . $filter_date_end;
		}

		if ($filter_type !== '') {
			$url .= '&filter_type=' . $filter_type;
		}

		if ($page > 1) {
			$url .= '&page=' . $page;
		}

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
			'href' => $this->url->link('finance/transaction', 'user_token=' . $this->session->data['user_token'] . $url)
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_type_all'] = $this->language->get('text_type_all');
		$data['text_type_credit'] = $this->language->get('text_type_credit');
		$data['text_type_debit'] = $this->language->get('text_type_debit');
		$data['column_transaction_id'] = $this->language->get('column_transaction_id');
		$data['column_customer'] = $this->language->get('column_customer');
		$data['column_email'] = $this->language->get('column_email');
		$data['column_description'] = $this->language->get('column_description');
		$data['column_amount'] = $this->language->get('column_amount');
		$data['column_type'] = $this->language->get('column_type');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['entry_customer'] = $this->language->get('entry_customer');
		$data['entry_customer_id'] = $this->language->get('entry_customer_id');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_amount_min'] = $this->language->get('entry_amount_min');
		$data['entry_amount_max'] = $this->language->get('entry_amount_max');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_type'] = $this->language->get('entry_type');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_reset'] = $this->language->get('button_reset');

		$this->load->model('finance/transaction');

		$filter_data = [
			'filter_customer'    => $filter_customer,
			'filter_customer_id' => $filter_customer_id,
			'filter_description' => $filter_description,
			'filter_amount_min'  => $filter_amount_min,
			'filter_amount_max'  => $filter_amount_max,
			'filter_date_start'  => $filter_date_start,
			'filter_date_end'    => $filter_date_end,
			'filter_type'        => $filter_type,
			'start'              => ($page - 1) * $limit,
			'limit'              => $limit
		];

		$results = $this->model_finance_transaction->getTransactions($filter_data);
		$total = $this->model_finance_transaction->getTotalTransactions($filter_data);

		$data['transactions'] = [];
		$currency_code = $this->config->get('config_currency');

		foreach ($results as $result) {
			$customer_name = trim(($result['firstname'] ?? '') . ' ' . ($result['lastname'] ?? ''));

			if ($customer_name === '') {
				$customer_name = $result['email'] ?? '#' . $result['customer_id'];
			}

			$amount_raw = (float)$result['amount'];
			$amount_formatted = $this->currency->format($amount_raw, $currency_code);

			if ($amount_raw > 0) {
				$type_text = $this->language->get('text_type_credit');
			} elseif ($amount_raw < 0) {
				$type_text = $this->language->get('text_type_debit');
			} else {
				$type_text = $this->language->get('text_type_all');
			}

			$data['transactions'][] = [
				'transaction_id' => $result['customer_transaction_id'],
				'customer_id'    => $result['customer_id'],
				'customer'       => $customer_name,
				'email'          => $result['email'] ?? '',
				'description'    => $result['description'],
				'amount'         => $amount_formatted,
				'amount_raw'     => $amount_raw,
				'type_text'      => $type_text,
				'date_added'     => $result['date_added']
			];
		}

		$pagination = new \Opencart\System\Library\Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('finance/transaction', 'user_token=' . $this->session->data['user_token'] . $this->buildFilterQuery([
			'filter_customer'    => $filter_customer,
			'filter_customer_id' => $filter_customer_id,
			'filter_description' => $filter_description,
			'filter_amount_min'  => $filter_amount_min,
			'filter_amount_max'  => $filter_amount_max,
			'filter_date_start'  => $filter_date_start,
			'filter_date_end'    => $filter_date_end,
			'filter_type'        => $filter_type
		]) . '&page={page}');

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		$data['filter_customer'] = $filter_customer;
		$data['filter_customer_id'] = $filter_customer_id ?: '';
		$data['filter_description'] = $filter_description;
		$data['filter_amount_min'] = $filter_amount_min;
		$data['filter_amount_max'] = $filter_amount_max;
		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_type'] = $filter_type;

		$data['user_token'] = $this->session->data['user_token'];
		$data['action_filter'] = $this->url->link('finance/transaction', 'user_token=' . $this->session->data['user_token']);
		$data['reset'] = $this->url->link('finance/transaction', 'user_token=' . $this->session->data['user_token']);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('finance/transaction_list', $data));
	}

	/**
	 * Build query string for pagination
	 *
	 * @param array<string, mixed> $filters
	 *
	 * @return string
	 */
	protected function buildFilterQuery(array $filters): string {
		$query = '';

		foreach ($filters as $key => $value) {
			if ($value === '' || $value === null) {
				continue;
			}

			if (is_string($value)) {
				$query .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
			} else {
				$query .= '&' . $key . '=' . $value;
			}
		}

		return $query;
	}
}
