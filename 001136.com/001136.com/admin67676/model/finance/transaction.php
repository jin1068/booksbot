<?php
namespace Opencart\Admin\Model\Finance;
/**
 * Class Transaction
 */
class Transaction extends \Opencart\System\Engine\Model {
	/**
	 * Get transaction list
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getTransactions(array $data = []): array {
		$sql = "SELECT ct.*, c.firstname, c.lastname, c.email FROM `" . DB_PREFIX . "customer_transaction` ct LEFT JOIN `" . DB_PREFIX . "customer` c ON (ct.customer_id = c.customer_id)";

		$where = $this->buildWhere($data);

		if ($where) {
			$sql .= " WHERE " . implode(' AND ', $where);
		}

		$sql .= " ORDER BY ct.date_added DESC";

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
	 * Get total transactions
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalTransactions(array $data = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "customer_transaction` ct LEFT JOIN `" . DB_PREFIX . "customer` c ON (ct.customer_id = c.customer_id)";

		$where = $this->buildWhere($data);

		if ($where) {
			$sql .= " WHERE " . implode(' AND ', $where);
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	/**
	 * Build WHERE conditions based on filters
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, string>
	 */
	protected function buildWhere(array $data): array {
		$where = [];

		if (!empty($data['filter_customer'])) {
			$filter = $this->db->escape($data['filter_customer']);
			$where[] = "(CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $filter . "%' OR c.email LIKE '%" . $filter . "%')";
		}

		if (!empty($data['filter_customer_id'])) {
			$where[] = "ct.customer_id = '" . (int)$data['filter_customer_id'] . "'";
		}

		if (!empty($data['filter_description'])) {
			$filter = $this->db->escape($data['filter_description']);
			$where[] = "ct.description LIKE '%" . $filter . "%'";
		}

		if ($data['filter_amount_min'] !== '' && $data['filter_amount_min'] !== null) {
			$where[] = "ct.amount >= '" . (float)$data['filter_amount_min'] . "'";
		}

		if ($data['filter_amount_max'] !== '' && $data['filter_amount_max'] !== null) {
			$where[] = "ct.amount <= '" . (float)$data['filter_amount_max'] . "'";
		}

		if (!empty($data['filter_date_start'])) {
			$where[] = "DATE(ct.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}

		if (!empty($data['filter_date_end'])) {
			$where[] = "DATE(ct.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}

		if (!empty($data['filter_type'])) {
			if ($data['filter_type'] === 'credit') {
				$where[] = "ct.amount > 0";
			} elseif ($data['filter_type'] === 'debit') {
				$where[] = "ct.amount < 0";
			}
		}

		return $where;
	}
}
