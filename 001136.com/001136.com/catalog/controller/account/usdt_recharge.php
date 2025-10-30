<?php
namespace Opencart\Catalog\Controller\Account;
/**
 * Class UsdtRecharge
 */
class UsdtRecharge extends \Opencart\System\Engine\Controller {
	/**
	 * @var array<string, string>
	 */
	private array $error = [];
	private \Opencart\System\Library\Log $logger;

	/**
	 * Index
	 */
	public function index(): void {
		$this->load->language('account/usdt_recharge');

		if (!isset($this->logger)) {
			$this->logger = new \Opencart\System\Library\Log('usdt_recharge.log');
		}

		$this->logger->write('Request method: ' . ($this->request->server['REQUEST_METHOD'] ?? 'UNKNOWN'));
		$this->logger->write('POST payload: ' . json_encode($this->request->post, JSON_UNESCAPED_UNICODE));
		$this->logger->write('FILES payload: ' . json_encode($this->request->files));

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/usdt_recharge', 'language=' . $this->config->get('config_language'));

			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language'), true));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$receipt_path = '';

			try {
				$receipt_path = $this->saveReceipt($this->request->files['receipt'] ?? []);
			} catch (\Exception $exception) {
				$this->error['receipt'] = $exception->getMessage();
				$this->error['warning'] = $this->language->get('error_warning');
			}

			if (!$this->error) {
				$this->load->model('account/usdt_recharge');

				$this->model_account_usdt_recharge->addRecharge([
					'customer_id' => (int)$this->customer->getId(),
					'network'     => trim((string)$this->request->post['network']),
					'amount'      => (float)$this->request->post['amount'],
					'txhash'      => trim((string)$this->request->post['txhash']),
					'note'        => $receipt_path,
					'status'      => 0
				]);

				$this->logger->write('Recharge stored successfully. Receipt path: ' . $receipt_path);

				$this->session->data['success'] = $this->language->get('text_success');

				$this->response->redirect($this->url->link('account/usdt_recharge', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']));
			}
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/usdt_recharge', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'])
		];

		$data['success'] = $this->session->data['success'] ?? '';
		unset($this->session->data['success']);

		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['error_network'] = $this->error['network'] ?? '';
		$data['error_amount'] = $this->error['amount'] ?? '';
		$data['error_txhash'] = $this->error['txhash'] ?? '';
		$data['error_receipt'] = $this->error['receipt'] ?? '';

		$data['network'] = $this->request->post['network'] ?? '';
		$data['amount'] = $this->request->post['amount'] ?? '';
		$data['txhash'] = $this->request->post['txhash'] ?? '';

		$default_wallets = [
			[
				'network' => 'TRC20',
				'address' => ''
			],
			[
				'network' => 'BEP20',
				'address' => ''
			],
			[
				'network' => 'ERC20',
				'address' => ''
			]
		];

		$config_wallets = $this->config->get('config_usdt_wallets');

		if ($config_wallets && is_array($config_wallets)) {
			$data['wallets'] = $config_wallets;
		} else {
			$data['wallets'] = $default_wallets;
		}

