<?php
namespace Opencart\Admin\Model\Tool;

use Opencart\System\Library\SpecData;

class SpecImport extends \Opencart\System\Engine\Model {
	private const GROUP_TARGET = [
		'apple-iphone-series'               => 'iphone',
		'google-pixel-series'               => 'google',
		'samsung-galaxy-s-series'           => 'samsung',
		'other-series-xiaomi'               => 'phone_other',
		'other-series-huawei'               => 'phone_other',
		'other-series-oneplus'              => 'phone_other',
		'other-series-nothing'              => 'phone_other',
		'other-series-motorola'             => 'phone_other',
		'other-series-realme'               => 'phone_other',
		'other-series-sony'                 => 'phone_other',
		'apple-ipad-series-tablets'         => 'tablet',
		'samsung-galaxy-tab-series-tablets' => 'tablet'
	];

	private const TARGET_CATEGORY = [
		'iphone'      => ['parent' => "\xE6\x89\x8B\xE6\x9C\xBA\xE4\xB8\x8E\xE5\xB9\xB3\xE6\x9D\xBF", 'child' => 'iPhone'],
		'samsung'     => ['parent' => "\xE6\x89\x8B\xE6\x9C\xBA\xE4\xB8\x8E\xE5\xB9\xB3\xE6\x9D\xBF", 'child' => "\xE4\xB8\x89\xE6\x98\x9F Galaxy"],
		'google'      => ['parent' => "\xE6\x89\x8B\xE6\x9C\xBA\xE4\xB8\x8E\xE5\xB9\xB3\xE6\x9D\xBF", 'child' => "\xE8\xB0\xB7\xE6\xAD\x8C Pixel"],
		'phone_other' => ['parent' => "\xE6\x89\x8B\xE6\x9C\xBA\xE4\xB8\x8E\xE5\xB9\xB3\xE6\x9D\xBF", 'child' => "\xE5\x85\xB6\xE4\xBB\x96\xE5\x93\x81\xE7\x89\x8C\xE6\x89\x8B\xE6\x9C\xBA"],
		'tablet'      => ['parent' => "\xE6\x89\x8B\xE6\x9C\xBA\xE4\xB8\x8E\xE5\xB9\xB3\xE6\x9D\xBF", 'child' => "\xE5\xB9\xB3\xE6\x9D\xBF\xE7\x94\xB5\xE8\x84\x91"]
	];

	private const OPTION_DEFINITIONS = [
		'color'   => [
			'type'  => 'radio',
			'names' => [
				'en-gb' => 'Color',
				'zh-cn' => "\u{989C}\u{8272}"
			]
		],
		'storage' => [
			'type'  => 'radio',
			'names' => [
				'en-gb' => 'Storage',
				'zh-cn' => "\u{5B58}\u{50A8}\u{5BB9}\u{91CF}"
			]
		]
	];

	private const COLUMN_INDEX = [
		'model'        => 1,
		'color'        => 5,
		'storage'      => 9,
		'us_price'     => 10,
		'version_type' => 11
	];

	private const DISCOUNT_RULES = [
		3000 => 350,
		2000 => 200,
		1000 => 100
	];

