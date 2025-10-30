<?php
namespace Opencart\Catalog\Model\Checkout;
/**
 * Class Shipping Method
 *
 * Can be called using $this->load->model('checkout/shipping_method');
 *
 * @package Opencart\Catalog\Model\Checkout
 */
class ShippingMethod extends \Opencart\System\Engine\Model {
	/**
	 * Get Methods
	 *
	 * @param array<string, mixed> $shipping_address array of data
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function getMethods(array $shipping_address): array {
		$this->load->language('checkout/shipping_method');

		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		$dhl_cost   = 35.00;
		$fedex_cost = 32.00;
		$ups_cost   = 30.00;

		return [
			'dhl' => [
				'name'       => $this->language->get('text_shipping_dhl'),
				'quote'      => [
					'express' => [
						'code'         => 'dhl.express',
						'name'         => $this->language->get('text_shipping_dhl'),
						'cost'         => $dhl_cost,
						'tax_class_id' => 0,
						'text'         => $this->currency->format($dhl_cost, $currency)
					]
				],
				'sort_order' => 1,
				'error'      => ''
			],
			'fedex' => [
				'name'       => $this->language->get('text_shipping_fedex'),
				'quote'      => [
					'priority' => [
						'code'         => 'fedex.priority',
						'name'         => $this->language->get('text_shipping_fedex'),
						'cost'         => $fedex_cost,
						'tax_class_id' => 0,
						'text'         => $this->currency->format($fedex_cost, $currency)
					]
				],
				'sort_order' => 2,
				'error'      => ''
			],
			'ups' => [
				'name'       => $this->language->get('text_shipping_ups'),
				'quote'      => [
					'worldwide_saver' => [
						'code'         => 'ups.worldwide_saver',
						'name'         => $this->language->get('text_shipping_ups'),
						'cost'         => $ups_cost,
						'tax_class_id' => 0,
						'text'         => $this->currency->format($ups_cost, $currency)
					]
				],
				'sort_order' => 3,
				'error'      => ''
			]
		];
	}
}
