<?php
namespace Opencart\System\Library;

use RuntimeException;

/**
 * Loads the bilingual specification dataset from tab-delimited sources.
 */
class SpecData {
	protected const CN_PATH = DIR_SYSTEM . 'library/spec_data_cn.txt';
	protected const EN_PATH = DIR_SYSTEM . 'library/spec_data_en.txt';

	/**
	 * Cached dataset indexed by slug.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $cache = [];

	/**
	 * Return the full dataset grouped by brand/series slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function getAll(): array {
		if (!$this->cache) {
			$this->cache = $this->buildDataset();
		}

		return $this->cache;
	}

	/**
	 * Return a single group by slug.
	 *
	 * @param string $slug
	 *
	 * @return array<string, mixed>|null
	 */
	public function getGroup(string $slug): ?array {
		$all = $this->getAll();

		return $all[$slug] ?? null;
	}

	/**
	 * Build dataset structure.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function buildDataset(): array {
		$cn_table = $this->parseFile(self::CN_PATH);
		$en_table = $this->parseFile(self::EN_PATH);

		if (count($cn_table['rows']) !== count($en_table['rows'])) {
			throw new RuntimeException('Spec dataset row mismatch between Chinese and English tables.');
		}

		$result = [];

		foreach ($cn_table['rows'] as $index => $cn_row) {
			$en_row = $en_table['rows'][$index];

			if (count($cn_row) !== count($cn_table['header'])) {
				throw new RuntimeException('Chinese row column mismatch at index ' . $index);
			}

			if (count($en_row) !== count($en_table['header'])) {
				throw new RuntimeException('English row column mismatch at index ' . $index);
			}

			$brand_cn = $cn_row[0];
			$brand_en = $en_row[0];
			$slug = $this->slugify($brand_en);

			if (!isset($result[$slug])) {
				$result[$slug] = [
					'slug'    => $slug,
					'brand'   => [
						'zh-cn' => $brand_cn,
						'en-gb' => $brand_en
					],
					'columns' => [
						'zh-cn' => $cn_table['header'],
						'en-gb' => $en_table['header']
					],
					'rows'    => []
				];
			}

			$result[$slug]['rows'][] = [
				'zh-cn' => $cn_row,
				'en-gb' => $en_row,
				'index' => $index
			];
		}

		return $result;
	}

	/**
	 * Parse a TSV file.
	 *
	 * @param string $path
	 *
	 * @return array{header: array<int, string>, rows: array<int, array<int, string>>}
	 */
	protected function parseFile(string $path): array {
		if (!is_file($path)) {
			throw new RuntimeException('Spec data file missing: ' . $path);
		}

		$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if (!$lines) {
			throw new RuntimeException('Spec data file empty: ' . $path);
		}

		$header = $this->splitRow(array_shift($lines));
		$rows = [];
		$previous_first = '';

		foreach ($lines as $line) {
			$cells = $this->splitRow($line);

			if (count($cells) < count($header)) {
				$cells = array_pad($cells, count($header), '');
			}

			if ($cells[0] === '' && $previous_first !== '') {
				$cells[0] = $previous_first;
			} else {
				$previous_first = $cells[0];
			}

			$rows[] = $cells;
		}

		return [
			'header' => $header,
			'rows'   => $rows
		];
	}

	/**
	 * Split a TSV row into cells.
	 *
	 * @param string $line
	 *
	 * @return array<int, string>
	 */
	protected function splitRow(string $line): array {
		$cells = explode("\t", $line);

		return array_map(static fn(string $value): string => trim($value), $cells);
	}

	/**
	 * Create a slug for indexing.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function slugify(string $value): string {
		$value = strtolower($value);
		$value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
		$value = trim($value, '-');

		return $value ?: 'group-' . substr(md5($value), 0, 8);
	}
}
