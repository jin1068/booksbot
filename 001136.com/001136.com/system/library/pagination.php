<?php
namespace Opencart\System\Library;
/**
 * Class Pagination
 *
 * Simple pagination component compatible with OpenCart controllers.
 */
class Pagination {
	/**
	 * @var int
	 */
	public int $total = 0;

	/**
	 * @var int
	 */
	public int $page = 1;

	/**
	 * @var int
	 */
	public int $limit = 20;

	/**
	 * @var string
	 */
	public string $url = '';

	/**
	 * Render pagination links
	 *
	 * @return string
	 */
	public function render(): string {
		if ($this->limit <= 0 || $this->total <= $this->limit) {
			return '';
		}

		$total_pages = (int)ceil($this->total / $this->limit);

		if ($total_pages <= 1) {
			return '';
		}

		$current_page = $this->page;

		if ($current_page < 1) {
			$current_page = 1;
		} elseif ($current_page > $total_pages) {
			$current_page = $total_pages;
		}

		$num_links = 5;
		$url = str_replace('&amp;', '&', $this->url);

		$output = '<nav aria-label="Pagination"><ul class="pagination">';

		// Previous button
		if ($current_page > 1) {
			$output .= '<li class="page-item"><a class="page-link" href="' . $this->buildLink($current_page - 1, $url) . '">&laquo;</a></li>';
		} else {
			$output .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
		}

		$start = $current_page - (int)floor($num_links / 2);
		$end = $current_page + (int)floor($num_links / 2);

		if ($start < 1) {
			$end += 1 - $start;
			$start = 1;
		}

		if ($end > $total_pages) {
			$start -= $end - $total_pages;
			$end = $total_pages;
		}

		if ($start < 1) {
			$start = 1;
		}

		for ($i = $start; $i <= $end; $i++) {
			if ($i == $current_page) {
				$output .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
			} else {
				$output .= '<li class="page-item"><a class="page-link" href="' . $this->buildLink($i, $url) . '">' . $i . '</a></li>';
			}
		}

		// Next button
		if ($current_page < $total_pages) {
			$output .= '<li class="page-item"><a class="page-link" href="' . $this->buildLink($current_page + 1, $url) . '">&raquo;</a></li>';
		} else {
			$output .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
		}

		$output .= '</ul></nav>';

		return $output;
	}

	/**
	 * Construct page link by replacing placeholder
	 *
	 * @param int    $page
	 * @param string $url
	 *
	 * @return string
	 */
	protected function buildLink(int $page, string $url): string {
		return str_replace('{page}', (string)$page, $url);
	}
}
