<?php

class localizatorLanguageCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'localizatorLanguage';
    public $classKey = 'localizatorLanguage';
    public $languageTopics = array('localizator');
    public $beforeSaveEvent = 'OnBeforeSaveLocalizatorLanguage';
    public $afterSaveEvent = 'OnSaveLocalizatorLanguage';
    //public $permission = 'create';


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $key = trim($this->getProperty('key'));
        if (empty($key)) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_language_err_no_key'));
        } elseif ($this->modx->getCount($this->classKey, array('key' => $key))) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_language_err_key_exist'));
        }

        $http_host = trim($this->getProperty('http_host'));
        if (empty($http_host)) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_language_err_no_http_host'));
        } elseif ($this->modx->getCount($this->classKey, array('http_host' => $http_host))) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_language_err_http_host_exist'));
        }

        return parent::beforeSet();
    }
}

return 'localizatorLanguageCreateProcessor';
