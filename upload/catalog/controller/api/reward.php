<?php
/**
 * Class Reward
 *
 * @package Catalog\Controller\Api
 */
class ControllerApiReward extends Controller {
	/**
	 * @return void
	 */
	public function index(): void {
		$this->load->language('api/reward');

		// Delete past reward in case there is an error
		unset($this->session->data['reward']);

		$json = [];

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$points = $this->customer->getRewardPoints();

			$points_total = 0;

			foreach ($this->cart->getProducts() as $product) {
				if ($product['points']) {
					$points_total += $product['points'];
				}
			}

			if (empty($this->request->post['reward'])) {
				$json['error'] = $this->language->get('error_reward');
			}

			if ($this->request->post['reward'] > $points) {
				$json['error'] = sprintf($this->language->get('error_points'), $this->request->post['reward']);
			}

			if ($this->request->post['reward'] > $points_total) {
				$json['error'] = sprintf($this->language->get('error_maximum'), $points_total);
			}

			if (!$json) {
				$this->session->data['reward'] = abs($this->request->post['reward']);

				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Maximum
	 *
	 * @return void
	 */
	public function maximum(): void {
		$this->load->language('api/reward');

		$json = [];

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json['maximum'] = 0;

			foreach ($this->cart->getProducts() as $product) {
				if ($product['points']) {
					$json['maximum'] += $product['points'];
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Available
	 *
	 * @return void
	 */
	public function available(): void {
		$this->load->language('api/reward');

		$json = [];

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$json['points'] = $this->customer->getRewardPoints();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
