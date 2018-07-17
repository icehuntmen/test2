<?php

class catalog extends def_module {
	public $per_page;
    protected $umiHierarchy;

	public function __construct() {
		parent::__construct();

		$this->loadCommonExtension();

		if($this->cmsController->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__catalog");
			
			$this->__loadLib("__admin_filters.php");
			$this->__implement("AdminFilters");

			$this->loadAdminExtension();

			$this->__loadLib("__custom_adm.php");
			$this->__implement("__custom_adm_catalog");
			
			$commonTabs = $this->getCommonTabs();
			
			if($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('tree', array('tree'));
				$commonTabs->add("filters", array('filters'));
			}
			
		} else {
			$this->per_page = $this->regedit->getVal("//modules/catalog/per_page");
			$this->autoDetectAttributes();
		}

		$this->loadSiteExtension();

		$this->__loadLib("__custom.php");
		$this->__implement("__custom_catalog");

		$this->__loadLib("__search.php");
		$this->__implement("__search_catalog");

		$this->__loadLib("__filter.php");
		$this->__implement("__filter_catalog");

		$this->__loadLib("__filter_events_handlers.php");
		$this->__implement("__catalog_filter_events_handlers");

        $this->umiHierarchy = umiHierarchy::getInstance();
	}


	public function category($template = "default", $element_path = false) {
		if(!$template) $template = "default";
		list($template_block) = def_module::loadTemplates("catalog/".$template, "category");

		$category_id = $this->analyzeRequiredPath($element_path);
		$controller = $this->cmsController;

		$hierarchy = $this->umiHierarchy;
		if(!$category_id && $category_id = getRequest('param0') && $controller->getCurrentModule() == "catalog" && $controller->getCurrentMethod() == "category") {
            $category = $hierarchy->getElement($category_id);
			$link = $this->umiLinksHelper->getLink($category);
			$this->redirect($link);
		}

        $category = $hierarchy->getElement($category_id);
		$link = $this->umiLinksHelper->getLink($category);
            $block_arr = array(
			'category_id'	=> $category_id,
			'category_path'	=> $link,
			'link'			=> $link
		);

		$this->pushEditable("catalog", "category", $category_id);
		return self::parseTemplate($template_block, $block_arr, $category_id);
	}

