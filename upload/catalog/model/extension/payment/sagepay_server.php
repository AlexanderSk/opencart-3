<?php
/**
 * Class Sagepay Server
 *
 * @package Catalog\Model\Extension\Payment
 */
class ModelExtensionPaymentSagePayServer extends Model {
	/**
	 * getMethod
	 *
	 * @param array $address
	 *
	 * @return array
	 */
	public function getMethod(array $address): array {
		$this->load->language('extension/payment/sagepay_server');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_sagepay_server_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		if (!$this->config->get('payment_sagepay_server_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$method_data = [
				'code'       => 'sagepay_server',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_sagepay_server_sort_order')
			];
		}

		return $method_data;
	}

	/**
	 * getCards
	 *
	 * @param int $customer_id
	 *
	 * @return array
	 */
	public function getCards(int $customer_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sagepay_server_card` WHERE `customer_id` = '" . (int)$customer_id . "'");

		$card_data = [];

		// Addresses
		$this->load->model('account/address');

		foreach ($query->rows as $row) {
			$card_data[] = [
				'card_id'     => $row['card_id'],
				'customer_id' => $row['customer_id'],
				'token'       => $row['token'],
				'digits'      => '**** ' . $row['digits'],
				'expiry'      => $row['expiry'],
				'type'        => $row['type'],
			];
		}

		return $card_data;
	}

	/**
	 * getCard
	 *
	 * @param string $card_id
	 * @param string $token
	 *
	 * @return array
	 */
	public function getCard(string $card_id, string $token): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sagepay_server_card` WHERE (`card_id` = '" . $this->db->escape($card_id) . "' OR `token` = '" . $this->db->escape($token) . "') AND `customer_id` = '" . (int)$this->customer->getId() . "'");

		if ($query->num_rows) {
			return $query->row;
		} else {
			return [];
		}
	}

