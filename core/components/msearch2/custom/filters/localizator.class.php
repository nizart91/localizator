<?php
class mse2LocalizatorFilter extends mse2FiltersHandler {

	/**
	 * Retrieves values from Template Variables table
	 *
	 * @param array $tvs Names of tvs
	 * @param array $ids Ids of needed resources
	 *
	 * @return array Array with tvs values as keys and resources ids as values
	 */
	public function getTvValues(array $tvs, array $ids) {
		$filters = $results = array();

		$q = $this->modx->newQuery('modResource', array('modResource.id:IN' => $ids));
		$q->leftJoin('modTemplateVarTemplate', 'TemplateVarTemplate',
			'TemplateVarTemplate.tmplvarid IN (SELECT id FROM ' . $this->modx->getTableName('modTemplateVar') . ' WHERE name IN ("' . implode('","', $tvs) . '") )
			AND modResource.template = TemplateVarTemplate.templateid'
		);
		$q->leftJoin('modTemplateVar', 'TemplateVar', 'TemplateVarTemplate.tmplvarid = TemplateVar.id');
		$q->leftJoin('modTemplateVarResource', 'TemplateVarResource', 'TemplateVarResource.tmplvarid = TemplateVar.id AND TemplateVarResource.contentid = modResource.id');
		$q->leftJoin('locTemplateVarResource', 'locTemplateVarResource', array(
			'locTemplateVarResource.tmplvarid = TemplateVar.id', 
			'locTemplateVarResource.contentid = modResource.id',
			'locTemplateVarResource.key:=' => $this->modx->getOption('localizator_key', $this->config, $this->modx->localizator_key, true),
		));
		$q->select('TemplateVar.name, 
		            IF(
                        (TemplateVar.localizator_enabled = 1),
                        locTemplateVarResource.value,
                        TemplateVarResource.value
                    ) value,
		            IF(
                        (TemplateVar.localizator_enabled = 1),
                        locTemplateVarResource.contentid,
                        TemplateVarResource.contentid
                    ) id,
		            TemplateVar.type, 
		            TemplateVar.default_text
		');
		$tstart = microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$this->modx->queryTime += microtime(true) - $tstart;
			$this->modx->executedQueries++;
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				if (empty($row['id'])) {
					continue;
				}
				if (is_null($row['value']) || trim($row['value']) == '') {
					$row['value'] = $row['default_text'];
				}
				if ($row['type'] == 'tag' || $row['type'] == 'autotag') {
					$row['value'] = str_replace(',', '||', $row['value']);
				}
				$tmp = strpos($row['value'], '||') !== false
					? explode('||', $row['value'])
					: array($row['value']);
				foreach ($tmp as $v) {
					$v = str_replace('"', '&quot;', trim($v));
					if ($v == '') {
						continue;
					}
					$name = strtolower($row['name']);
					if (isset($filters[$name][$v])) {
						$filters[$name][$v][$row['id']] = $row['id'];
					}
					else {
						$filters[$name][$v] = array($row['id'] => $row['id']);
					}
				}
			}
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: ".$q->toSQL()."\nResponse: ".print_r($q->stmt->errorInfo(),1));
		}

