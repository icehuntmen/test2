<?php

	interface iUmiMessages {

		public static function getAllowedTypes();

		public function getMessages($senderId = false, $onlyNew = false);

		public function getSendedMessages($recipientId = false);

		public function create($type = 'private');

		public function testMessages();

		public function dropTestMessages();
	}
