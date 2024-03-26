<?php
class localizatorContent extends xPDOSimpleObject
{

    protected $tvs = null;
    protected $TVKeys = null;

    public $_originalFieldMeta;

    /**
     * msProduct constructor.
     *
     * @param xPDO $xpdo
     */
    public function __construct(xPDO &$xpdo)
    {
        parent::__construct($xpdo);
        $this->_originalFieldMeta = $this->_fieldMeta;
    }

    public static function getTemplateVarCollection(localizatorContent &$content)
    {
        $c = $content->xpdo->call('localizatorContent', 'prepareTVListCriteria', array(&$content));

        $c->query['distinct'] = 'DISTINCT';
        $c->select($content->xpdo->getSelectColumns('modTemplateVar', 'modTemplateVar'));
        $c->select($content->xpdo->getSelectColumns('modTemplateVarTemplate', 'tvtpl', '', array('rank')));
        if ($content->isNew()) {
            $c->select(array(
                'modTemplateVar.default_text AS value',
                '0 AS resourceId'
            ));
        } else {
            $c->select(array(
                'IF(ISNULL(tvc.value),modTemplateVar.default_text,tvc.value) AS value',
                $content->get('resource_id') . ' AS resourceId'
            ));
        }
        if (!$content->isNew()) {
            $c->leftJoin('locTemplateVarResource', 'tvc', array(
                'tvc.tmplvarid = modTemplateVar.id',
                'tvc.contentid' => $content->get('resource_id'),
                'tvc.key' => $content->get('key'),
            ));
        }
        $c->sortby('tvtpl.rank,modTemplateVar.rank');
        $c->leftJoin('modCategory', 'Category', 'Category.id=modTemplateVar.category');
        $c->select(array(
            'IF(ISNULL(Category.id),0,Category.id) AS category_id, Category.category AS category_name',
        ));
        return $content->xpdo->getCollection('modTemplateVar', $c);
    }



    public function getTemplateVars()
    {
        return $this->xpdo->call('localizatorContent', 'getTemplateVarCollection', array(&$this));
    }


    /**
     * @return xPDOQuery
     */
    public static function prepareTVListCriteria(localizatorContent &$content)
    {
        $resource = $content->getOne('Resource');
        $c = $content->xpdo->newQuery('modTemplateVar');
        $c->innerJoin('modTemplateVarTemplate', 'tvtpl', array(
            'tvtpl.tmplvarid = modTemplateVar.id',
            'tvtpl.templateid' => $resource->get('template'),
        ));
        $c->groupby('modTemplateVar.id');
        $c->where(array(
            'modTemplateVar.localizator_enabled' => 1,
        ));

        return $c;
    }


    /**
     * @return array
     */
    public static function _loadTVs(localizatorContent &$content)
    {
        $c = $content->xpdo->call('localizatorContent', 'prepareTVListCriteria', array(&$content));
        $c->query['distinct'] = 'DISTINCT';
        $c->select($content->xpdo->getSelectColumns('modTemplateVar', 'modTemplateVar'));
        $c->select($content->xpdo->getSelectColumns('modTemplateVarTemplate', 'tvtpl', '', array('rank')));
        if ($content->isNew()) {
            $c->select(array(
                'modTemplateVar.default_text AS value',
            ));
        } else {
            $c->select(array(
                'IF(ISNULL(tvc.value),modTemplateVar.default_text,tvc.value) AS value',
            ));
        }
        if (!$content->isNew()) {
            $c->leftJoin('locTemplateVarResource', 'tvc', array(
                'tvc.tmplvarid = modTemplateVar.id',
                'tvc.contentid' => $content->get('resource_id'),
                'tvc.key' => $content->get('key'),
            ));
        }
        $c->sortby('tvtpl.rank,modTemplateVar.rank');

        $data = array();
        if ($c->prepare() && $c->stmt->execute()) {
            while ($tv = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[$tv['name']] = $tv['value'];
            }
        }

        return $data;
    }

