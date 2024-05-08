<?php
/**
 * Class Pilibaba Button
 *
 * @package Catalog\Controller\Extension\Module
 */
class ControllerExtensionModulePilibabaButton extends Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('extension/module/pilibaba_button');
		$status = true;

		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$status = false;
		}

		if ($status) {
			$data['payment_url'] = $this->url->link('extension/payment/pilibaba/express', '', true);

			return $this->load->view('extension/module/pilibaba_button', $data);
		} else {
			return '';
		}
	}
}