		$networks = array_unique(array_filter(array_column($data['wallets'], 'network')));
		$data['networks'] = $networks;
		$data['balance'] = $this->currency->format($this->customer->getBalance(), $this->session->data['currency']);
		$data['action'] = $this->url->link('account/usdt_recharge', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);
		$data['back'] = $this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);

		$data['entry_network'] = $this->language->get('entry_network');
		$data['entry_amount'] = $this->language->get('entry_amount');
		$data['entry_txhash'] = $this->language->get('entry_txhash');
		$data['entry_receipt'] = $this->language->get('entry_receipt');
		$data['button_submit'] = $this->language->get('button_submit');
		$data['button_back'] = $this->language->get('button_back');
		$data['text_description'] = $this->language->get('text_description');
		$data['text_wallet_title'] = $this->language->get('text_wallet_title');
		$data['text_wallet_note'] = $this->language->get('text_wallet_note');
		$data['text_steps_title'] = $this->language->get('text_steps_title');
		$data['text_step_1'] = $this->language->get('text_step_1');
		$data['text_step_2'] = $this->language->get('text_step_2');
		$data['text_step_3'] = $this->language->get('text_step_3');
		$data['text_current_balance'] = $this->language->get('text_current_balance');
		$data['text_network_placeholder'] = $this->language->get('text_network_placeholder');
		$data['text_contact_support'] = $this->language->get('text_contact_support');
		$data['text_copy'] = $this->language->get('text_copy');
		$data['text_receipt_hint'] = $this->language->get('text_receipt_hint');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/usdt_recharge', $data));
	}

	/**
	 * Validate form
	 */
	protected function validate(): bool {
		$this->load->language('account/usdt_recharge');

		if (empty($this->request->post['network'])) {
			$this->error['network'] = $this->language->get('error_network');
		}

		$amount = $this->request->post['amount'] ?? '';
		if (!is_numeric($amount) || (float)$amount <= 0) {
			$this->error['amount'] = $this->language->get('error_amount');
		}

		$txhash = trim((string)($this->request->post['txhash'] ?? ''));
		if (oc_strlen($txhash) < 10) {
			$this->error['txhash'] = $this->language->get('error_txhash');
		}

		$receipt = $this->request->files['receipt'] ?? [];

		$error_code = isset($receipt['error']) ? (int)$receipt['error'] : UPLOAD_ERR_NO_FILE;
		$this->logger->write('Receipt error code: ' . $error_code);

		if (empty($receipt['name']) || !isset($receipt['tmp_name']) || $error_code !== UPLOAD_ERR_OK || !is_uploaded_file($receipt['tmp_name'])) {
			switch ($error_code) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$this->error['receipt'] = $this->language->get('error_receipt_size');
					break;
				case UPLOAD_ERR_NO_FILE:
					$this->error['receipt'] = $this->language->get('error_receipt_missing');
					break;
				default:
					$this->error['receipt'] = $this->language->get('error_receipt');
			}
			$this->logger->write('Receipt validation failed: ' . $this->error['receipt']);
		} else {
			$allowed_extensions = ['jpg', 'jpeg', 'png'];
			$extension = strtolower((string)pathinfo($receipt['name'], PATHINFO_EXTENSION));
			$this->logger->write('Receipt extension detected: ' . $extension);

			if (!in_array($extension, $allowed_extensions, true)) {
				$this->error['receipt'] = $this->language->get('error_receipt_type');
				$this->logger->write('Receipt validation failed: ' . $this->error['receipt']);
			}

			$max_size = 5 * 1024 * 1024;
			if ((int)$receipt['size'] > $max_size) {
				$this->error['receipt'] = $this->language->get('error_receipt_size');
				$this->logger->write('Receipt validation failed: ' . $this->error['receipt'] . ' (size=' . (int)$receipt['size'] . ')');
			}
		}

		if ($this->error) {
			$this->error['warning'] = $this->language->get('error_warning');
			$this->logger->write('Validation failed. Errors: ' . json_encode($this->error, JSON_UNESCAPED_UNICODE));
		}

		return !$this->error;
	}

	/**
	 * Save receipt file and return relative path
	 *
	 * @param array<string, mixed> $file
	 *
	 * @return string
	 */
	protected function saveReceipt(array $file): string {
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			$this->logger->write('saveReceipt: uploaded file missing or invalid tmp_name');
			throw new \RuntimeException($this->language->get('error_receipt'));
		}

		$extension = strtolower((string)pathinfo($file['name'], PATHINFO_EXTENSION));
		$allowed_extensions = ['jpg', 'jpeg', 'png'];

		if (!in_array($extension, $allowed_extensions, true)) {
			$this->logger->write('saveReceipt: invalid extension ' . $extension);
			throw new \RuntimeException($this->language->get('error_receipt'));
		}

		$max_size = 5 * 1024 * 1024;

		if ((int)$file['size'] > $max_size) {
			$this->logger->write('saveReceipt: file too large (' . (int)$file['size'] . ')');
			throw new \RuntimeException($this->language->get('error_receipt'));
		}

		$sub_directory = 'usdt_receipts';
		$target_directory = rtrim(DIR_IMAGE, '/\\') . '/' . $sub_directory . '/';

		if (!is_dir($target_directory) && !mkdir($target_directory, 0755, true) && !is_dir($target_directory)) {
			$this->logger->write('saveReceipt: failed to create directory ' . $target_directory);
			throw new \RuntimeException($this->language->get('error_receipt_move'));
		}

		try {
			$random = bin2hex(random_bytes(8));
		} catch (\Exception $exception) {
			$random = bin2hex((string)uniqid('', true));
		}

		$unique_name = 'receipt_' . $this->customer->getId() . '_' . $random . '.' . $extension;
		$target_path = $target_directory . $unique_name;

		if (!move_uploaded_file($file['tmp_name'], $target_path)) {
			$this->logger->write('saveReceipt: move_uploaded_file failed from ' . $file['tmp_name'] . ' to ' . $target_path);
			throw new \RuntimeException($this->language->get('error_receipt_move'));
		}

		$this->logger->write('saveReceipt: saved file to ' . $target_path);

		return $sub_directory . '/' . $unique_name;
	}
}
