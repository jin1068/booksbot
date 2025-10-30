<?php
namespace Opencart\Admin\Model\Catalog;
/**
 * Class Duobao
 *
 * 负责后台一元夺宝数据的增删改查及开奖处理。
 *
 * @package Opencart\Admin\Model\Catalog
 */
class Duobao extends \Opencart\System\Engine\Model {
	/**
	 * 避免重复执行建表逻辑
	 */
	private bool $schemaInitialised = false;

	/**
	 * 确保相关数据表存在
	 *
	 * @return void
	 */
	private function ensureSchema(): void {
		if ($this->schemaInitialised) {
			return;
		}

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "duobao` (
		  `duobao_id` INT(11) NOT NULL AUTO_INCREMENT,
		  `product_id` INT(11) DEFAULT NULL,
		  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
		  `issue_no` INT(11) NOT NULL DEFAULT 1,
		  `total_slots` INT(11) NOT NULL DEFAULT 0,
		  `joined_slots` INT(11) NOT NULL DEFAULT 0,
		  `price` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
		  `start_time` DATETIME DEFAULT NULL,
		  `end_time` DATETIME DEFAULT NULL,
		  `date_added` DATETIME NOT NULL,
		  `date_modified` DATETIME NOT NULL,
		  PRIMARY KEY (`duobao_id`),
		  KEY `product_id` (`product_id`),
		  KEY `status` (`status`),
		  KEY `issue_no` (`issue_no`),
		  KEY `start_time` (`start_time`),
		  KEY `end_time` (`end_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "duobao_description` (
		  `duobao_id` INT(11) NOT NULL,
		  `language_id` INT(11) NOT NULL,
		  `title` VARCHAR(255) NOT NULL,
		  `sub_title` VARCHAR(255) DEFAULT '',
		  `meta_title` VARCHAR(255) NOT NULL,
		  `meta_description` TEXT,
		  `meta_keyword` TEXT,
		  `description` MEDIUMTEXT,
		  PRIMARY KEY (`duobao_id`, `language_id`),
		  KEY `language_id` (`language_id`),
		  CONSTRAINT `fk_duobao_desc_duobao` FOREIGN KEY (`duobao_id`) REFERENCES `" . DB_PREFIX . "duobao` (`duobao_id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "duobao_issue` (
		  `issue_id` INT(11) NOT NULL AUTO_INCREMENT,
		  `duobao_id` INT(11) NOT NULL,
		  `issue_no` INT(11) NOT NULL,
		  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
		  `total_slots` INT(11) NOT NULL DEFAULT 0,
		  `joined_slots` INT(11) NOT NULL DEFAULT 0,
		  `winner_customer_id` INT(11) DEFAULT NULL,
		  `winner_order_id` INT(11) DEFAULT NULL,
		  `winner_ticket` VARCHAR(64) DEFAULT NULL,
		  `start_time` DATETIME DEFAULT NULL,
		  `end_time` DATETIME DEFAULT NULL,
		  `draw_time` DATETIME DEFAULT NULL,
		  `date_added` DATETIME NOT NULL,
		  `date_modified` DATETIME NOT NULL,
		  PRIMARY KEY (`issue_id`),
		  UNIQUE KEY `uniq_duobao_issue` (`duobao_id`,`issue_no`),
		  KEY `duobao_id` (`duobao_id`),
		  KEY `status` (`status`),
		  KEY `start_time` (`start_time`),
		  KEY `end_time` (`end_time`),
		  CONSTRAINT `fk_duobao_issue_duobao` FOREIGN KEY (`duobao_id`) REFERENCES `" . DB_PREFIX . "duobao` (`duobao_id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "duobao_issue_description` (
		  `issue_id` INT(11) NOT NULL,
		  `language_id` INT(11) NOT NULL,
		  `title` VARCHAR(255) NOT NULL,
		  `description` MEDIUMTEXT,
		  PRIMARY KEY (`issue_id`,`language_id`),
		  KEY `language_id` (`language_id`),
		  CONSTRAINT `fk_duobao_issue_desc_issue` FOREIGN KEY (`issue_id`) REFERENCES `" . DB_PREFIX . "duobao_issue` (`issue_id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "duobao_ticket` (
		  `ticket_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
		  `issue_id` INT(11) NOT NULL,
		  `duobao_id` INT(11) NOT NULL,
		  `customer_id` INT(11) DEFAULT NULL,
		  `order_id` INT(11) DEFAULT NULL,
		  `order_product_id` INT(11) DEFAULT NULL,
		  `ticket_no` VARCHAR(64) NOT NULL,
		  `date_added` DATETIME NOT NULL,
		  PRIMARY KEY (`ticket_id`),
		  UNIQUE KEY `uniq_issue_ticket` (`issue_id`,`ticket_no`),
		  KEY `duobao_id` (`duobao_id`),
		  KEY `customer_id` (`customer_id`),
		  KEY `order_id` (`order_id`),
		  CONSTRAINT `fk_duobao_ticket_issue` FOREIGN KEY (`issue_id`) REFERENCES `" . DB_PREFIX . "duobao_issue` (`issue_id`) ON DELETE CASCADE,
		  CONSTRAINT `fk_duobao_ticket_duobao` FOREIGN KEY (`duobao_id`) REFERENCES `" . DB_PREFIX . "duobao` (`duobao_id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->schemaInitialised = true;
	}

	/**
	 * 获取夺宝列表
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDuobaos(array $data = []): array {
		$this->ensureSchema();

		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT d.*, dd.title, issue.issue_id, issue.issue_no AS current_issue_no, issue.status AS issue_status, issue.total_slots AS issue_total_slots, issue.joined_slots AS issue_joined_slots, issue.start_time AS issue_start_time, issue.end_time AS issue_end_time, issue.winner_ticket, issue.robot_enabled, pd.name AS product_name "
			. "FROM `" . DB_PREFIX . "duobao` d "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = d.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.duobao_id = d.duobao_id AND issue.issue_no = d.issue_no) "
			. "LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = d.product_id AND pd.language_id = '" . $language_id . "') "
			. "WHERE 1";

		if (!empty($data['filter_title'])) {
			$sql .= " AND dd.title LIKE '" . $this->db->escape($data['filter_title']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND issue.status = '" . $this->db->escape($data['filter_status']) . "'";
		}

		$sort_data = [
			'dd.title',
			'product_name',
			'issue.issue_no',
			'issue.total_slots',
			'issue.joined_slots',
			'issue.status',
			'issue.start_time',
			'issue.end_time',
			'd.date_added',
			'd.date_modified'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data, true)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY d.date_added";
		}

		if (isset($data['order']) && $data['order'] === 'DESC') {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		$start = (int)($data['start'] ?? 0);
		$limit = (int)($data['limit'] ?? 20);

		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$sql .= " LIMIT " . $start . "," . $limit;

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * 获取夺宝总数
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalDuobaos(array $data = []): int {
		$this->ensureSchema();

		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "duobao` d "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = d.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.duobao_id = d.duobao_id AND issue.issue_no = d.issue_no) "
			. "LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = d.product_id AND pd.language_id = '" . $language_id . "') "
			. "WHERE 1";

		if (!empty($data['filter_title'])) {
			$sql .= " AND dd.title LIKE '" . $this->db->escape($data['filter_title']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND issue.status = '" . $this->db->escape($data['filter_status']) . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	/**
	 * 获取单个夺宝详情
	 *
	 * @param int $duobao_id
	 *
	 * @return array<string, mixed>
	 */
	public function getDuobao(int $duobao_id): array {
		$this->ensureSchema();

		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query(
			"SELECT d.*, issue.issue_id, issue.status AS issue_status, issue.total_slots AS issue_total_slots, issue.joined_slots AS issue_joined_slots, issue.winner_customer_id, issue.winner_order_id, issue.winner_ticket, issue.start_time AS issue_start_time, issue.end_time AS issue_end_time, issue.draw_time, pd.name AS product_name "
			. "FROM `" . DB_PREFIX . "duobao` d "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.duobao_id = d.duobao_id AND issue.issue_no = d.issue_no) "
			. "LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = d.product_id AND pd.language_id = '" . $language_id . "') "
			. "WHERE d.duobao_id = '" . (int)$duobao_id . "'"
		);

		return $query->row;
	}

	/**
	 * 获取夺宝多语言描述
	 *
	 * @param int $duobao_id
	 *
	 * @return array<int, array<string, string>>
	 */
	public function getDescriptions(int $duobao_id): array {
		$this->ensureSchema();

		$description_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "duobao_description` WHERE `duobao_id` = '" . (int)$duobao_id . "'");

		foreach ($query->rows as $result) {
			$description_data[(int)$result['language_id']] = $result;
		}

		return $description_data;
	}

	/**
	 * 获取期次描述
	 *
	 * @param int $issue_id
	 *
	 * @return array<int, array<string, string>>
	 */
	public function getIssueDescriptions(int $issue_id): array {
		$this->ensureSchema();

		$data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "duobao_issue_description` WHERE `issue_id` = '" . (int)$issue_id . "'");

		foreach ($query->rows as $result) {
			$data[(int)$result['language_id']] = $result;
		}

		return $data;
	}

	/**
	 * 获取指定期次
	 *
	 * @param int      $duobao_id
	 * @param int|null $issue_id
	 *
	 * @return array<string, mixed>
	 */
	public function getIssue(int $duobao_id, ?int $issue_id = null): array {
		$this->ensureSchema();

		$sql = "SELECT issue.* FROM `" . DB_PREFIX . "duobao_issue` issue WHERE issue.duobao_id = '" . (int)$duobao_id . "'";

		if ($issue_id) {
			$sql .= " AND issue.issue_id = '" . (int)$issue_id . "'";
		} else {
			$sql .= " AND issue.issue_no = (SELECT issue_no FROM `" . DB_PREFIX . "duobao` WHERE duobao_id = '" . (int)$duobao_id . "')";
		}

		$query = $this->db->query($sql . " LIMIT 1");

		return $query->row;
	}

	/**
	 * 新增夺宝商品
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int 新增记录的 duobao_id
	 */
	public function addDuobao(array $data): int {
		$this->ensureSchema();

		$issue_info = $data['issue'] ?? [];
		$issue_no = (int)($issue_info['issue_no'] ?? 1);
		$total_slots = (int)($issue_info['total_slots'] ?? 0);
		$joined_slots = (int)($issue_info['joined_slots'] ?? 0);
		$issue_status = $issue_info['status'] ?? 'draft';
		$start_time = $issue_info['start_time'] ?? null;
		$end_time = $issue_info['end_time'] ?? null;

		$this->db->query("INSERT INTO `" . DB_PREFIX . "duobao` SET 
			`product_id` = " . ($data['product_id'] ? "'" . (int)$data['product_id'] . "'" : "NULL") . ",
			`status` = '" . $this->db->escape($data['status']) . "',
			`issue_no` = '" . $issue_no . "',
			`total_slots` = '" . $total_slots . "',
			`joined_slots` = '" . $joined_slots . "',
			`price` = '" . (float)$data['price'] . "',
			`start_time` = " . ($start_time ? "'" . $this->db->escape($start_time) . "'" : "NULL") . ",
			`end_time` = " . ($end_time ? "'" . $this->db->escape($end_time) . "'" : "NULL") . ",
			`date_added` = NOW(),
			`date_modified` = NOW()
		");

		$duobao_id = $this->db->getLastId();

		// 描述
		$this->insertDescriptions($duobao_id, $data['descriptions']);

		// 默认期次
		$this->db->query("INSERT INTO `" . DB_PREFIX . "duobao_issue` SET 
			`duobao_id` = '" . (int)$duobao_id . "',
			`issue_no` = '" . $issue_no . "',
			`status` = '" . $this->db->escape($issue_status) . "',
			`total_slots` = '" . $total_slots . "',
			`joined_slots` = '" . $joined_slots . "',
			`winner_customer_id` = NULL,
			`winner_order_id` = NULL,
			`winner_ticket` = NULL,
			`start_time` = " . ($start_time ? "'" . $this->db->escape($start_time) . "'" : "NULL") . ",
			`end_time` = " . ($end_time ? "'" . $this->db->escape($end_time) . "'" : "NULL") . ",
			`draw_time` = NULL,
			`date_added` = NOW(),
			`date_modified` = NOW()
		");

		$issue_id = $this->db->getLastId();

		$this->insertIssueDescriptions($issue_id, $data['descriptions']);

		return $duobao_id;
	}

	/**
	 * 编辑夺宝商品
	 *
	 * @param int   $duobao_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editDuobao(int $duobao_id, array $data): void {
		$this->ensureSchema();

		$issue_info = $data['issue'] ?? [];
		$issue_no = (int)($issue_info['issue_no'] ?? 1);
		$total_slots = (int)($issue_info['total_slots'] ?? 0);
		$joined_slots = (int)($issue_info['joined_slots'] ?? 0);
		$issue_status = $issue_info['status'] ?? 'draft';
		$start_time = $issue_info['start_time'] ?? null;
		$end_time = $issue_info['end_time'] ?? null;

		$this->db->query("UPDATE `" . DB_PREFIX . "duobao` SET 
			`product_id` = " . ($data['product_id'] ? "'" . (int)$data['product_id'] . "'" : "NULL") . ",
			`status` = '" . $this->db->escape($data['status']) . "',
			`issue_no` = '" . $issue_no . "',
			`total_slots` = '" . $total_slots . "',
			`joined_slots` = '" . $joined_slots . "',
			`price` = '" . (float)$data['price'] . "',
			`start_time` = " . ($start_time ? "'" . $this->db->escape($start_time) . "'" : "NULL") . ",
			`end_time` = " . ($end_time ? "'" . $this->db->escape($end_time) . "'" : "NULL") . ",
			`date_modified` = NOW()
			WHERE `duobao_id` = '" . (int)$duobao_id . "'");

		// 描述
		$this->db->query("DELETE FROM `" . DB_PREFIX . "duobao_description` WHERE `duobao_id` = '" . (int)$duobao_id . "'");
		$this->insertDescriptions($duobao_id, $data['descriptions']);

		// 当前期次
		$issue_id = (int)($issue_info['issue_id'] ?? 0);

		if ($issue_id) {
			$this->db->query("UPDATE `" . DB_PREFIX . "duobao_issue` SET 
				`issue_no` = '" . $issue_no . "',
				`status` = '" . $this->db->escape($issue_status) . "',
				`total_slots` = '" . $total_slots . "',
				`joined_slots` = '" . $joined_slots . "',
				`start_time` = " . ($start_time ? "'" . $this->db->escape($start_time) . "'" : "NULL") . ",
				`end_time` = " . ($end_time ? "'" . $this->db->escape($end_time) . "'" : "NULL") . ",
				`date_modified` = NOW()
				WHERE `issue_id` = '" . $issue_id . "'".
				" AND `duobao_id` = '" . (int)$duobao_id . "'");

			$this->db->query("DELETE FROM `" . DB_PREFIX . "duobao_issue_description` WHERE `issue_id` = '" . $issue_id . "'");
			$this->insertIssueDescriptions($issue_id, $data['descriptions']);
		} else {
			// 若当前关联期次不存在则创建
			$this->db->query("INSERT INTO `" . DB_PREFIX . "duobao_issue` SET 
				`duobao_id` = '" . (int)$duobao_id . "',
				`issue_no` = '" . $issue_no . "',
				`status` = '" . $this->db->escape($issue_status) . "',
				`total_slots` = '" . $total_slots . "',
				`joined_slots` = '" . $joined_slots . "',
				`start_time` = " . ($data['issue']['start_time'] ? "'" . $this->db->escape($data['issue']['start_time']) . "'" : "NULL") . ",
				`end_time` = " . ($data['issue']['end_time'] ? "'" . $this->db->escape($data['issue']['end_time']) . "'" : "NULL") . ",
				`date_added` = NOW(),
				`date_modified` = NOW()
			");

			$new_issue_id = $this->db->getLastId();
			$this->insertIssueDescriptions($new_issue_id, $data['descriptions']);
		}
	}

	/**
	 * 删除夺宝商品
	 *
	 * @param array<int> $duobao_ids
	 *
	 * @return void
	 */
	public function deleteDuobaos(array $duobao_ids): void {
		$this->ensureSchema();

		foreach ($duobao_ids as $duobao_id) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "duobao` WHERE `duobao_id` = '" . (int)$duobao_id . "'");
		}
	}

	/**
	 * 开奖处理
	 *
	 * @param int   $duobao_id
	 * @param int   $issue_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function drawDuobao(int $duobao_id, int $issue_id, array $data): void {
		$this->ensureSchema();

		$this->db->query("UPDATE `" . DB_PREFIX . "duobao_issue` SET 
			`status` = '" . $this->db->escape($data['status']) . "',
			`winner_customer_id` = " . ($data['winner_customer_id'] !== null ? "'" . (int)$data['winner_customer_id'] . "'" : "NULL") . ",
			`winner_order_id` = " . ($data['winner_order_id'] !== null ? "'" . (int)$data['winner_order_id'] . "'" : "NULL") . ",
			`winner_ticket` = " . ($data['winner_ticket'] ? "'" . $this->db->escape($data['winner_ticket']) . "'" : "NULL") . ",
			`draw_time` = " . ($data['status'] === 'completed' ? 'NOW()' : 'NULL') . ",
			`date_modified` = NOW()
			WHERE `issue_id` = '" . (int)$issue_id . "' AND `duobao_id` = '" . (int)$duobao_id . "'");

		// 同步主表状态
		$this->db->query("UPDATE `" . DB_PREFIX . "duobao` SET `status` = '" . $this->db->escape($data['status']) . "', `date_modified` = NOW() WHERE `duobao_id` = '" . (int)$duobao_id . "'");

		// 若有备注，追加至 duobao_issue_description（简单写入默认语言）
		if (!empty($data['notes'])) {
			$language_id = (int)$this->config->get('config_language_id');

			$this->db->query("REPLACE INTO `" . DB_PREFIX . "duobao_issue_description` SET 
				`issue_id` = '" . (int)$issue_id . "',
				`language_id` = '" . $language_id . "',
				`title` = (SELECT `title` FROM `" . DB_PREFIX . "duobao_description` WHERE `duobao_id` = '" . (int)$duobao_id . "' AND `language_id` = '" . $language_id . "'),
				`description` = '" . $this->db->escape($data['notes']) . "'");
		}
	}

	/**
	 * 插入夺宝描述
	 *
	 * @param int $duobao_id
	 * @param array<int, array<string, string>> $descriptions
	 *
	 * @return void
	 */
	private function insertDescriptions(int $duobao_id, array $descriptions): void {
		foreach ($descriptions as $language_id => $value) {
			$meta_title = $value['meta_title'] ?? $value['title'];

			$this->db->query("INSERT INTO `" . DB_PREFIX . "duobao_description` SET 
				`duobao_id` = '" . (int)$duobao_id . "',
				`language_id` = '" . (int)$language_id . "',
				`title` = '" . $this->db->escape($value['title']) . "',
				`sub_title` = '" . $this->db->escape($value['sub_title'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($meta_title) . "',
				`meta_description` = '" . $this->db->escape($value['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($value['meta_keyword'] ?? '') . "',
				`description` = '" . $this->db->escape($value['description'] ?? '') . "'");
		}
	}

	/**
	 * 插入期次描述（缺省使用主描述）
	 *
	 * @param int $issue_id
	 * @param array<int, array<string, string>> $descriptions
	 *
	 * @return void
	 */
	private function insertIssueDescriptions(int $issue_id, array $descriptions): void {
		foreach ($descriptions as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "duobao_issue_description` SET 
				`issue_id` = '" . (int)$issue_id . "',
				`language_id` = '" . (int)$language_id . "',
				`title` = '" . $this->db->escape($value['title']) . "',
				`description` = '" . $this->db->escape($value['description'] ?? '') . "'");
		}
	}

	/**
	 * 获取开奖历史
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDrawHistory(array $data = []): array {
		$this->ensureSchema();

		$language_id = (int)$this->config->get('config_language_id');

		$status = $data['filter_status'] ?? '';
		$status_sql = $status ? " AND issue.status = '" . $this->db->escape($status) . "'" : " AND issue.status IN ('completed','cancelled')";

		$sql = "SELECT issue.*, dd.title, dd.sub_title, d.price, CONCAT(c.firstname, ' ', c.lastname) AS winner_name, c.email AS winner_email "
			. "FROM `" . DB_PREFIX . "duobao_issue` issue "
			. "LEFT JOIN `" . DB_PREFIX . "duobao` d ON (d.duobao_id = issue.duobao_id) "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = issue.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "LEFT JOIN `" . DB_PREFIX . "customer` c ON (c.customer_id = issue.winner_customer_id) "
			. "WHERE 1" . $status_sql;

		if (!empty($data['filter_title'])) {
			$sql .= " AND dd.title LIKE '" . $this->db->escape($data['filter_title']) . "%'";
		}

		if (!empty($data['filter_issue_no'])) {
			$sql .= " AND issue.issue_no = '" . (int)$data['filter_issue_no'] . "'";
		}

		$sort_data = [
			'dd.title',
			'issue.issue_no',
			'issue.status',
			'issue.date_modified'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data, true)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY issue.date_modified";
		}

		if (isset($data['order']) && $data['order'] === 'ASC') {
			$sql .= " ASC";
		} else {
			$sql .= " DESC";
		}

		$start = (int)($data['start'] ?? 0);
		$limit = (int)($data['limit'] ?? 20);

		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$sql .= " LIMIT " . $start . "," . $limit;

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * 获取开奖历史总数
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalDrawHistory(array $data = []): int {
		$this->ensureSchema();

		$language_id = (int)$this->config->get('config_language_id');

		$status = $data['filter_status'] ?? '';

		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "duobao_issue` issue "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = issue.duobao_id AND dd.language_id = '" . $language_id . "') WHERE 1";

		if ($status) {
			$sql .= " AND issue.status = '" . $this->db->escape($status) . "'";
		} else {
			$sql .= " AND issue.status IN ('completed','cancelled')";
		}

		if (!empty($data['filter_title'])) {
			$sql .= " AND dd.title LIKE '" . $this->db->escape($data['filter_title']) . "%'";
		}

		if (!empty($data['filter_issue_no'])) {
			$sql .= " AND issue.issue_no = '" . (int)$data['filter_issue_no'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	/**
	 * 获取参与记录
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getTickets(array $data = []): array {
		$this->ensureSchema();

		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT ticket.*, dd.title, issue.issue_no, issue.status, CONCAT(c.firstname, ' ', c.lastname) AS customer_name, c.email "
			. "FROM `" . DB_PREFIX . "duobao_ticket` ticket "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.issue_id = ticket.issue_id) "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = ticket.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "LEFT JOIN `" . DB_PREFIX . "customer` c ON (c.customer_id = ticket.customer_id) WHERE 1";

		if (!empty($data['filter_duobao_id'])) {
			$sql .= " AND ticket.duobao_id = '" . (int)$data['filter_duobao_id'] . "'";
		}

		if (!empty($data['filter_issue_id'])) {
			$sql .= " AND ticket.issue_id = '" . (int)$data['filter_issue_id'] . "'";
		}

		if (!empty($data['filter_issue_no'])) {
			$sql .= " AND issue.issue_no = '" . (int)$data['filter_issue_no'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND (c.firstname LIKE '" . $this->db->escape($data['filter_customer']) . "%' OR c.lastname LIKE '" . $this->db->escape($data['filter_customer']) . "%' OR c.email LIKE '" . $this->db->escape($data['filter_customer']) . "%')";
		}

		if (!empty($data['filter_ticket'])) {
			$sql .= " AND ticket.ticket_no LIKE '" . $this->db->escape($data['filter_ticket']) . "%'";
		}

		$sort_data = [
			'ticket.ticket_no',
			'dd.title',
			'issue.issue_no',
			'customer_name',
			'ticket.date_added'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data, true)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY ticket.date_added";
		}

		if (isset($data['order']) && $data['order'] === 'ASC') {
			$sql .= " ASC";
		} else {
			$sql .= " DESC";
		}

		$start = (int)($data['start'] ?? 0);
		$limit = (int)($data['limit'] ?? 20);

		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$sql .= " LIMIT " . $start . "," . $limit;

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * 获取参与记录总数
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalTickets(array $data = []): int {
		$this->ensureSchema();

		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "duobao_ticket` ticket "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_issue` issue ON (issue.issue_id = ticket.issue_id) "
			. "LEFT JOIN `" . DB_PREFIX . "duobao_description` dd ON (dd.duobao_id = ticket.duobao_id AND dd.language_id = '" . $language_id . "') "
			. "LEFT JOIN `" . DB_PREFIX . "customer` c ON (c.customer_id = ticket.customer_id) WHERE 1";

		if (!empty($data['filter_duobao_id'])) {
			$sql .= " AND ticket.duobao_id = '" . (int)$data['filter_duobao_id'] . "'";
		}

		if (!empty($data['filter_issue_id'])) {
			$sql .= " AND ticket.issue_id = '" . (int)$data['filter_issue_id'] . "'";
		}

		if (!empty($data['filter_issue_no'])) {
			$sql .= " AND issue.issue_no = '" . (int)$data['filter_issue_no'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND (c.firstname LIKE '" . $this->db->escape($data['filter_customer']) . "%' OR c.lastname LIKE '" . $this->db->escape($data['filter_customer']) . "%' OR c.email LIKE '" . $this->db->escape($data['filter_customer']) . "%')";
		}

		if (!empty($data['filter_ticket'])) {
			$sql .= " AND ticket.ticket_no LIKE '" . $this->db->escape($data['filter_ticket']) . "%'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	/**
	 * 获取机器人配置
	 *
	 * @param int $issue_id
	 *
	 * @return array<string, mixed>
	 */
	public function getRobotConfig(int $issue_id): array {
		$this->ensureSchema();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "duobao_issue` WHERE `issue_id` = '" . (int)$issue_id . "'");

		if ($query->num_rows) {
			return [
				'robot_enabled'       => (int)($query->row['robot_enabled'] ?? 0),
				'robot_target_percent' => (int)($query->row['robot_target_percent'] ?? 80)
			];
		}

		return [];
	}

	/**
	 * 获取机器人时间段配置
	 *
	 * @param int $issue_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getRobotSchedules(int $issue_id): array {
		$this->ensureSchema();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "duobao_robot_schedule` WHERE `issue_id` = '" . (int)$issue_id . "' ORDER BY `sort_order` ASC, `start_time` ASC");

		return $query->rows;
	}

	/**
	 * 保存机器人配置
	 *
	 * @param int $duobao_id
	 * @param int $issue_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function saveRobotConfig(int $duobao_id, int $issue_id, array $data): void {
		$this->ensureSchema();

		$robot_enabled = (int)($data['robot_enabled'] ?? 0);
		$robot_target_percent = (int)($data['robot_target_percent'] ?? 80);

		// 限制范围
		if ($robot_target_percent < 1) $robot_target_percent = 1;
		if ($robot_target_percent > 100) $robot_target_percent = 100;

		$this->db->query("UPDATE `" . DB_PREFIX . "duobao_issue` SET 
			`robot_enabled` = '" . $robot_enabled . "',
			`robot_target_percent` = '" . $robot_target_percent . "',
			`date_modified` = NOW()
			WHERE `issue_id` = '" . (int)$issue_id . "' AND `duobao_id` = '" . (int)$duobao_id . "'");
	}

	/**
	 * 更新期次的自动派奖类型
	 *
	 * @param int $issue_id
	 * @param string $auto_draw_type
	 *
	 * @return void
	 */
	public function updateIssueAutoDrawType(int $issue_id, string $auto_draw_type): void {
		$this->ensureSchema();

		$allowed_types = ['real', 'robot', 'random', ''];
		if (!in_array($auto_draw_type, $allowed_types, true)) {
			$auto_draw_type = '';
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "duobao_issue` SET 
			`auto_draw_type` = " . ($auto_draw_type ? "'" . $this->db->escape($auto_draw_type) . "'" : "NULL") . ",
			`date_modified` = NOW()
			WHERE `issue_id` = '" . (int)$issue_id . "'");
	}

	/**
	 * 保存机器人时间段配置
	 *
	 * @param int $issue_id
	 * @param array<int, array<string, mixed>> $schedules
	 *
	 * @return void
	 */
	public function saveRobotSchedules(int $issue_id, array $schedules): void {
		$this->ensureSchema();

		// 删除旧的时间段配置
		$this->db->query("DELETE FROM `" . DB_PREFIX . "duobao_robot_schedule` WHERE `issue_id` = '" . (int)$issue_id . "'");

		// 插入新的时间段配置
		$sort_order = 0;
		foreach ($schedules as $schedule) {
			$start_time = isset($schedule['start_time']) && $schedule['start_time'] ? date('Y-m-d H:i:s', strtotime($schedule['start_time'])) : null;
			$end_time = isset($schedule['end_time']) && $schedule['end_time'] ? date('Y-m-d H:i:s', strtotime($schedule['end_time'])) : null;

			if (!$start_time || !$end_time) {
				continue; // 跳过无效的时间段
			}

			$target_percent = (int)($schedule['target_percent'] ?? 70);
			$quantity_min = (int)($schedule['quantity_min'] ?? 1);
			$quantity_max = (int)($schedule['quantity_max'] ?? 5);
			$interval_min = (int)($schedule['interval_min'] ?? 30);
			$interval_max = (int)($schedule['interval_max'] ?? 120);

			$this->db->query("INSERT INTO `" . DB_PREFIX . "duobao_robot_schedule` SET 
				`issue_id` = '" . (int)$issue_id . "',
				`start_time` = '" . $this->db->escape($start_time) . "',
				`end_time` = '" . $this->db->escape($end_time) . "',
				`target_percent` = '" . $target_percent . "',
				`purchase_interval_min` = '" . $interval_min . "',
				`purchase_interval_max` = '" . $interval_max . "',
				`quantity_min` = '" . $quantity_min . "',
				`quantity_max` = '" . $quantity_max . "',
				`is_completed` = 0,
				`sort_order` = '" . $sort_order . "',
				`date_added` = NOW()");

			$sort_order++;
		}
	}

	/**
	 * 自动派奖：根据配置选择中奖票券
	 *
	 * @param int $issue_id
	 * @param string $auto_draw_type 派奖类型：real=优先真人, robot=优先机器人, random=随机
	 *
	 * @return array{success: bool, winner_ticket: string, winner_customer_id: int, ticket_type: string, message?: string}
	 */
	public function autoDrawWinner(int $issue_id, string $auto_draw_type): array {
		$this->ensureSchema();

		$result = [
			'success'           => false,
			'winner_ticket'     => '',
			'winner_customer_id' => 0,
			'ticket_type'       => ''
		];

		// 根据派奖类型选择不同的SQL
		switch ($auto_draw_type) {
			case 'real':
				// 优先派给真人购买的票券
				$query = $this->db->query("
					SELECT ticket_no, customer_id, ticket_type
					FROM `" . DB_PREFIX . "duobao_ticket`
					WHERE issue_id = '" . (int)$issue_id . "'
					  AND ticket_type = 'real'
					ORDER BY RAND()
					LIMIT 1
				");
				
				// 如果没有真人票券，回退到机器人票券
				if (!$query->num_rows) {
					$query = $this->db->query("
						SELECT ticket_no, customer_id, ticket_type
						FROM `" . DB_PREFIX . "duobao_ticket`
						WHERE issue_id = '" . (int)$issue_id . "'
						  AND ticket_type = 'robot'
						ORDER BY RAND()
						LIMIT 1
					");
					
					if ($query->num_rows) {
						$result['message'] = '无真人票券，回退到机器人票券';
					}
				}
				break;

			case 'robot':
				// 优先派给机器人购买的票券
				$query = $this->db->query("
					SELECT ticket_no, customer_id, ticket_type
					FROM `" . DB_PREFIX . "duobao_ticket`
					WHERE issue_id = '" . (int)$issue_id . "'
					  AND ticket_type = 'robot'
					ORDER BY RAND()
					LIMIT 1
				");
				
				// 如果没有机器人票券，回退到真人票券
				if (!$query->num_rows) {
					$query = $this->db->query("
						SELECT ticket_no, customer_id, ticket_type
						FROM `" . DB_PREFIX . "duobao_ticket`
						WHERE issue_id = '" . (int)$issue_id . "'
						  AND ticket_type = 'real'
						ORDER BY RAND()
						LIMIT 1
					");
					
					if ($query->num_rows) {
						$result['message'] = '无机器人票券，回退到真人票券';
					}
				}
				break;

			case 'random':
			default:
				// 完全随机选择
				$query = $this->db->query("
					SELECT ticket_no, customer_id, ticket_type
					FROM `" . DB_PREFIX . "duobao_ticket`
					WHERE issue_id = '" . (int)$issue_id . "'
					ORDER BY RAND()
					LIMIT 1
				");
				break;
		}

		if ($query->num_rows) {
			$result['success'] = true;
			$result['winner_ticket'] = $query->row['ticket_no'];
			$result['winner_customer_id'] = (int)$query->row['customer_id'];
			$result['ticket_type'] = $query->row['ticket_type'];
		} else {
			$result['message'] = '该期次没有任何票券';
		}

		return $result;
	}

	/**
	 * 获取期次的购买统计（区分真人和机器人）
	 *
	 * @param int $issue_id
	 *
	 * @return array{total: int, real: int, robot: int}
	 */
	public function getIssueTicketStats(int $issue_id): array {
		$this->ensureSchema();

		$query = $this->db->query("
			SELECT 
				COUNT(*) as total,
				SUM(CASE WHEN ticket_type = 'real' THEN 1 ELSE 0 END) as real_count,
				SUM(CASE WHEN ticket_type = 'robot' THEN 1 ELSE 0 END) as robot_count
			FROM `" . DB_PREFIX . "duobao_ticket`
			WHERE issue_id = '" . (int)$issue_id . "'
		");

		if ($query->num_rows) {
			return [
				'total' => (int)$query->row['total'],
				'real'  => (int)$query->row['real_count'],
				'robot' => (int)$query->row['robot_count']
			];
		}

		return [
			'total' => 0,
			'real'  => 0,
			'robot' => 0
		];
	}
}
