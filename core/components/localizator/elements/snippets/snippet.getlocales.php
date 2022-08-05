<?php

$pdoFetch = $modx->getService('pdoFetch');
$defaultLocale = $modx->getOption('defaultLocale', $scriptProperties, 'ua');
$tpl = $modx->getOption('tpl', $scriptProperties, '');
$currentTpl = $modx->getOption('currentTpl', $scriptProperties, '');
$start = $modx->getOption('site_start');
$pageId = $modx->getOption('pageId', $scriptProperties, $modx->resource->get('id'));
$currentLocale = $modx->config['cultureKey'];
$where = $modx->getOption('where', $scriptProperties, ['active' => 1]);

$protocol = $modx->getOption('server_protocol') . '://';

$locales = $pdoFetch->getCollection('localizatorLanguage', $where, $scriptProperties);

$output = '';

if ($locales) {

    foreach ($locales as $data) {
        $data['current'] = false;
        $url = $data['http_host'];
        $chunk = $tpl;

        if ($pageId != $start) {
            $url = $data['http_host'] . $modx->makeUrl($pageId);
        }

        if ($data['key'] == $currentLocale || $data['cultureKey'] == $currentLocale) {
            $data['current'] = true;

            if (!empty($currentTpl)) {
                $chunk = $currentTpl;
            }
        }

        $data['url'] = $protocol . $url;

        $output .= $pdoFetch->getChunk($chunk, $data);
    }
}

return $output;
