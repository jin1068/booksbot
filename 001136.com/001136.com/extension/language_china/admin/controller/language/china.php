<?php
namespace Opencart\Admin\Controller\Extension\Languagechina\Language;
class china extends \Opencart\System\Engine\Controller {

	private $extensionPath         = 'extension/language_china/language/china';
	private $extensionDescription  = 'china 翻译-简体中文';
	private $extensionVersion      = '4.0.2.1';
	private $extensionCopy         = true;
	private $extensionMaintenance  = false;
	private $extensionTest         = false;

	public function index(): void {
		$this->load->language($this->extensionPath);

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=language')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->extensionPath, 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link($this->extensionPath . '.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=language');

		$data['language_china_status'] = $this->config->get('language_china_status');

		$this->load->model('localisation/language');

		$language_info = $this->model_localisation_language->getLanguageByCode('zh-cn');

		if ($language_info) {
			$data['entry_language_name'] = $language_info['name'];
			$data['language_china_cn_at_status'] = $language_info['status'];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->extensionPath, $data));
	}

	public function save(): void {
		$this->load->language($this->extensionPath);

		if (!$this->user->hasPermission('modify', $this->extensionPath)) {

			$json['error'] = $this->language->get('error_permission');
				
			if (isset($this->extensionMaintenance)) {
				$this->log->write($json['error']);				
			}

			return;
		}

		$json = [];

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('language_china', $this->request->post);

			// Update languages
			$this->load->model('localisation/language');

			// china (AT)
			$language_info = $this->model_localisation_language-> getLanguageByCode('zh-cn');

			$language_info['status'] = (empty($this->request->post['language_china_cn_at_status']) ? '0' : '1');

			$this->model_localisation_language->editLanguage($language_info['language_id'], $language_info);

			$json['success'] = $this->language->get('text_success');
		}

		if (isset($this->extensionMaintenance)) {
			$this->log->write('Extension: ' . $this->extensionDescription . ' config data saved.');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->language($this->extensionPath);

		if (!$this->user->hasPermission('modify', 'extension/language')) {

			$json['error'] = $this->language->get('error_permission');
				
			if (isset($this->extensionMaintenance)) {
				$this->log->write('警告：此用户无权修改扩展名/语言扩展名.');				
			}

			return;
		}

		$this->load->model('localisation/language');

		// find china language (zh-cn)
		$language_info = $this->model_localisation_language->getLanguageByCode('zh-cn');

		if (!$language_info) {
			// Add language
			$language_data = [
				'name'       => '简体中文',
				'code'       => 'zh-cn',
				'locale'     => 'zh-cn,zh_cn',
				'extension'  => 'language_china',
				'status'     => 1,
				'sort_order' => 2
			];

			$this->load->model('localisation/language');

			$this->model_localisation_language->addLanguage($language_data);

			$this->log->write('信息：增加了简体中文的语言本地化数据.');
		} else {
			$this->log->write('信息：语言本地化数据简体中文已经存在.');		
		}
		
		if (isset($this->extensionCopy)) {
			// Copy translation files for extension/opencart
			$extension_folder = implode('', glob(DIR_EXTENSION));

			// Source and destination of translation files
			$source = $extension_folder . 'language_china/extension/opencart/';
			$destination = $extension_folder . 'opencart/';

			if (isset($this->extensionMaintenance)) {
				$this->log->write('翻译扩展/开放源码文件夹: (' . $source . ')');
				$this->log->write('翻译扩展名/打开目的地文件夹: (' . $destination . ')');
			}

			if ((is_dir($source)) && (is_dir($destination))) {
				$this->custom_copy($source, $destination);
			} else {
				$this->log->write('警告：翻译源或目标文件夹不存在!');
			}
		}
		
		if (isset($this->extensionMaintenance)) {	
			$this->log->write('Extension: ' . $this->extensionDescription . ' 安装过程已完成.');
		}
	}

	public function uninstall(): void {
		$this->load->language($this->extensionPath);

		if (!$this->user->hasPermission('modify', 'extension/language')) {

			$json['error'] = $this->language->get('error_permission');
				
			if (isset($this->extensionMaintenance)) {
				$this->log->write('警告：此用户无权修改扩展名/语言扩展名.');				
			}

			return;
		}

		$this->load->model('localisation/language');

		// china (zh-cn)
		$language_info = $this->model_localisation_language->getLanguageByCode('zh-cn');

		// deleteLanguage is only active in extensionTest mode 
		if (($language_info) && (isset($this->extensionTest))) {
			$this->model_localisation_language->deleteLanguage($language_info['language_id']);
		}
	}

	private function custom_copy($src, $dst) : void { 
		// open the source directory
		$dir = opendir($src); 
	  
		// Make the destination directory if not exist
		// @mkdir($dst); 
		if(!is_dir($dst))
		{
			mkdir($dst, 0755);
		}

		// Loop through the files in source directory
		while( $file = readdir($dir) ) { 

			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					// Recursively calling custom copy function for sub directory 
					$this->custom_copy($src . '/' . $file, $dst . '/' . $file); 
				} else { 
					copy($src . '/' . $file, $dst . '/' . $file); 
				}
			}
		}

		closedir($dir);
	}
}
