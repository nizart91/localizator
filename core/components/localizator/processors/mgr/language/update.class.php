<?php

class localizatorLanguageUpdateProcessor extends modObjectUpdateProcessor
{
    public $objectType = 'localizatorLanguage';
    public $classKey = 'localizatorLanguage';
    public $languageTopics = array('localizator');
    public $beforeSaveEvent = 'OnBeforeSaveLocalizatorLanguage';
    public $afterSaveEvent = 'OnSaveLocalizatorLanguage';
    //public $permission = 'save';

    protected $old_key = null;

    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return bool|string
     */
    public function beforeSave()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $id = (int)$this->getProperty('id');
        if (empty($id)) {
            return $this->modx->lexicon('localizator_item_err_ns');
        }

        $key = trim($this->getProperty('key'));
        if (empty($key)) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_language_err_no_key'));
        } elseif ($this->modx->getCount($this->classKey, array('key' => $key, 'id:!=' => $id))) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_language_err_key_exist'));
        }

        $http_host = trim($this->getProperty('http_host'));
        if (empty($http_host)) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_language_err_no_http_host'));
        } elseif ($this->modx->getCount($this->classKey, array('http_host' => $http_host, 'id:!=' => $id))) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_language_err_http_host_exist'));
        }

        $this->old_key = $this->object->get('key');

        return parent::beforeSet();
    }

    public function afterSave()
    {
        if ($this->old_key != $this->object->get('key')) {

            foreach (array('localizatorContent', 'locTemplateVarResource') as $class) {
                if ($upd = $this->modx->prepare("UPDATE " . $this->modx->getTableName($class) . " SET `key` = ? WHERE `key` = ?")) {
                    $upd2->execute(array(
                        $this->object->get('key'),
                        $this->old_key
                    ));
                }
            }
        }

        return true;
    }
}

return 'localizatorLanguageUpdateProcessor';