	public function run(bool $simulate = false): array {
		$this->load->model('catalog/product');
		$this->load->model('catalog/option');
		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();

		$language_ids = [
			'en-gb' => $languages['en-gb']['language_id'] ?? null,
			'zh-cn' => $languages['zh-cn']['language_id'] ?? null
		];

		if (!$language_ids['en-gb']) {
			throw new \RuntimeException('The en-gb language must be enabled before running the specification import.');
		}

		$this->initialiseLongRunningTask();
		$this->logProgress('Import run started. Simulate=' . ($simulate ? 'yes' : 'no'));

		$category_map = $this->resolveTargetCategories($language_ids);
		$option_ids   = $this->setupOptions($language_ids);

		$stock_status_id = (int)($this->config->get('config_stock_status_id') ?? 0);
		$weight_class_id = (int)($this->config->get('config_weight_class_id') ?? 0);
		$length_class_id = (int)($this->config->get('config_length_class_id') ?? 0);

		$stats = [
			'products_created' => 0,
			'products_skipped' => 0,
			'skipped'          => [],
			'warnings'         => [],
			'created_list'     => []
		];

		$spec_data = new SpecData();
		$groups    = $spec_data->getAll();

		$this->logProgress('Dataset loaded. Groups=' . count($groups));

		foreach ($groups as $group) {
			$target_key = self::GROUP_TARGET[$group['slug']] ?? null;

			if (!$target_key || empty($category_map[$target_key])) {
				$this->logProgress('Skipping group ' . ($group['slug'] ?? 'unknown') . ' due to missing category mapping.');
				continue;
			}

			$category_id = $category_map[$target_key];
			$this->logProgress('Processing group ' . ($group['slug'] ?? 'unknown') . ' rows=' . count($group['rows']));

			foreach ($group['rows'] as $row) {
				$this->refreshExecutionBudget();

				$result = $this->createOrSkipProduct(
					$group,
					$row,
					$category_id,
					$language_ids,
					$option_ids,
					$stock_status_id,
					$weight_class_id,
					$length_class_id,
					$simulate
				);

				if ($result['created']) {
					$stats['products_created']++;

					if ($simulate) {
						$stats['created_list'][] = $result['name'];
					}

					$this->logProgress('Created product: ' . $result['name']);
				} else {
					$stats['products_skipped']++;

					if (!empty($result['reason'])) {
						$stats['skipped'][] = [
							'name'        => $result['name'],
							'reason_code' => $result['reason']
						];
					}

					$this->logProgress('Skipped product: ' . ($result['name'] ?? 'unknown') . ' reason=' . ($result['reason'] ?? ''));
				}

				if (!empty($result['warnings'])) {
					$stats['warnings'] = array_merge($stats['warnings'], $result['warnings']);
					$this->logProgress('Warnings: ' . implode('; ', $result['warnings']));
				}
			}
		}

		$this->logProgress('Import finished. Created=' . $stats['products_created'] . ', skipped=' . $stats['products_skipped']);

		return $stats;
	}

	public function preview(): array {
		return $this->run(true);
	}

	protected function resolveTargetCategories(array $language_ids): array {
		$language_id = $language_ids['zh-cn'] ?? $language_ids['en-gb'];
		$cache       = [];

		foreach (self::TARGET_CATEGORY as $key => $path) {
			$parent_id = $this->findCategoryIdByName($path['parent'], 0, (int)$language_id);

			if (!$parent_id) {
				throw new \RuntimeException('Unable to locate parent category: ' . $path['parent']);
			}

			$child_id = $this->findCategoryIdByName($path['child'], $parent_id, (int)$language_id);

			if (!$child_id) {
				throw new \RuntimeException('Unable to locate child category: ' . $path['parent'] . ' / ' . $path['child']);
			}

			$cache[$key] = $child_id;
		}

		return $cache;
	}

	protected function findCategoryIdByName(string $name, int $parent_id, int $language_id): ?int {
		$query = $this->db->query(
			"SELECT c.`category_id`, cd.`name` FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.`category_id` = cd.`category_id`) WHERE cd.`language_id` = '" . (int)$language_id . "' AND cd.`name` = '" . $this->db->escape($name) . "' AND c.`parent_id` = '" . (int)$parent_id . "' LIMIT 1"
		);

		if ($query->num_rows) {
			return (int)$query->row['category_id'];
		}

		$normalised_target = $this->normaliseName($name);

		$query = $this->db->query(
			"SELECT c.`category_id`, cd.`name` FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.`category_id` = cd.`category_id`) WHERE cd.`language_id` = '" . (int)$language_id . "' AND c.`parent_id` = '" . (int)$parent_id . "'"
		);

		foreach ($query->rows as $row) {
			if ($this->normaliseName($row['name']) === $normalised_target) {
				return (int)$row['category_id'];
			}
		}

		return null;
	}

	protected function normaliseName(string $value): string {
		$value = preg_replace('/[\s　]+/u', '', $value) ?? '';

		if (function_exists('mb_strtolower')) {
			return mb_strtolower($value, 'UTF-8');
		}

		return strtolower($value);
	}

	protected function setupOptions(array $language_ids): array {
		$option_ids = [];

		foreach (self::OPTION_DEFINITIONS as $code => $definition) {
			$option_ids[$code] = $this->getOrCreateOption($definition['names'], $definition['type'], $language_ids);
		}

		return $option_ids;
	}

