<?php

/**
 * Loads the Tabs panel for Localizator.
 *
 * Note: This page is not to be accessed directly.
 *
 * @package localizator
 * @subpackage processors
 */

class localizatorFormProcessor extends modProcessor
{

    public function process()
    {

        require_once MODX_CORE_PATH . 'model/modx/modmanagercontroller.class.php';
        require_once MODX_CORE_PATH . 'components/localizator/model/localizator/localizatorformcontroller.class.php';
        $controller = new LocalizatorFormController($this->modx);
        $this->modx->controller = &$controller;

        $this->modx->getService('smarty', 'smarty.modSmarty');
        $localizator = $this->modx->getService('localizator');
        $scriptProperties = $this->getProperties();

        $localizator->working_context = 'web';

        $which_editor = $this->modx->getOption('which_editor', null, false, true);


        $class_key = 'modDocument';
        $richtext = false;
        if ($this->modx->resource = $this->modx->getObject('modResource', $scriptProperties['resource_id'])) {
            $localizator->working_context = $this->modx->resource->get('context_key');
            $class_key = $this->modx->resource->get('class_key');

            if ($which_editor != false) {
                $richtext = $this->modx->resource->get('richtext');
            }
        }

        $controller->loadTemplatesPath();

        $controller->setPlaceholder('_config', $this->modx->config);
        $this->modx->lexicon->load('core:resource');
        $this->modx->lexicon->load('core:default');
        $this->modx->lexicon->load('core:formcustomization');

        /*actual record */
        if ($loc = $this->modx->getObject('localizatorContent', $scriptProperties['loc_id'])) {
            $scriptProperties['isnew'] = 0;
        } else {
            $loc = $this->modx->newObject('localizatorContent');
            $loc->set('resource_id', $scriptProperties['resource_id']);
            $scriptProperties['isnew'] = 1;
        }

        $allfields = array();

        $resourcefields = array(
            'id' => array(
                'inputTVtype' => 'hidden',
            ),
            'key' => array(
                'caption' => $this->modx->lexicon('localizator_language'),
                'inputTVtype' => 'listbox',
                'inputOptionValues' => '@SELECT `name`,`key` FROM `[[+PREFIX]]localizator_languages` WHERE `active` = 1',
            ),
            'pagetitle' => array(),
            'longtitle' => array(),
            'menutitle' => array(),
            'description' => array(
                'inputTVtype' => 'textarea',
            ),
            'introtext' => array(
                'inputTVtype' => 'textarea',
                'caption' => $this->modx->lexicon('introtext'),
            ),
            'seotitle' => array(
                'caption' => $this->modx->lexicon('localizator_seotitle'),
            ),
            'keywords' => array(
                'caption' => $this->modx->lexicon('localizator_keywords'),
            ),
        );
        if (!in_array($class_key, array('modStaticResource', 'modSymLink', 'modWebLink'))) {
            $resourcefields['content'] = array(
                'inputTVtype' => $richtext ? 'richtext' : 'textarea',
            );
        }

        foreach ($resourcefields as $key => &$values) {
            $values = array_merge(array(
                'field' => $key,
                'caption' => $this->modx->lexicon("resource_{$key}"),
                'inputTVtype' => 'text',
            ), $values);
        }

        $tvtabs = array();

        /* get categories */
        $c = $this->modx->newQuery('modCategory');
        $c->sortby('rank', 'ASC');
        $c->sortby('category', 'ASC');
        $cats = $this->modx->getCollection('modCategory', $c);
        /** @var modCategory $cat */
        foreach ($cats as $cat) {
            $tvtabs[$cat->get('id')] = array(
                'caption' => $cat->get('category'),
                'fields' => array(),
            );
        }

        $tvtabs[0] = array(
            'caption' => ucfirst($this->modx->lexicon('uncategorized')),
            'fields' => array(),
        );

        //$tvkeys = $loc->getTVKeys();

        foreach ($loc->getTemplateVars() as $tv) {
            if (!$tv->checkResourceGroupAccess()) {
                continue;
            }

            $tvtabs[$tv->get('category')]['fields'][] = array(
                'field' => $tv->get('name'),
                'caption' => $tv->get('caption') ? $tv->get('caption') : $tv->get('name'),
                'description' => $tv->get('description'),
                'inputTV' => $tv->get('name'),
            );
        }

        $tvtabs = array_filter($tvtabs, function ($var) {
            return (count($var['fields']) > 0);
        });

        $formtabs = array(
            'document' => array(
                'caption' => $this->modx->lexicon('document'),
                'tabs' => array(
                    'document' => array(
                        'caption' => $this->modx->lexicon('document'),
                        'fields' => array_values($resourcefields),
                    ),
                ),
            ),
        );

        if (!empty($tvtabs)) {
            $formtabs['tvs'] = array(
                'caption' => $this->modx->lexicon('tvs'),
                'tabs' => $tvtabs,
            );
        }

        $response = $localizator->invokeEvent('OnBuildLocalizationTabs', array(
            'localizatorContent' => &$loc,
            'tabs' => $formtabs,
        ));
        if ($response['success']) {
            $formtabs = $response['data']['tabs'];
        }
        $record = $loc->toArray();

        $categories = array();
        $result = $localizator->createForm($formtabs, $record, $allfields, $categories, $scriptProperties);

        if (isset($result['error'])) {
            $controller->setPlaceholder('error', $result['error']);
        }

        //$controller->setPlaceholder('formcaption', '');
        $controller->setPlaceholder('fields', $this->modx->toJSON($allfields));
        $controller->setPlaceholder('categories', $categories);
        $controller->setPlaceholder('resource_id', $loc->get('resource_id'));
        $controller->setPlaceholder('formAction', $scriptProperties['isnew'] ? 'create' : 'update');
        $controller->setPlaceholder('properties', $scriptProperties);

        $controller->setPlaceholder('win_id', $scriptProperties['win_id']);
        $controller->setPlaceholder('tvcount', count($resourcefields));

        if (!empty($_REQUEST['showCheckbox'])) {
            $controller->setPlaceholder('showCheckbox', 1);
        } else {
            $controller->setPlaceholder('showCheckbox', 0);
        }


        return $controller->process($scriptProperties);
    }
}
return 'localizatorFormProcessor';
