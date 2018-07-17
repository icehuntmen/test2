<?php
	/** Фабрика классов обработки изображений */
	class imageUtils {
		/** @var iImageProcessor|null $processor экземпляр текущего класса обработки изображений */
		private static $processor;

		/**
		 * Возвращает текущий экземпляр класса обработки изображений
		 * @return iImageProcessor
		 * @throws Exception
		 */
		public static function getImageProcessor() {
			if (self::$processor == null || get_class(self::$processor) == 'gdProcessor') {
				if (extension_loaded('imagick')) {
					self::$processor = new imageMagickProcessor();
				} else if (extension_loaded('gd')) {
					self::$processor = new gdProcessor();
				} else {
					self::throwNoModuleException();
				}
			}

			return self::$processor;
		}

		/**
		 * Возвращает экземпляр класса imageMagickProcessor
		 * @return imageMagickProcessor
		 * @throws Exception
		 */
		public static function getImageMagickProcessor (){
			if (extension_loaded('imagick')) {
				if (self::$processor == null || get_class(self::$processor) !== 'imageMagickProcessor') {
					self::$processor = new imageMagickProcessor();
				}
			} else {
				self::throwNoModuleException();
			}

			return self::$processor;
		}

		/**
		 * Возвращает экземпляр класса gdProcessor
		 * @return gdProcessor
		 * @throws Exception
		 */
		public static function getGDProcessor (){
			if (extension_loaded('gd')) {
				if (self::$processor == null || get_class(self::$processor) !== 'gdProcessor') {
					self::$processor = new gdProcessor();
				}
			} else {
				self::throwNoModuleException();
			}
			return self::$processor;
		}

		/**
		 * Бросает исключение о недоступности модулей php для работы с изображениями
		 * @throws Exception
		 */
		private static function throwNoModuleException (){
			throw new Exception('It does not install any image processing module.');
		}
	}