	protected function getOrCreateOption(array $names, string $type, array $language_ids): int {
		$search_name = $names['en-gb'];

		$query = $this->db->query(
			"SELECT o.`option_id` FROM `" . DB_PREFIX . "option` o LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.`option_id` = od.`option_id`) WHERE od.`language_id` = '" . (int)$language_ids['en-gb'] . "' AND od.`name` = '" . $this->db->escape($search_name) . "' LIMIT 1"
		);

		if ($query->num_rows) {
			return (int)$query->row['option_id'];
		}

		$option_description = [];

		foreach ($language_ids as $code => $language_id) {
			if ($language_id) {
				$option_description[$language_id] = [
					'name' => $names[$code] ?? $search_name
				];
			}
		}

		return (int)$this->model_catalog_option->addOption([
			'option_description' => $option_description,
			'type'               => $type,
			'validation'         => '',
			'sort_order'         => 0
		]);
	}

	protected function getOrCreateOptionValue(int $option_id, string $value, array $language_ids): int {
		$value = trim($value);

		$query = $this->db->query(
			"SELECT ov.`option_value_id` FROM `" . DB_PREFIX . "option_value` ov LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (ov.`option_value_id` = ovd.`option_value_id`) WHERE ov.`option_id` = '" . (int)$option_id . "' AND ovd.`language_id` = '" . (int)$language_ids['zh-cn'] . "' AND ovd.`name` = '" . $this->db->escape($value) . "' LIMIT 1"
		);

		if ($query->num_rows) {
			return (int)$query->row['option_value_id'];
		}

		$descriptions = [];

		foreach ($language_ids as $code => $language_id) {
			if (!$language_id) {
				continue;
			}

			$display = $value;

			if ($code === 'en-gb') {
				$display = $this->generateTransliteratedName($value);
			}

			$descriptions[$language_id] = ['name' => $display];
		}

		return (int)$this->model_catalog_option->addValue($option_id, [
			'option_value_description' => $descriptions,
			'image'                    => '',
			'sort_order'               => 0
		]);
	}

	protected function createOrSkipProduct(
		array $group,
		array $row,
		int $category_id,
		array $language_ids,
		array $option_ids,
		int $stock_status_id,
		int $weight_class_id,
		int $length_class_id,
		bool $simulate
	): array {
		$en_row = $row['en-gb'];
		$cn_row = $row['zh-cn'];

		$model_name = trim($en_row[self::COLUMN_INDEX['model']] ?? '');

		if ($model_name === '') {
			return [
				'created'  => false,
				'name'     => '',
				'reason'   => 'missing_name',
				'warnings' => ["\u{89C4}\u{683C}\u{6570}\u{636E}\u{7F3A}\u{5C11}\u{4EA7}\u{54C1}\u{540D}\u{79F0}"]
			];
		}

		$product_exists = $this->db->query(
			"SELECT product_id FROM `" . DB_PREFIX . "product_description` WHERE `language_id` = '" . (int)$language_ids['en-gb'] . "' AND `name` = '" . $this->db->escape($model_name) . "' LIMIT 1"
		);

		if ($product_exists->num_rows) {
			return [
				'created'  => false,
				'name'     => $model_name,
				'reason'   => 'duplicate',
				'warnings' => []
			];
		}

		$color_cn   = $cn_row[self::COLUMN_INDEX['color']] ?? '';
		$storage_cn = $cn_row[self::COLUMN_INDEX['storage']] ?? '';
		$price_str  = $en_row[self::COLUMN_INDEX['us_price']] ?? '';

		$warnings = [];

		if ($color_cn === '') {
			$warnings[] = "\u{89C4}\u{683C}\u{6570}\u{636E}\u{7F3A}\u{5C11}\u{989C}\u{8272}";
		}

		if ($storage_cn === '') {
			$warnings[] = "\u{89C4}\u{683C}\u{6570}\u{636E}\u{7F3A}\u{5C11}\u{5B58}\u{50A8}";
		}

		$price = $this->parsePrice($price_str);

		if ($price === null) {
			$warnings[] = "\u{4EF7}\u{683C}\u{65E0}\u{6548}\u{FF0C}\u{4F7F}\u{7528} 0";
			$price = 0.0;
		}

		$discounted_price = $this->applyDiscount($price);

		if ($simulate) {
			return [
				'created'  => true,
				'name'     => $model_name,
				'reason'   => '',
				'warnings' => $warnings
			];
		}

		$color_value_id   = $this->getOrCreateOptionValue($option_ids['color'], $color_cn, $language_ids);
		$storage_value_id = $this->getOrCreateOptionValue($option_ids['storage'], $storage_cn, $language_ids);

		$product_data = [
			'master_id'           => 0,
			'model'               => $model_name,
			'location'            => '',
			'variant'             => [],
			'override'            => [],
			'quantity'            => 1,
			'minimum'             => 1,
			'subtract'            => true,
			'stock_status_id'     => $stock_status_id,
			'image'               => '',
			'date_available'      => date('Y-m-d'),
			'manufacturer_id'     => 0,
			'shipping'            => true,
			'price'               => $discounted_price,
			'points'              => 0,
			'weight'              => 0.0,
			'weight_class_id'     => $weight_class_id,
			'length'              => 0.0,
			'width'               => 0.0,
			'height'              => 0.0,
			'length_class_id'     => $length_class_id,
			'status'              => true,
			'tax_class_id'        => 0,
			'sort_order'          => 0,
			'product_description' => [],
			'product_category'    => [$category_id],
			'product_filter'      => [],
			'product_related'     => [],
			'product_attribute'   => [],
			'product_option'      => [
				[
					'option_id' => $option_ids['color'],
					'value'     => [$color_value_id => ['option_value_id' => $color_value_id, 'quantity' => 1, 'subtract' => false, 'price' => 0.0, 'price_prefix' => '+', 'points' => 0, 'points_prefix' => '+', 'weight' => 0.0, 'weight_prefix' => '+']]
				],
				[
					'option_id' => $option_ids['storage'],
					'value'     => [$storage_value_id => ['option_value_id' => $storage_value_id, 'quantity' => 1, 'subtract' => false, 'price' => 0.0, 'price_prefix' => '+', 'points' => 0, 'points_prefix' => '+', 'weight' => 0.0, 'weight_prefix' => '+']]
				]
			],
			'product_reward'      => [],
			'product_seo_url'     => [],
			'product_layout'      => [],
			'product_store'       => [0]
		];

		foreach ($language_ids as $code => $language_id) {
			if (!$language_id) {
				continue;
			}

			$row_data = $row[$code];

			$product_data['product_description'][$language_id] = [
				'name'             => trim($row_data[self::COLUMN_INDEX['model']] ?? ''),
				'description'      => '',
				'tag'              => '',
				'meta_title'       => trim($row_data[self::COLUMN_INDEX['model']] ?? ''),
				'meta_description' => '',
				'meta_keyword'     => ''
			];
		}

		$product_id = $this->model_catalog_product->addProduct($product_data);

		return [
			'created'  => true,
			'name'     => $model_name,
			'reason'   => '',
			'warnings' => $warnings
		];
	}