    public function loadTVs()
    {
        if ($this->tvs === null) {
            $this->tvs = $this->xpdo->call('localizatorContent', '_loadTVs', array(&$this));
        }
        return $this->tvs;
    }

    /**
     * @param bool $force
     *
     * @return array
     */
    public function getTVKeys($force = false)
    {
        if ($this->TVKeys === null || $force) {
            /** @var xPDOQuery $c */
            $c = $this->xpdo->call('localizatorContent', 'prepareTVListCriteria', array(&$this));
            $c->select('modTemplateVar.id,modTemplateVar.name');

            $this->TVKeys = array();
            if ($c->prepare() && $c->stmt->execute()) {
                while ($tv = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->TVKeys[$tv['id']] = $tv['name'];
                }
            }
        }

        return $this->TVKeys;
    }

    /**
     * @param array|string $k
     * @param null $format
     * @param null $formatTemplate
     *
     * @return array|mixed|null|xPDOObject
     */
    public function get($k, $format = null, $formatTemplate = null)
    {

        if (is_array($k)) {
            $array = array();
            foreach ($k as $v) {
                $array[$v] = isset($this->_fieldMeta[$v])
                    ? parent::get($v, $format, $formatTemplate)
                    : $this->get($v, $format, $formatTemplate);
            }

            return $array;
        } elseif (isset($this->_fieldMeta[$k])) {
            return parent::get($k, $format, $formatTemplate);
        } elseif (in_array($k, $this->getTVKeys())) {
            if (isset($this->$k)) {
                return $this->$k;
            }
            $this->loadTVs();
            $value = isset($this->tvs[$k])
                ? $this->tvs[$k]
                : null;

            return $value;
        } else {
            return parent::get($k, $format, $formatTemplate);
        }
    }

    /**
     * 
     * @param null $cacheFlag
     *
     * @return bool
     */
    public function save($cacheFlag = null)
    {
        $save = parent::save($cacheFlag);
        $this->saveTVs();

        return $save;
    }

    public function toArray($keyPrefix = '', $rawValues = false, $excludeLazy = false, $includeRelated = false)
    {
        $original = parent::toArray($keyPrefix, $rawValues, $excludeLazy, $includeRelated);
        $additional = $this->loadTVs();
        $intersect = array_keys(array_intersect_key($original, $additional));
        foreach ($intersect as $key) {
            unset($additional[$key]);
        }

        return array_merge($original, $additional);
    }

