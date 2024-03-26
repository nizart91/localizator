<?php

/**
 * The home manager controller for localizator.
 *
 */
class localizatorHomeManagerController extends modExtraManagerController
{
    /** @var localizator $localizator */
    public $localizator;


    /**
     *
     */
    public function initialize()
    {
        $path = $this->modx->getOption(
            'localizator_core_path',
            null,
            $this->modx->getOption('core_path') . 'components/localizator/'
        ) . 'model/localizator/';
        $this->localizator = $this->modx->getService('localizator', 'localizator', $path);
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return array('localizator:default');
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('localizator');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->localizator->config['cssUrl'] . 'mgr/main.css');
        $this->addCss($this->localizator->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
        $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/localizator.js');
        $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/widgets/languages.grid.js');

        //$this->addJavascript('/manager/assets/modext/workspace/lexicon/lexicon.grid.js');
        $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/widgets/lexicon.grid.js');

        $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        localizator.config = ' . json_encode($this->localizator->config) . ';
        localizator.config.connector_url = "' . $this->localizator->config['connectorUrl'] . '";
        Ext.onReady(function() {
            MODx.load({ xtype: "localizator-page-home"});
        });
        </script>
        ');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->localizator->config['templatesPath'] . 'home.tpl';
    }
}
