<?php
namespace Opencart\Catalog\Controller\Checkout;
/**
 * Class BuyNow
 * Handles immediate purchase (Buy Now) functionality
 *
 * @package Opencart\Catalog\Controller\Checkout
 */
class BuyNow extends \Opencart\System\Engine\Controller {
	/**
	 * Index - Main entry point for Buy Now
	 * Adds product to cart and redirects to payment method selection
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('checkout/buy_now');

		// Check if user is logged in
		if (!$this->customer->isLogged()) {
			// Store the redirect URL in session
			$this->session->data['redirect'] = $this->url->link('checkout/buy_now', 'language=' . $this->config->get('config_language'), true);
			
			// Set error message
			$this->session->data['error'] = $this->language->get('error_login_required');
			
			// Redirect to login
			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language'), true));
		}

		// Get product data from POST
		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['quantity'])) {
			$quantity = (int)$this->request->post['quantity'];
		} else {
			$quantity = 1;
		}

		if (isset($this->request->post['option'])) {
			$option = array_filter($this->request->post['option']);
		} else {
			$option = [];
		}

		// Validate product
		$this->load->model('catalog/product');
		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			$this->session->data['error'] = $this->language->get('error_product_not_found');
			$this->response->redirect($this->url->link('common/home', 'language=' . $this->config->get('config_language'), true));
		}

		// Clear existing cart (Buy Now should replace cart)
		$this->cart->clear();

		// Add product to cart
		$this->cart->add($product_id, $quantity, $option);

		// Redirect to payment method selection
		$this->response->redirect($this->url->link('checkout/buy_now.payment', 'language=' . $this->config->get('config_language'), true));
	}

	/**
	 * Payment Method Selection Page
	 * Shows options: Pay with Balance or Recharge
	 *
	 * @return void
	 */
	public function payment(): void {
		$this->load->language('checkout/buy_now');

		// Check if user is logged in
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('checkout/buy_now.payment', 'language=' . $this->config->get('config_language'), true);
			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language'), true));
		}

		// Check if cart has products
		if (!$this->cart->hasProducts()) {
			$this->response->redirect($this->url->link('common/home', 'language=' . $this->config->get('config_language'), true));
		}

		$this->document->setTitle($this->language->get('heading_payment_method'));

		// Get customer balance
		$this->load->model('account/customer');
		$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
		
		// Get cart total
		$this->load->model('checkout/cart');
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		$this->load->model('setting/extension');
		$results = $this->model_setting_extension->getExtensionsByType('total');

		foreach ($results as $result) {
			if ($this->config->get('total_' . $result['code'] . '_status')) {
				$this->load->model('extension/' . $result['extension'] . '/total/' . $result['code']);

				$this->{'model_extension_' . $result['extension'] . '_total_' . $result['code']}->getTotal($totals, $taxes, $total);
			}
		}

		$sort_order = [];

		foreach ($totals as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $totals);

