<?php
$pdoFetch = $modx->getService('pdoFetch');
$defaultLocale = $modx->getOption('defaultLocale', $scriptProperties, 'ru');
$tpl = $modx->getOption('tpl', $scriptProperties, 'languages.tpl');
$activeClass = $modx->getOption('activeClass', $scriptProperties, 'active');
$outerClass = $modx->getOption('outerClass', $scriptProperties, 'languages');
$rowClass = $modx->getOption('rowClass', $scriptProperties, '');
$start = $modx->getOption('site_start');
$pageId = $modx->getOption('pageId', $scriptProperties, $modx->resource->get('id'));
$currentLocale = $modx->config['cultureKey'];
$where = $modx->getOption('where', $scriptProperties, ['active' => 1]);
$protocol = $modx->getOption('server_protocol') . '://';

$locales = $pdoFetch->getCollection('localizatorLanguage', $where, $scriptProperties);

$output = '';
$data = [];
$languages = [];

if ($locales) {

    $data = $scriptProperties;

    foreach ($locales as $locale) {

        $http_host = $locale['http_host'];
        $is_current = false;
        $pos_modx = strpos($http_host, '[[');
        $pos_protocol = strpos($http_host, '://');

        if ($pos_modx === false && $pos_protocol === false) {
            $http_host = $protocol . $http_host;
        }

        $url = $http_host;

        if ($pageId != $start) {
            if (mb_substr($url, -1) != '/') {
                $url .= '/';
            }

            $url .= $modx->makeUrl($pageId);
        }

        if ($locale['key'] == $currentLocale || $locale['cultureKey'] == $currentLocale) {
            $is_current = true;
        }

        $class = $is_current ? $rowClass . ' ' . $activeClass : $rowClass;

        $languages[] = array_merge(
            $locale,
            [
                'rowClass' => $class,
                'url' => $url,
                'is_current' => $is_current
            ]
        );
    }

    $data['languages'] = $languages;

    $output = $pdoFetch->getChunk($tpl, $data);
}

return $output;
