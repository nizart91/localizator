<?php

require_once(dirname(__FILE__) . '/update.class.php');

class localizatorContentUpdateFromGridProcessor extends localizatorContentUpdateProcessor
{

    /**
     * @return bool|null|string
     */
    public function initialize()
    {
        $data = $this->getProperty('data');
        if (empty($data)) {
            return $this->modx->lexicon('invalid_data');
        }

        $data = json_decode($data, true);
        if (empty($data)) {
            return $this->modx->lexicon('invalid_data');
        }

        $this->setProperties($data);
        $this->unsetProperty('data');

        return parent::initialize();
    }


    /**
     * @return array|string
     */
    public function beforeSet()
    {
        $properties = $this->getProperties();

        foreach ($properties as $key => $value) {
            if ($key == '_key') {
                $key_value = explode(" ", $value);
                $this->setProperty('key', $key_value[0]);
            }
        }

        if ($resource = $this->modx->getObject($this->classKey, $properties['id'])) {
            $resource_id = $resource->get('resource_id');
            $this->setProperty('resource_id', $resource_id);
        }

        return parent::beforeSet();
    }
}

return 'localizatorContentUpdateFromGridProcessor';