	protected function parsePrice(string $value): ?float {
		$value = trim($value);
		$value = str_replace(['$', ',', ' ', '¥'], '', $value);

		if ($value === '' || !is_numeric($value)) {
			return null;
		}

		return (float)$value;
	}

	protected function applyDiscount(float $price): float {
		if ($price <= 0) {
			return 0.0;
		}

		foreach (self::DISCOUNT_RULES as $threshold => $discount) {
			if ($price >= $threshold) {
				return $price - $discount;
			}
		}

		return $price;
	}

	protected function generateTransliteratedName(string $chinese): string {
		$pinyin_map = [
			"\u{9ed1}\u{8272}" => 'Black',
			"\u{767d}\u{8272}" => 'White',
			"\u{7ea2}\u{8272}" => 'Red',
			"\u{84dd}\u{8272}" => 'Blue',
			"\u{7eff}\u{8272}" => 'Green',
			"\u{7c89}\u{8272}" => 'Pink',
			"\u{7d2b}\u{8272}" => 'Purple',
			"\u{7070}\u{8272}" => 'Gray',
			"\u{91d1}\u{8272}" => 'Gold',
			"\u{94f6}\u{8272}" => 'Silver',
			'64GB'             => '64GB',
			'128GB'            => '128GB',
			'256GB'            => '256GB',
			'512GB'            => '512GB',
			'1TB'              => '1TB'
		];

		return $pinyin_map[$chinese] ?? $chinese;
	}

	protected function initialiseLongRunningTask(): void {
		if (function_exists('set_time_limit')) {
			set_time_limit(0);
		}

		if (function_exists('ini_set')) {
			ini_set('memory_limit', '512M');
		}
	}

	protected function refreshExecutionBudget(): void {
		if (function_exists('set_time_limit')) {
			set_time_limit(120);
		}
	}

	protected function logProgress(string $message): void {
		if (defined('DIR_LOGS')) {
			$log_file = DIR_LOGS . 'spec_import.log';
			$timestamp = date('Y-m-d H:i:s');
			file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND);
		}
	}
}
