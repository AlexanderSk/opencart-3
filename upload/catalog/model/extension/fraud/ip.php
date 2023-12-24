<?php
/**
 * Class Ip
 *
 * @package Catalog\Model\Extension\Fraud
 */
class ModelExtensionFraudIp extends Model {
	/**
	 * Check
	 *
	 * @param array $order_info
	 *
	 * @return int
	 */
	public function check(array $order_info): int {
		$status = false;

		if ($order_info['customer_id']) {
			// Customers
			$this->load->model('account/customer');

			$results = $this->model_account_customer->getIps($order_info['customer_id']);

			foreach ($results as $result) {
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "fraud_ip` WHERE `ip` = '" . $this->db->escape($result['ip']) . "'");

				if ($query->num_rows) {
					$status = true;
					break;
				}
			}
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "fraud_ip` WHERE `ip` = '" . $this->db->escape($order_info['ip']) . "'");

			if ($query->num_rows) {
				$status = true;
			}
		}

		if ($status) {
			return (int)$this->config->get('fraud_ip_order_status_id');
		} else {
			return 0;
		}
	}
}