    /**
     *
     */
    protected function saveTVs()
    {
        $tvs = $this->xpdo->call('localizatorContent', 'getTemplateVarCollection', array(&$this));

        $tvids = [];
        foreach ($tvs as $tv) {
            $tvids[] = $tv->get('id');
            if (!$tv->checkResourceGroupAccess()) {
                continue;
            }

            $value = $this->get($tv->get('name'));

            /* set value of TV */
            if ($tv->get('type') != 'checkbox') {
                $value = $value !== null ? $value : $tv->get('default_text');
            } else {
                $value = $value ? $value : '';
            }

            /* validation for different types */
            switch ($tv->get('type')) {
                case 'url':
                    $value = str_replace(array('ftp://', 'http://'), '', $value);
                    $value = $prefix . $value;
                    break;
                case 'date':
                    $value = empty($value) ? '' : strftime('%Y-%m-%d %H:%M:%S', strtotime($value));
                    break;
                    /* ensure tag types trim whitespace from tags */
                case 'tag':
                case 'autotag':
                    $tags = explode(',', $value);
                    $newTags = array();
                    foreach ($tags as $tag) {
                        $newTags[] = trim($tag);
                    }
                    $value = implode(',', $newTags);
                    break;
                default:
                    /* handles checkboxes & multiple selects elements */
                    if (is_array($value)) {
                        $featureInsert = array();
                        foreach ($value as $featureValue => $featureItem) {
                            if (isset($featureItem) && $featureItem === '') {
                                continue;
                            }
                            $featureInsert[count($featureInsert)] = $featureItem;
                        }
                        $value = implode('||', $featureInsert);
                    }
                    break;
            }

            /* if different than default and set, set TVR record */
            $default = $tv->processBindings($tv->get('default_text'), $this->get('resource_id'));
            if (strcmp($value, $default) != 0) {
                /* update the existing record */
                $tvc = $this->xpdo->getObject('locTemplateVarResource', array(
                    'key' => $this->get('key'),
                    'tmplvarid' => $tv->get('id'),
                    'contentid' => $this->get('resource_id'),
                ));
                if ($tvc == null) {
                    /** @var modTemplateVarResource $tvc add a new record */
                    $tvc = $this->xpdo->newObject('locTemplateVarResource');
                    $tvc->set('key', $this->get('key'));
                    $tvc->set('tmplvarid', $tv->get('id'));
                    $tvc->set('contentid', $this->get('resource_id'));
                }
                $tvc->set('value', $value);
                $tvc->save();

                /* if equal to default value, erase TVR record */
            } else {
                $tvc = $this->xpdo->getObject('locTemplateVarResource', array(
                    'key' => $this->get('key'),
                    'tmplvarid' => $tv->get('id'),
                    'contentid' => $this->get('resource_id'),
                ));
                if (!empty($tvc)) {
                    $tvc->remove();
                }
            }
        }

        if (!empty($tvids)) {
            $this->xpdo->removeCollection('locTemplateVarResource', array(
                'key' => $this->get('key'),
                'tmplvarid:NOT IN' => $tvids,
                'contentid' => $this->get('resource_id'),
            ));
        }
    }


    /**
     * Returns the processed output of a template variable.
     *
     * @access public
     * @param xPDO $xpdo
     * @param modTemplateVar||int||string $tv template variable element
     * @param string $value The TV value.
     * @param integer $resourceId The id of the resource; 0 defaults to the
     * current resource.
     * @return mixed The processed output of the template variable.
     */
    public static function renderTVOutput(xPDO &$xpdo, $tv, $value = '', $resourceId = 0)
    {
        if (!($tv instanceof modTemplateVar)) {
            $byName = !is_numeric($tv);

            $tv = $xpdo->getObject('modTemplateVar', $byName ? array('name' => $tv) : $tv);

            if ($tv == null) {
                return $value;
            }
        }

        /* process any TV commands in value */
        $value = $tv->processBindings($value, $resourceId);

        $params = array();
        /**
         * Backwards support for display_params
         * @deprecated To be removed in 2.2
         */
        if ($paramstring = $tv->get('display_params')) {
            $tv->xpdo->deprecated('2.2.0', 'Use output_properties instead.', 'modTemplateVar renderOutput display_params');
            $cp = explode("&", $paramstring);
            foreach ($cp as $p => $v) {
                $ar = explode("=", $v);
                if (is_array($ar) && count($ar) == 2) {
                    $params[$ar[0]] = $tv->decodeParamValue($ar[1]);
                }
            }
        }
        /* get output_properties for rendering properties */
        $outputProperties = $tv->get('output_properties');
        if (!empty($outputProperties) && is_array($outputProperties)) {
            $params = array_merge($params, $outputProperties);
        }

        /* run prepareOutput to allow for custom overriding */
        $value = $tv->prepareOutput($value, $resourceId);

        /* find the render */
        $outputRenderPaths = $tv->getRenderDirectories('OnTVOutputRenderList', 'output');
        return $tv->getRender($params, $value, $outputRenderPaths, 'output', $resourceId, $tv->get('display'));
    }

    /**
     * @param array $ancestors
     *
     * @return bool
     */
    public function remove(array $ancestors = array())
    {

        $tvs = $this->xpdo->getIterator('locTemplateVarResource', array(
            'key' => $this->get('key'),
            'contentid' => $this->get('resource_id'),
        ));
        /** @var locTemplateVarResource $tv */
        foreach ($tvs as $tv) {
            $tv->remove();
        }

        return parent::remove($ancestors);
    }
}
