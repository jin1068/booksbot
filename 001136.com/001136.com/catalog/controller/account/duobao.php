<?php
namespace Opencart\Catalog\Controller\Account;
/**
 * Class Duobao
 *
 * 会员中心夺宝页
 */
class Duobao extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('account/duobao');
		$this->load->language('duobao/detail');

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/duobao', 'language=' . $this->config->get('config_language'));

			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language'), true));
		}

		$this->document->setTitle($this->language->get('heading_title'));
		$data['heading_title'] = $this->language->get('heading_title');

		$page = (int)($this->request->get['page'] ?? 1);
		if ($page < 1) {
			$page = 1;
		}

		$limit = 10;
		$customer_id = $this->customer->getId();
		$customer_token = $this->session->data['customer_token'];
		$language_code = $this->config->get('config_language');

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $language_code)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . $language_code . '&customer_token=' . $customer_token)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/duobao', 'language=' . $language_code . '&customer_token=' . $customer_token)
		];

		$this->load->model('duobao/duobao');

		$status_map = [
			'draft'     => $this->language->get('text_status_draft'),
			'active'    => $this->language->get('text_status_active'),
			'suspended' => $this->language->get('text_status_suspended'),
			'completed' => $this->language->get('text_status_completed'),
			'cancelled' => $this->language->get('text_status_cancelled')
		];

		$data['text_joined_list'] = $this->language->get('text_joined_list');
		$data['text_wins_list'] = $this->language->get('text_wins_list');
		$data['text_balance'] = $this->language->get('text_balance');
		$data['text_no_issues'] = $this->language->get('text_no_issues');
		$data['text_no_wins'] = $this->language->get('text_no_wins');
		$data['button_view_detail'] = $this->language->get('button_view_detail');
		$data['button_back'] = $this->language->get('button_back');
		$data['column_activity'] = $this->language->get('column_activity');
		$data['column_issue'] = $this->language->get('column_issue');
		$data['column_my_slots'] = $this->language->get('column_my_slots');
		$data['column_tickets'] = $this->language->get('column_tickets');
		$data['column_progress'] = $this->language->get('column_progress');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_start_time'] = $this->language->get('column_start_time');
		$data['column_end_time'] = $this->language->get('column_end_time');
		$data['column_ticket'] = $this->language->get('column_ticket');
		$data['column_draw_time'] = $this->language->get('column_draw_time');

		$date_format = $this->language->get('datetime_format') ?: 'Y-m-d H:i:s';

		$issues = $this->model_duobao_duobao->getCustomerIssues($customer_id, ($page - 1) * $limit, $limit);
		$data['issues'] = [];

		foreach ($issues as $issue) {
			$total_slots = (int)$issue['total_slots'];
			$joined_slots = (int)$issue['joined_slots'];
			$progress = $total_slots ? min(100, max(0, (int)round($joined_slots / $total_slots * 100))) : 0;

			$start_time = $issue['start_time'] ? date($date_format, strtotime($issue['start_time'])) : '';
			$end_time = $issue['end_time'] ? date($date_format, strtotime($issue['end_time'])) : '';

			$ticket_range = '';

			if ($issue['first_ticket'] && $issue['last_ticket']) {
				if ($issue['first_ticket'] === $issue['last_ticket']) {
					$ticket_range = $issue['first_ticket'];
				} else {
					$ticket_range = sprintf($this->language->get('text_ticket_range'), $issue['first_ticket'], $issue['last_ticket']);
				}
			}

			$data['issues'][] = [
				'duobao_id'     => (int)$issue['duobao_id'],
				'issue_id'      => (int)$issue['issue_id'],
				'title'         => $issue['title'],
				'sub_title'     => $issue['sub_title'],
				'issue_no'      => (int)$issue['issue_no'],
				'my_slots'      => (int)$issue['my_slots'],
				'ticket_range'  => $ticket_range,
				'status_text'   => $status_map[$issue['status']] ?? $issue['status'],
				'status'        => $issue['status'],
				'total_slots'   => $total_slots,
				'joined_slots'  => $joined_slots,
				'progress'      => $progress,
				'start_time'    => $start_time,
				'end_time'      => $end_time,
				'detail_href'   => $this->url->link('duobao/detail', 'language=' . $language_code . '&duobao_id=' . (int)$issue['duobao_id'])
			];
		}

		$issue_total = $this->model_duobao_duobao->getTotalCustomerIssues($customer_id);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $issue_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('account/duobao', 'language=' . $language_code . '&customer_token=' . $customer_token . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($issue_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($issue_total - $limit)) ? $issue_total : ((($page - 1) * $limit) + $limit), $issue_total, ceil($issue_total / $limit));

		$wins = $this->model_duobao_duobao->getCustomerWins($customer_id);
		$data['wins'] = [];

		foreach ($wins as $win) {
			$win_time = $win['date_modified'] ? date($date_format, strtotime($win['date_modified'])) : '';

			$data['wins'][] = [
				'duobao_id'   => (int)$win['duobao_id'],
				'issue_id'    => (int)$win['issue_id'],
				'title'       => $win['title'],
				'sub_title'   => $win['sub_title'],
				'issue_no'    => (int)$win['issue_no'],
				'winner_ticket' => $win['winner_ticket'],
				'status_text' => $status_map['completed'],
				'date_modified' => $win_time,
				'detail_href' => $this->url->link('duobao/detail', 'language=' . $language_code . '&duobao_id=' . (int)$win['duobao_id'])
			];
		}

		$data['wins_total'] = $this->model_duobao_duobao->getTotalCustomerWins($customer_id);
		$data['current_balance'] = $this->currency->format($this->customer->getBalance(), $this->session->data['currency'] ?? $this->config->get('config_currency'));

		$data['continue'] = $this->url->link('account/account', 'language=' . $language_code . '&customer_token=' . $customer_token);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/duobao', $data));
	}
}
