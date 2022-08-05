<?php

class localizator
{
    /** @var modX $modx */
    public $modx;


    /**
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX &$modx, array $config = array())
    {
        $this->modx = &$modx;

        $corePath = $this->modx->getOption(
            'localizator_core_path',
            $config,
            $this->modx->getOption('core_path') . 'components/localizator/'
        );
        $assetsUrl = $this->modx->getOption(
            'localizator_assets_url',
            $config,
            $this->modx->getOption('assets_url') . 'components/localizator/'
        );
        $connectorUrl = $assetsUrl . 'connector.php';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/',
            'translator' => $this->modx->getOption('localizator_default_translator', null, 'SimpleCopy', true),
        ), $config);

        require_once dirname(__FILE__, 3) . '/translators/' . strtolower($this->config['translator']) . '.class.php';

        $this->translator = new $this->config['translator']($this->modx, $this->config);

        $this->modx->addPackage('localizator', $this->config['modelPath']);
        $this->modx->lexicon->load('localizator:default');
    }


    public function translate($text, $from, $to)
    {
        return $this->translator->translate($text, $from = '', $to = '');
    }


    public function createForm(&$formtabs, &$record, &$allfields, &$categories, $scriptProperties)
    {

        $input_prefix = $this->modx->getOption('input_prefix', $scriptProperties, '');
        $input_prefix = !empty($input_prefix) ? $input_prefix . '_' : '';
        $rte = isset($scriptProperties['which_editor']) ? $scriptProperties['which_editor'] : $this->modx->getOption('which_editor', '', $this->modx->_userConfig);


        foreach ($formtabs as $tabid => $subtab) {
            $tabs = array();
            foreach ($subtab['tabs'] as $subtabid => $tab) {
                $tvs = array();
                $fields = $this->modx->getOption('fields', $tab, array());
                $fields = is_array($fields) ? $fields : $this->modx->fromJson($fields);
                if (is_array($fields) && count($fields) > 0) {

                    foreach ($fields as &$field) {

                        $fieldname = $this->modx->getOption('field', $field, '');
                        $useDefaultIfEmpty = $this->modx->getOption('useDefaultIfEmpty', $field, 0);

                        /*generate unique tvid, must be numeric*/
                        /*todo: find a better solution*/
                        $field['tv_id'] = 'localizator_' . $fieldname;
                        $params = array();
                        $tv = false;


                        if (isset($field['inputTV']) && $tv = $this->modx->getObject('modTemplateVar', array('name' => $field['inputTV']))) {
                            $params = $tv->get('input_properties');
                            $params['inputTVid'] = $tv->get('id');
                        }

                        if (!empty($field['inputTVtype'])) {
                            $tv = $this->modx->newObject('modTemplateVar');
                            $tv->set('type', $field['inputTVtype']);
                        }

                        if (!$tv) {
                            $tv = $this->modx->newObject('modTemplateVar');
                            $tv->set('type', 'text');
                        }

                        $tv->set('name', ($fieldname == 'content' ? 'localizator_content' : $fieldname));

                        $o_type = $tv->get('type');

                        if ($tv->get('type') == 'richtext') {
                            $tv->set('type', 'migx' . str_replace(' ', '_', strtolower($rte)));
                        }

                        //we change the phptype, that way we can use any id, not only integers (issues on windows-systems with big integers!)
                        $tv->_fieldMeta['id']['phptype'] = 'string';

                        if (!empty($field['inputOptionValues'])) {
                            $tv->set('elements', $field['inputOptionValues']);
                        }
                        if (!empty($field['default'])) {
                            $tv->set('default_text', $tv->processBindings($field['default']));
                        }
                        if (isset($field['display'])) {
                            $tv->set('display', $field['display']);
                        }
                        if (!empty($field['configs'])) {
                            $cfg = $this->modx->fromJson($field['configs']);
                            if (is_array($cfg)) {
                                $params = array_merge($params, $cfg);
                            } else {
                                $params['configs'] = $field['configs'];
                            }
                        }

                        /*insert actual value from requested record, convert arrays to ||-delimeted string */
                        $fieldvalue = '';
                        if (isset($record[$fieldname])) {
                            $fieldvalue = $record[$fieldname];
                            if (is_array($fieldvalue)) {
                                $fieldvalue = is_array($fieldvalue[0]) ? $this->modx->toJson($fieldvalue) : implode('||', $fieldvalue);
                            }
                        }