	/**
	 * addCard
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function addCard(array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "sagepay_server_card` SET `customer_id` = '" . (int)$data['customer_id'] . "', `token` = '" . $this->db->escape($data['Token']) . "', `digits` = '" . $this->db->escape($data['Last4Digits']) . "', `expiry` = '" . $this->db->escape($data['ExpiryDate']) . "', `type` = '" . $this->db->escape($data['CardType']) . "'");
	}

	/**
	 * deleteCard
	 *
	 * @param int $card_id
	 *
	 * @return void
	 */
	public function deleteCard(int $card_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "sagepay_server_card` WHERE `card_id` = '" . (int)$card_id . "'");
	}

	/**
	 * addOrder
	 *
	 * @param array $order_info
	 *
	 * @return void
	 */
	public function addOrder(array $order_info): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "sagepay_server_order` WHERE `order_id` = '" . (int)$order_info['order_id'] . "'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "sagepay_server_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', `customer_id` = '" . (int)$this->customer->getId() . "', `VPSTxId` = '" . $this->db->escape($order_info['VPSTxId']) . "', `VendorTxCode` = '" . $this->db->escape($order_info['VendorTxCode']) . "', `SecurityKey` = '" . $this->db->escape($order_info['SecurityKey']) . "', `date_added` = NOW(), `date_modified` = NOW(), `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], false, false) . "'");
	}

	/**
	 * getOrder
	 *
	 * @param int    $order_id
	 * @param string $vpstx_id
	 *
	 * @return array
	 */
	public function getOrder(int $order_id, ?string $vpstx_id = null): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sagepay_server_order` WHERE `order_id` = '" . (int)$order_id . "' OR `VPSTxId` = '" . $this->db->escape($vpstx_id) . "' LIMIT 1");

		if ($query->num_rows) {
			$order = $query->row;
			$order['transactions'] = $this->getTransactions($order['sagepay_server_order_id']);

			return $order;
		} else {
			return [];
		}
	}

	/**
	 * updateOrder
	 *
	 * @param array  $order_info
	 * @param string $vps_txn_id
	 * @param string $tx_auth_no
	 *
	 * @return void
	 */
	public function updateOrder(array $order_info, string $vps_txn_id, string $tx_auth_no): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "sagepay_server_order` SET `VPSTxId` = '" . $this->db->escape($vps_txn_id) . "', `TxAuthNo` = '" . $this->db->escape($tx_auth_no) . "' WHERE `order_id` = '" . (int)$order_info['order_id'] . "'");
	}

	/**
	 * deleteOrder
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function deleteOrder(int $order_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "sagepay_server_order` WHERE `order_id` = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "subscription` WHERE `order_id` = '" . (int)$order_id . "'");
	}

	/**
	 * addTransaction
	 *
	 * @param int    $sagepay_server_order_id
	 * @param string $type
	 * @param array  $order_info
	 *
	 * @return void
	 */
	public function addTransaction(int $sagepay_server_order_id, string $type, array $order_info): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "sagepay_server_order_transaction` SET `sagepay_server_order_id` = '" . (int)$sagepay_server_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], false, false) . "'");
	}

	private function getTransactions($sagepay_server_order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sagepay_server_order_transaction` WHERE `sagepay_server_order_id` = '" . (int)$sagepay_server_order_id . "'");

		if ($query->num_rows) {
			return $query->rows;
		} else {
			return [];
		}
	}

	/**
	 * getRecurringOrders
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function getRecurringOrders(int $order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription` WHERE `order_id` = '" . (int)$order_id . "'");

		return $query->rows;
	}

	/**
	 * addRecurringPayment
	 *
	 * @param array  $item
	 * @param string $vendor_tx_code
	 *
	 * @return void
	 */
	public function addRecurringPayment(array $item, string $vendor_tx_code): void {
		$this->load->language('extension/payment/sagepay_server');

		// Subscriptions
		$this->load->model('checkout/subscription');

		// Trial information
		if ($item['trial_status'] == 1) {
			$trial_amt = $this->currency->format($this->tax->calculate($item['trial_price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];
			$trial_text = sprintf($this->language->get('text_trial'), $trial_amt, $item['trial_cycle'], $item['trial_frequency'], $item['trial_duration']);
		} else {
			$trial_text = '';
		}

		$subscription_amt = $this->currency->format($this->tax->calculate($item['price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];
		$subscription_description = $trial_text . sprintf($this->language->get('text_subscription'), $subscription_amt, $item['cycle'], $item['frequency']);

		$item['description'] = [];

		if ($item['duration'] > 0) {
			$subscription_description .= sprintf($this->language->get('text_length'), $item['duration']);
		}

		$item['description'] = $subscription_description;

		// Create new subscription and set to pending status as no payment has been made yet.
		$subscription_id = $this->model_checkout_subscription->addSubscription($this->session->data['order_id'], $item);

		$this->model_checkout_subscription->editReference($subscription_id, $vendor_tx_code);
	}

	/**
	 * updateRecurringPayment
	 *
	 * @param array $item
	 * @param array $order_details
	 *
	 * @return void
	 */
	public function updateRecurringPayment(array $item, array $order_details): void {
		// Orders
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_details['order_id']);

		if ($order_info) {
			// Subscriptions
			$this->load->model('account/subscription');

			// Trial information
			if ($item['trial_status'] == 1) {
				$price = $this->currency->format($item['trial_price'], $this->session->data['currency'], false, false);
			} else {
				$price = $this->currency->format($item['price'], $this->session->data['currency'], false, false);
			}

			$subscription_info = $this->model_account_subscription->getSubscriptionByReference($order_details['VendorTxCode']);

			if ($subscription_info) {
				$response_data = $this->setPaymentData($order_info, $order_details, $price, $subscription_info['subscription_id'], $item['name']);

				$next_payment = new \DateTime('now');
				$trial_end = new \DateTime('now');
				$subscription_end = new \DateTime('now');

				if ($item['trial_status'] == 1 && $item['trial_duration'] != 0) {
					$next_payment = $this->calculateSchedule($item['trial_frequency'], $next_payment, $item['trial_cycle']);
					$trial_end = $this->calculateSchedule($item['trial_frequency'], $trial_end, $item['trial_cycle'] * $item['trial_duration']);
				} elseif ($item['trial_status'] == 1) {
					$next_payment = $this->calculateSchedule($item['trial_frequency'], $next_payment, $item['trial_cycle']);
					$trial_end = new \DateTime('0000-00-00');
				}

				if ($trial_end > $subscription_end && $item['duration'] != 0) {
					$subscription_end = new \DateTime(date_format($trial_end, 'Y-m-d H:i:s'));
					$subscription_end = $this->calculateSchedule($item['frequency'], $subscription_end, $item['cycle'] * $item['duration']);
				} elseif ($trial_end == $subscription_end && $item['duration'] != 0) {
					$next_payment = $this->calculateSchedule($item['frequency'], $next_payment, $item['cycle']);
					$subscription_end = $this->calculateSchedule($item['frequency'], $subscription_end, $item['cycle'] * $item['duration']);
				} elseif ($trial_end > $subscription_end && $item['duration'] == 0) {
					$subscription_end = new \DateTime('0000-00-00');
				} elseif ($trial_end == $subscription_end && $item['duration'] == 0) {
					$next_payment = $this->calculateSchedule($item['frequency'], $next_payment, $item['cycle']);
					$subscription_end = new \DateTime('0000-00-00');
				}

				$this->addRecurringOrder($order_details['order_id'], $response_data, $subscription_info['subscription_id'], date_format($trial_end, 'Y-m-d H:i:s'), date_format($subscription_end, 'Y-m-d H:i:s'));

				$transaction = [
					'order_id'       => $subscription_info['order_id'],
					'description'    => $response_data['Status'],
					'amount'         => $price,
					'payment_method' => $order_info['payment_method'],
					'payment_code'   => $order_info['payment_code']
				];

				if ($response_data['Status'] == 'OK') {
					$this->updateRecurringOrder($subscription_info['subscription_id'], date_format($next_payment, 'Y-m-d H:i:s'));

					$this->addRecurringTransaction($subscription_info['subscription_id'], $response_data, $transaction, 1);
				} else {
					$this->addRecurringTransaction($subscription_info['subscription_id'], $response_data, $transaction, 4);
				}
			}
		}
	}

	private function setPaymentData($order_info, $sagepay_order_info, $price, $order_recurring_id, $recurring_name, $i = null) {
		if ($this->config->get('payment_sagepay_server_test') == 'live') {
			$url = 'https://live.sagepay.com/gateway/service/repeat.vsp';
			$payment_data['VPSProtocol'] = '3.00';
		} elseif ($this->config->get('payment_sagepay_server_test') == 'test') {
			$url = 'https://test.sagepay.com/gateway/service/repeat.vsp';
			$payment_data['VPSProtocol'] = '3.00';
		} elseif ($this->config->get('payment_sagepay_server_test') == 'sim') {
			$url = 'https://test.sagepay.com/Simulator/VSPServerGateway.asp?Service=VendorRepeatTx';
			$payment_data['VPSProtocol'] = '2.23';
		}

		$payment_data['TxType'] = 'REPEAT';
		$payment_data['Vendor'] = $this->config->get('payment_sagepay_server_vendor');
		$payment_data['VendorTxCode'] = $order_recurring_id . 'RSD' . date('YmdHis') . mt_rand(1, 999);
		$payment_data['Amount'] = $this->currency->format($price, $this->session->data['currency'], false, false);
		$payment_data['Currency'] = $this->session->data['currency'];
		$payment_data['Description'] = substr($recurring_name, 0, 100);
		$payment_data['RelatedVPSTxId'] = trim($sagepay_order_info['VPSTxId'], '{}');
		$payment_data['RelatedVendorTxCode'] = $sagepay_order_info['VendorTxCode'];
		$payment_data['RelatedSecurityKey'] = $sagepay_order_info['SecurityKey'];
		$payment_data['RelatedTxAuthNo'] = $sagepay_order_info['TxAuthNo'];

		if (!empty($order_info['shipping_lastname'])) {
			$payment_data['DeliverySurname'] = substr($order_info['shipping_lastname'], 0, 20);
			$payment_data['DeliveryFirstnames'] = substr($order_info['shipping_firstname'], 0, 20);
			$payment_data['DeliveryAddress1'] = substr($order_info['shipping_address_1'], 0, 100);

			if ($order_info['shipping_address_2']) {
				$payment_data['DeliveryAddress2'] = $order_info['shipping_address_2'];
			}

			$payment_data['DeliveryCity'] = substr($order_info['shipping_city'], 0, 40);
			$payment_data['DeliveryPostCode'] = substr($order_info['shipping_postcode'], 0, 10);
			$payment_data['DeliveryCountry'] = $order_info['shipping_iso_code_2'];

			if ($order_info['shipping_iso_code_2'] == 'US') {
				$payment_data['DeliveryState'] = $order_info['shipping_zone_code'];
			}

			$payment_data['CustomerName'] = substr($order_info['firstname'] . ' ' . $order_info['lastname'], 0, 100);
			$payment_data['DeliveryPhone'] = substr($order_info['telephone'], 0, 20);
		} else {
			$payment_data['DeliveryFirstnames'] = $order_info['payment_firstname'];
			$payment_data['DeliverySurname'] = $order_info['payment_lastname'];
			$payment_data['DeliveryAddress1'] = $order_info['payment_address_1'];

			if ($order_info['payment_address_2']) {
				$payment_data['DeliveryAddress2'] = $order_info['payment_address_2'];
			}

			$payment_data['DeliveryCity'] = $order_info['payment_city'];
			$payment_data['DeliveryPostCode'] = $order_info['payment_postcode'];
			$payment_data['DeliveryCountry'] = $order_info['payment_iso_code_2'];

			if ($order_info['payment_iso_code_2'] == 'US') {
				$payment_data['DeliveryState'] = $order_info['payment_zone_code'];
			}

			$payment_data['DeliveryPhone'] = $order_info['telephone'];
		}

		$response_data = $this->sendCurl($url, $payment_data, $i);
		$response_data['VendorTxCode'] = $payment_data['VendorTxCode'];
		$response_data['Amount'] = $payment_data['Amount'];
		$response_data['Currency'] = $payment_data['Currency'];

		return $response_data;
	}

	/**
	 * cronPayment
	 */
	public function cronPayment() {
		// Orders
		$this->load->model('account/order');

		$cron_data = [];

		$subscriptions = $this->getProfiles();

		$i = 0;

		foreach ($subscriptions as $subscription) {
			$subscription_order = $this->getRecurringOrder($subscription['subscription_id']);

			$today = new \DateTime('now');
			$unlimited = new \DateTime('0000-00-00');
			$next_payment = new \DateTime($subscription_order['next_payment']);
			$trial_end = new \DateTime($subscription_order['trial_end']);
			$subscription_end = new \DateTime($subscription_order['subscription_end']);

			$order_info = $this->model_account_order->getOrder($subscription['order_id']);

			if (($today > $next_payment) && ($trial_end > $today || $trial_end == $unlimited)) {
				$price = $this->currency->format($subscription['trial_price'], $order_info['currency_code'], false, false);
				$frequency = $subscription['trial_frequency'];
				$cycle = $subscription['trial_cycle'];
			} elseif (($today > $next_payment) && ($subscription_end > $today || $subscription_end == $unlimited)) {
				$price = $this->currency->format($subscription['price'], $order_info['currency_code'], false, false);
				$frequency = $subscription['frequency'];
				$cycle = $subscription['cycle'];
			} else {
				continue;
			}

			$sagepay_order_info = $this->getOrder($subscription['order_id']);
			$response_data = $this->setPaymentData($order_info, $sagepay_order_info, $price, $subscription['subscription_id'], $subscription['name'], $i);
			$cron_data[] = $response_data;

			$transaction = [
				'order_id'       => $subscription['order_id'],
				'description'    => $response_data['RepeatResponseData_' . $i++]['Status'],
				'amount'         => $price,
				'payment_method' => $order_info['payment_method'],
				'payment_code'   => $order_info['payment_code']
			];

			if ($response_data['RepeatResponseData_' . $i++]['Status'] == 'OK') {
				$this->addRecurringTransaction($subscription['subscription_id'], $response_data, $transaction, 1);

				$next_payment = $this->calculateSchedule($frequency, $next_payment, $cycle);
				$next_payment = date_format($next_payment, 'Y-m-d H:i:s');

				$this->updateRecurringOrder($subscription['subscription_id'], $next_payment);
			} else {
				$this->addRecurringTransaction($subscription['subscription_id'], $response_data, $transaction, 4);
			}
		}

		// Log
		$log = new \Log('sagepay_server_recurring_orders.log');
		$log->write(print_r($cron_data, 1));

		return $cron_data;
	}

	private function calculateSchedule($frequency, $next_payment, $cycle) {
		if ($frequency == 'semi_month') {
			// https://stackoverflow.com/a/35473574
			$day = date_create_from_format('j M, Y', $next_payment->date);
			$day = date_create($day);
			$day = date_format($day, 'd');
			$value = 15 - $day;
			$is_even = false;

			if ($cycle % 2 == 0) {
				$is_even = true;
			}

			$odd = ($cycle + 1) / 2;
			$plus_even = ($cycle / 2) + 1;
			$minus_even = $cycle / 2;

			if ($day == 1) {
				$odd--;
				$plus_even--;
				$day = 16;
			}

			if ($day <= 15 && $is_even) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $minus_even . ' month');
			} elseif ($day <= 15) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $odd . ' month');
			} elseif ($day > 15 && $is_even) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $plus_even . ' month');
			} elseif ($day > 15) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $odd . ' month');
			}
		} else {
			$next_payment->modify('+' . $cycle . ' ' . $frequency);
		}

		return $next_payment;
	}

	private function addRecurringOrder($order_id, $response_data, $order_recurring_id, $trial_end, $subscription_end): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "sagepay_server_order_recurring` SET `order_id` = '" . (int)$order_id . "', `order_recurring_id` = '" . (int)$order_recurring_id . "', `VPSTxId` = '" . $this->db->escape($response_data['VPSTxId']) . "', `VendorTxCode` = '" . $this->db->escape($response_data['VendorTxCode']) . "', `SecurityKey` = '" . $this->db->escape($response_data['SecurityKey']) . "', `TxAuthNo` = '" . $this->db->escape($response_data['TxAuthNo']) . "', `date_added` = NOW(), `date_modified` = NOW(), `next_payment` = NOW(), `trial_end` = '" . $this->db->escape($trial_end) . "', `subscription_end` = '" . $this->db->escape($subscription_end) . "', `currency_code` = '" . $this->db->escape($response_data['Currency']) . "', `total` = '" . $this->currency->format($response_data['Amount'], $response_data['Currency'], false, false) . "'");
	}

	private function updateRecurringOrder($order_recurring_id, $next_payment): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "sagepay_server_order_recurring` SET `next_payment` = '" . $this->db->escape($next_payment) . "', `date_modified` = NOW() WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");
	}

	private function getRecurringOrder($order_recurring_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sagepay_server_order_recurring` WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");

		return $query->row;
	}

	private function addRecurringTransaction($subscription_id, $response_data, $transaction, $type): void {
		// Subscriptions
		$this->load->model('account/subscription');

		$subscription_info = $this->model_account_subscription->getSubscription($subscription_id);

		if ($subscription_info) {
			// Subscriptions
			$this->load->model('checkout/subscription');

			$this->model_checkout_subscription->editReference($subscription_id, $response_data['VendorTxCode']);

			$this->model_account_subscription->editStatus($subscription_id, $type);
			$this->model_account_subscription->addTransaction($subscription_id, $transaction['order_id'], $transaction['description'], $transaction['amount'], $type, $transaction['payment_method'], $transaction['payment_code']);
		}
	}

	private function getProfiles() {
		$subscriptions = [];

		// Subscriptions
		$this->load->model('account/subscription');

		$sql = "SELECT `s`.`subscription_id` FROM `" . DB_PREFIX . "subscription` `s` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE `o`.`payment_code` = 'sagepay_server'";

		$query = $this->db->query($sql);

		foreach ($query->rows as $subscription) {
			$subscriptions[] = $this->model_account_subscription->getSubscription($subscription['subscription_id']);
		}

		return $subscriptions;
	}

	/**
	 * updateCronJobRunTime
	 */
	public function updateCronJobRunTime(): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'sagepay_server' AND `key` = 'payment_sagepay_server_last_cron_job_run'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '0', `code` = 'sagepay_server', `key` = 'payment_sagepay_server_last_cron_job_run', `value` = NOW(), `serialized` = '0'");
	}

	/**
	 * sendCurl
	 *
	 * @param string $url
	 * @param array  $payment_data
	 * @param int    $i
	 *
	 * @return array
	 */
	public function sendCurl(string $url, array $payment_data, ?int $i = null): array {
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payment_data));

		$response = curl_exec($curl);

		curl_close($curl);

		$response_info = explode(chr(10), $response);

		foreach ($response_info as $string) {
			if (strpos($string, '=') && $i !== null) {
				$parts = explode('=', $string, 2);
				$data['RepeatResponseData_' . $i][trim($parts[0])] = trim($parts[1]);
			} elseif (strpos($string, '=')) {
				$parts = explode('=', $string, 2);
				$data[trim($parts[0])] = trim($parts[1]);
			}
		}

		return $data;
	}

	/**
	 * Logger
	 *
	 * @param string $title
	 * @param mixed  $data
	 *
	 * @return void
	 */
	public function logger(string $title, mixed $data): void {
		if ($this->config->get('payment_sagepay_server_debug')) {
			// Log
			$log = new \Log('sagepay_server.log');
			$backtrace = debug_backtrace();
			$log->write($backtrace[6]['class'] . '::' . $backtrace[6]['function'] . ' - ' . $title . ': ' . print_r($data, 1));
		}
	}

	/**
	 * subscriptionPayments
	 */
	public function subscriptionPayments() {
		/*
		 * Used by the checkout to state the module
		 * supports subscriptions.
		 */

		return true;
	}
}