		return $filters;
	}

	/**
	 * Retrieves values from Resource table
	 *
	 * @param array $fields Names of resource fields
	 * @param array $ids Ids of needed resources
	 *
	 * @return array Array with resource fields as keys and resources ids as values
	 */
	public function getResourceValues(array $fields, array $ids) {
		$filters = array();
		$no_id = false;
		if (!in_array('id', $fields)) {
			$fields[] = 'id';
			$no_id = true;
		}
		$q = $this->modx->newQuery('modResource');
		$q->innerJoin('localizatorContent', 'localizatorContent', array(
			'localizatorContent.resource_id = modResource.id',
			'localizatorContent.key:=' => $this->modx->getOption('localizator_key', $this->config, $this->modx->localizator_key, true),
			'localizatorContent.active' => 1,
		));
		$this->modx->loadClass('localizatorContent');
		$localizatorFields = array_intersect(array_diff(array_keys($this->modx->map['localizatorContent']['fields']), array('resource_id', 'key', 'active')), $fields);
		$fields = array_diff($fields,$localizatorFields);

		if (count($fields) > 0){
			$q->select(implode(',', array_map(function($value) {
			    return "`modResource`.`{$value}`";
			}, $fields)));
		}
		if (count($localizatorFields) > 0){
			$q->select(implode(',', array_map(function($value) {
			    return "`localizatorContent`.`{$value}`";
			}, $localizatorFields)));
		}
		
		$q->where(array('modResource.id:IN' => $ids));
		if (in_array('parent', $fields) && $this->mse2->checkMS2()) {
			$q->leftJoin('msCategoryMember','Member', 'Member.product_id = modResource.id');
			$q->orCondition(array('Member.product_id:IN' => $ids));
			$q->select('category_id');
		}
		$tstart = microtime(true);

		if ($q->prepare() && $q->stmt->execute()) {
			$this->modx->queryTime += microtime(true) - $tstart;
			$this->modx->executedQueries++;
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				foreach ($row as $k => $v) {
					$v = str_replace('"', '&quot;', trim($v));
					if ($k == 'category_id') {
						if (!$v || $v == $row['parent']) {
							continue;
						}
						else {
							$k = 'parent';
						}
					}
					if ($k == 'id' && $no_id) {
						continue;
					}
					elseif (isset($filters[$k][$v])) {
						$filters[$k][$v][$row['id']] = $row['id'];
					}
					else {
						$filters[$k][$v] = array($row['id'] => $row['id']);
					}
				}
			}
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: ".$q->toSQL()."\nResponse: ".print_r($q->stmt->errorInfo(),1));
		}

		return $filters;
	}

	/**
	 * Prepares values for filter
	 * Returns array with human-readable parents of resources
	 *
	 * @param array $values
	 * @param string $name Filter name
	 * @param integer $depth
	 * @param string $separator
	 *
	 * @return array Prepared values
	 */
	public function buildParentsFilter(array $values, $name = '', $depth = 1, $separator = ' / ') {
		$results = $parents = $menuindex = array();
		$q = $this->modx->newQuery('modResource', array('modResource.id:IN' => array_keys($values), 'published' => 1));
		$q->innerJoin('localizatorContent', 'localizatorContent', array(
			'localizatorContent.resource_id = modResource.id',
			'localizatorContent.key:=' => $this->modx->getOption('localizator_key', $this->config, $this->modx->localizator_key, true),
			'localizatorContent.active' => 1,
		));
		$q->select('modResource.id,localizatorContent.pagetitle,localizatorContent.menutitle,modResource.context_key,modResource.menuindex');
		$tstart = microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$this->modx->queryTime += microtime(true) - $tstart;
			$this->modx->executedQueries++;
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				$parents[$row['id']] = $row;
				$menuindex[$row['id']] = $row['menuindex'];
			}
		}

		foreach ($values as $value => $ids) {
			if ($value === 0 || !isset($parents[$value])) {
				continue;
			}
			$parent = $parents[$value];
			$titles = array();
			if ($depth > 0) {
				$pids = $this->modx->getParentIds($value, $depth, array('context' => $parent['context_key']));
				if (!empty($pids)) {
					$q = $this->modx->newQuery('modResource', array('modResource.id:IN' => array_reverse($pids), 'published' => 1));
					$q->innerJoin('localizatorContent', 'localizatorContent', array(
						'localizatorContent.resource_id = modResource.id',
						'localizatorContent.key:=' => $this->modx->getOption('localizator_key', $this->config, $this->modx->localizator_key, true),
						'localizatorContent.active' => 1,
					));
					$q->select('modResource.id,localizatorContent.pagetitle,localizatorContent.menutitle');
					$tstart = microtime(true);
					if ($q->prepare() && $q->stmt->execute()) {
						$this->modx->queryTime += microtime(true) - $tstart;
						$this->modx->executedQueries++;
						while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
							$titles[$row['id']] = !empty($row['menutitle'])
								? $row['menutitle']
								: $row['pagetitle'];
						}
					}
				}
			}
			$titles[$value] = !empty($parent['menutitle'])
				? $parent['menutitle']
				: $parent['pagetitle'];

			$title = implode($separator, $titles);
			$results[$menuindex[$value]][$title] = array(
				'title' => $title,
				'value' => $value,
				'type' => 'parents',
				'resources' => $ids,
			);
		}

		return count($results) < 2 && empty($this->config['showEmptyFilters'])
			? array()
			: $this->sortFilters($results, 'parents', array('name' => $name));
	}

	/**
	 * Prepares values for filter
	 * Returns array with human-readable grandparent of resource
	 *
	 * @param array $values
	 * @param string $name
	 * @param boolean $filter
	 *
	 * @return array
	 */
	public function buildGrandParentsFilter(array $values, $name = '', $filter = false) {
		if (count($values) < 2 && empty($this->config['showEmptyFilters'])) {
			return array();
		}

		$grandparents = array();
		$q = $this->modx->newQuery('modResource', array('modResource.id:IN' => array_keys($values), 'published' => 1));
		$q->innerJoin('localizatorContent', 'localizatorContent', array(
			'localizatorContent.resource_id = modResource.id',
			'localizatorContent.key:=' => $this->modx->getOption('localizator_key', $this->config, $this->modx->localizator_key, true),
			'localizatorContent.active' => 1,
		));
		$q->select('modResource.id,parent');
		$tstart = microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$this->modx->queryTime += microtime(true) - $tstart;
			$this->modx->executedQueries++;
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				$grandparents[$row['id']] = $row['parent'];
			}
		}

		$tmp = array();
		foreach ($values as $k => $v) {
			if (isset($grandparents[$k]) && $grandparents[$k] != 0) {
				$parent = $grandparents[$k];
				if (!isset($tmp[$parent])) {
					$tmp[$parent] = $v;
				}
				else {
					$tmp[$parent] = array_merge($tmp[$parent], $v);
				}
			}
			else {
				$tmp[$k] = $v;
			}
		}

		return $filter
			? $tmp
			: $this->buildParentsFilter($tmp, $name, 0);
	}
	
	/**
	 * Retrieves values from miniShop2 Product table
	 *
	 * @param array $keys Keys of ms2 products options
	 * @param array $ids Ids of needed resources
	 *
	 * @return array Array with ms2 fields as keys and resources ids as values
	 */
	public function getMsOptionValues(array $keys, array $ids) {
	    $this->modx->getService('msLocalizatorOptions', 'msLocalizatorOptions', MODX_CORE_PATH . 'components/mslocalizatoroptions/model/');
		$filters = array();
		$fields = $this->modx->getFields('msProductData');
		$q = $this->modx->newQuery('msLocalizatorOption');
		if (array_key_exists('sku_id', $fields)) {
			$q->innerJoin('msProductData', 'Data', 'Data.sku_id = msLocalizatorOption.product_id');
			$q->where(array('Data.id:IN' => $ids, 'key:IN' => $keys));
			$q->select('Data.id as product_id, key, value');
		}
		else {
			$q->where(array(
				'product_id:IN' => $ids,
				'key:IN' => $keys,
				'localizator_key' => $this->modx->getOption('localizator_key', $this->config, $this->modx->localizator_key, true)
			));
			$q->select('product_id, key, value');
		}
		$tstart = microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$this->modx->queryTime += microtime(true) - $tstart;
			$this->modx->executedQueries++;
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				$value = str_replace('"', '&quot;', trim($row['value']));
				//if ($value == '') {continue;}
				$key = strtolower($row['key']);
				// Get ready for the special options in "key==value" format
				if (strpos($value, '==')) {
					list($key, $value) = explode('==', $value);
					$key = preg_replace('/\s+/', '_', $key);
				}
				// --
				if (isset($filters[$key][$value])) {
					$filters[$key][$value][$row['product_id']] = $row['product_id'];
				}
				else {
					$filters[$key][$value] = array($row['product_id'] => $row['product_id']);
				}
			}
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: " . $q->toSQL() . "\nResponse: " . print_r($q->stmt->errorInfo(), 1));
		}

		return $filters;
	}
	
	/**
	 * Prepares values for filter
	 * Sort ms2 product options by order in its settings
	 *
	 * @param array $values IDs of resources
	 * @param string $name Name of option
	 *
	 * @return array Prepared values
	 */
	public function buildOptionsFilter(array $values, $name = '') {
	    $this->modx->getService('msLocalizatorOptions', 'msLocalizatorOptions', MODX_CORE_PATH . 'components/mslocalizatoroptions/model/');
		$keys = array_keys($values);
		if (empty($keys) || (count($keys) < 2 && empty($this->config['showEmptyFilters']))) {
			return array();
		}

		$values = $this->buildDefaultFilter($values, $name);
		
		/** @var msOption $option */
		if ($option = $this->modx->getObject('msLocalizatorOption', array(
			'key' => $name,
			'localizator_key' => $this->modx->getOption('localizator_key', $this->config, $this->modx->localizator_key, true)))) {
			if ($properties = $option->get('properties')) {
				if (!empty($properties['values'])) {
					$tmp = array();
					foreach ($properties['values'] as $key) {
						if (isset($values[$key])) {
							$tmp[$key] = $values[$key];
							unset($values[$key]);
						}
					}
					$values = array_merge($tmp, $values);
				}
			}
		}

		return $this->sortFilters($values, 'options', array('name' => $name));
	}
}