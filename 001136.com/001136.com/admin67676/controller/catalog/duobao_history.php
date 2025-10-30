<?php
namespace Opencart\Admin\Controller\Catalog;
/**
 * Class DuobaoHistory
 *
 * 后台夺宝开奖历史
 */
class DuobaoHistory extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('catalog/duobao_history');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_catalog'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/duobao_history', 'user_token=' . $this->session->data['user_token'])
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_filter'] = $this->language->get('text_filter');
		$data['text_list'] = $this->language->get('text_list');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_issue_no'] = $this->language->get('entry_issue_no');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['text_status_completed'] = $this->language->get('text_status_completed');
		$data['text_status_cancelled'] = $this->language->get('text_status_cancelled');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_reset'] = $this->language->get('button_reset');

		$data['filter_title'] = $this->request->get['filter_title'] ?? '';
		$data['filter_issue_no'] = $this->request->get['filter_issue_no'] ?? '';
		$data['filter_status'] = $this->request->get['filter_status'] ?? '';

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/duobao_history', $data));
	}

	public function list(): void {
		$this->load->language('catalog/duobao_history');

		$this->response->setOutput($this->getList());
	}

	protected function getList(): string {
		$this->load->model('catalog/duobao');

		$filter_title = $this->request->get['filter_title'] ?? '';
		$filter_issue_no = $this->request->get['filter_issue_no'] ?? '';
		$filter_status = $this->request->get['filter_status'] ?? '';
		$sort = $this->request->get['sort'] ?? 'issue.date_modified';
		$order = $this->request->get['order'] ?? 'DESC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		if ($page < 1) {
			$page = 1;
		}

		$url = '';

		if ($filter_title !== '') {
			$url .= '&filter_title=' . urlencode(html_entity_decode($filter_title, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_issue_no !== '') {
			$url .= '&filter_issue_no=' . (int)$filter_issue_no;
		}

		if ($filter_status !== '') {
			$url .= '&filter_status=' . $filter_status;
		}

		if ($sort) {
			$url .= '&sort=' . $sort;
		}

		if ($order) {
			$url .= '&order=' . $order;
		}

		if ($page) {
			$url .= '&page=' . $page;
		}

		$data['action'] = $this->url->link('catalog/duobao_history.list', 'user_token=' . $this->session->data['user_token'] . $url);

		$filter_data = [
			'filter_title'    => $filter_title,
			'filter_issue_no' => $filter_issue_no,
			'filter_status'   => $filter_status,
			'sort'            => $sort,
			'order'           => $order,
			'start'           => ($page - 1) * $this->config->get('config_pagination_admin'),
			'limit'           => $this->config->get('config_pagination_admin')
		];

		$results = $this->model_catalog_duobao->getDrawHistory($filter_data);
		$total = $this->model_catalog_duobao->getTotalDrawHistory($filter_data);

		$status_map = [
			'draft'     => $this->language->get('text_status_draft'),
			'active'    => $this->language->get('text_status_active'),
			'suspended' => $this->language->get('text_status_suspended'),
			'completed' => $this->language->get('text_status_completed'),
			'cancelled' => $this->language->get('text_status_cancelled')
		];

		$data['column_title'] = $this->language->get('column_title');
		$data['column_issue'] = $this->language->get('column_issue');
		$data['column_winner'] = $this->language->get('column_winner');
		$data['column_ticket'] = $this->language->get('column_ticket');
		$data['column_total'] = $this->language->get('column_total');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_draw_time'] = $this->language->get('column_draw_time');
		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['draws'] = [];

		foreach ($results as $result) {
			$winner = $result['winner_name'] ?: $result['winner_email'] ?: $this->language->get('text_unknown_winner');

			$data['draws'][] = [
				'issue_id'     => $result['issue_id'],
				'duobao_id'    => $result['duobao_id'],
				'title'        => $result['title'],
				'sub_title'    => $result['sub_title'],
				'issue_no'     => $result['issue_no'],
				'winner'       => $winner,
				'winning_ticket' => $result['winner_ticket'],
				'joined_slots' => $result['joined_slots'],
				'total_slots'  => $result['total_slots'],
				'status'       => $status_map[$result['status']] ?? $result['status'],
				'date_modified'=> $result['date_modified'],
				'view'         => $this->url->link('catalog/duobao', 'user_token=' . $this->session->data['user_token'] . '&duobao_id=' . $result['duobao_id'])
			];
		}

		$url = '';

		if ($filter_title !== '') {
			$url .= '&filter_title=' . urlencode(html_entity_decode($filter_title, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_issue_no !== '') {
			$url .= '&filter_issue_no=' . (int)$filter_issue_no;
		}

		if ($filter_status !== '') {
			$url .= '&filter_status=' . $filter_status;
		}

		if ($order === 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_title'] = $this->url->link('catalog/duobao_history.list', 'user_token=' . $this->session->data['user_token'] . '&sort=dd.title' . $url);
		$data['sort_issue'] = $this->url->link('catalog/duobao_history.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.issue_no' . $url);
		$data['sort_status'] = $this->url->link('catalog/duobao_history.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.status' . $url);
		$data['sort_modified'] = $this->url->link('catalog/duobao_history.list', 'user_token=' . $this->session->data['user_token'] . '&sort=issue.date_modified' . $url);

		$url = '';

		if ($filter_title !== '') {
			$url .= '&filter_title=' . urlencode(html_entity_decode($filter_title, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_issue_no !== '') {
			$url .= '&filter_issue_no=' . (int)$filter_issue_no;
		}

		if ($filter_status !== '') {
			$url .= '&filter_status=' . $filter_status;
		}

		if ($sort) {
			$url .= '&sort=' . $sort;
		}

		if ($order) {
			$url .= '&order=' . $order;
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('catalog/duobao_history.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($total - $this->config->get('config_pagination_admin'))) ? $total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $total, ceil($total / $this->config->get('config_pagination_admin')));

		$data['filter_title'] = $filter_title;
		$data['filter_issue_no'] = $filter_issue_no;
		$data['filter_status'] = $filter_status;
		$data['sort'] = $sort;
		$data['order'] = $order;

		return $this->load->view('catalog/duobao_history_list', $data);
	}
}
