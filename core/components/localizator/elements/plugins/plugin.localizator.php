<?php
/* @var modX $modx */
/* @var localizator $localizator */
$localizator = $modx->getService('localizator');
switch($modx->event->name) {
    case 'OnTVFormPrerender':
        $modx->controller->addLexiconTopic('localizator:default');
        $modx->controller->addHtml('
            <script type="text/javascript">
                Ext.ComponentMgr.onAvailable("modx-panel-tv", function(config) {
                    Ext.ComponentMgr.onAvailable("modx-tv-form", function() {
                        this.items[1].items[1].items.push({
                            xtype: "xcheckbox"
                            ,boxLabel: _("tv_localizator_enabled")
                            ,description: _("tv_localizator_enabled_msg")
                            ,name: "localizator_enabled"
                            ,id: "modx-tv-localizator_enabled"
                            ,inputValue: 1
                            ,checked: config.record.localizator_enabled || false
                        });
                    });
                });
            </script>
            ');
        break;
    case 'OnDocFormPrerender':
        if ($mode == 'upd' && $resource instanceof modResource){
            $modx->controller->addLexiconTopic('localizator:default');
            $modx->controller->addCss($localizator->config['cssUrl'] . 'mgr/main.css');
            $modx->controller->addCss($localizator->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
            $modx->controller->addJavascript($localizator->config['jsUrl'] . 'mgr/localizator.js');
            $modx->controller->addJavascript($localizator->config['jsUrl'] . 'mgr/misc/utils.js');
            $modx->controller->addJavascript($localizator->config['jsUrl'] . 'mgr/misc/combo.js');
            $modx->controller->addJavascript($localizator->config['jsUrl'] . 'mgr/widgets/content.grid.js');
            $modx->controller->addHtml('
            <script type="text/javascript">
                localizator.config = ' . json_encode($localizator->config) . ';
                localizator.config.connector_url = "' . $localizator->config['connectorUrl'] . '";
                localizator.config.resource_template = "' . $resource->get('template') . '";
                Ext.ComponentMgr.onAvailable("modx-resource-tabs", function() {
                    this.on("beforerender", function() {
                        this.add({
                            title: _("localizator_tab"),
                            id: "localizator-resource-tab",
                            items: [{
                                xtype: "localizator-grid-content",
                                cls: "main-wrapper",
                                resource_id: ' . $id . ',
                            }]
                        });
                    });
                });
            </script>
            ');
        }
        break;

    case 'OnMODXInit':
        $include = include_once($localizator->config['modelPath'] . 'localizator/plugin.mysql.inc.php');
        if (is_array($include)) {
            foreach ($include as $class => $map){
                if (!isset($modx->map[$class])) {
                    $modx->loadClass($class);
                }
                if (isset($modx->map[$class])) {
                    foreach ($map as $key => $values) {
                        $modx->map[$class][$key] = array_merge($modx->map[$class][$key], $values);
                    }
                }
            }
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
        if ($modx->getOption('friendly_urls') && $isAjax && isset($_SERVER['HTTP_REFERER'])){
            $referer = parse_url($_SERVER['HTTP_REFERER']);
            if (stripos($referer['path'], MODX_MANAGER_URL) === 0) return;
            $localizator->findLocalization($referer['host'], ltrim($referer['path'], '/'));
        }
        break;

    case 'OnHandleRequest':
        if($modx->context->key == 'mgr' || !$modx->getOption('friendly_urls')) return;
        $q_var = $modx->getOption('request_param_alias', null, 'q');
        $localizator->findLocalization($_SERVER['HTTP_HOST'], $_REQUEST[$q_var]);
        break;

    case 'OnPageNotFound':
        $localizator_key = $modx->localizator_key;
        $q_var = $modx->getOption('request_param_alias', null, 'q');
        $request = &$_REQUEST[$q_var];
        if($request == $localizator_key) {
            $modx->sendRedirect($request . '/', array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
        } else if (preg_match('/^('.$localizator_key.')\//i', $request)) {
            $request = preg_replace('/^'.$localizator_key.'\//', '', $request);
        }
        $resource_id = (!$request) ? $modx->getOption('site_start', null, 1) : $localizator->findResource($request);
        if($modx->getObject('modResource',['id' => $resource_id, 'deleted' => 0, 'published' => 1])){
            $modx->sendForward($resource_id);
        }
        break;

    case 'OnLoadWebDocument':
        $q = $modx->newQuery('localizatorContent');
        $q->leftJoin('localizatorLanguage','localizatorLanguage', 'localizatorLanguage.key = localizatorContent.key');
        $q->where(array(
            'localizatorContent.resource_id' => $modx->resource->id,
        ));
        $q->where(array(
            'localizatorLanguage.key' => $modx->localizator_key,
            'OR:localizatorLanguage.cultureKey:=' => $modx->localizator_key,
        ));
        $content = $modx->getObject('localizatorContent', $q);
        if($content) {
            $placeholders = array();
            $fields = explode(',', $modx->getOption('localizator_translate_fields'));
            foreach($fields as $field) {
                $value = $content->get($field);
                if($field == 'content') {
                    $placeholders['localizator_content'] = $value;
                    $modx->resource->set('localizator_content', $value);
                } else {
                    $placeholders[$field] = $value;
                    $modx->resource->set($field, $value);
                }
            }
            foreach ($content->getTVKeys() as $field){
                $value = $content->get($field);
                if (!empty($value)){
                    $value = localizatorContent::renderTVOutput($modx, $field, $value, $modx->resource->id);
                    $modx->resource->_fieldMeta[$field] = [
                        'dbtype' => 'mediumtext',
                        'phptype' => 'string',
                    ];
                    
                    $placeholders[$field] = $value;
                    $modx->resource->set($field, $value);
                }
            }
            $modx->setPlaceholders($placeholders, '*');
        }
        //$modx->resource->cacheable = false;
        break;
        
    case 'OnDocFormSave':
        if ($mode == 'new'){
            if ($key = $modx->getOption('localizator_default_language', null, false, true)){
                if ($fields = $modx->getOption('localizator_translate_fields', null, false, true)){
                    //if (!$content = $modx->getObject('localizatorContent', ['resource_id' => $resource->get('id'), 'key' => $key])){
                        $content = $modx->newObject('localizatorContent');
                        $content->set('resource_id', $resource->get('id'));
                        $content->set('key', $key);
                    //}
                    $fields = array_map('trim', explode(',', $fields));
                    foreach ($fields as $field) {
                        if (isset($resource->_fieldMeta[$field])){
                            $v = $resource->get($field);
                            if ($v){
                                $content->set($field, $v);
                            }
                        }
                    }
                    foreach ($content->getTVKeys() as $field){
                        //if (!in_array($field, $fields)) continue;
                        $v = $resource->getTVValue($field);
                        if ($v){
                            $content->set($field, $v);
                        }
                    }
                    $content->save();
                }
            }
        }
        elseif (in_array($resource->get('class_key'), array('modStaticResource', 'modSymLink', 'modWebLink'))){
            $upd = $modx->prepare("UPDATE ".$modx->getTableName('localizatorContent')." SET `content` = ? WHERE `resource_id` = ?");
            $upd->execute(array($resource->get('content'), $resource->get('id')));
        }
        break;

    case 'OnEmptyTrash':
        if (!empty($ids)){
            $modx->removeCollection('localizatorContent', array('resource_id:IN' => $ids));
            $modx->removeCollection('locTemplateVarResource', array('contentid:IN' => $ids));
        }
        break;

    case 'mse2OnBeforeSearchIndex':
        $keys = $mSearch2->fields;
        unset($keys['comment']);

        if ($contents = $modx->getCollection('localizatorContent', array('resource_id' => $resource->id))) {
            foreach ($contents as $content) {
                foreach ($keys as $k => $v) {
                    $field = $k;
                    if (strpos($field, 'tv_') !== false) {
                        $field = substr($field, 3);
                    }
                    $value = $content->get($field);
                    // Если поле заполнено
                    if (!empty($value)) {
                        $field_key = $content->key . '-' . $k;
                        $mSearch2->fields[$field_key] = $v;
                        $resource->set($field_key, $value);
                    }
                }
            }
        }
        break;

    case 'pdoToolsOnFenomInit':
        /** @var Fenom $fenom */
        $pdo = $modx->getService('pdoTools');

        $fenom->addModifier('locfield', function ($id, $field = null) use ($pdo, $modx) {
            /** @var modResource $resource */
            if (empty($id)) {
                $resource = $modx->resource;
            } elseif (!is_numeric($id)) {
                $field = $id;
                $resource = $modx->resource;
            } elseif (!$resource = $pdo->getStore($id, 'resource')) {
                $resource = $modx->getObject('modResource', $id);
                $pdo->setStore($id, $resource, 'resource');
            }

            if (!$resource)
                return '';

            $id = $resource->get('id');
            $key = $modx->localizator_key;
            $output = '';

            if (in_array($field, array_diff(array_keys($modx->getFields('localizatorContent')), array('id', 'resource_id')))){
                $q = $modx->newQuery("localizatorContent")
                    ->where(array(
                        "resource_id" => $id,
                        "key" => $key,
                    ))
                    ->select($field);
                if ($q->prepare() && $q->stmt->execute()){
                    $output = $q->stmt->fetchColumn();
                }
            }
            elseif (in_array($field, array_keys($modx->getFields('modResource')))){
                $output = $resource->get($field);
            }
            elseif ($tv = $modx->getObject('modTemplateVar', array('name' => $field))){
                if ($tv->get('localizator_enabled')){
                    $q = $modx->newQuery("locTemplateVarResource")
                        ->where(array(
                            "contentid" => $id,
                            "key" => $key,
                            "tmplvarid" => $tv->get('id'),
                        ))
                        ->select('value');
                    if ($q->prepare() && $q->stmt->execute()){
                        if ($output = $q->stmt->fetchColumn()){
                            $output = localizatorContent::renderTVOutput($modx, $tv, $output, $id);
                        }
                    }
                }
                else{
                    $output = $resource->getTVValue($field);
                }
            }
            return $output;
        });
        break;
}
