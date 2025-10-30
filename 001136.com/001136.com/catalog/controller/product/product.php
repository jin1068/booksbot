<?php
namespace Opencart\Catalog\Controller\Product;

use Opencart\System\Library\SpecData;
/**
 * Class Product
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Product extends \Opencart\System\Engine\Controller {
	private const SPEC_MODEL_INDEX = 1;
	/**
	 * Index
	 *
	 * @return ?\Opencart\System\Engine\Action
	 */
	public function index(): ?\Opencart\System\Engine\Action {
		$this->load->language('product/product');

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			$this->document->addStyle('catalog/view/stylesheet/jd-product.css');
			// Plan B: Use enhanced JS only to avoid double event binding
			// $this->document->addScript('catalog/view/javascript/jd-product.js');
			$this->document->addStyle('catalog/view/stylesheet/jd-product-enhanced.css');
			$this->document->addScript('catalog/view/javascript/jd-product-enhanced.js');

			$data['text_price_label'] = $this->language->get('text_price_label');
			$data['text_delivery_label'] = $this->language->get('text_delivery_label');
			$data['text_delivery_default'] = $this->language->get('text_delivery_default');
			$data['text_delivery_select'] = $this->language->get('text_delivery_select');
			$data['text_delivery_login'] = $this->language->get('text_delivery_login');
			$data['text_delivery_hint'] = $this->language->get('text_delivery_hint');
			$data['text_option_color'] = $this->language->get('text_option_color');
			$data['text_option_storage'] = $this->language->get('text_option_storage');
			$data['text_media_placeholder'] = $this->language->get('text_media_placeholder');
			$data['text_address_modal_title'] = $this->language->get('text_address_modal_title');
			$data['text_address_modal_body'] = $this->language->get('text_address_modal_body');
			$data['button_notify'] = $this->language->get('button_notify');
			$data['button_address_manage'] = $this->language->get('button_address_manage');
			$data['button_address_later'] = $this->language->get('button_address_later');
			$data['notify_message'] = $this->language->get('text_notify_message');

			$data['spec_label_map'] = [
				'price'   => ['US Price', 'Price', '美国价格'],
				'color'   => ['Colour', 'Color', '颜色', '外观'],
				'storage' => ['Storage', '存储容量', '容量']
			];

			$this->document->setTitle($product_info['meta_title']);
			$this->document->setDescription($product_info['meta_description']);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->addLink($this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product_id), 'canonical');

			$media_slug = $this->slugify($product_info['model'] ?: $product_info['name']);
			$media = [
				'video'   => null,
				'poster'  => null,
				'model3d' => null
			];

			$video_path = 'media/video/' . $media_slug . '.mp4';
			$poster_path = 'media/video/' . $media_slug . '.jpg';
			$model_path = 'media/model/' . $media_slug . '.glb';

			if (is_file(DIR_IMAGE . $video_path)) {
				$media['video'] = 'image/' . $video_path . '?v=' . filemtime(DIR_IMAGE . $video_path);
				if (is_file(DIR_IMAGE . $poster_path)) {
					$media['poster'] = 'image/' . $poster_path . '?v=' . filemtime(DIR_IMAGE . $poster_path);
				}
			}

			if (is_file(DIR_IMAGE . $model_path)) {
				$media['model3d'] = 'image/' . $model_path . '?v=' . filemtime(DIR_IMAGE . $model_path);
			}

			$data['media'] = $media;

			$data['model_viewer'] = (bool)$media['model3d'];

			$data['breadcrumbs'] = [];
			$data['spec_compare'] = null;

			try {
				$spec_library = new SpecData();
				$groups = $spec_library->getAll();

				$target_group = null;
				$current_identifier = mb_strtolower($product_info['name']);
				$current_model = mb_strtolower($product_info['model']);

				foreach ($groups as $group) {
					foreach ($group['rows'] as $row_index => $row) {
						$en_name = mb_strtolower(trim($row['en-gb'][self::SPEC_MODEL_INDEX] ?? ''));
						$cn_name = mb_strtolower(trim($row['zh-cn'][self::SPEC_MODEL_INDEX] ?? ''));

						if ($en_name === $current_identifier || $cn_name === $current_identifier || $en_name === $current_model || $cn_name === $current_model) {
							$target_group = $group;
							break 2;
						}
					}
				}

				if ($target_group) {
					$columns = $target_group['columns']['zh-cn'] ?? $target_group['columns']['en-gb'] ?? [];
					$compare_rows = [];

					foreach ($target_group['rows'] as $row) {
						$values = $target_group['columns']['zh-cn'] ? $row['zh-cn'] : $row['en-gb'];

						if (count($values) < count($columns)) {
							$values = array_pad($values, count($columns), '');
						}

						$compare_rows[] = [
							'name'       => $row['zh-cn'][self::SPEC_MODEL_INDEX] ?? $row['en-gb'][self::SPEC_MODEL_INDEX] ?? '',
							'values'     => $values,
							'is_current' => mb_strtolower(trim($row['en-gb'][self::SPEC_MODEL_INDEX] ?? '')) === $current_identifier || mb_strtolower(trim($row['zh-cn'][self::SPEC_MODEL_INDEX] ?? '')) === $current_identifier
						];
					}

					$data['spec_compare'] = [
						'columns' => $columns,
						'rows'    => $compare_rows
					];
				}
			} catch (\Throwable $exception) {
				// Ignore dataset issues silently.
			}

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
			];

			// Category
			$this->load->model('catalog/category');

			if (isset($this->request->get['path'])) {
				$path = '';

				$parts = explode('_', (string)$this->request->get['path']);

				$category_id = (int)array_pop($parts);

				foreach ($parts as $path_id) {
					if (!$path) {
						$path = $path_id;
					} else {
						$path .= '_' . $path_id;
					}

					$category_info = $this->model_catalog_category->getCategory((int)$path_id);

					if ($category_info) {
						$data['breadcrumbs'][] = [
							'text' => $category_info['name'],
							'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $path)
						];
					}
				}

				// Set the last category breadcrumb
				$category_info = $this->model_catalog_category->getCategory($category_id);

				if ($category_info) {
					$url = '';

					if (isset($this->request->get['sort'])) {
						$url .= '&sort=' . $this->request->get['sort'];
					}

					if (isset($this->request->get['order'])) {
						$url .= '&order=' . $this->request->get['order'];
					}

					if (isset($this->request->get['page'])) {
						$url .= '&page=' . $this->request->get['page'];
					}

					if (isset($this->request->get['limit'])) {
						$url .= '&limit=' . $this->request->get['limit'];
					}

					$data['breadcrumbs'][] = [
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . $url)
					];
				}
			}

			// Manufacturer
			$this->load->model('catalog/manufacturer');

			if (isset($this->request->get['manufacturer_id'])) {
				$data['breadcrumbs'][] = [
					'text' => $this->language->get('text_brand'),
					'href' => $this->url->link('product/manufacturer', 'language=' . $this->config->get('config_language'))
				];

				$url = '';

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['page'])) {
					$url .= '&page=' . $this->request->get['page'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);

				if ($manufacturer_info) {
					$data['breadcrumbs'][] = [
						'text' => $manufacturer_info['name'],
						'href' => $this->url->link('product/manufacturer.info', 'language=' . $this->config->get('config_language') . '&manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
					];
				}
			}

			if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
				$url = '';

				if (isset($this->request->get['search'])) {
					$url .= '&search=' . $this->request->get['search'];
				}

				if (isset($this->request->get['tag'])) {
					$url .= '&tag=' . $this->request->get['tag'];
				}

				if (isset($this->request->get['description'])) {
					$url .= '&description=' . $this->request->get['description'];
				}

				if (isset($this->request->get['category_id'])) {
					$url .= '&category_id=' . $this->request->get['category_id'];
				}

				if (isset($this->request->get['sub_category'])) {
					$url .= '&sub_category=' . $this->request->get['sub_category'];
				}

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['page'])) {
					$url .= '&page=' . $this->request->get['page'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$data['breadcrumbs'][] = [
					'text' => $this->language->get('text_search'),
					'href' => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . $url)
				];
			}

			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = [
				'text' => $product_info['name'],
				'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . $url . '&product_id=' . $product_id)
			];

			$this->document->setTitle($product_info['meta_title']);
			$this->document->setDescription($product_info['meta_description']);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->addLink($this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product_id), 'canonical');

			$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
			$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');

			$data['heading_title'] = $product_info['name'];

			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', 'language=' . $this->config->get('config_language')), $this->url->link('account/register', 'language=' . $this->config->get('config_language')));
			$data['text_reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);

			$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

			$data['error_upload_size'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size'));

			$data['config_file_max_size'] = ((int)$this->config->get('config_file_max_size') * 1024 * 1024);

			$this->session->data['upload_token'] = oc_token(32);

			$data['upload'] = $this->url->link('tool/upload', 'language=' . $this->config->get('config_language') . '&upload_token=' . $this->session->data['upload_token']);

			$data['product_id'] = $product_id;

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

			if ($manufacturer_info) {
				$data['manufacturer'] = $manufacturer_info['name'];
			} else {
				$data['manufacturer'] = '';
			}

			$data['manufacturers'] = $this->url->link('product/manufacturer.info', 'language=' . $this->config->get('config_language') . '&manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];

			$data['product_codes'] = [];

			$results = $this->model_catalog_product->getCodes($product_id);

			foreach ($results as $result) {
				if ($result['status']) {
					$data['product_codes'][] = $result;
				}
			}

			$data['reward'] = $product_info['reward'];
			$data['points'] = $product_info['points'];
			$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');

			// Stock Status
			if ($product_info['quantity'] <= 0) {
				$stock_status_id = $product_info['stock_status_id'];
			} elseif (!$this->config->get('config_stock_display')) {
				$stock_status_id = (int)$this->config->get('config_stock_status_id');
			} else {
				$stock_status_id = 0;
			}

			// Stock Status
			$this->load->model('localisation/stock_status');

			$stock_status_info = $this->model_localisation_stock_status->getStockStatus($stock_status_id);

			if ($stock_status_info) {
				$data['stock'] = $stock_status_info['name'];
			} else {
				$data['stock'] = $product_info['quantity'];
			}

			$data['rating'] = (int)$product_info['rating'];
			$data['review_status'] = (int)$this->config->get('config_review_status');
			$data['review'] = $this->load->controller('product/review');

			$data['wishlist_add'] = $this->url->link('account/wishlist.add', 'language=' . $this->config->get('config_language'));
			$data['compare_add'] = $this->url->link('product/compare.add', 'language=' . $this->config->get('config_language'));

			// Image
			$this->load->model('tool/image');

			$main_image = $product_info['image'] ? html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8') : '';
			$is_remote_main = $main_image && preg_match('#^https?://#i', $main_image);

			if ($product_info['image'] && ($is_remote_main || is_file(DIR_IMAGE . $main_image))) {
				if ($is_remote_main) {
					$data['popup'] = $product_info['image'];
					$data['thumb'] = $product_info['image'];
				} else {
					$data['popup'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'));
					$data['thumb'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_image_thumb_width'), $this->config->get('config_image_thumb_height'));
				}
			} else {
				$data['popup'] = '';
				$data['thumb'] = '';
			}

			$data['images'] = [];

			$results = $this->model_catalog_product->getImages($product_id);

			foreach ($results as $result) {
				if (!$result['image']) {
					continue;
				}

				$image_path = html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8');
				$is_remote_image = $image_path && preg_match('#^https?://#i', $image_path);

				if ($is_remote_image) {
					$data['images'][] = [
						'popup' => $result['image'],
						'thumb' => $result['image']
					];
				} elseif (is_file(DIR_IMAGE . $image_path)) {
					$data['images'][] = [
						'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height')),
						'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('config_image_additional_width'), $this->config->get('config_image_additional_height'))
					];
				}
			}

			$data['picture_group'] = [];

			if (!empty($data['popup'])) {
				$data['picture_group'][] = [
					'popup' => $data['popup'],
					'thumb' => $data['popup'],
					'alt'   => $product_info['name']
				];
			}

			foreach ($data['images'] as $image) {
				$data['picture_group'][] = [
					'popup' => $image['popup'],
					'thumb' => $image['popup'],
					'alt'   => $product_info['name']
				];
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			if ((float)$product_info['special']) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['special'] = false;
			}

			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			$discounts = $this->model_catalog_product->getDiscounts($product_id);

			$data['discounts'] = [];

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				foreach ($discounts as $discount) {
					$data['discounts'][] = ['price' => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])] + $discount;
				}
			}

			$base_original_price = $product_info['upc'] !== '' ? (float)$product_info['upc'] : (float)$product_info['price'];
			$base_discount = $product_info['ean'] !== '' ? (float)$product_info['ean'] : max($base_original_price - (float)$product_info['price'], 0);

			$promotion_summary = [
				['threshold' => 1000, 'discount' => 100],
				['threshold' => 2000, 'discount' => 200],
				['threshold' => 3000, 'discount' => 350]
			];

			$currency_code = $this->session->data['currency'] ?? $this->config->get('config_currency');
			$final_formatted = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $currency_code);
			$original_formatted = $base_original_price > $product_info['price'] ? $this->currency->format($this->tax->calculate($base_original_price, $product_info['tax_class_id'], $this->config->get('config_tax')), $currency_code) : '';
			$discount_formatted = $base_discount > 0 ? $this->currency->format($this->tax->calculate($base_discount, $product_info['tax_class_id'], $this->config->get('config_tax')), $currency_code) : '';
			$discount_label = $discount_formatted ? sprintf($this->language->get('text_discount_save'), $discount_formatted) : '';

			$promotion_items = [];

			foreach ($promotion_summary as $promo) {
				$threshold_formatted = $this->currency->format($promo['threshold'], $currency_code);
				$discount_formatted_item = $this->currency->format($promo['discount'], $currency_code);

				$promotion_items[] = [
					'threshold' => $threshold_formatted,
					'discount'  => $discount_formatted_item,
					'label'     => sprintf($this->language->get('text_promotion_item'), $threshold_formatted, $discount_formatted_item)
				];
			}

			$data['jd_price'] = [
				'final_raw'      => (float)$product_info['price'],
				'original_raw'   => max($base_original_price, (float)$product_info['price']),
				'discount_raw'   => max($base_discount, 0),
				'final'          => $final_formatted,
				'original'       => $original_formatted,
				'discount'       => $discount_formatted,
				'discount_label' => $discount_label,
				'promotion_tag'  => $promotion_items ? implode(' · ', array_column($promotion_items, 'label')) : '',
				'promotion'      => $promotion_items
			];

			$data['options_form'] = [];
			$data['configurator'] = [];

			if ($product_info['master_id']) {
				$master_id = (int)$product_info['master_id'];
			} else {
				$master_id = (int)$this->request->get['product_id'];
			}

			$product_options = $this->model_catalog_product->getOptions($master_id);

			// Plan B: Dynamic option group detection
			$option_groups = [];

			foreach ($product_options as $option) {
				if (!(int)$this->request->get['product_id']) {
					continue;
				}

				$option_entry = [
					'product_option_id' => $option['product_option_id'],
					'option_id'         => $option['option_id'],
					'name'              => $option['name'],
					'type'              => $option['type'],
					'required'          => $option['required'],
					'values'            => []
				];

				foreach ($option['product_option_value'] as $option_value) {
					if ($option_value['subtract'] && !($option_value['quantity'] > 0)) {
						continue;
					}

					$diff_raw = (float)$option_value['price'];

					if ($option_value['price_prefix'] === '-') {
						$diff_raw = -$diff_raw;
					}

					$final_raw = (float)$product_info['price'] + $diff_raw;
					$original_raw = $option_value['points'] ? (float)$option_value['points'] / 100 : $final_raw;
					$discount_raw = $option_value['weight'] ?? 0.0;

					$image = '';
					$full_image = '';

					if ($option_value['image']) {
						$image_path = html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8');
						$is_remote_option_image = $image_path && preg_match('#^https?://#i', $image_path);

						if ($is_remote_option_image) {
							$image = $option_value['image'];
							$full_image = $option_value['image'];
						} elseif (is_file(DIR_IMAGE . $image_path)) {
							$image = $this->model_tool_image->resize($option_value['image'], 80, 80);
							$full_image = 'image/' . ltrim($image_path, '/');

							if (is_file(DIR_IMAGE . $image_path)) {
								$full_image .= '?v=' . filemtime(DIR_IMAGE . $image_path);
							}
						}
					}

					$discount_formatted_option = $discount_raw > 0 ? $this->currency->format($this->tax->calculate($discount_raw, $product_info['tax_class_id'], $this->config->get('config_tax')), $currency_code) : '';
					$discount_label_option = $discount_formatted_option ? sprintf($this->language->get('text_discount_save'), $discount_formatted_option) : '';
					$swatch = [
						'type'  => '',
						'value' => '',
						'label' => $option_value['name']
					];

					if ($image) {
						$swatch = [
							'type'  => 'image',
							'value' => $image,
							'label' => $option_value['name']
						];
					} else {
						$hex = $this->resolveColorHex($option_value['name']);

						if ($hex) {
							$swatch = [
								'type'  => 'color',
								'value' => $hex,
								'label' => $option_value['name']
							];
						} else {
							$initial = trim(mb_substr($option_value['name'], 0, 1));

							if ($initial === '') {
								$initial = '?';
							}

							$swatch = [
								'type'  => 'initial',
								'value' => $initial,
								'label' => $option_value['name']
							];
						}
					}

					$value_data = [
						'product_option_value_id' => $option_value['product_option_value_id'],
						'option_value_id'         => $option_value['option_value_id'],
						'name'                    => $option_value['name'],
						'image'                   => $image,
						'full_image'              => $full_image,
						'quantity'                => $option_value['quantity'],
						'diff_raw'                => $diff_raw,
						'diff'                    => $diff_raw !== 0.0 ? (($diff_raw > 0 ? '+' : '-') . $this->currency->format($this->tax->calculate(abs($diff_raw), $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])) : '',
						'final_raw'               => $final_raw,
						'final'                   => $this->currency->format($this->tax->calculate($final_raw, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
						'original_raw'            => $original_raw,
						'original'                => $original_raw > $final_raw ? $this->currency->format($this->tax->calculate($original_raw, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : '',
						'discount_raw'            => (float)$discount_raw,
						'discount'                => $discount_formatted_option,
						'discount_label'          => $discount_label_option,
						'swatch'                  => $swatch
					];

					$option_entry['values'][] = $value_data + $option_value;
				}

				if (!$option_entry['values']) {
					continue;
				}

				$data['options_form'][] = $option_entry;

				// Plan B: Get option metadata (display_type, option_group)
				$option_group_key = 'other';
				$display_type = 'default';
				
				// Try to get from database (Plan B fields)
				$meta_query = $this->db->query("
					SELECT display_type, option_group, sort_order 
					FROM `" . DB_PREFIX . "product_option` 
					WHERE product_option_id = '" . (int)$option['product_option_id'] . "'
				");
				
				if ($meta_query->num_rows && !empty($meta_query->row['option_group'])) {
					$option_group_key = $meta_query->row['option_group'];
					$display_type = $meta_query->row['display_type'] ?: 'default';
				} else {
					// Fallback: detect by keywords for backward compatibility
					$storage_keywords = ['存储', '內存', '内存', '容量', 'storage', 'capacity', 'memory'];
					$color_keywords = ['颜色', '顏色', 'colour', 'color', '配色', '机身颜色', '机身顏色', '外观颜色', '外觀顏色'];
					$lower_name = mb_strtolower($option['name'], 'UTF-8');
					
					if ($this->optionNameContains($lower_name, $storage_keywords)) {
						$option_group_key = 'storage';
						$display_type = 'button';
					} elseif ($this->optionNameContains($lower_name, $color_keywords)) {
						$option_group_key = 'color';
						$display_type = 'swatch';
					}
				}
				
				// Initialize group if not exists
				if (!isset($option_groups[$option_group_key])) {
					$option_groups[$option_group_key] = [
						'product_option_id' => $option['product_option_id'],
						'label' => $option['name'],
						'display_type' => $display_type,
						'values' => []
					];
				}
				
				// Add values to group
				$option_groups[$option_group_key]['values'] = $option_entry['values'];
				
				// Calculate price range if multiple options
				if (count($option_entry['values']) > 1) {
					$prices = array_column($option_entry['values'], 'final_raw');
					$min_price = min($prices);
					$max_price = max($prices);
					
					if ($min_price < $max_price) {
						$count = count($prices);
						$option_groups[$option_group_key]['price_range'] = $count . ' options from ' . $this->currency->format($min_price, $this->session->data['currency']);
					}
				}
			}

			// Assign to configurator
			$data['configurator'] = $option_groups;

			$data['options'] = $data['options_form'];

			if ($this->customer->isLogged()) {
				$this->load->model('account/address');

				$address_id = $this->customer->getAddressId();
				$default_address = [];

				if ($address_id) {
					$default_address = $this->model_account_address->getAddress($address_id);
				}

				$has_address = !empty($default_address);

				if (!$has_address) {
					$addresses = $this->model_account_address->getAddresses();
					$has_address = !empty($addresses);
				} else {
					$default_address['full'] = trim(($default_address['firstname'] ?? '') . ' ' . ($default_address['lastname'] ?? '') . ' ' . ($default_address['address_1'] ?? '') . ' ' . ($default_address['city'] ?? '') . ' ' . ($default_address['zone'] ?? '') . ' ' . ($default_address['country'] ?? ''));
				}

				$data['address_prompt'] = [
					'has_address'    => $has_address,
					'default'        => $default_address,
					'manage_url'     => $this->url->link('account/address', 'language=' . $this->config->get('config_language')),
					'login_url'      => '',
					'is_logged'      => true
				];
			} else {
				$data['address_prompt'] = [
					'has_address'    => false,
					'default'        => [],
					'manage_url'     => $this->url->link('account/login', 'language=' . $this->config->get('config_language')),
					'login_url'      => $this->url->link('account/login', 'language=' . $this->config->get('config_language')),
					'is_logged'      => false
				];
			}

			// Subscriptions
			$data['subscription_plans'] = [];

			$results = $this->model_catalog_product->getSubscriptions($product_id);

			foreach ($results as $result) {
				$description = '';

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					if ($result['duration']) {
						$price = ($product_info['special'] ?: $product_info['price']) / $result['duration'];
					} else {
						$price = ($product_info['special'] ?: $product_info['price']);
					}

					$price = $this->currency->format($this->tax->calculate($price, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$cycle = $result['cycle'];
					$frequency = $this->language->get('text_' . $result['frequency']);
					$duration = $result['duration'];

					if ($duration) {
						$description = sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
					} else {
						$description = sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
					}
				}

				$data['subscription_plans'][] = ['description' => $description] + $result;
			}

			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			$data['share'] = $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$this->request->get['product_id']);
			$data['button_compare_specs'] = $this->language->get('button_compare_specs');
			$data['text_compare_specs'] = $this->language->get('text_compare_specs');
			$data['text_spec_column'] = $this->language->get('text_spec_column');
			$data['button_close'] = $this->language->get('button_close');

			$data['attribute_groups'] = $this->model_catalog_product->getAttributes($product_id);

			$data['related'] = $this->load->controller('product/related');

			$data['tags'] = [];

			if ($product_info['tag']) {
				$tags = explode(',', $product_info['tag']);

				foreach ($tags as $tag) {
					$data['tags'][] = [
						'tag'  => trim($tag),
						'href' => $this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&tag=' . trim($tag))
					];
				}
			}

			if ($this->config->get('config_product_report_status')) {
				$this->model_catalog_product->addReport($this->request->get['product_id'], oc_get_ip());
			}

			$data['language'] = $this->config->get('config_language');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/product', $data));
		} else {
			return new \Opencart\System\Engine\Action('error/not_found');
		}

		return null;
	}

	protected function optionNameContains(string $haystack, array $keywords): bool {
		foreach ($keywords as $keyword) {
			$keyword = trim((string)$keyword);

			if ($keyword === '') {
				continue;
			}

			$normalized = mb_strtolower($keyword, 'UTF-8');

			if ($normalized !== '' && str_contains($haystack, $normalized)) {
				return true;
			}
		}

		return false;
	}

	protected function resolveColorHex(string $name): string {
		$normalized = mb_strtolower(trim($name), 'UTF-8');

		if ($normalized === '') {
			return '';
		}

		$color_map = [
			'#111827' => ['黑色', '曜石黑', '亮黑', '幻夜黑', '午夜黑', '远峰黑', 'black', '炭黑'],
			'#f8fafc' => ['白色', '珍珠白', '雪域白', '极光白', '真我白', '白', 'starlight', 'white', '雪白'],
			'#e11d48' => ['红色', '烈焰红', '赤焰', '大红', '红', 'crimson', 'red', 'scarlet'],
			'#f472b6' => ['粉色', '樱花粉', '粉红', '玫瑰金', '粉', 'pink', 'rose'],
			'#2563eb' => ['蓝色', '天蓝', '湖水蓝', '海洋蓝', '蓝', 'blue', 'sierra blue'],
			'#1e3a8a' => ['深蓝', '远峰蓝', 'navy', 'midnight'],
			'#0ea5e9' => ['青色', '青', '青雾', 'cyan', 'aqua', 'teal'],
			'#22c55e' => ['绿色', '松岭绿', '绿', 'green', '森林绿'],
			'#facc15' => ['金色', '香槟金', '曙光金', '金', 'gold', '琥珀'],
			'#94a3b8' => ['银色', '银', '银灰', '银白', '银翼', '银子', 'silver', 'platinum'],
			'#6b7280' => ['灰色', '石墨', '灰', '星空灰', '深空灰', 'graphite', '灰黑', 'grey', 'gray'],
			'#8b5cf6' => ['紫色', '远峰紫', '淡紫', '紫', 'violet', 'purple', 'lavender'],
			'#fb923c' => ['橙色', '曜橙', '琥珀橙', '橙', 'orange', 'amber'],
			'#b45309' => ['棕色', '咖啡色', '古铜', '青铜', 'brown', 'bronze'],
			'#fbbf24' => ['黄色', '金黄', '杏', 'yellow', 'sunny'],
			'#64748b' => ['蓝灰', '石墨蓝', '深空蓝', 'slate', 'slategrey'],
		];

		foreach ($color_map as $hex => $candidates) {
			foreach ($candidates as $candidate) {
				$candidate = mb_strtolower($candidate, 'UTF-8');

				if ($candidate !== '' && str_contains($normalized, $candidate)) {
					return $hex;
				}
			}
		}

		return '';
	}

	protected function slugify(string $value): string {
		$transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

		if ($transliterated !== false && $transliterated !== '') {
			$value = $transliterated;
		}

		$value = strtolower($value);
		$value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
		$value = trim($value, '-');

		return $value ?: 'product';
	}

	/**
	 * Get Option Combination (AJAX for Plan B)
	 * 用于方案B的选项组合检查
	 *
	 * @return void
	 */
	public function getOptionCombination(): void {
		$this->load->language('product/product');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$json = [];

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['options'])) {
			$options = $this->request->post['options'];
		} else {
			$options = [];
		}

		if ($product_id && $options) {
			// 构建组合键
			$combination_keys = [];
			foreach ($options as $option_id => $value_id) {
				$combination_keys[] = 'option_' . $option_id . '_' . $value_id;
			}
			sort($combination_keys);
			$combination_key = implode('_', $combination_keys);

			// 查询组合信息（如果数据库升级完成）
			$query = $this->db->query("
				SELECT 
					combination_id,
					sku,
					quantity,
					price,
					image,
					status
				FROM `" . DB_PREFIX . "product_option_combination`
				WHERE product_id = '" . (int)$product_id . "'
				AND combination_key = '" . $this->db->escape($combination_key) . "'
			");

			if ($query->num_rows) {
				$combination = $query->row;
				$json['success'] = true;
				$json['combination'] = [
					'sku' => $combination['sku'],
					'quantity' => $combination['quantity'],
					'price' => $this->currency->format($combination['price'], $this->session->data['currency']),
					'in_stock' => $combination['quantity'] > 0 && $combination['status'],
					'image' => $combination['image'] ? $this->model_tool_image->resize($combination['image'], 500, 500) : null
				];
			} else {
				// 如果没有找到组合，返回基础信息
				$json['success'] = true;
				$json['combination'] = [
					'message' => 'Using default product settings',
					'in_stock' => true
				];
			}
		} else {
			$json['success'] = false;
			$json['error'] = 'Product ID and options required';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
