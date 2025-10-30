<?php
namespace Opencart\Admin\Model\Finance;
/**
 * Class Recharge
 */
class Recharge extends \Opencart\System\Engine\Model {
	/**
	 * Get recharge records
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getRecharges(array $data = []): array {
		$this->ensureTable();

		$sql = "SELECT ur.*, c.firstname, c.lastname, c.email FROM `" . DB_PREFIX . "usdt_recharge` ur LEFT JOIN `" . DB_PREFIX . "customer` c ON (ur.customer_id = c.customer_id)";

		$order = $data['order'] ?? 'DESC';
		$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

		$sql .= " ORDER BY ur.date_added " . $order;

		if (isset($data['start']) || isset($data['limit'])) {
			$start = (int)($data['start'] ?? 0);
			$limit = (int)($data['limit'] ?? 20);

			if ($start < 0) {
				$start = 0;
			}

			if ($limit < 1) {
				$limit = 20;
			}

			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get total recharge records
	 *
	 * @return int
	 */
	public function getTotalRecharges(): int {
		$this->ensureTable();

		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "usdt_recharge`");

		return (int)$query->row['total'];
	}

	/**
	 * Update recharge status
	 *
	 * @param int $recharge_id
	 * @param int $status
	 *
	 * @return bool
	 */
	public function updateStatus(int $recharge_id, int $status, int $transaction_id = 0): bool {
		$this->ensureTable();

		$query = $this->db->query("SELECT recharge_id FROM `" . DB_PREFIX . "usdt_recharge` WHERE recharge_id = '" . (int)$recharge_id . "'");

		if (!$query->num_rows) {
			return false;
		}

		$sql = "UPDATE `" . DB_PREFIX . "usdt_recharge` SET `status` = '" . (int)$status . "', `date_modified` = NOW()";

		if ($transaction_id > 0) {
			$sql .= ", `transaction_id` = '" . (int)$transaction_id . "'";
		}

		$sql .= " WHERE `recharge_id` = '" . (int)$recharge_id . "'";

		$this->db->query($sql);

		return true;
	}

	/**
	 * Get single recharge
	 *
	 * @param int $recharge_id
	 *
	 * @return array<string, mixed>
	 */
	public function getRecharge(int $recharge_id): array {
		$this->ensureTable();

		$query = $this->db->query("SELECT ur.*, c.firstname, c.lastname, c.email FROM `" . DB_PREFIX . "usdt_recharge` ur LEFT JOIN `" . DB_PREFIX . "customer` c ON (ur.customer_id = c.customer_id) WHERE ur.recharge_id = '" . (int)$recharge_id . "'");

		return $query->row ?? [];
	}

	/**
	 * Add customer transaction and return id
	 *
	 * @param int    $customer_id
	 * @param string $description
	 * @param float  $amount
	 *
	 * @return int
	 */
	public function addTransaction(int $customer_id, string $description, float $amount): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_transaction` SET `customer_id` = '" . (int)$customer_id . "', `order_id` = '0', `description` = '" . $this->db->escape($description) . "', `amount` = '" . (float)$amount . "', `date_added` = NOW()");

		return (int)$this->db->getLastId();
	}

	/**
	 * Ensure table exists
	 *
	 * @return void
	 */
	protected function ensureTable(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "usdt_recharge` (
			`recharge_id` INT(11) NOT NULL AUTO_INCREMENT,
			`customer_id` INT(11) NOT NULL,
			`network` VARCHAR(64) NOT NULL,
			`amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
			`txhash` VARCHAR(191) NOT NULL,
			`note` TEXT NOT NULL,
			`status` TINYINT(1) NOT NULL DEFAULT 0,
			`transaction_id` INT(11) NOT NULL DEFAULT 0,
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`recharge_id`),
			INDEX `idx_customer_id` (`customer_id`),
			INDEX `idx_status` (`status`),
			INDEX `idx_date_added` (`date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "usdt_recharge` LIKE 'transaction_id'");

		if (!$column->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "usdt_recharge` ADD COLUMN `transaction_id` INT(11) NOT NULL DEFAULT 0 AFTER `status`");
		}
	}
}
