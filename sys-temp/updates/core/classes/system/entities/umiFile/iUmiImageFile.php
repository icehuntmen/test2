<?php
	interface iUmiImageFile extends iUmiFile {
		public function getWidth();
		public function getHeight();
		public function getAlt();
		public function setAlt($alt);
	}