<?php

class localizatorContentTranslateProcessor extends modProcessor {

    /* @var localizator $localizator */
    public $localizator;

    public function process() 
	{

		$this->localizator = $this->modx->getService('localizator');

		if (!$resource_id = $this->getProperty('resource_id')) {
            return $this->failure('Не указан id ресурса');
        }

		if (!$default_language = $this->modx->getOption('localizator_default_language')) {
			return $this->failure('Не указана опция localizator_default_language, невозможно определить исходный языка для перевода');
		}

		/* @var localizatorContent $default_content */
		$default_content = $this->modx->getObject('localizatorContent', array('key' => $default_language, 'resource_id' => $resource_id));
		if(!$default_content) {
			return $this->failure('Для автоматического перевода необходимо добавить хотя бы одну запись в таблицу');
		}

        $loc_permission = $this->modx->getOption('localizator_check_permissions', null, false, true);

		$translate_translated = $this->modx->getOption('localizator_translate_translated', null, false);
		$translate_translated_fields = $this->modx->getOption('localizator_translate_translated_fields', null, false);
		$translate_fields = explode(',', $this->modx->getOption('localizator_translate_fields', null, 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content'));


        $processed = 0; 

        $c = $this->modx->newQuery('localizatorLanguage');
        $c->limit(1000000); 
		$c->where(array(
			'key:!=' => $default_language
		));
		
		$total = $this->modx->getCount('localizatorLanguage', $c);

		$defaultTVs = $default_content->loadTVs();

		$languages = $this->modx->getIterator('localizatorLanguage', $c);
		foreach ($languages as $language) {

            if ($loc_permission && !$this->modx->hasPermission("localizatorcontent_save_" . $language->key)){
                continue;
            }

			//$this->modx->log(1, 'Перевод на ' . $language->key . ' - ' . $resource_id);

            /* @var localizatorContent $content */
			$content = $this->modx->getObject('localizatorContent', array('key' => $language->key, 'resource_id' => $resource_id));
			if($content && $translate_translated) {
				$contentData = $content->toArray();

				foreach($translate_fields as $field) {
					$current = $content->get($field);
					$val = $default_content->get($field);
					if(empty($val)) continue;
					if(empty($current) || !empty($current) && $translate_translated_fields) {
						if (isset($this->modx->map['localizatorContent']['fieldMeta'][$field])){
							$contentData[$field] = $this->localizator->translate($val, $default_language, ($language->cultureKey ?: $language->key));
						}
						elseif (isset($defaultTVs[$field])){
							if ($tv = $this->modx->getObject('modTemplateVar', ['name' => $field])){
								$tv->set('value', $val);
								$contentData[$field] = $this->translateTV($tv, $default_language, ($language->cultureKey ?: $language->key));
							}
						}
					}
				}
				$response = $this->modx->runProcessor('mgr/content/update', 
	                $contentData, 
	                array(
	                    'processors_path' => $this->localizator->config['processorsPath']
	            	)
	            );
	            if ($response->isError()) {
	                return $response->getResponse();
	            }

			} else if(!$content) {
				/*
				$content = $this->modx->newObject('localizatorContent');
				$content->fromArray(array(
					'key' => $language->key,
					'resource_id' => $resource_id,
					'active' => 1,
				));*/
				$contentData = array(
					'key' => $language->key,
					'resource_id' => $resource_id,
					'active' => 1,
				);
				foreach($translate_fields as $field) {
					$val = $default_content->get($field);
					if(!empty($val)) {
						if (isset($this->modx->map['localizatorContent']['fieldMeta'][$field])){
							$contentData[$field] = $this->localizator->translate($val, $default_language, ($language->cultureKey ?: $language->key));
						}
						elseif (isset($defaultTVs[$field])){
							if ($tv = $this->modx->getObject('modTemplateVar', ['name' => $field])){
								$tv->set('value', $val);
								$contentData[$field] = $this->translateTV($tv, $default_language, ($language->cultureKey ?: $language->key));
							}
						}
					}
				}
				$response = $this->modx->runProcessor('mgr/content/create', 
	                $contentData, 
	                array(
	                    'processors_path' => $this->localizator->config['processorsPath']
	            	)
	            );
	            if ($response->isError()) {
	                return $response->getResponse();
	            }
			}

			$processed++; 
		}

        return $this->success('', array(
            'total' => $total,
            'processed' => $processed,
        ));
    }

    public function translateTV(modTemplateVar $tvvar, $default_language, $language){
    	$type = $tvvar->get('type');
    	$val = $tvvar->get('value');
    	if (in_array($type, ['text', 'textarea', 'richtext'])){
    		return $this->localizator->translate($val, $default_language, $language);
    	}
    	elseif($type == 'migx'){
    		$this->modx->addPackage('migx', MODX_CORE_PATH . 'components/migx/model/');
		    $params = $tvvar->get('input_properties');
		    $formtabs = $params['formtabs'];
		    if (!empty($params['configs']) && $cfg = $this->modx->getObject('migxConfig', ['name' => $params['configs']])){
		        $formtabs = $cfg->get('formtabs');
		    }
		    if (!is_array($formtabs))
		    	$formtabs = json_decode($formtabs, 1);

		    if (!is_array($formtabs))
		    	return $val;

		    if (!is_array($val))
		    	$val = json_decode($val, 1);

		    foreach ($formtabs as $tab){
				foreach ($tab['fields'] as $field){

	                $tv = false;
					if (isset($field['inputTV']) && $tv = $this->modx->getObject('modTemplateVar', array('name' => $field['inputTV']))) {
	                    
	                }
	                if (!empty($field['inputTVtype'])) {
	                    $tv = $this->modx->newObject('modTemplateVar');
	                    $tv->set('type', $field['inputTVtype']);
	                }
	                if (!$tv) {
	                    $tv = $this->modx->newObject('modTemplateVar');
	                    $tv->set('type', 'text');
	                }

	                if (!empty($field['inputOptionValues'])) {
	                    $tv->set('elements', $field['inputOptionValues']);
	                }
	                if (!empty($field['configs'])) {
	                    $cfg = $this->modx->fromJson($field['configs']);
	                    if (is_array($cfg)) {
	                        $params = array_merge($params, $cfg);
	                    } else {
	                        $params['configs'] = $field['configs'];
	                    }
	                }

	                foreach ($val as &$v){
	                	$tv->set('value', $v[$field['field']]);
	                	$v[$field['field']] = $this->translateTV($tv, $default_language, $language);
	                }
				}
			}

			return json_encode($val);
    	}
    	else{
    		return $val;
    	}
    }

}

return 'localizatorContentTranslateProcessor';  