                        $tv->set('value', $fieldvalue);

                        if (!empty($field['caption'])) {
                            $field['caption'] = htmlentities($field['caption'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                            $tv->set('caption', $field['caption']);
                        }



                        $desc = '';
                        if (!empty($field['description'])) {
                            $desc = $field['description'];
                            $field['description'] = htmlentities($field['description'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                            $tv->set('description', $field['description']);
                        }


                        $allfield = array();
                        $allfield['field'] = $fieldname;
                        $allfield['tv_id'] = $field['tv_id'];
                        $allfield['array_tv_id'] = $field['tv_id'] . '[]';
                        $allfields[] = $allfield;

                        $field['array_tv_id'] = $field['tv_id'] . '[]';
                        $mediasource = $this->getFieldSource($field, $tv);

                        $tv->setSource($mediasource);
                        $tv->set('id', $field['tv_id']);

                        $isnew = $this->modx->getOption('isnew', $scriptProperties, 0);
                        $isduplicate = $this->modx->getOption('isduplicate', $scriptProperties, 0);


                        if (!empty($useDefaultIfEmpty)) {
                            //old behaviour minus use now default values for checkboxes, if new record
                            if ($tv->get('value') == null) {
                                $v = $tv->get('default_text');
                                if ($tv->get('type') == 'checkbox' && $tv->get('value') == '') {
                                    if (!empty($isnew) && empty($isduplicate)) {
                                        $v = $tv->get('default_text');
                                    } else {
                                        $v = '';
                                    }
                                }
                                $tv->set('value', $v);
                            }
                        } else {
                            //set default value, only on new records
                            if (!empty($isnew) && empty($isduplicate)) {
                                $v = $tv->get('default_text');
                                $tv->set('value', $v);
                            }
                        }


                        $this->modx->smarty->assign('tv', $tv);

                        if (!isset($params['allowBlank']))
                            $params['allowBlank'] = 1;

                        $value = $tv->get('value');
                        if ($value === null) {
                            $value = $tv->get('default_text');
                        }

                        $this->modx->smarty->assign('params', $params);
                        /* find the correct renderer for the TV, if not one, render a textbox */
                        $inputRenderPaths = $tv->getRenderDirectories('OnTVInputRenderList', 'input');

                        if ($o_type == 'richtext') {
                            $fallback = true;
                            foreach ($inputRenderPaths as $path) {
                                $renderFile = $path . $tv->get('type') . '.class.php';
                                if (file_exists($renderFile)) {
                                    $fallback = false;
                                    break;
                                }
                            }
                            if ($fallback) {
                                $tv->set('type', 'textarea');
                            }
                        }

                        $inputForm = $tv->getRender($params, $value, $inputRenderPaths, 'input', null, $tv->get('type'));
                        $tv->set('formElement', $inputForm);
                        $tvs[] = $tv;
                    }
                }
                $tabs[] = array(
                    'category' => $this->modx->getOption('caption', $tab, 'undefined'),
                    'print_before_tabs' => (isset($tab['print_before_tabs']) && !empty($tab['print_before_tabs']) ? true : false),
                    'id' => $subtabid,
                    'tvs' => $tvs,
                );
            }

            $categories[] = array(
                'category' => $this->modx->getOption('caption', $subtab, 'undefined'),
                'print_before_tabs' => (isset($subtab['print_before_tabs']) && !empty($subtab['print_before_tabs']) ? true : false),
                'id' => $tabid,
                'tabs' => $tabs,
            );
        }
    }



    public function getFieldSource($field, &$tv)
    {
        //source from config
        $sourcefrom = isset($field['sourceFrom']) && !empty($field['sourceFrom']) ? $field['sourceFrom'] : 'config';

        if ($sourcefrom == 'config' && isset($field['sources'])) {
            if (is_array($field['sources'])) {
                foreach ($field['sources'] as $context => $sourceid) {
                    $sources[$context] = $sourceid;
                }
            } else {
                $fsources = $this->modx->fromJson($field['sources']);
                if (is_array($fsources)) {
                    foreach ($fsources as $source) {
                        if (isset($source['context']) && isset($source['sourceid'])) {
                            $sources[$source['context']] = $source['sourceid'];
                        }
                    }
                }
            }
        }

        if (isset($sources[$this->working_context]) && !empty($sources[$this->working_context])) {
            //try using field-specific mediasource from config
            if ($mediasource = $this->modx->getObject('sources.modMediaSource', $sources[$this->working_context])) {
                return $mediasource;
            }
        }

        $mediasource = $tv->getSource($this->working_context, false);

        //try to get the context-default-media-source
        if (!$mediasource) {
            $defaultSourceId = null;
            if ($contextSetting = $this->modx->getObject('modContextSetting', array('key' => 'default_media_source', 'context_key' => $this->working_context))) {
                $defaultSourceId = $contextSetting->get('value');
            }
            $mediasource = modMediaSource::getDefaultSource($this->modx, $defaultSourceId);
        }

        return $mediasource;
    }


    public function findLocalization($http_host, &$request)
    {
        /* @var localizatorLanguage $language */
        $language = null;

        $response = $this->invokeEvent('OnBeforeFindLocalization', array(
            'language' => &$language,
            'http_host' => $http_host,
            'request' => $request,
        ));
        if (!$response['success']) {
            return $response['message'];
        }

        if (!$language) {
            $host = $find = $http_host;
            if ($request) {
                if (strpos($request, '/') !== false) {
                    // "site.com/en/blog/article" to "site.com/en/"
                    $tmp = explode('/', $request);
                    $find = $host . '/' . $tmp[0] . '/';
                } else {
                    $find = $host . '/' . $request;
                }
            }
            $q = $this->modx->newQuery('localizatorLanguage');
            $q->where(array(
                array('http_host' => $find),
                array('OR:http_host:=' => $host . '/'),
                array('OR:http_host:=' => $host),
            ));
            $q->sortby("FIELD(http_host, '{$find}', '{$host}/', '{$host}')");
            $language = $this->modx->getObject('localizatorLanguage', $q);
        }

        if ($language) {
            if (preg_match("/^(http(s):\/\/)/i", $language->http_host)) {
                $site_url = $language->http_host;
            } else
                $site_url = MODX_URL_SCHEME . $language->http_host;

            if (substr($site_url, -1) != '/') {
                $site_url .= '/';
            }

            $base_url = '/';
            $parse_url = parse_url($site_url);
            if (isset($parse_url['path'])) {
                $base_url = $parse_url['path'];
                if (substr($base_url, -1) != '/') {
                    $base_url .= '/';
                }
            }

            $this->modx->localizator_key = $language->key;
            $this->modx->setOption('localizator_key', $this->modx->localizator_key);
            $this->modx->setOption('cache_resource_key', 'resource/' . $this->modx->localizator_key);

            $this->modx->cultureKey = $cultureKey = ($language->cultureKey ?: $language->key);
            $this->modx->setOption('cultureKey', $cultureKey);
            $this->modx->setOption('site_url', $site_url);
            $this->modx->setOption('base_url', $base_url);

            $this->modx->setPlaceholders(array(
                'localizator_key' => $language->key,
                'cultureKey' => $cultureKey,
                'site_url' => $site_url,
                'base_url' => $base_url,
            ), '+');

            $this->modx->lexicon->load($cultureKey . ':localizator:site');
        }

        $this->invokeEvent('OnFindLocalization', array(
            'language' => $language,
            'http_host' => $http_host,
            'request' => $request,
        ));

        return false;
    }


    public function findResource($request)
    {
        $resourceId = false;

        $this->invokeEvent('OnFindLocalizatorResource', array(
            'resource' => &$resourceId,
            'request' => $request,
        ));

        if (!$resourceId) {
            $resourceId = $this->modx->findResource($request);
        }

        return $resourceId;
    }


    /**
     * Shorthand for original modX::invokeEvent() method with some useful additions.
     *
     * @param $eventName
     * @param array $params
     * @param $glue
     *
     * @return array
     */
    public function invokeEvent($eventName, array $params = array(), $glue = '<br/>')
    {
        if (isset($this->modx->event->returnedValues)) {
            $this->modx->event->returnedValues = null;
        }

        $response = $this->modx->invokeEvent($eventName, $params);
        if (is_array($response) && count($response) > 1) {
            foreach ($response as $k => $v) {
                if (empty($v)) {
                    unset($response[$k]);
                }
            }
        }

        $message = is_array($response) ? implode($glue, $response) : trim((string)$response);
        if (isset($this->modx->event->returnedValues) && is_array($this->modx->event->returnedValues)) {
            $params = array_merge($params, $this->modx->event->returnedValues);
        }

        return array(
            'success' => empty($message),
            'message' => $message,
            'data' => $params,
        );
    }
}
