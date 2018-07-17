<?php

interface iUmiMail {
	public function __construct($template = 'default');

	public function __destruct();

	public function addRecipient($recipientEmail, $recipientName = false);

	/**
	 * Определяет, есть ли у письма получатели
	 * @return boolean
	 */
	public function hasRecipients();

	public function setFrom($fromEmail, $fromName = false);

	public function setContent($contentString, $parseTplMacros = true);

	public function setTxtContent($sTxtContent);

	public function setSubject($subjectString);

	public function setPriorityLevel($priorityLevel = 'normal');

	public function setImportanceLevel($importanceLevel = 'normal');

	public function getHeaders($arrXHeaders = [], $bOverwrite = false);

	public function attachFile(iUmiFile $file);

	public function commit();

	public function send();

	public static function clearFilesCache();

	public static function checkEmail($emailString);
}
