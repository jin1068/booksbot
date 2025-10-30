<?php
namespace Opencart\Catalog\Model\Checkout;
/**
 * Class Payment Method
 *
 * Can be called using $this->load->model('checkout/payment_method');
 *
 * @package Opencart\Catalog\Model\Checkout
 */
class PaymentMethod extends \Opencart\System\Engine\Model {
	/**
	 * Get Methods
	 *
	 * @param array<string, mixed> $payment_address array of data
	 *
	 * @return array<string, mixed>
	 */
	public function getMethods(array $payment_address = []): array {
		$this->load->language('checkout/payment_method');

		return [
			'usdt' => [
				'name'       => $this->language->get('text_payment_usdt_title'),
				'option'     => [
					'usdt' => [
						'code' => 'usdt.usdt',
						'name' => $this->language->get('text_payment_usdt_title'),
						'terms' => '',
						'description' => $this->language->get('text_payment_usdt_description')
					]
				],
				'sort_order' => 1,
				'error'      => ''
			]
		];
	}
}