		// Prepare data
		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_payment_method'),
			'href' => $this->url->link('checkout/buy_now.payment', 'language=' . $this->config->get('config_language'))
		];

		$data['heading_title'] = $this->language->get('heading_payment_method');
		$data['text_payment_method'] = $this->language->get('text_payment_method');
		$data['text_balance'] = $this->language->get('text_balance');
		$data['text_recharge'] = $this->language->get('text_recharge');
		$data['text_current_balance'] = $this->language->get('text_current_balance');
		$data['text_order_total'] = $this->language->get('text_order_total');
		$data['text_insufficient_balance'] = $this->language->get('text_insufficient_balance');
		$data['button_pay_with_balance'] = $this->language->get('button_pay_with_balance');
		$data['button_recharge_now'] = $this->language->get('button_recharge_now');

		// Customer balance
		$data['customer_balance'] = $this->currency->format($customer_info['balance'] ?? 0, $this->session->data['currency']);
		$data['customer_balance_raw'] = (float)($customer_info['balance'] ?? 0);

		// Order total
		$data['order_total'] = $this->currency->format($total, $this->session->data['currency']);
		$data['order_total_raw'] = $total;

		// Check if balance is sufficient
		$data['balance_sufficient'] = ($customer_info['balance'] ?? 0) >= $total;

		// URLs
		$data['pay_with_balance_url'] = $this->url->link('checkout/buy_now.processBalance', 'language=' . $this->config->get('config_language'), true);
		$data['recharge_url'] = $this->url->link('finance/recharge', 'language=' . $this->config->get('config_language'), true);
		$data['cart_url'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);

		// Cart products
		$data['products'] = [];

		foreach ($this->cart->getProducts() as $product) {
			$product_total = 0;

			foreach ($this->cart->getProducts() as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
			}

			$data['products'][] = [
				'product_id' => $product['product_id'],
				'name'       => $product['name'],
				'model'      => $product['model'],
				'option'     => $product['option'],
				'quantity'   => $product['quantity'],
				'price'      => $this->currency->format($product['price'], $this->session->data['currency']),
				'total'      => $this->currency->format($product['total'], $this->session->data['currency']),
				'href'       => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product['product_id'])
			];
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('checkout/buy_now_payment', $data));
	}

	/**
	 * Process payment with balance
	 *
	 * @return void
	 */
	public function processBalance(): void {
		$this->load->language('checkout/buy_now');

		// Check if user is logged in
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('checkout/buy_now.payment', 'language=' . $this->config->get('config_language'), true);
			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language'), true));
		}

		// Check if cart has products
		if (!$this->cart->hasProducts()) {
			$this->response->redirect($this->url->link('common/home', 'language=' . $this->config->get('config_language'), true));
		}

		// Get customer info
		$this->load->model('account/customer');
		$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

		// Calculate order total
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		$this->load->model('setting/extension');
		$results = $this->model_setting_extension->getExtensionsByType('total');

		foreach ($results as $result) {
			if ($this->config->get('total_' . $result['code'] . '_status')) {
				$this->load->model('extension/' . $result['extension'] . '/total/' . $result['code']);
				$this->{'model_extension_' . $result['extension'] . '_total_' . $result['code']}->getTotal($totals, $taxes, $total);
			}
		}

		// Check balance
		if (($customer_info['balance'] ?? 0) < $total) {
			$this->session->data['error'] = $this->language->get('error_insufficient_balance');
			$this->response->redirect($this->url->link('checkout/buy_now.payment', 'language=' . $this->config->get('config_language'), true));
		}

		// Create order
		$this->load->model('checkout/order');

		$order_data = [];
		$order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
		$order_data['store_id'] = $this->config->get('config_store_id');
		$order_data['store_name'] = $this->config->get('config_name');
		$order_data['store_url'] = $this->config->get('config_url');

		$order_data['customer_id'] = $this->customer->getId();
		$order_data['customer_group_id'] = $customer_info['customer_group_id'];
		$order_data['firstname'] = $customer_info['firstname'];
		$order_data['lastname'] = $customer_info['lastname'];
		$order_data['email'] = $customer_info['email'];
		$order_data['telephone'] = $customer_info['telephone'];

		$order_data['payment_firstname'] = $customer_info['firstname'];
		$order_data['payment_lastname'] = $customer_info['lastname'];
		$order_data['payment_company'] = '';
		$order_data['payment_address_1'] = '';
		$order_data['payment_address_2'] = '';
		$order_data['payment_city'] = '';
		$order_data['payment_postcode'] = '';
		$order_data['payment_zone'] = '';
		$order_data['payment_zone_id'] = 0;
		$order_data['payment_country'] = '';
		$order_data['payment_country_id'] = 0;
		$order_data['payment_address_format'] = '';
		$order_data['payment_custom_field'] = [];
		$order_data['payment_method'] = ['name' => $this->language->get('text_balance'), 'code' => 'balance'];

		$order_data['shipping_firstname'] = $customer_info['firstname'];
		$order_data['shipping_lastname'] = $customer_info['lastname'];
		$order_data['shipping_company'] = '';
		$order_data['shipping_address_1'] = '';
		$order_data['shipping_address_2'] = '';
		$order_data['shipping_city'] = '';
		$order_data['shipping_postcode'] = '';
		$order_data['shipping_zone'] = '';
		$order_data['shipping_zone_id'] = 0;
		$order_data['shipping_country'] = '';
		$order_data['shipping_country_id'] = 0;
		$order_data['shipping_address_format'] = '';
		$order_data['shipping_custom_field'] = [];
		$order_data['shipping_method'] = ['name' => $this->language->get('text_balance'), 'code' => 'balance'];

		$order_data['products'] = [];

		foreach ($this->cart->getProducts() as $product) {
			$order_data['products'][] = [
				'product_id' => $product['product_id'],
				'name'       => $product['name'],
				'model'      => $product['model'],
				'option'     => $product['option'],
				'download'   => $product['download'],
				'quantity'   => $product['quantity'],
				'subtract'   => $product['subtract'],
				'price'      => $product['price'],
				'total'      => $product['total'],
				'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
				'reward'     => $product['reward']
			];
		}

		$order_data['vouchers'] = [];
		$order_data['totals'] = $totals;
		$order_data['comment'] = $this->language->get('text_paid_with_balance');
		$order_data['total'] = $total;
		$order_data['language_id'] = $this->config->get('config_language_id');
		$order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
		$order_data['currency_code'] = $this->session->data['currency'];
		$order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
		$order_data['ip'] = $this->request->server['REMOTE_ADDR'] ?? '';
		$order_data['forwarded_ip'] = '';
		$order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'] ?? '';
		$order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'] ?? '';

		// Add order
		$order_id = $this->model_checkout_order->addOrder($order_data);

		// Deduct balance
		$new_balance = ($customer_info['balance'] ?? 0) - $total;
		$this->db->query("UPDATE `" . DB_PREFIX . "customer` SET balance = '" . (float)$new_balance . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		// Add transaction record
		$this->load->model('account/transaction');
		$this->model_account_transaction->addTransaction($this->customer->getId(), $this->language->get('text_order') . ' #' . $order_id, -$total, $order_id);

		// Update order status to complete
		$this->model_checkout_order->addHistory($order_id, $this->config->get('config_order_status_id'), $this->language->get('text_paid_with_balance'), true);

		// Clear cart
		$this->cart->clear();

		// Redirect to success page
		$this->session->data['order_id'] = $order_id;
		$this->response->redirect($this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true));
	}
}

