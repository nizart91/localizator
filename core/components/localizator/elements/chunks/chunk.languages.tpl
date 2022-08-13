<ul class="{$outerClass}">
    {foreach $languages as $language}
        <li class="{$language.rowClass}"><a href="{$language.url}">{$language.name}</a></li>
    {/foreach}
</ul>