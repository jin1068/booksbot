<?php
namespace Opencart\Catalog\Model\Account;
/**
 * Class UsdtRecharge
 */
class UsdtRecharge extends \Opencart\System\Engine\Model {
	/**
	 * Add recharge request
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addRecharge(array $data): int {
		$this->ensureTable();

		$amount = (string)(float)$data['amount'];

		$this->db->query("INSERT INTO `" . DB_PREFIX . "usdt_recharge` SET `customer_id` = '" . (int)$data['customer_id'] . "', `network` = '" . $this->db->escape($data['network']) . "', `amount` = '" . $this->db->escape($amount) . "', `txhash` = '" . $this->db->escape($data['txhash']) . "', `note` = '" . $this->db->escape($data['note']) . "', `status` = '" . (int)$data['status'] . "', `transaction_id` = '0', `date_added` = NOW(), `date_modified` = NOW()");

		return $this->db->getLastId();
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