	public function getCategoryList($template = "default", $category_id = false, $limit = false, $ignore_paging = false, $i_need_deep = 0) {
		if(!$template) $template = "default";
		list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("catalog/".$template, "category_block", "category_block_empty", "category_block_line");

		if (!$i_need_deep) $i_need_deep = intval(getRequest('param4'));
		if (!$i_need_deep) $i_need_deep = 0;
		$i_need_deep = intval($i_need_deep);
		if ($i_need_deep === -1) $i_need_deep = 100;

		if((string) $category_id != '0') $category_id = $this->analyzeRequiredPath($category_id);

		/** @var social_networks $social_module */
		$social_module = $this->cmsController->getModule("social_networks");
		if($social_module) {
			$social = $social_module->getCurrentSocial();
		}
		else {
			$social = false;
		}

		if ($category_id === false) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $category_id));
		}

		$per_page = ($limit) ? $limit : $this->per_page;
		$curr_page = (int) getRequest('p');
		if ($ignore_paging) $curr_page = 0;

		$categories = new selector('pages');
		$categories->types('object-type')->name('catalog', 'category');
		if (is_array($category_id)) {
			foreach ($category_id as $category) {
				$categories->where('hierarchy')->page($category)->childs($i_need_deep + 1);
			}
		} else {
			$categories->where('hierarchy')->page($category_id)->childs($i_need_deep + 1);
		}
		$categories->limit($curr_page * $per_page, $per_page);
		$result = $categories->result();
		$total = $categories->length();

		if(($sz = count($result)) > 0) {
			$block_arr = array();
			$lines = array();

			foreach ($result as $element) {
				if (!$element instanceof umiHierarchyElement) {
					continue;
				}

				$element_id = $element->getId();

				if($social && !$social->isHierarchyAllowed($element_id)) {
					continue;
				}

				$line_arr = Array();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['void:alt_name'] = $element->getAltName();
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($element);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['node:text'] = $element->getName();
				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);
			}

			if (is_array($category_id)) {
				list($category_id) = $category_id;
			}

			$block_arr['attribute:category-id'] = $block_arr['void:category_id'] = $category_id;
			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return self::parseTemplate($template_block, $block_arr, $category_id);
		} else {
			$block_arr = array();
			$block_arr['attribute:category-id'] = $block_arr['void:category_id'] = $category_id;
			return self::parseTemplate($template_block_empty, $block_arr, $category_id);
		}
	}

	/** @deprecated, use getSmartCatalog() */
	public function getObjectsList($template = "default", $path = false, $limit = false, $ignore_paging = false, $i_need_deep = 0, $field_id = false, $asc = true) {
		if(!$template) $template = "default";

		if (!$i_need_deep) $i_need_deep = intval(getRequest('param4'));
		if (!$i_need_deep) $i_need_deep = 0;
		$i_need_deep = intval($i_need_deep);
		if ($i_need_deep === -1) $i_need_deep = 100;

		$hierarchy = $this->umiHierarchy;

		list($template_block, $template_block_empty, $template_block_search_empty, $template_line) = def_module::loadTemplates("catalog/".$template, "objects_block", "objects_block_empty", "objects_block_search_empty", "objects_block_line");

		$hierarchy_type_id = $this->umiHierarchyTypesCollection->getTypeByName("catalog", "object")->getId();

		$category_id = $this->analyzeRequiredPath($path);

		if($category_id === false && $path != KEYWORD_GRAB_ALL) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $path));
		}

		$per_page = ($limit) ? $limit : $this->per_page;
		$curr_page = getRequest('p');
		if($ignore_paging) $curr_page = 0;

		$sel = new umiSelection;
		$sel->setElementTypeFilter();
		$sel->addElementType($hierarchy_type_id);

		if($path != KEYWORD_GRAB_ALL) {
			$sel->setHierarchyFilter();
			$sel->addHierarchyFilter($category_id, $i_need_deep);
		}

		$sel->setPermissionsFilter();
		$sel->addPermissions();

		$hierarchy_type = $this->umiHierarchyTypesCollection->getType($hierarchy_type_id);
		$type_id = $this->umiObjectTypesCollection->getTypeIdByHierarchyTypeName($hierarchy_type->getName(), $hierarchy_type->getExt());


		if($path === KEYWORD_GRAB_ALL) {
			$curr_category_id = $this->cmsController->getCurrentElementId();
		} else {
			$curr_category_id = $category_id;
		}


		if($path != KEYWORD_GRAB_ALL) {
			$type_id = $hierarchy->getDominantTypeId($curr_category_id, $i_need_deep, $hierarchy_type_id);
		}

		if(!$type_id) {
			$type_id = $this->umiObjectTypesCollection->getTypeIdByHierarchyTypeName($hierarchy_type->getName(), $hierarchy_type->getExt());
		}


		if($type_id) {
			$this->autoDetectOrders($sel, $type_id);
			$this->autoDetectFilters($sel, $type_id);

			if($this->isSelectionFiltered) {
				$template_block_empty = $template_block_search_empty;
				$this->isSelectionFiltered = false;
			}
		} else {
			$sel->setOrderFilter();
			$sel->setOrderByName();
		}

		if($curr_page !== "all") {
			$curr_page = (int) $curr_page;
			$sel->setLimitFilter();
			$sel->addLimit($per_page, $curr_page);
		}

		if($field_id) {
			if (is_numeric($field_id)) {
				$sel->setOrderByProperty($field_id, $asc);
			} else {
				if ($type_id) {
					$field_id = $this->umiObjectTypesCollection->getType($type_id)->getFieldId($field_id);
					if ($field_id) {
						$sel->setOrderByProperty($field_id, $asc);
					} else {
						$sel->setOrderByOrd($asc);
					}
				} else {
					$sel ->setOrderByOrd($asc);
				}
			}
		}
		else {
			$sel ->setOrderByOrd($asc);
		}

		$result = umiSelectionsParser::runSelection($sel);
		$total = umiSelectionsParser::runSelectionCounts($sel);

		if(($sz = count($result)) > 0) {
			$block_arr = Array();
			$this->loadElements($result, true, $this->umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object'));
			$lines = Array();
			for($i = 0; $i < $sz; $i++) {
				$element_id = $result[$i];
				$element = $this->umiHierarchy->getElement($element_id);

				if (!$element instanceof umiHierarchyElement) {
					continue;
				}

				$line_arr = Array();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:alt_name'] = $element->getAltName();
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($element);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['node:text'] = $element->getName();
				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);
				$this->pushEditable("catalog", "object", $element_id);
				$this->umiHierarchy->unloadElement($element_id);
			}

			$block_arr['subnodes:lines'] = $lines;
			$block_arr['numpages'] = umiPagenum::generateNumPage($total, $per_page);
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['category_id'] = $category_id;

			if($type_id) {
				$block_arr['type_id'] = $type_id;
			}

			return self::parseTemplate($template_block, $block_arr, $category_id);
		} else {
			$block_arr['numpages'] = umiPagenum::generateNumPage(0, 0);
			$block_arr['lines'] = "";
			$block_arr['total'] = 0;
			$block_arr['per_page'] = 0;
			$block_arr['category_id'] = $category_id;

			return self::parseTemplate($template_block_empty, $block_arr, $category_id);
		}

	}


	public function object($template = "default", $element_path = false) {
		if(!$template) $template = "default";

		$element_id = $this->analyzeRequiredPath($element_path);

		$this->pushEditable("catalog", "object", $element_id);
		return $this->viewObject($element_id, $template);
	}

	public function viewObject($element_id, $template = "default") {
		if(!$template) $template = "default";

		$element_id = $this->analyzeRequiredPath($element_id);

		$element = $this->umiHierarchy->getElement($element_id);

		if(!$element) {
			return "";
		}

		$block_arr = Array();
		list($template_block) = self::loadTemplates("catalog/".$template, "view_block");

		$block_arr['id'] = $element_id;
		$block_arr['name'] = $element->getName();
		$block_arr['alt_name'] = $element->getAltName();
		$block_arr['link'] = $this->umiLinksHelper->getLink($element);

		$this->pushEditable("catalog", "object", $element_id);
		return self::parseTemplate($template_block, $block_arr, $element_id);
	}

	/** @deprecated, use getSmartFilters() */
	public function search($category_id = false, $group_names = "", $template = "default", $type_id = false) {
		/** @var __search_catalog|self|def_module $this */
		if(!$template) $template = "default";

        if (!is_numeric($category_id)) {
			$category_id = $this->analyzeRequiredPath($category_id);
			if(!$category_id) return "";
		}


		list($template_block, $template_block_empty, $template_block_line, $template_block_line_text,
			$template_block_line_relation, $template_block_line_item_relation, $template_block_line_item_relation_separator,
			$template_block_line_price, $template_block_line_boolean, $template_block_line_symlink) =

			self::loadTemplates("catalog/".$template, "search_block", "search_block_empty",
			"search_block_line", "search_block_line_text", "search_block_line_relation",
			"search_block_line_item_relation", "search_block_line_item_relation_separator",
			"search_block_line_price", "search_block_line_boolean", "search_block_line_symlink");

		$block_arr = Array();

		if($type_id === false) {
			$type_id = $this->umiHierarchy->getDominantTypeId($category_id);
		}

		if(is_null($type_id)) return "";

		if(!($type = $this->umiObjectTypesCollection->getType($type_id))) {
			trigger_error("Failed to load type", E_USER_WARNING);
			return "";
		}

		$fields = array();
		/** @var umiFieldsGroup[] $groups */
		$groups = array();
		$lines = array();

		$group_names = trim($group_names);
		if($group_names) {
			$group_names_arr = explode(" ", $group_names);
    			foreach($group_names_arr as $group_name) {
				if(!($fields_group = $type->getFieldsGroupByName($group_name))) {
				} else {
					$groups[] = $fields_group;
				}
			}
		} else {
			$groups = $type->getFieldsGroupsList();
		}


		$lines_all = Array();
		$groups_arr = Array();

		foreach($groups as $fields_group) {
			$fields = $fields_group->getFields();

			$group_block = Array();
			$group_block['attribute:name'] = $fields_group->getName();
			$group_block['attribute:title'] = $fields_group->getTitle();

			$lines = Array();


			foreach($fields as $field_id => $field) {
				if(!$field->getIsVisible()) continue;
				if(!$field->getIsInFilter()) continue;

				$line_arr = Array();

				$field_type_id = $field->getFieldTypeId();
				$field_type = $this->umiFieldTypesCollection->getFieldType($field_type_id);

				$data_type = $field_type->getDataType();

				$line = Array();
				switch($data_type) {
					case "relation": {
						$line = $this->parseSearchRelation($field, $template_block_line_relation, $template_block_line_item_relation, $template_block_line_item_relation_separator);
						break;
					}

					case "text": {
						$line = $this->parseSearchText($field, $template_block_line_text);
						break;
					}

					case "date": {
						$line = $this->parseSearchDate($field, $template_block_line_text);
						break;
					}

					case "string": {
						$line = $this->parseSearchText($field, $template_block_line_text);
						break;
					}

					case "wysiwyg": {
						$line = $this->parseSearchText($field, $template_block_line_text);
						break;
					}

					case "float":
					case "price": {
			    			$line = $this->parseSearchPrice($field, $template_block_line_price);
						break;
					}

					case "int": {
						$line = $this->parseSearchInt($field, $template_block_line_price);
						break;
					}

					case "boolean": {
						$line = $this->parseSearchBoolean($field, $template_block_line_boolean);
						break;
					}

					case "symlink": {
						$line = $this->parseSearchSymlink($field, $template_block_line_symlink, $category_id);
						break;
					}

					default: {
						$line = "[search filter for \"{$data_type}\" not specified]";
						break;
					}
				}

				if (self::isXSLTResultMode()) {
					if (is_array($line)) {
					$line['attribute:data-type'] = $data_type;
				}
				}

				$line_arr['void:selector'] = $line;

				if (self::isXSLTResultMode()) {
					$lines[] = $line;
				} else {
					$lines[] = $tmp = self::parseTemplate($template_block_line, $line_arr);
					$lines_all[] = $tmp;
				}
			}


			if(empty($lines)) {
				continue;
			}

			$group_block['nodes:field'] = $lines;
			$groups_arr[] = $group_block;

		}

		$block_arr['void:items'] = $block_arr['void:lines'] = $lines_all;
		$block_arr['nodes:group'] = $groups_arr;
		$block_arr['attribute:category_id'] = $category_id;

		if(!$groups_arr && !$lines && !$this->isXSLTResultMode()) {
			return $template_block_empty;
		}

		return self::parseTemplate($template_block, $block_arr);
	}


	public function addCatalogObject() {
		$hierarchy = $this->umiHierarchy;
		$hierarchyTypes = $this->umiHierarchyTypesCollection;
		$objectTypes = $this->umiObjectTypesCollection;
		$cmsController = $this->cmsController;

		$parent_id = (int) getRequest('param0');
		$object_type_id = (int) getRequest('param1');
		$title = htmlspecialchars(trim(getRequest('title')));

		$parentElement = $hierarchy->getElement($parent_id);
		$tpl_id		= $parentElement->getTplId();
		$domain_id	= $parentElement->getDomainId();
		$lang_id	= $parentElement->getLangId();

		$hierarchy_type_id = $hierarchyTypes->getTypeByName("catalog", "object")->getId();
		if(!$object_type_id) {
			$object_type_id = $objectTypes->getTypeIdByHierarchyTypeName("catalog", "object");
		}

		if($parentElement instanceof umiHierarchyElement) {
			if($type_id = $hierarchy->getDominantTypeId($parent_id)) {
				$object_type_id = $type_id;
			}
		}


		$object_type = $objectTypes->getType($object_type_id);
		if($object_type->getHierarchyTypeId() != $hierarchy_type_id) {
			$this->errorNewMessage("Object type and hierarchy type doesn't match");
			$this->errorPanic();
		}

		$element_id = $hierarchy->addElement($parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);

		$users = $cmsController->getModule("users");
		if($users instanceof def_module) {
			$users->setDefaultPermissions($element_id);
		}

		$element = $hierarchy->getElement($element_id, true);

		$element->setIsActive(true);
		$element->setIsVisible(false);
		$element->setName($title);

		$data = $cmsController->getModule("data");
		if($data instanceof def_module) {
			$object_id = $element->getObjectId();
			$data->saveEditedObject($object_id, true);
		}
		$element->getObject()->commit();
		$element->commit();
		$parentElement->setUpdateTime(time());
		$parentElement->commit();

		if(self::isXSLTResultMode()) {
			return Array("node:result" => "ok");
		} else {
			if($element->getIsActive()) {
				$referer_url = $hierarchy->getPathById($element_id);
			} else {
				$referer_url = getServer('HTTP_REFERER');
			}
			$this->redirect($referer_url);
		}
	}


	public function getEditLink($element_id = false, $element_type = false) {
		$element = $this->umiHierarchy->getElement($element_id);
		if (!$element) return false;
		switch($element_type) {
			case "category": {
				$link_add = $this->pre_lang . "/admin/catalog/add/{$element_id}/object/";
				$link_edit = $this->pre_lang . "/admin/catalog/edit/{$element_id}/";

				return Array($link_add, $link_edit);
				break;
			}

			case "object": {
				$link_edit = $this->pre_lang . "/admin/catalog/edit/{$element_id}/";

				return Array(false, $link_edit);
				break;
			}

			default: {
				return false;
			}
		}
	}

	/**
	 * Возвращает идентификатор иерархического объектов каталога (товаров)
	 * @return int
	 */
	public function getProductHierarchyTypeId() {
		static $cache;

		if (is_numeric($cache)) {
			return $cache;
		}

		$umiTypesHelper = umiTypesHelper::getInstance();
		return $cache = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');
	}

};

?>