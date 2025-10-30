<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Banner
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Banner extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $setting array of filters
	 *
	 * @return string
	 */
	public function index(array $setting): string {
		static $module = 0;

		$this->load->model('design/banner');
		$this->load->model('tool/image');

		$data['banners'] = [];

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		foreach ($results as $result) {
			$image_path = html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8');
			$mobile_path = isset($result['image_mobile']) ? html_entity_decode($result['image_mobile'], ENT_QUOTES, 'UTF-8') : '';

			if (!$image_path || !is_file(DIR_IMAGE . $image_path)) {
				continue;
			}

		$pc_1x = $this->model_tool_image->resize($image_path, 1920, 780);
		$pc_2x = $this->model_tool_image->resize($image_path, 2560, 1040);

			if ($mobile_path && is_file(DIR_IMAGE . $mobile_path)) {
				$mobile_1x = $this->model_tool_image->resize($mobile_path, 828, 1200);
				$mobile_2x = $this->model_tool_image->resize($mobile_path, 1242, 1800);
			} else {
				$mobile_1x = $pc_1x;
				$mobile_2x = $pc_2x;
			}

			$data['banners'][] = [
				'title'            => $result['title'],
				'link'             => $result['link'],
				'button_text'      => $result['button_text'] ?? '',
				'image_pc_1x'      => $pc_1x,
				'image_pc_2x'      => $pc_2x,
				'image_mobile_1x'  => $mobile_1x,
				'image_mobile_2x'  => $mobile_2x,
				// fallback keys for other components expecting legacy names
				'image'            => $pc_1x,
				'image_mobile'     => $mobile_1x
			];
		}

		if ($data['banners']) {
			$data['module'] = $module++;

			$data['effect'] = $setting['effect'];
			$data['controls'] = $setting['controls'];
			$data['indicators'] = $setting['indicators'];
			$data['items'] = $setting['items'];
			$data['interval'] = $setting['interval'];
			$data['width'] = $setting['width'];
			$data['height'] = $setting['height'];

			return $this->load->view('extension/opencart/module/banner', $data);
		}

		return '';
	}
}
