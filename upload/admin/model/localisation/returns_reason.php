<?php
/**
 * Class Returns Reason
 *
 * @package Admin\Model\Localisation
 */
class ModelLocalisationReturnsReason extends Model {
	/**
	 * addReturnReason
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public function addReturnReason(array $data): int {
		$return_reason_id = null;
		
		foreach ($data['return_reason'] as $language_id => $value) {
			if (isset($return_reason_id)) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "return_reason` SET `return_reason_id` = '" . (int)$return_reason_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "return_reason` SET `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");

				$return_reason_id = $this->db->getLastId();
			}
		}

		$this->cache->delete('return_reason');

		return $return_reason_id;
	}

	/**
	 * editReturnReason
	 *
	 * @param int   $return_reason_id
	 * @param array $data
	 *
	 * @return void
	 */
	public function editReturnReason(int $return_reason_id, array $data): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "return_reason` WHERE `return_reason_id` = '" . (int)$return_reason_id . "'");

		foreach ($data['return_reason'] as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "return_reason` SET `return_reason_id` = '" . (int)$return_reason_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");
		}

		$this->cache->delete('return_reason');
	}

	/**
	 * deleteReturnReason
	 *
	 * @param int $return_reason_id
	 *
	 * @return void
	 */
	public function deleteReturnReason(int $return_reason_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "return_reason` WHERE `return_reason_id` = '" . (int)$return_reason_id . "'");

		$this->cache->delete('return_reason');
	}

	/**
	 * getReturnReason
	 *
	 * @param int $return_reason_id
	 *
	 * @return array
	 */
	public function getReturnReason(int $return_reason_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "return_reason` WHERE `return_reason_id` = '" . (int)$return_reason_id . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * getReturnReasons
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function getReturnReasons(array $data = []): array {
		if ($data) {
			$sql = "SELECT * FROM `" . DB_PREFIX . "return_reason` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "'";

			$sql .= " ORDER BY `name`";

			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC";
			} else {
				$sql .= " ASC";
			}

			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) {
					$data['start'] = 0;
				}

				if ($data['limit'] < 1) {
					$data['limit'] = 20;
				}

				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}

			$query = $this->db->query($sql);

			return $query->rows;
		} else {
			$return_reason_data = $this->cache->get('return_reason.' . (int)$this->config->get('config_language_id'));

			if (!$return_reason_data) {
				$query = $this->db->query("SELECT `return_reason_id`, `name` FROM `" . DB_PREFIX . "return_reason` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `name`");

				$return_reason_data = $query->rows;

				$this->cache->set('return_reason.' . (int)$this->config->get('config_language_id'), $return_reason_data);
			}

			return $return_reason_data;
		}
	}

	/**
	 * getDescriptions
	 *
	 * @param int $return_reason_id
	 *
	 * @return array
	 */
	public function getDescriptions(int $return_reason_id): array {
		$return_reason_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "return_reason` WHERE `return_reason_id` = '" . (int)$return_reason_id . "'");

		foreach ($query->rows as $result) {
			$return_reason_data[$result['language_id']] = ['name' => $result['name']];
		}

		return $return_reason_data;
	}

	/**
	 * getTotalReturnReasons
	 *
	 * @return int
	 */
	public function getTotalReturnReasons(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "return_reason` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return (int)$query->row['total'];
	}
}
