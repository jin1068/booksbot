<?php
namespace Opencart\Admin\Controller\Catalog;

class SpecImport extends \Opencart\System\Engine\Controller {
	protected array $error = [];

	public function index(): void {
		$this->load->language('catalog/spec_import');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('tool/spec_import');
		$this->load->model('setting/setting');

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->logStatus('Import request received.');
			if (function_exists('session_write_close')) {
				@session_write_close();
			}
			$this->prepareLongRunningTask();
			try {
				$stats = $this->model_tool_spec_import->run();

				$stats_payload = $stats + ['timestamp' => time()];

				$this->model_setting_setting->editSettingValue('spec_import', 'spec_import_last_stats', json_encode($stats_payload, JSON_UNESCAPED_UNICODE));

				$this->session->data['success'] = sprintf($this->language->get('text_success'), $stats['products_created'], $stats['products_skipped']);

				$this->logStatus('Import finished. Created: ' . $stats['products_created'] . ', skipped: ' . $stats['products_skipped']);

				$this->response->redirect($this->url->link('catalog/spec_import', 'user_token=' . $this->session->data['user_token']));

				return;
			} catch (\Throwable $exception) {
				$this->logStatus('Import failed: ' . $exception->getMessage());
				$this->error['warning'] = $exception->getMessage();
			}
		}

		$data['error_warning'] = $this->error['warning'] ?? '';

		if (!empty($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
			],
			[
				'text' => $this->language->get('text_catalog'),
				'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'])
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('catalog/spec_import', 'user_token=' . $this->session->data['user_token'])
			]
		];

		$data['action'] = $this->url->link('catalog/spec_import', 'user_token=' . $this->session->data['user_token']);
		$data['user_token'] = $this->session->data['user_token'];

		$stats_raw = $this->config->get('spec_import_last_stats');

		if ($stats_raw) {
			$stats = json_decode((string)$stats_raw, true);

			if (is_array($stats)) {
				$data['stats'] = [
					'products_created' => (int)($stats['products_created'] ?? 0),
					'products_skipped' => (int)($stats['products_skipped'] ?? 0),
					'skipped'          => $stats['skipped'] ?? [],
					'warnings'         => $stats['warnings'] ?? [],
					'timestamp'        => (int)($stats['timestamp'] ?? 0)
				];

				if (!empty($data['stats']['timestamp'])) {
					$format = $this->language->get('datetime_format') ?? 'Y-m-d H:i';
					$data['stats']['formatted_time'] = date($format, $data['stats']['timestamp']);
				} else {
					$data['stats']['formatted_time'] = '';
				}
			} else {
				$data['stats'] = null;
			}
		} else {
			$data['stats'] = null;
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['button_import'] = $this->language->get('button_import');
		$data['button_preview'] = $this->language->get('button_preview');
		$data['button_close'] = $this->language->get('button_close');
		$data['text_preview_title'] = $this->language->get('text_preview_title');
		$data['text_preview_created'] = $this->language->get('text_preview_created');
		$data['text_preview_skipped'] = $this->language->get('text_preview_skipped');
		$data['text_preview_warnings'] = $this->language->get('text_preview_warnings');
		$data['text_preview_error'] = $this->language->get('text_preview_error');
		$data['text_preview_empty'] = $this->language->get('text_preview_empty');
		$data['text_recent_warnings'] = $this->language->get('text_recent_warnings');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/spec_import', $data));
	}

	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'catalog/spec_import')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function preview(): void {
		$this->load->language('catalog/spec_import');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/spec_import')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('tool/spec_import');

			try {
				$data = $this->model_tool_spec_import->preview();

				$json['data'] = $data;
				$json['data']['skipped'] = [];

				foreach ($data['skipped'] as $item) {
					$json['data']['skipped'][] = [
						'name'   => $item['name'] ?? '',
						'reason' => $this->formatReason($item['reason_code'] ?? '')
					];
				}

				$json['data']['created_list'] = $data['created_list'] ?? [];
			} catch (\Throwable $exception) {
				$json['error'] = $exception->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
	}

	protected function formatReason(string $code): string {
		switch ($code) {
			case 'duplicate':
				return $this->language->get('text_reason_duplicate');
			case 'invalid_storage_price':
				return $this->language->get('text_reason_invalid_storage');
			case 'missing_name':
				return $this->language->get('text_reason_missing_name');
			default:
				return $code ?: '-';
		}
	}

	protected function prepareLongRunningTask(): void {
		if (function_exists('ignore_user_abort')) {
			@ignore_user_abort(true);
		}

		if (function_exists('set_time_limit')) {
			@set_time_limit(0);
		}

		if (ini_get('memory_limit') !== '-1') {
			@ini_set('memory_limit', '1024M');
		}

		$this->logStatus('Long-running task safeguards applied.');
	}

	protected function logStatus(string $message): void {
		$line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

		@file_put_contents(DIR_LOGS . 'spec_import.log', $line, FILE_APPEND);
	}
}
