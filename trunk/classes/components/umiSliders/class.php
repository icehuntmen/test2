<?php

	use UmiCms\Service;

	/** Базовый класс модуля "Слайдеры" */
class umiSliders extends def_module {
	/** @var UmiCms\Classes\Components\UmiSliders\SlidersCollection $slidersCollection */
	private $slidersCollection;
	/** @var UmiCms\Classes\Components\UmiSliders\SlidesCollection $slidesCollection */
	private $slidesCollection;

	/** Конструктор */
	public function __construct() {
		parent::__construct();
		$this->initProperties();

		if (Service::Request()->isAdmin()) {
			$this->initTabs()
				->includeAdminClasses();
		}

		$this->includeCommonClasses();
	}

	/**
	 * Возвращает коллекцию слайдов
	 * @return iUmiService|\UmiCms\Classes\Components\UmiSliders\SlidesCollection
	 * @throws RequiredPropertyHasNoValueException
	 */
	public function getSlidesCollection() {
		if (!$this->slidesCollection instanceof \UmiCms\Classes\Components\UmiSliders\SlidesCollection) {
			throw new RequiredPropertyHasNoValueException('You should set SlidesCollection first');
		}

		return $this->slidesCollection;
	}

	/**
	 * Возвращает коллекцию слайдеров
	 * @return iUmiService|\UmiCms\Classes\Components\UmiSliders\SlidersCollection
	 * @throws RequiredPropertyHasNoValueException
	 */
	public function getSlidersCollection() {
		if (!$this->slidersCollection instanceof \UmiCms\Classes\Components\UmiSliders\SlidersCollection) {
			throw new RequiredPropertyHasNoValueException('You should set SlidersCollection first');
		}

		return $this->slidersCollection;
	}

	/**
	 * Создает вкладки административной панели модуля
	 * @return UmiSliders
	 */
	protected function initTabs() {
		$configTabs = $this->getConfigTabs();

		if ($configTabs instanceof iAdminModuleTabs) {
			$configTabs->add('config');
		}

		$commonTabs = $this->getCommonTabs();

		if ($commonTabs instanceof iAdminModuleTabs) {
			$commonTabs->add('getSliders');
		}

		return $this;
	}

	/**
	 * Подключает классы функционала административной панели
	 * @return UmiSliders
	 */
	protected function includeAdminClasses() {
		$this->__loadLib('admin.php');
		$this->__implement('UmiSlidersAdmin');

		$this->loadAdminExtension();

		$this->__loadLib('customAdmin.php');
		$this->__implement('UmiSlidersCustomAdmin', true);

		return $this;
	}

	/**
	 * Подключает общие классы функционала
	 * @return UmiSliders
	 */
	protected function includeCommonClasses() {
		$this->__loadLib('macros.php');
		$this->__implement('UmiSlidersMacros');

		$this->loadSiteExtension();

		$this->__loadLib('customMacros.php');
		$this->__implement('UmiSlidersCustomMacros', true);

		$this->__loadLib('handlers.php');
		$this->__implement('UmiSlidersHandlers');

		$this->loadCommonExtension();
		$this->loadTemplateCustoms();

		return $this;
	}

	/** Инициализирует свойства */
	protected function initProperties() {
		$serviceContainer = ServiceContainerFactory::create();

		$slidesCollection = $serviceContainer->get('SlidesCollection');
		$this->setSlidesCollection($slidesCollection);

		$slidersCollection = $serviceContainer->get('SlidersCollection');
		$this->setSlidersCollection($slidersCollection);
	}

	/**
	 * Устанавливает экземпляр коллекции слайдов и возвращает текущий объект
	 * @param \UmiCms\Classes\Components\UmiSliders\SlidesCollection $slidesCollection экземпляр коллекции слайдов
	 * @return UmiSlidersAdmin
	 */
	private function setSlidesCollection(\UmiCms\Classes\Components\UmiSliders\SlidesCollection $slidesCollection) {
		$this->slidesCollection = $slidesCollection;
		return $this;
	}

	/**
	 * Устанавливает экземпляр коллекции слайдеров и возвращает текущий объект
	 * @param \UmiCms\Classes\Components\UmiSliders\SlidersCollection $slidersCollection экземпляр коллекции слайдеров
	 * @return UmiSlidersAdmin
	 */
	private function setSlidersCollection(\UmiCms\Classes\Components\UmiSliders\SlidersCollection $slidersCollection) {
		$this->slidersCollection = $slidersCollection;
		return $this;
	}
}
