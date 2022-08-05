<?php

class localizatorContentRemoveProcessor extends modObjectRemoveProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = 'localizatorContent';
    public $languageTopics = array('localizator');
    public $permission = '';

    public $beforeRemoveEvent = 'OnBeforeRemoveLocalization';
    public $afterRemoveEvent = 'OnRemoveLocalization';

    /**
     * @return bool|null|string
     */
    public function initialize()
    {
        if ($this->modx->getOption('localizator_check_permissions', null, false, true)) {
            $key = trim($this->getProperty('key'));
            $this->permission = "localizatorcontent_save_{$key}";
        }
        if (!$this->modx->hasPermission($this->permission)) {
            return $this->modx->lexicon('access_denied');
        }

        return parent::initialize();
    }
}

return 'localizatorContentRemoveProcessor';
