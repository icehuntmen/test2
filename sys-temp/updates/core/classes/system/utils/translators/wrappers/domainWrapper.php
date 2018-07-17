<?php
	/** Класс xml транслятора (сериализатора) доменов */
	class domainWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param iDomain $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует домен в массив с разметкой для последующей сериализации в xml
		 * @param iDomain $domain домен
		 * @return array
		 */
		protected function translateData(iDomain $domain) {
			return [
				'attribute:id' => $domain->getId(),
				'attribute:host' => $domain->getHost(),
				'attribute:lang-id' => $domain->getDefaultLangId(),
				'attribute:is-default' => $domain->getIsDefault(),
				'attribute:using-ssl' => $domain->isUsingSsl()
			];
		}
	}
