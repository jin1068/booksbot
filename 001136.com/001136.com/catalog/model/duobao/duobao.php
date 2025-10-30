<?php
namespace Opencart\Catalog\Model\Duobao;
/**
 * Class Duobao
 *
 * 前台一元夺宝数据访问层
 */
class Duobao extends \Opencart\System\Engine\Model {
	/**
	 * 获取正在进行或最近开始的夺宝活动
	 *
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getActiveDuobaos(int $limit = 4): array {
		if ($limit < 1) {
			$limit = 4;
		}

		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT d.duobao_id, d.product_id, dd.title, dd.sub_title, "
			. "issue.issue_id, issue.issue_no, issue.total_slots, issue.joined_slots, "
			. "issue.status, issue.start_time, issue.end_time "
			. "FROM `" . DB_PREFIX . "duobao` d "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.duobao_id = d.duobao_id AND issue.issue_no = d.issue_no) "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = d.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "WHERE issue.status IN ('active', 'draft', 'suspended') "
			. "ORDER BY issue.status = 'active' DESC, issue.start_time DESC, d.date_added DESC "
			. "LIMIT " . (int)$limit;

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * 获取单个夺宝详情
	 *
	 * @param int $duobao_id
	 *
	 * @return array<string, mixed>
	 */
	public function getDuobao(int $duobao_id): array {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT d.duobao_id, d.product_id, d.price, d.status AS duobao_status, d.date_added, d.date_modified, "
			. "dd.title, dd.sub_title, dd.meta_title, dd.meta_description, dd.meta_keyword, dd.description, "
			. "issue.issue_id, issue.issue_no, issue.total_slots, issue.joined_slots, issue.status, "
			. "issue.start_time, issue.end_time, issue.winner_customer_id, issue.winner_order_id, issue.winner_ticket "
			. "FROM `" . DB_PREFIX . "duobao` d "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = d.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.duobao_id = d.duobao_id AND issue.issue_no = d.issue_no) "
			. "WHERE d.duobao_id = '" . (int)$duobao_id . "'";

		$query = $this->db->query($sql);

