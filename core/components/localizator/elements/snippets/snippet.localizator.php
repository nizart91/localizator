<?php
$localizator = $modx->getService('localizator');

$class = $modx->getOption('class', $scriptProperties, 'modResource', true);
$localizator_key = $modx->getOption('localizator_key', $scriptProperties, $modx->getOption('localizator_key', null), true);

$elementName = $modx->getOption('snippet', $scriptProperties, 'pdoResources', true);
$elementSet = array();
if (strpos($elementName, '@') !== false) {
    list($elementName, $elementSet) = explode('@', $elementName);
}
if ($elementName == 'msProducts') {
    $class = $scriptProperties['class'] = 'msProduct';
}

// Start build "where" expression
$where = array(
    'localizator.key' => $localizator_key,
);

// Join tables
$leftJoin = array(
    'localizator' => array('class' => 'localizatorContent', 'on' => "`localizator`.`resource_id` = `{$class}`.`id`"),
);

$select = array(
    'localizator' => "`{$class}`.*, `localizator`.*, `{$class}`.`id`",
);

// Add user parameters
foreach (array('where', 'leftJoin', 'select') as $v) {
    if (!empty($scriptProperties[$v])) {
        $tmp = $scriptProperties[$v];
        if (!is_array($tmp)) {
            $tmp = json_decode($tmp, true);
        }
        if (is_array($tmp)) {
            $$v = array_merge($$v, $tmp);
        }
    }
    unset($scriptProperties[$v]);
}

$localizatorProperties = array(
    'where' => $where,
    'leftJoin' => $leftJoin,
    'select' => $select,
    'localizator_key' => $localizator_key,
);


unset($scriptProperties['snippet']);
/** @var modSnippet $snippet */
if (!empty($elementName) && $element = $modx->getObject('modSnippet', array('name' => $elementName))) {
    $elementProperties = $element->getProperties();
    $elementPropertySet = !empty($elementSet)
        ? $element->getPropertySet($elementSet)
        : array();
    if (!is_array($elementPropertySet)) {
        $elementPropertySet = array();
    }
    $params = array_merge(
        $elementProperties,
        $elementPropertySet,
        $scriptProperties,
        $localizatorProperties
    );
    $element->setCacheable(false);
    return $element->process($params);
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator] Could not find main snippet with name: "' . $elementName . '"');
    return '';
}
