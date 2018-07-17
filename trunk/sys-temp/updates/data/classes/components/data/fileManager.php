<?php

	use UmiCms\Service;

	/** Класс функционала файлового менеджера */
	class DataFileManager {
		/** @var data $module */
		public $module;
		/** @var string $cwd путь до директории, в которой работает файловый менеджер */
		private $cwd = USER_FILES_PATH;
		/** @const string FILES_HASH_PREFIX префикс для хеша адреса файла, @see elfinder_get_hash() */
		const FILES_HASH_PREFIX = 'umifiles';
		/** @const string IMAGES_HASH_PREFIX префикс для хеша адреса изображения, @see elfinder_get_hash() */
		const IMAGES_HASH_PREFIX = 'umiimages';

		/**
		 * Запускает файловый менеджер "Elfinder", либо выводит в буффер максимальный размер
		 * загружаемого файла
		 * @param bool $needInfo вернуть максимальный разме возвращаемого файла
		 */
		public function elfinder_connector($needInfo = false) {
			$needInfo = (!$needInfo) ? getRequest('param0') : $needInfo;

			if ($needInfo == 'getSystemInfo') {
				$arData = [
					'maxFilesCount' => ini_get('max_file_uploads') ?: 20
				];
				$this->module->flush(json_encode($arData), 'text/javascript');
			}

			$elfClasses = CURRENT_WORKING_DIR . '/styles/common/other/elfinder/php/';
			require_once $elfClasses . 'elFinderConnector.class.php';
			require_once $elfClasses . 'elFinder.umi.class.php';
			require_once $elfClasses . 'elFinderVolumeDriver.class.php';
			require_once $elfClasses . 'elFinderVolumeLocalFileSystem.class.php';
			require_once $elfClasses . 'elFinderVolumeUmiLocalFileSystem.class.php';

			$isFullAccess = (bool) getRequest('full-access');

			function elfinder_full_access($attr, $path, $data, $volume) {
				$readOrWrite = ($attr == 'read' || $attr == 'write');
				return mb_strpos(basename($path), '.') === 0  ? !$readOrWrite : $readOrWrite;
			}

			function elfinder_access($attr, $path, $data, $volume) {
				if (mb_strpos(basename($path), '.') === 0) {
					return !($attr == 'read' || $attr == 'write');
				}

				if (isDemoMode()) {
					return !($attr == 'write' || $attr == 'hidden');
				}
				return ($attr == 'read' || $attr == 'write');
			}

			$opts = [
				'debug' => true,
				'roots' => []
			];

			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$user = umiObjectsCollection::getInstance()->getObject($userId);
			$allowedDirectories = [];

			if (!isDemoMode() && $fileManagerDirectory = $user->getValue('filemanager_directory')) {
				$directories = explode(',', $fileManagerDirectory);

				foreach ($directories as $directory) {
					$directory = trim($directory);

					if (!mb_strlen($directory)) {
						continue;
					}

					$directory = trim($directory, '/');
					$directoryPath = realpath(CURRENT_WORKING_DIR . '/' . $directory);
					$pathNotInUserFilesPath = (mb_strpos($directoryPath, USER_FILES_PATH ) === false);
					$pathNotInUserImagesPath = (mb_strpos($directoryPath, USER_IMAGES_PATH ) === false);

					if (($pathNotInUserFilesPath && $pathNotInUserImagesPath) || !is_dir($directoryPath)) {
						continue;
					}

					$allowedDirectories[] = $directory;
				}

			}

			$cwdLength = mb_strlen(CURRENT_WORKING_DIR);
			$imagesDir = mb_substr(USER_IMAGES_PATH, $cwdLength) . '/';
			$filesDir = mb_substr(USER_FILES_PATH, $cwdLength) . '/';
			$target = isset($_REQUEST['target']) ? $_REQUEST['target'] : '';

			if (umiCount($allowedDirectories)) {
				$i = 1;
				foreach ($allowedDirectories as $directory) {
					$opts['roots'][] = [
						'id'			=> 'files' . $i,
						'driver'		=> 'UmiLocalFileSystem',
						'path'			=> CURRENT_WORKING_DIR . '/' . $directory,
						'URL'			=> '/' . $directory,
						'accessControl'	=> 'elfinder_access'
					];
					$i++;
				}
			} else {
				$rootImagesCategoryOptions = [
					'id'			=> 'images',
					'driver'		=> 'UmiLocalFileSystem',
					'path'			=> USER_IMAGES_PATH . '/',
					'URL'			=> $imagesDir,
					'accessControl'	=> $isFullAccess ? 'elfinder_full_access' : 'elfinder_access'
				];

				$rootFilesCategoryOptions = [
					'id'			=> 'files',
					'driver'		=> 'UmiLocalFileSystem',
					'path'			=> USER_FILES_PATH . '/',
					'URL'			=> $filesDir,
					'accessControl'	=> $isFullAccess ? 'elfinder_full_access' : 'elfinder_access'
				];

				switch (true) {
					case mb_strpos($target, self::IMAGES_HASH_PREFIX) === 0 : {
						$opts['roots'][] = $rootImagesCategoryOptions;
						$opts['roots'][] = $rootFilesCategoryOptions;
						break;
					}
					default : {
						$opts['roots'][] = $rootFilesCategoryOptions;
						$opts['roots'][] = $rootImagesCategoryOptions;
					}
				}
			}

			$connector = new elFinderConnector(new elFinder($opts));
			$connector->run();
		}

		/**
		 * Выводит в буффер настройки файлового менеджера
		 * @throws coreException
		 */
		public function get_filemanager_info() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->clear();

			$folder = (string) getRequest('folder');
			$file = (string) getRequest('file');
			$folderHash = $folder ? elfinder_get_hash($folder) : '';
			$fileHash = $file ? elfinder_get_hash($file) : '';

			$objects = umiObjectsCollection::getInstance();
			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$user = $objects->getObject($userId);
			$fmId = $user->getValue('filemanager');

			if ($fmId) {
				$fm = $objects->getObject($fmId);
				$fmPrefix = $fm->getValue('fm_prefix') ?: 'elfinder';
			} else {
				$fmPrefix = 'elfinder';
			}

			$data = [
				'folder_hash' 	=> $folderHash,
				'file_hash' 	=> $fileHash,
				'filemanager'	=> $fmPrefix,
				'lang'			=> Service::LanguageDetector()->detectPrefix()
			];

			$json = new jsonTranslator;
			$result = $json->translateToJson($data);
			$buffer->push($result);
			$buffer->end();
		}

		/**
		 * Возвращает список файлов, если в $_REQUEST
		 * передана дополнительная операци (копировать, удалить и переместить),
		 * то также выполняет ее
		 * @return array
		 */
		public function getfilelist() {
			$this->module->flushAsXML('getfilelist');
			$this->setupCwd();

			$param = [
				[
					'delete',
					'unlink',
					1
				],
				[
					'copy',
					'copy',
					2
				],
				[
					'move',
					'rename',
					2
				]
			];

			for ($i = 0; $i < umiCount($param); $i++) {
				if ($param!= 'copy' && isDemoMode()) {
					continue;
				}
				if (isset($_REQUEST[$param[$i][0]]) && !empty($_REQUEST[$param[$i][0]])) {
					foreach ($_REQUEST[$param[$i][0]] as $item) {
						$item = CURRENT_WORKING_DIR . base64_decode($item);
						$arguments = [$item];
						if ($param[$i][2] > 1) {
							$arguments[] = $this->cwd . '/' . basename($item);
						}
						@call_user_func_array($param[$i][1], $arguments);
					}
				}
			}

			$imageExt = [
				'jpg',
				'jpeg',
				'gif',
				'png'
			];
			$sizeMeasure = [
				'b',
				'Kb',
				'Mb',
				'Gb',
				'Tb'
			];
			$allowedExt = true;

			if (isset($_REQUEST['showOnlyImages'])) {
				$allowedExt = $imageExt;
			} elseif (isset($_REQUEST['showOnlyVideos'])) {
				$allowedExt = [
					'flv',
					'mp4'
				];
			} elseif (isset($_REQUEST['showOnlyMedia'])) {
				$allowedExt = [
					'swf',
					'flv',
					'dcr',
					'mov',
					'qt',
					'mpg',
					'mp3',
					'mp4',
					'mpeg',
					'avi',
					'wmv',
					'wm',
					'asf',
					'asx',
					'wmx',
					'wvx',
					'rm',
					'ra',
					'ram'
				];
			}

			$directory = new DirectoryIterator($this->cwd);
			$cwd = mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR));

			$warning = false;
			$filesData = [];
			$countFiles = 0;
			$wrongFileNameMessage =
				'Error: Присутствуют файлы с недопустимыми названиями! Ошибка: http://errors.umi-cms.ru/13050/';

			foreach ($directory as $file) {
				if ($file->isDir() || $file->isDot()) {
					continue;
				}

				$name = $file->getFilename();
				$ext = mb_substr($name, mb_strrpos($name, '.')+1);

				if ($allowedExt !== true && !in_array(mb_strtolower($ext), $allowedExt)) {
					continue;
				}

				$ts = $file->getCTime();
				$time = date('G:i, d.m.Y' , $ts );
				$size = $file->getSize();

				$img = $file;

				$sCharset = detectCharset($name);

				if (function_exists('iconv') && $sCharset !== 'UTF-8') {
					$warning = $wrongFileNameMessage;
					continue;
				}

				if (!empty($ext)) {
					$sCharset = detectCharset($ext);
					if (function_exists('iconv') && $sCharset !== 'UTF-8') {
						$warning = $wrongFileNameMessage;
						continue;
					}
				}

				$countFiles++;

				$maxFilesCount = (int) mainConfiguration::getInstance()->get('kernel', 'max-guided-items');

				if ($maxFilesCount <= 0) {
					$maxFilesCount = 50;
				}

				if (getRequest('rrr') === null && $maxFilesCount < $countFiles) {
					$data = [
						'empty' => [
							'attribute:result' => 'Too much items'
						]
					];

					return $data;
				}

				$file = [
					'attribute:name' => $name,
					'attribute:type' => $ext,
					'attribute:size' => $size,
					'attribute:ctime' => $time,
					'attribute:timestamp' => $ts
				];

				$i = 0;
				while ($size > 1024.0) {
					$size /= 1024;
					$i++;
				}
				$convertedSize = (int) round($size);

				if ($convertedSize == 1 && (int) floor($size) != $convertedSize) {
					$i++;
				}
				$file['attribute:converted-size'] = $convertedSize.$sizeMeasure[$i];

				if (in_array($ext, $imageExt) && $info = @getimagesize($img->getPath() . '/' . $img->getFilename())) {
					$file['attribute:mime']   = $info['mime'];
					$file['attribute:width']  = $info[0];
					$file['attribute:height'] = $info[1];
				}

				$filesData[] = $file;

			}

			$data = [
				'attribute:folder' => $cwd,
				'data' => [
					'list' => [
						'files' => [
							'nodes:file' => $filesData
						]
					]
				]
			];

			if ($warning != '') {
				$data['data']['warning'] = $warning;
			}

			return $data;
		}

		/**
		 * Возвращает список директорий
		 * @return array
		 */
		public function getfolderlist() {
			$this->module->flushAsXML('getfolderlist');
			$this->setupCwd();

			$folders = glob($this->cwd . '/*', GLOB_ONLYDIR);
			$cwd = mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR));
			$foldersData = [];

			if (is_array($folders)) {
				foreach ($folders as $item) {
					$name = basename($item);
					$foldersData[] = [
						'attribute:name' => $name
					];
				}
			}

			$data = [
				'attribute:folder' => $cwd,
				'data' => [
					'list' => [
						'folders' => [
							'nodes:folder' => $foldersData
						]
					]
				]
			];

			return $data;
		}

		/**
		 * Создает директорию
		 * @return array
		 */
		public function createfolder() {
			$this->module->flushAsXML('createfolder');

			if (isDemoMode()) {
				return $this->getfilelist();
			}

			$folder = rtrim(base64_decode(getRequest('folder')), '/');
			$_REQUEST['folder'] = base64_encode(dirname($folder));
			$folder = basename($folder);
			$this->setupCwd();

			if (!is_dir($this->cwd . '/' . $folder)) {
				mkdir($this->cwd . '/' . $folder);
			}

			return [];
		}

		/**
		 * Удаляет директорию
		 * @return array
		 */
		public function deletefolder() {
			$this->module->flushAsXML('deletefolder');

			if (isDemoMode()) {
				return [];
			}

			$this->setupCwd();

			if (is_dir($this->cwd)) {
				@rmdir($this->cwd);
			}

			return [];
		}

		/**
		 * Загружает файл на сервер
		 * @return array
		 */
		public function uploadfile() {
			$this->module->flushAsXML('uploadfile');
			$this->setupCwd();

			$quota_byte = getBytesFromString( mainConfiguration::getInstance()->get('system', 'quota-files-and-images') );
			if ($quota_byte != 0) {
				$all_size = getBusyDiskSize();
				if ($all_size >= $quota_byte) {
					return [
						'attribute:folder'	=> mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR)),
						'attribute:upload'	=> 'error',
						'nodes:error'		=> [getLabel('error-files_quota_exceeded')]
					];
				}
			}

			if (isDemoMode()) {
				return [
					'attribute:folder'	=> mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR)),
					'attribute:upload'	=> 'done',
				];
			}

			$file = null;

			if (isset($_FILES['Filedata']['name'])) {

				foreach ($_FILES['Filedata'] as $k => $v) {
					$_FILES['Filedata'][$k] = [
						'upload' => $v
					];
				}

				$file = umiFile::upload('Filedata', 'upload', $this->cwd);
			} elseif (isset($_REQUEST['filename'])) {
				$file = umiFile::upload(false, false, $this->cwd);
			}

			$cwd = mb_substr($this->cwd, mb_strlen(CURRENT_WORKING_DIR));
			$result = [
				'attribute:folder'	=> $cwd,
				'attribute:upload'	=> 'done',
			];

			if ($file instanceof iUmiFile) {
				$item = $this->cwd . '/' . $file->getFileName();

				$imageExt = [
					'jpg',
					'jpeg',
					'gif',
					'png'
				];

				$sizeMeasure = [
					'b',
					'Kb',
					'Mb',
					'Gb',
					'Tb'
				];

				$name = $file->getFileName();
				$type = mb_strtolower($file->getExt());
				$ts   = $file->getModifyTime();
				$time = date('g:i, d.m.Y' , $ts );
				$size = $file->getSize();
				$path = $file->getFilePath(true);

				if (isset($_REQUEST['imagesOnly']) && !in_array($type, $imageExt)) {
					unlink($item);
					return $result;
				}

				$file = [
					'attribute:name' => $name,
					'attribute:type' => $type,
					'attribute:size' => $size,
					'attribute:ctime'     => $time,
					'attribute:timestamp' => $ts,
					'attribute:path' => $path
				];

				$i = 0;

				while ($size > 1024.0) {
					$size /= 1024;
					$i++;
				}

				$convertedSize = (int)round($size);

				if ($convertedSize == 1 && (int) floor($size) != $convertedSize) {
					$i++;
				}

				$file['attribute:converted-size'] = $convertedSize . $sizeMeasure[$i];

				if (in_array($type, $imageExt)) {
					$info = @getimagesize($item);

					if ($info) {
						umiImageFile::addWatermark('.' . $cwd . '/' . $name);
						$file['attribute:mime']   = $info['mime'];
						$file['attribute:width']  = $info[0];
						$file['attribute:height'] = $info[1];
					} else {
						unlink($item);
						return $result;
					}
				}

				$result['file'] = $file;
			}

			return $result;
		}

		/**
		 * Удаляет файлы и директории
		 * @return array
		 */
		public function deletefiles() {
			$this->module->flushAsXML('deletefiles');

			if (isDemoMode()) {
				return $this->getfilelist();
			}

			$this->setupCwd();

			if (isset($_REQUEST['delete']) && is_array($_REQUEST['delete'])) {
				foreach ($_REQUEST['delete'] as $item) {
					$item = $this->cwd . '/' . base64_decode($item);

					if (is_dir($item)) {
						@rmdir($item);
					} else {
						@unlink($item);
					}
				}
			}
			if (!isset($_REQUEST['nolisting'])) {
				return $this->getfilelist();
			}
		}

		/**
		 * Переименовывает файл или директорию
		 * @return array
		 */
		public function rename() {
			$this->module->flushAsXML('rename');

			$path = CURRENT_WORKING_DIR . base64_decode(getRequest('oldName'));
			$newName = dirname($path) . '/' . basename(base64_decode(getRequest('newName')));

			$old = getPathInfo($path);
			$new = getPathInfo($newName);

			if (mb_strtolower($old['extension']) != mb_strtolower($new['extension'])) {
				return [];
			}

			$oldDir =  str_replace('\\', '/',  $old['dirname']);
			$newDir =  str_replace('\\', '/',  $new['dirname']);

			if(
				mb_strpos($newDir, USER_IMAGES_PATH ) === false &&
				mb_strpos($newDir, USER_FILES_PATH ) === false &&
				mb_strpos($oldDir, USER_IMAGES_PATH ) === false &&
				mb_strpos($oldDir, USER_FILES_PATH ) === false
			) {
				return [];
			}

			if (isDemoMode()) {
				$newName = $path;
			} else {
				rename($path, $newName);
			}

			return [
				'attribute:path' => mb_substr($newName, mb_strlen(CURRENT_WORKING_DIR))
			];
		}

		/** Выводит в буфер изображение */
		public function getimagepreview() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$file = getRequest('file');

			if ($file) {
				$file = base64_decode($file);

				if ($this->checkPath($file)) {
					$file = CURRENT_WORKING_DIR . $file;

					if (@getimagesize($file) !== false) {
						$buffer->push(file_get_contents($file));
					}
				}
			}

			$buffer->end();
		}

		/**
		 * Устанавливает директорию, в рамках которой производятся работы с файлами
		 * @return mixed|string
		 */
		private function setupCwd() {
			$this->cwd = str_replace("\\", '/', realpath(USER_FILES_PATH));
			$newCwd = getRequest('folder');

			if ($newCwd) {
				$newCwd = rtrim(base64_decode($newCwd), "/\\");
				$newCwd = str_replace("\\", '/', $newCwd);
				if ($this->checkPath($newCwd)) {
					$this->cwd = str_replace("\\", '/', realpath(CURRENT_WORKING_DIR . $newCwd));
				}
			}

			return $this->cwd;
		}

		/**
		 * Проверяет имеет ли право класс работать с данным файлом или директорий
		 * @param string $path путь проверяемого файла или директории
		 * @return bool
		 */
		private function checkPath($path) {
			$allowedRoots = [
				USER_FILES_PATH ,
				USER_IMAGES_PATH
			];

			$path = rtrim($path, '/');
			$path = str_replace("\\", '/', realpath(CURRENT_WORKING_DIR . $path));
			if (mb_strlen($path)) {
				foreach ($allowedRoots as $test) {
					$test = str_replace("\\", '/', realpath($test));
					if (mb_substr($path, 0, mb_strlen($test)) == $test) {
						return true;
					}
				}
			}
			return false;
		}
	}
