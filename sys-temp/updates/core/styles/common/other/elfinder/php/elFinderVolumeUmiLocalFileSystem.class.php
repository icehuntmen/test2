<?php

	class elFinderVolumeUmiLocalFileSystem extends elFinderVolumeLocalFileSystem {
    	protected $driverId = 'umi';
		public function fullRoot() {
			return $this->root;
		}

		/**
		* Переименовываение файла (исключаем проверку на существование - меняем название в случае коллизий)
		* @param mixed $hash
		* @param mixed $name
		* @return string|false
		*/
		public function rename($hash, $name) {
			$path = $this->decode($hash);


			if (!($file = $this->file($hash))) {
				return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
			}

			$dir = $this->_dirname($path);

			if ($this->attr($path, 'locked')) {
				return $this->setError(elFinder::ERROR_LOCKED, $file['name']);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			}
			
			if ($name == $file['name']) {
				return $file;
			}
			
			$newLastDotPosition = mb_strrpos($name, '.');
			$newExt = $newLastDotPosition ? mb_substr($name, mb_strrpos($name, '.')) : '';
			
			$lastDotPosition = mb_strrpos($file['name'], '.');
			$ext = $lastDotPosition ? mb_substr($file['name'], mb_strrpos($file['name'], '.')) : '';

			if ($newExt != $ext) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			} 

			if ($this->_moveWithRename($path, $dir, $name)) {
				$this->rmTmb($path);
				return $this->stat($this->_joinPath($dir, $name));
			}
			return false;
		}

		/**
		* Дубликат (вместо постфикса " copy" делаем "_copy")
		* @param mixed $hash
		* @return false
		*/
		public function duplicate($hash) {
			if (($file = $this->file($hash)) == false) {
				return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
			}

			$path = $this->decode($hash);
			$dir  = $this->_dirname($path);

			return ($path = $this->doCopy($path, $dir, $this->uniqueName($dir, $file['name'], '_copy'))) == false
				? false
				: $this->stat($path);
		}

		/**
		* Создание папки с корректировкой названия
		* @param string $dst
		* @param string $name
		* @param mixed $copy
		* @return bool
		*/
		public function mkdir($dst, $name, $copy=false) {
			$path = $this->decode($dst);

			if (($dir = $this->dir($dst)) == false) {
				return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#'.$dst);
			}

			if (!$dir['write']) {
				return $this->setError(elFinder::ERROR_PERM_DENIED);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME);
			}

			if ($copy && !$this->options['copyOverwrite']) {
				$name = $this->uniqueName($path, $name, '-', false);
			}

			$dst = $this->_joinPath($path, $name);

			if ($this->_fileExists($dst)) {

				if ($copy) {
					if (!$this->options['copyJoin'] && $this->attr($dst, 'write')) {
						foreach ($this->_scandir($dst) as $p) {
							$this->doRm($p);
						}
					}
					return $this->stat($dst);
				}

				return $this->setError(elFinder::ERROR_EXISTS, $name);
			}

			return $this->_mkdirWithRename($path, $name) ? $this->stat($this->_joinPath($path, $name)) : false;
		}

		/**
		* Сохранение загруженного файла
		* @param mixed $fp
		* @param string $dst
		* @param mixed $name
		* @param mixed $cmd
		* @return string|false
		*/
		public function save($fp, $dst, $name, $cmd = 'upload') {

			if (($dir = $this->dir($dst, true, true)) == false) {
				return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#'.$dst);
			}

			if (!$dir['write']) {
				return $this->setError(elFinder::ERROR_PERM_DENIED);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			}

			$dst = $this->decode($dst);

			if (mb_strpos($dst, USER_FILES_PATH ) !== false || mb_strpos($dst, USER_IMAGES_PATH) !== false) {
				$quota_byte = getBytesFromString( mainConfiguration::getInstance()->get('system', 'quota-files-and-images') );
				if ( $quota_byte != 0 ) {
					$all_size = getBusyDiskSize();
					if ( $all_size >= $quota_byte ) {
						return $this->setError(getLabel('error-files_quota_exceeded'));
					}
				}
			}

			//Загрузка файла
			$sMethodName = method_exists($this, "_doSave_{$cmd}") ? "_doSave_{$cmd}" : '_doSave_unknown';
			$path = $this->$sMethodName($fp, $dst, $name);

			$result = false;
			if ($path) {
				$result = $this->stat($path);
			}
			return $result;
		}


		/**
		* Восстановить старое значение локали
		* @param mixed $oldLocale список зачение, полученных c LC_ALL
		*/
		private function restoreLocale($oldLocale) {
			$originalLocales = explode(';', $oldLocale);
			
			foreach($originalLocales as $localeSetting) {
				if (mb_strpos($localeSetting, '=') !== false) {
					list($category, $locale) = explode('=', $localeSetting);
				} else {
					$category = LC_ALL;
					$locale = $localeSetting;
				}
				setlocale($category, $locale);
			}
		}

		/**
		* Переместить файл в новое место (перемещение/переименование)
		* @param  string  $source  source file path
		* @param  string  $target  target dir path
		* @param  string  $name    file name
		* @return bool
		*/
		protected function _moveWithRename($source, &$targetDir, &$name='') {
			$i = 0;
			$bNeedRename = true;

			$old_locale = setlocale(LC_ALL, NULL);
			setlocale(LC_ALL, ['ru_RU.UTF-8', 'ru_RU.CP1251', 'ru_RU.KOI8-R', 'ru_SU.CP1251', 'ru_RU', 'russian', 'ru_SU', 'ru']);

			while($bNeedRename) {
				$name = $this->_getNewFilename($name, $i);
				$target = $targetDir . DIRECTORY_SEPARATOR . ($name ?: basename($source));
				clearstatcache();
				$bNeedRename = (file_exists($target) || is_dir($target));
				$i++;
			}

			$this->restoreLocale($old_locale);

			return @rename($source, $target);
		}

		/**
		 * Создать папку с переименованием
		 * @param  string  $path  parent dir path
		 * @param string  $name  new directory name
		 * @return bool
		 * @author Dmitry (dio) Levashov
		 */
		protected function _mkdirWithRename($path, &$name) {
			$i = 0;
			$bNeedRename = true;

			$old_locale = setlocale(LC_ALL, NULL);
			setlocale(LC_ALL, ['ru_RU.UTF-8', 'ru_RU.CP1251', 'ru_RU.KOI8-R', 'ru_SU.CP1251', 'ru_RU', 'russian', 'ru_SU', 'ru']);

			while($bNeedRename) {
				$name = $this->_getNewFilename($name, $i);
				$target = $path.DIRECTORY_SEPARATOR.$name;
				clearstatcache();
				$bNeedRename = (file_exists($target) || is_dir($target));
				$i++;
			}

			$this->restoreLocale($old_locale);

			if (@mkdir($target)) {
				@chmod($target, $this->options['dirMode']);
				return true;
			}
			return false;
		}

		/**
		* Получить корректное название файла с числовым постфиксом в названии
		*
		* @param mixed $sOldName Имя файла
		* @param mixed $i Числовой постфикс
		* @return string Новое имя файла
		*/
		protected function _getNewFilename($sOldName, $i) {
			if($sOldName == '') {
				return $sOldName;
			}

			$iLastDotPosition = mb_strrpos($sOldName, '.');
			$sBaseName = $iLastDotPosition ? mb_substr($sOldName, 0, mb_strrpos($sOldName, '.')) : $sOldName;
			$sBaseName = $this->_convertFilename($sBaseName);

			$sExt = $iLastDotPosition ? mb_substr($sOldName, mb_strrpos($sOldName, '.')) : '';
			$sExt = $this->_convertFilename($sExt);

			if($i == 0) {
				return "{$sBaseName}{$sExt}";
			}

			return "{$sBaseName}_{$i}{$sExt}";
		}

		/** Перекрываем обработку архивов */
		protected function _checkArchivers() {
			return $this->options['archivers'] = $this->options['archive'] = [];
		}

		/**
		* Конвертация имени файла
		* @param string $sFileBaseName
		* @return string
		*/
		protected function _convertFilename($sFileBaseName) {
			$arConvertions = [
				['a', ['а', 'А']], ['b', ['б', 'Б']], ['v', ['в', 'В']],
				['g', ['г', 'Г']], ['d', ['д', 'Д']], ['e', ['е', 'Е']],
				['e', ['ё', 'Ё']], ['zsh', ['ж', 'Ж']], ['z', ['з', 'З']],
				['i', ['и', 'И']], ['i', ['й', 'Й']], ['k', ['к', 'К']],
				['l', ['л', 'Л']], ['m', ['м', 'М']], ['n', ['н', 'Н']],
				['o', ['о', 'О']], ['p', ['п', 'П']], ['r', ['р', 'Р']],
				['s', ['с', 'С']], ['t', ['т', 'Т']], ['u', ['у', 'У']],
				['f', ['ф', 'Ф']], ['h', ['х', 'Х']], ['c', ['ц', 'Ц']],
				['ch', ['ч', 'Ч']], ['sh', ['ш', 'Ш']], ['sh', ['щ', 'Щ']],
				['', ['ъ', 'Ъ']], ['i', ['ы', 'Ы']], ['', ['ь', 'Ь']],
				['e', ['э', 'Э']], ['yu', ['ю', 'Ю']], ['ya', ['я', 'Я']],
				['_', ' '], ['', '~'], ['', '`'],
				['', '!'], ['', '@'], ['', '"'],
				['', "'"], ['', '#'], ['', '№'],
				['', '$'], ['', ';'], ['', '%'],
				['', '^'], ['', ':'], ['', '&'],
				['', '?'], ['', '*'], ['', '+'],
				['', '='], ['', '|'], ['', "\\"],
				['', '/'], ['', ','], ['', '<'],
				['', '>']
			];

			foreach($arConvertions as $arConvPair) {
				$sFileBaseName = str_replace($arConvPair[1], $arConvPair[0], $sFileBaseName);
			}

			return $sFileBaseName;
		}

		/**
		* Действия для сохранения файла при его загрузке
		* @param mixed $dst
		* @param mixed $name
		* @return false
		*/
		protected function _doSave_upload($fp, $dst, $name) {
			$cwd = getcwd();
			chdir(CURRENT_WORKING_DIR);

			$files_index = 0;
			$controller = cmsController::getInstance();

			$filename = '.' . rtrim($dst, "/\\") . DIRECTORY_SEPARATOR . $name;
			if(isset($_FILES['upload'])) {
				foreach($_FILES['upload']['name'] as $i => $f_name) {
					if($f_name == $name) {
						$filename = $_FILES['upload']['tmp_name'][$i];
						$files_index = $i;
					}
				}
			}
			$filesize = (int) filesize($filename);
			if (umiImageFile::getIsImage($name)) {
				$max_img_filesize =	$controller->getModule('data')->getAllowedMaxFileSize('img') * 1024 * 1024;
				if ($max_img_filesize > 0) {
					if ($max_img_filesize < $filesize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_img_filesize') . ' ' . ($max_img_filesize / 1024 / 1024) . 'M');
					}
				}
				if(getRequest('water_mark')) {
					umiImageFile::setWatermarkOn();
				}
				$file = umiImageFile::upload('upload', $files_index, $dst);
			}
			else {
				$upload_max_filesize = $controller->getModule('data')->getAllowedMaxFileSize() * 1024 * 1024;
				if ($upload_max_filesize > 0) {
					if ($upload_max_filesize < $filesize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_filesize') . ' ' . ($upload_max_filesize / 1024 / 1024) . 'M');
					}
				}
				$file = umiFile::upload('upload', $files_index, $dst);
			}

			chdir($cwd);

			if(!$file instanceof umiFile || $file->getIsBroken()) {
				return $this->setError(elFinder::ERROR_UPLOAD);
			}

			return CURRENT_WORKING_DIR . $file->getFilePath(true);
		}

		/**
		* Действия для сохранения файла при его копировании
		* @param mixed $dst
		* @param mixed $name
		*/
		protected function _doSave_copy($fp, $dst, $name) {
			$path = $dst.DIRECTORY_SEPARATOR.$name;

			if (!($target = @fopen($path, 'wb'))) {
				$this->setError(elFinder::ERROR_COPY);
				return false;
			}

			while (!feof($fp)) {
				fwrite($target, fread($fp, 8192));
			}
			fclose($target);
			@chmod($path, $this->options['fileMode']);
			clearstatcache();

			return $path;
		}

		/**
		* Неизвестный режим сохранения файла
		* @param mixed $dst
		* @param mixed $name
		* @return false
		*/
		protected function _doSave_unknown($fp, $dst, $name) {
			return $this->setError(elFinder::ERROR_UNKNOWN_CMD);
		}

		/**
		 * Execute shell command
		 *
		 * @param  string  $command       command line
		 * @param  array   $output        stdout strings
		 * @param  array   $return_var    process exit code
		 * @param  array   $error_output  stderr strings
		 * @return int     exit code
		 * @author Alexey Sukhotin
		 **/
		protected function procExec($command , array &$output = null, &$return_var = -1, array &$error_output = null) {

			$descriptorspec = [
				0 => ['pipe', 'r'],  // stdin
				1 => ['pipe', 'w'],  // stdout
				2 => ['pipe', 'w'] // stderr
			];

			$process = proc_open($command, $descriptorspec, $pipes) or false;

			if (is_resource($process)) {

				fclose($pipes[0]);

				$tmpout = '';
				$tmperr = '';

				if( !feof( $pipes[1] ) ) {
					$output[] = fgets($pipes[1], 1024);
				}
				if( !feof( $pipes[2] ) ) {
					$error_output[] = fgets($pipes[2], 1024);
				}

				fclose($pipes[1]);
				fclose($pipes[2]);
				$return_var = proc_close($process);


			}

			return $return_var;

		}

		/** @inheritdoc */
		protected function _abspath($path) {
			$path = preg_replace('/(\.\.\/?)/i', '', $path);
			return ($path == DIRECTORY_SEPARATOR) ? $this->root : $this->root . DIRECTORY_SEPARATOR . $path;
		}
	}

