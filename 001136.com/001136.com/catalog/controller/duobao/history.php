<?php
namespace Opencart\Catalog\Controller\Duobao;
/**
 * Class History
 *
 * 前台夺宝开奖列表
 */
class History extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('duobao/history');
		$this->load->model('duobao/duobao');

		$language_code = $this->config->get('config_language');

		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		if ($page < 1) {
			$page = 1;
		}

		$limit = 10;

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $language_code)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('duobao/history', 'language=' . $language_code)
		];

		$status_map = [
			'draft'     => $this->language->get('text_status_draft'),
			'active'    => $this->language->get('text_status_active'),
			'suspended' => $this->language->get('text_status_suspended'),
			'completed' => $this->language->get('text_status_completed'),
			'cancelled' => $this->language->get('text_status_cancelled')
		];

		$results = $this->model_duobao_duobao->getDrawHistory([
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		]);

        $data['heading_title'] = $this->language->get('heading_title');

        $data['draws'] = [];

		$date_format = $this->language->get('datetime_format') ?? 'Y-m-d H:i:s';

		foreach ($results as $result) {
			$progress = $result['total_slots'] ? min(100, max(0, (int)round($result['joined_slots'] / $result['total_slots'] * 100))) : 0;

			$winner_display = '';

			if (!empty($result['winner_name'])) {
				$winner_display = $result['winner_name'];
			} elseif (!empty($result['winner_email'])) {
				$winner_display = $result['winner_email'];
			} else {
				$winner_display = $this->language->get('text_unknown_winner');
			}

			$data['draws'][] = [
				'duobao_id'    => (int)$result['duobao_id'],
				'title'        => $result['title'],
				'sub_title'    => $result['sub_title'],
				'issue_no'     => (int)$result['issue_no'],
				'winner'       => $winner_display,
				'winner_ticket'=> $result['winner_ticket'],
				'total_slots'  => (int)$result['total_slots'],
				'joined_slots' => (int)$result['joined_slots'],
				'progress'     => $progress,
				'status'       => $status_map[$result['status']] ?? $result['status'],
				'price'        => $this->currency->format((float)$result['price'], $this->session->data['currency']),
				'start_time'   => $result['start_time'] ? date($date_format, strtotime($result['start_time'])) : '',
				'end_time'     => $result['end_time'] ? date($date_format, strtotime($result['end_time'])) : '',
				'draw_time'    => $result['date_modified'] ? date($date_format, strtotime($result['date_modified'])) : '',
				'href'         => $this->url->link('duobao/detail', 'language=' . $language_code . '&duobao_id=' . (int)$result['duobao_id'])
			];
		}

		$total = $this->model_duobao_duobao->getTotalDrawHistory();

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('duobao/history', 'language=' . $language_code . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		$data['text_no_results'] = $this->language->get('text_no_results');
        $data['column_title'] = $this->language->get('column_title');
        $data['column_issue'] = $this->language->get('column_issue');
        $data['column_winner'] = $this->language->get('column_winner');
        $data['column_ticket'] = $this->language->get('column_ticket');
        $data['column_draw_time'] = $this->language->get('column_draw_time');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_progress'] = $this->language->get('column_progress');
        $data['button_view'] = $this->language->get('button_view');
        $data['button_continue'] = $this->language->get('button_continue');

		$data['continue'] = $this->url->link('common/home', 'language=' . $language_code);

		$this->document->setTitle($this->language->get('heading_title'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('duobao/history', $data));
	}
}