		return $query->row;
	}

	/**
	 * 获取期次备注描述
	 *
	 * @param int $issue_id
	 *
	 * @return array<string, string>
	 */
	public function getIssueDescription(int $issue_id): array {
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "duobao_issue_description` WHERE `issue_id` = '" . (int)$issue_id . "' AND `language_id` = '" . $language_id . "'");

		return $query->row;
	}

	/**
	 * 获取用户在指定期次的号码列表
	 *
	 * @param int $issue_id
	 * @param int $customer_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getTicketsByCustomer(int $issue_id, int $customer_id): array {
		$query = $this->db->query("SELECT `ticket_no`, `date_added` FROM `" . DB_PREFIX . "duobao_ticket` WHERE `issue_id` = '" . (int)$issue_id . "' AND `customer_id` = '" . (int)$customer_id . "' ORDER BY `ticket_id` ASC");

		return $query->rows;
	}

	/**
	 * 获取期次实时状态
	 *
	 * @param int $duobao_id
	 *
	 * @return array<string, mixed>
	 */
	public function getIssueStatus(int $duobao_id): array {
		$query = $this->db->query("SELECT issue.*, d.price FROM `" . DB_PREFIX . "duobao_issue` issue LEFT JOIN `" . DB_PREFIX . "duobao` d ON (d.duobao_id = issue.duobao_id) WHERE issue.duobao_id = '" . (int)$duobao_id . "' ORDER BY issue.issue_no DESC LIMIT 1");

		return $query->row;
	}

	/**
	 * 获取用户参与记录
	 *
	 * @param int $customer_id
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getCustomerIssues(int $customer_id, int $start = 0, int $limit = 10): array {
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT issue.issue_id, issue.duobao_id, issue.issue_no, issue.status, issue.total_slots, issue.joined_slots, issue.start_time, issue.end_time, issue.winner_ticket, dd.title, dd.sub_title, COUNT(ticket.ticket_id) AS my_slots, MIN(ticket.ticket_no) AS first_ticket, MAX(ticket.ticket_no) AS last_ticket FROM `" . DB_PREFIX . "duobao_ticket` ticket LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.issue_id = ticket.issue_id) LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = ticket.duobao_id AND dd.language_id = '" . $language_id . "') WHERE ticket.customer_id = '" . (int)$customer_id . "' GROUP BY issue.issue_id ORDER BY issue.date_modified DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	/**
	 * 获取用户参与记录总数
	 *
	 * @param int $customer_id
	 *
	 * @return int
	 */
	public function getTotalCustomerIssues(int $customer_id): int {
		$query = $this->db->query("SELECT COUNT(DISTINCT ticket.issue_id) AS total FROM `" . DB_PREFIX . "duobao_ticket` ticket WHERE ticket.customer_id = '" . (int)$customer_id . "'");

		return (int)$query->row['total'];
	}

	/**
	 * 获取用户中奖记录
	 *
	 * @param int $customer_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getCustomerWins(int $customer_id): array {
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT issue.issue_id, issue.duobao_id, issue.issue_no, issue.winner_ticket, issue.date_modified, dd.title, dd.sub_title FROM `" . DB_PREFIX . "duobao_issue` issue LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = issue.duobao_id AND dd.language_id = '" . $language_id . "') WHERE issue.winner_customer_id = '" . (int)$customer_id . "' ORDER BY issue.date_modified DESC");

		return $query->rows;
	}

	/**
	 * 获取用户中奖记录总数
	 *
	 * @param int $customer_id
	 *
	 * @return int
	 */
	public function getTotalCustomerWins(int $customer_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "duobao_issue` WHERE `winner_customer_id` = '" . (int)$customer_id . "'");

		return (int)$query->row['total'];
	}

	/**
	 * 获取开奖历史列表
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDrawHistory(array $data = []): array {
		$language_id = (int)$this->config->get('config_language_id');

		$start = (int)($data['start'] ?? 0);
		$limit = (int)($data['limit'] ?? 10);

		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$sql = "SELECT issue.*, d.duobao_id, d.price, dd.title, dd.sub_title, CONCAT(c.firstname, ' ', c.lastname) AS winner_name, c.email AS winner_email "
			. "FROM `" . DB_PREFIX . "duobao_issue` issue "
			. "LEFT JOIN `" . DB_PREFIX . "duobao` d ON (d.duobao_id = issue.duobao_id) "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = issue.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "LEFT JOIN `" . DB_PREFIX . "customer` c ON (c.customer_id = issue.winner_customer_id) "
			. "WHERE issue.status IN ('completed', 'cancelled') "
			. "ORDER BY issue.date_modified DESC, issue.issue_id DESC "
			. "LIMIT " . $start . "," . $limit;

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * 获取开奖历史总数
	 *
	 * @return int
	 */
	public function getTotalDrawHistory(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "duobao_issue` WHERE `status` IN ('completed', 'cancelled')");

		return (int)$query->row['total'];
	}

	/**
	 * 余额参与夺宝，写入号码并更新期次
	 *
	 * @param int $duobao_id
	 * @param int $customer_id
	 * @param int $quantity
	 * @param bool $is_robot 是否为机器人购买
	 *
	 * @return array<string, mixed>
	 */
	public function join(int $duobao_id, int $customer_id, int $quantity, bool $is_robot = false): array {
		$result = [
			'success'      => false,
			'issue_id'     => 0,
			'issue_no'     => 0,
			'joined_slots' => 0,
			'total_slots'  => 0,
			'tickets'      => []
		];

		$this->db->query("START TRANSACTION");

		$issue_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "duobao_issue` WHERE `duobao_id` = '" . (int)$duobao_id . "' AND `status` = 'active' ORDER BY `issue_no` DESC LIMIT 1 FOR UPDATE");

		if (!$issue_query->num_rows) {
			$this->db->query("ROLLBACK");

			return $result;
		}

		$issue_info = $issue_query->row;

		if (!in_array($issue_info['status'], ['active', 'draft'], true)) {
			$this->db->query("ROLLBACK");

			return $result;
		}

		$total_slots = (int)$issue_info['total_slots'];
		$joined_slots = (int)$issue_info['joined_slots'];

		if ($quantity < 1 || ($joined_slots + $quantity) > $total_slots) {
			$this->db->query("ROLLBACK");

			return $result;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "duobao_issue` SET `joined_slots` = `joined_slots` + " . (int)$quantity . " WHERE `issue_id` = '" . (int)$issue_info['issue_id'] . "'");

		$tickets = [];
		$ticket_type = $is_robot ? 'robot' : 'real';

		for ($i = 0; $i < $quantity; $i++) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "duobao_ticket` SET `issue_id` = '" . (int)$issue_info['issue_id'] . "', `duobao_id` = '" . (int)$duobao_id . "', `customer_id` = '" . (int)$customer_id . "', `order_id` = 0, `ticket_no` = '', `ticket_type` = '" . $this->db->escape($ticket_type) . "', `date_added` = NOW()");

			$ticket_id = $this->db->getLastId();
			$ticket_no = 'D' . str_pad((string)$ticket_id, 9, '0', STR_PAD_LEFT);

			$this->db->query("UPDATE `" . DB_PREFIX . "duobao_ticket` SET `ticket_no` = '" . $this->db->escape($ticket_no) . "' WHERE `ticket_id` = '" . (int)$ticket_id . "'");

			$tickets[] = $ticket_no;
		}

		// 如果是机器人购买，更新 robot_current_purchases 计数
		if ($is_robot) {
			$this->db->query("UPDATE `" . DB_PREFIX . "duobao_issue` SET `robot_current_purchases` = `robot_current_purchases` + " . (int)$quantity . " WHERE `issue_id` = '" . (int)$issue_info['issue_id'] . "'");
		}

		$new_joined = $joined_slots + $quantity;

		$this->db->query("UPDATE `" . DB_PREFIX . "duobao` SET `joined_slots` = '" . (int)$new_joined . "', `date_modified` = NOW() WHERE `duobao_id` = '" . (int)$duobao_id . "'");

		if ($new_joined >= $total_slots) {
			$this->db->query("UPDATE `" . DB_PREFIX . "duobao_issue` SET `status` = 'suspended' WHERE `issue_id` = '" . (int)$issue_info['issue_id'] . "'");
			$this->db->query("UPDATE `" . DB_PREFIX . "duobao` SET `status` = 'suspended', `date_modified` = NOW() WHERE `duobao_id` = '" . (int)$duobao_id . "'");
		}

		$this->db->query("COMMIT");

		$result['success'] = true;
		$result['issue_id'] = (int)$issue_info['issue_id'];
		$result['issue_no'] = (int)$issue_info['issue_no'];
		$result['joined_slots'] = $new_joined;
		$result['total_slots'] = $total_slots;
		$result['tickets'] = $tickets;
		$result['status_code'] = ($new_joined >= $total_slots) ? 'suspended' : $issue_info['status'];

		return $result;
	}

}
