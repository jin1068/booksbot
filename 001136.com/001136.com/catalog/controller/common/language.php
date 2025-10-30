<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Language
 *
 * Can be called from $this->load->controller('common/language');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Language extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/language');

		$data['action'] = $this->url->link('common/language.save', 'language=' . $this->config->get('config_language'));

		$data['code'] = $this->request->get['language'] ?? $this->config->get('config_language');

		$data['languages'] = [];

		$this->load->model('localisation/language');

		$results = $this->model_localisation_language->getLanguages();

	foreach ($results as $result) {
		if ($result['status']) {
			$data['languages'][$result['code']] = $result;
		}
	}

	$code = $data['code'];

	// 如果当前语言未启用，使用第一个可用语言
	if (!isset($data['languages'][$code]) && !empty($data['languages'])) {
		$code = array_key_first($data['languages']);
		$data['code'] = $code;
	}

	// 检查语言是否存在，避免"语言不可用"错误
	if (isset($data['languages'][$code])) {
		$data['name'] = $data['languages'][$code]['name'];
		$data['image'] = $data['languages'][$code]['image'];
	} else {
		// 如果没有可用语言，返回空视图
		return '';
	}

	// Build the url - 使用当前完整URL以避免SEO重写干扰
	$current_url = '';
	
	// 首先尝试获取当前实际访问的URL
	$config_url = $this->config->get('config_url');
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	
	if ($request_uri) {
		// 使用实际访问的URI构建完整URL
		$current_url = rtrim($config_url, '/') . $request_uri;
	} elseif (!empty($_SERVER['HTTP_REFERER'])) {
		// 回退到 HTTP_REFERER
		$referer = $_SERVER['HTTP_REFERER'];
		
		// 只有当 referer 来自同域名时才使用
		if (strpos($referer, $config_url) === 0) {
			$current_url = $referer;
		}
	}
	
	// 如果以上方法都失败，回退到构建URL
	if (!$current_url) {
		$url_data = $this->request->get;

		if (isset($url_data['route'])) {
			$route = $url_data['route'];
		} else {
			$route = $this->config->get('action_default');
		}

		unset($url_data['route']);
		unset($url_data['_route_']);
		unset($url_data['language']);

		$query = '';

		if ($url_data) {
			$query = '&' . urldecode(http_build_query($url_data, '', '&'));
		}

		$current_url = HTTP_SERVER . 'index.php?route=' . $route . $query;
	}
	
	// 为每个语言生成redirect（在当前URL基础上更改language参数）
	foreach ($data['languages'] as $language_code => &$language) {
		$lang_url = $current_url;
		
		// 解析URL并更新language参数
		$url_parts = parse_url($lang_url);
		$query_params = [];
		
		if (!empty($url_parts['query'])) {
			parse_str($url_parts['query'], $query_params);
		}
		
		// 更新或添加language参数
		$query_params['language'] = $language_code;
		
		// 重新构建URL，保留原始路径
		$redirect_url = ($url_parts['scheme'] ?? 'https') . '://' . ($url_parts['host'] ?? '001136.com');
		
		if (!empty($url_parts['port']) && !in_array($url_parts['port'], [80, 443])) {
			$redirect_url .= ':' . $url_parts['port'];
		}
		
		$redirect_url .= $url_parts['path'] ?? '/';
		
		if ($query_params) {
			$redirect_url .= '?' . http_build_query($query_params);
		}
		
		// 由于锚点会在切换语言后导致页面跳转定位，这里不再附加 fragment。
		
		$language['redirect'] = $redirect_url;
	}

	unset($language);

	$data['redirect'] = $data['languages'][$code]['redirect'];

	return $this->load->view('common/language', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('common/language');

		$json = [];

		$required = [
			'code'     => '',
			'redirect' => ''
		];

		$post_info = $this->request->post + $required;

		// 如果前端未显式提交code，则尝试从redirect参数中解析
		if ($post_info['code'] === '' && $post_info['redirect'] !== '') {
			$url_info = parse_url(html_entity_decode($post_info['redirect'], ENT_QUOTES, 'UTF-8')) ?: [];

			if (!empty($url_info['query'])) {
				$query = [];
				parse_str($url_info['query'], $query);

				if (!empty($query['language'])) {
					$post_info['code'] = (string)$query['language'];
				}
			}
		}

		$this->load->model('localisation/language');

		$language_info = $this->model_localisation_language->getLanguageByCode($post_info['code']);

		if (!$language_info) {
			$json['error'] = $this->language->get('error_language');
		}

		if (!$json) {
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);

			$config_url = rtrim($this->config->get('config_url'), '/');
			$config_host = parse_url($config_url, PHP_URL_HOST);
			$config_scheme = parse_url($config_url, PHP_URL_SCHEME) ?? 'https';
			$config_port = parse_url($config_url, PHP_URL_PORT);

			$redirect = '';

		if (!empty($post_info['redirect'])) {
			$raw_redirect = urldecode(html_entity_decode($post_info['redirect'], ENT_QUOTES, 'UTF-8'));

			// Normalise to absolute URL on the current domain.
			if (!preg_match('#^https?://#i', $raw_redirect)) {
				$raw_redirect = $config_url . '/' . ltrim($raw_redirect, '/');
			}

			$url_info = parse_url($raw_redirect) ?: [];
			$target_host = $url_info['host'] ?? $config_host;

			if ($target_host && $config_host && strcasecmp($target_host, $config_host) === 0) {
				$query = [];

				if (!empty($url_info['query'])) {
					parse_str($url_info['query'], $query);
				}

				// 移除旧的语言参数和SEO路由参数
				unset($query['language'], $query['_route_']);
				
				// 设置新的语言参数
				$query['language'] = $post_info['code'];

				$scheme = $url_info['scheme'] ?? $config_scheme;
				$host = $config_host;
				$port = $url_info['port'] ?? $config_port;
				$path = $url_info['path'] ?? '/';

				$redirect = $scheme . '://' . $host;

				if ($port && !in_array((int)$port, [80, 443], true)) {
					$redirect .= ':' . $port;
				}

				$redirect .= $path;

				// 如果有查询参数，添加到URL
				if (!empty($query)) {
					$query_string = http_build_query($query);
					$redirect .= '?' . str_replace('%2F', '/', $query_string);
				}
				
				// 不保留hash片段，避免语言切换时跳转到锚点位置（如 #lucky-purchase）
				// 这样可以确保语言切换后停留在页面当前位置，而不是跳转到特定锚点

			}
		}

			if (!$redirect) {
				$route = $this->config->get('action_default');
				$redirect = $config_url . '/index.php?route=' . $route . '&language=' . rawurlencode($post_info['code']);
			}

			if (!str_starts_with($redirect, $config_url)) {
				$route = $this->config->get('action_default');
				$redirect = $config_url . '/index.php?route=' . $route . '&language=' . rawurlencode($post_info['code']);
			}

			$json['redirect'] = $redirect;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}
}
