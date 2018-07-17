<?php

	namespace UmiCms\Manifest\Emarket;

	use UmiCms\Service;

	/** Команда обновления доменных идентификаторов у заказов на сайте */
	class UpdateOrderDomainIdsAction extends \Action {

		/** @inheritdoc */
		public function execute() {
			$orders = new \selector('objects');
			$orders->types('hierarchy-type')->name('emarket', 'order');
			$result = $orders->result();

			$domainId = Service::DomainDetector()->detectId();

			/** @var \iUmiObject $order */
			foreach ($result as $order) {
				$order->setValue('domain_id', $domainId);
				$order->commit();
			}
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}
	}
