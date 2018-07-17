<?php
interface iUmiEventListener {
	public function __construct($eventId, $callbakModule, $callbackMethod);

	public function setPriority($priority = 5);
	public function getPriority();

	public function setIsCritical($isCritical = false);
	public function getIsCritical();


	public function getEventId();
	public function getCallbackModule();
	public function getCallbackMethod();
}