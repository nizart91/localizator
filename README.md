# localizator

Языковые версии \ сателиты без контекстов, с автоматическим переводом всех полей ресурса + сео, да еще и автоперевод лексиконов — это все в дополнении localizator.

header.tpl

``` php
{'!Localizator' | snippet : [
  'snippet' => 'pdoMenu',
  'parents' => 0,
  'level' => 2,
  'startId' => 0,
  'tplParentRow' => '@INLINE
  <li class="[[+classnames]] dropdown">
  <a href="#" class="dropdown-toggle" data-toggle="dropdown" [[+attributes]]>[[+menutitle]]<b class="caret"></b></a>
  <ul class="dropdown-menu">{$wrapper}</ul>
  </li>'
  'tplOuter' => '@INLINE {$wrapper}'
]}
```

main.tpl

``` php
<h1>{$_modx->resource.longtitle ?: $_modx->resource.pagetitle}</h1>


<span style="color:red;">{$_modx->resource->localizator_content}</span><br>
<br>{$_modx->resource.localizator_content}
<hr>

<span style="color:red;">&#x7B;$_modx->resource.pagetitle}</span> = {$_modx->resource.pagetitle}
<br><span style="color:red;">&#x7B;$_modx->resource.longtitle}</span> = {$_modx->resource.longtitle}
<br>
<hr>
<br>
<ul>
{'!Localizator' | snippet : [
'snippet' => 'pdoResources',
  'parents' => 4,
    'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>'
]}
</ul>
<span style="color: #bbbbbb; font-size: 12px; margin-left: 41px;">Выводятся только переведенные документы</span>
<br>
<hr>
<span style="color:red;">{'localizator_key' | option}</span> = {'localizator_key' | option}
<br><span style="color:red;">{'cultureKey' | option}</span> = {'cultureKey' | option}
<br><span style="color:red;">{'cache_resource_key' | option}</span> = {'cache_resource_key' | option}
<br><span style="color:red;">{'site_url' | option}</span> = {'site_url' | option}
<hr>
{'!getLanguages' | snippet}
```

getLanguages snippet 

``` php
<?php
$output = "";

// определяем есть ли языки через "папки"
$uri = $_SERVER['REQUEST_URI'];
if(substr($uri, 0, 1)) {
    $uri = mb_substr($uri, 1);
    $tmp = explode('/', $uri);
    if($path = $tmp[0]) {
        $tmp = $modx->getObject('localizatorLanguage', array('http_host:LIKE' => "%/{$path}/"));
        if($tmp) {
            $uri = str_replace("{$path}/", "", $uri);
        }
    }
}

$languages = $modx->getIterator('localizatorLanguage');
foreach($languages as $language) {
    if(mb_substr($language->http_host, -1) == '/') {
        $link = $language->http_host . $uri;
    } else { 
        $link = $language->http_host . '/' . $uri;
    }
    $output .= "<br><a href=\"http://{$link}\">{$language->name}</a>"; 
}
    
return $output;
```
