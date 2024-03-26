<?php

class localizatorContentGetProcessor extends modObjectGetProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = 'localizatorContent';
    public $languageTopics = array('localizator:default');
    public $permission = 'localizatorcontent_view';

    public function checkPermissions()
    {
        if (!$this->modx->getOption('localizator_check_permissions', null, false, true)) return true;

        if (!empty($this->permission)) {
            $this->permission .= "_" . $this->object->key;
        }
        return parent::checkPermissions();
    }

    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return mixed
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }

        return parent::process();
    }
}

return 'localizatorContentGetProcessor';
