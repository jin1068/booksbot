<?php
namespace Opencart\Admin\Controller\Startup;
/**
 * Class Error
 *
 * @package Opencart\Admin\Controller\Startup
 */
class Error extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->registry->set('log', new \Opencart\System\Library\Log($this->config->get('config_error_filename') ?: $this->config->get('error_filename')));

		set_error_handler([$this, 'error']);
		set_exception_handler([$this, 'exception']);
	}

	/**
	 * Error
	 *
	 * @param int    $code
	 * @param string $message
	 * @param string $file
	 * @param int    $line
	 *
	 * @return bool
	 */
	public function error(int $code, string $message, string $file, int $line): bool {
		switch ($code) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$error = 'Notice';
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$error = 'Warning';
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$error = 'Fatal Error';
				break;
			default:
				$error = 'Unknown';
				break;
		}

		if ($this->config->get('config_error_log')) {
			$this->log->write('PHP ' . $error . ':  ' . $message . ' in ' . $file . ' on line ' . $line);
		}

		if ($this->config->get('config_error_display')) {
			echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';
		} else {
			header('Location: ' . $this->config->get('error_page'));
			exit();
		}

		return true;
	}

	/**
	 * Exception
	 *
	 * @param \Throwable $e
	 *
	 * @return void
	 */
	public function exception(\Throwable $e): void {
		$output  = 'Error: ' . $e->getMessage() . "\n";
		$output .= 'File: ' . $e->getFile() . "\n";
		$output .= 'Line: ' . $e->getLine() . "\n\n";

		foreach ($e->getTrace() as $key => $trace) {
			$output .= 'Backtrace: ' . $key . "\n";

			if (!empty($trace['file'])) {
				$output .= 'File: ' . $trace['file'] . "\n";
			}

			if (!empty($trace['line'])) {
				$output .= 'Line: ' . $trace['line'] . "\n";
			}

			if (isset($trace['class'])) {
				$output .= 'Class: ' . $trace['class'] . "\n";
			}

			if (isset($trace['function'])) {
				$output .= 'Function: ' . $trace['function'] . "\n";
			}

			$output .= "\n";
		}

		if ($this->config->get('config_error_log')) {
			$this->log->write(trim($output));
		}

		if ($this->config->get('config_error_display')) {
			echo $output;
		} else {
			header('Location: ' . $this->config->get('error_page'));
			exit();
		}
	}
}
