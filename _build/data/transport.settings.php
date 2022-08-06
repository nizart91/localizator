<?php

/** @var modX $modx */
/** @var array $sources */

$settings = array();

$tmp = array(
    'default_language' => array(
        'xtype' => 'textfield',
        'area' => 'localizator_main',
    ),
    'check_permissions' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator_main',
    ),

    // translator
    'default_translator' => array(
        'xtype' => 'textfield',
        'value' => 'Yandex',
        'area' => 'localizator_translator',
    ),
    'key_yandex' => array(
        'xtype' => 'textfield',
        'area' => 'localizator_translator',
    ),
    'key_google' => array(
        'xtype' => 'textfield',
        'area' => 'localizator_translator',
    ),
    'translate_translated' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator_translator',
    ),
    'translate_translated_fields' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator_translator',
    ),
    'translate_fields' => array(
        'xtype' => 'textfield',
        'value' => 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content',
        'area' => 'localizator_translator',
    ),
);

foreach ($tmp as $k => $v) {
    /** @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key' => 'localizator_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ),
        $v
    ), '', true, true);

    $settings[] = $setting;
}
unset($tmp);

return $settings;
