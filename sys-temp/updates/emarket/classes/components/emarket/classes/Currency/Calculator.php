<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\iCurrency;

	/**
	 * Класс калькулятора валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	class Calculator implements iCalculator {

		/** @inheritdoc */
		public function calculate($price, iCurrency $from, iCurrency $to) {
			if ($from->getId() === $to->getId()) {
				return $price;
			}

			$price = $price * $from->getDenomination() * $from->getRate();
			$price = $price / $to->getRate() / $to->getDenomination();
			return round($price, 2);
		}
	}