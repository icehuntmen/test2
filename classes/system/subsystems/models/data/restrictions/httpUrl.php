<?php
	class httpUrlRestriction extends baseRestriction implements iNormalizeInRestriction {
		public function validate($value, $objectId = false) {
			return !mb_strlen($value) || preg_match("/^(https?:\/\/)?([A-z\.]+)/", $value);
		}

		public function normalizeIn($value, $objectId = false) {
			if(mb_strlen($value) && !preg_match("/^https?:\/\//", $value)) {
				$value = getSelectedServerProtocol() . '://' . $value;
			}

			return $value;
		}
	}

