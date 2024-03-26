<?php

class localizatorContentGetListProcessor extends modObjectGetListProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = 'localizatorContent';
    public $defaultSortField = 'id';
    public $defaultSortDirection = 'DESC';
    public $permission = 'localizatorcontent_list';


    /**
     * We do a special check of permissions
     * because our objects is not an instances of modAccessibleObject
     *
     * @return boolean|string
     */
    public function beforeQuery()
    {
        $this->loc_permission = $this->modx->getOption('localizator_check_permissions', null, false, true);
        if (!$this->loc_permission) return true;

        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $resource_id = $this->getProperty('resource_id');
        $where = array(
            'resource_id' => $resource_id,
            'localizatorLanguage.active' => 1,
        );

        if ($this->loc_permission) {
            $q = $this->modx->newQuery('localizatorLanguage')
                ->where(array(
                    'active' => 1,
                ))
                ->select('key');

            if ($q->prepare() && $q->stmt->execute()) {
                while ($key = $q->stmt->fetchColumn()) {
                    if (!$this->modx->hasPermission("localizatorcontent_view_{$key}")) {
                        $where['localizatorContent.key:NOT IN'][] = $key;
                    }
                }
            }
        }

        $c->leftJoin('localizatorLanguage', 'localizatorLanguage', 'localizatorLanguage.key = localizatorContent.key');
        $c->select('localizatorContent.*, CONCAT(localizatorLanguage.name, char(32), char(91), CONCAT(localizatorLanguage.key, char(93), " (", localizatorLanguage.http_host, ")"))  as `_key` ');
        $c->where($where);

        $query = trim($this->getProperty('query'));
        if ($query) {
            $c->where(array(
                'pagetitle:LIKE' => "%{$query}%",
                'OR:longtitle:LIKE' => "%{$query}%",
                'OR:menutitle:LIKE' => "%{$query}%",
                'OR:seotitle:LIKE' => "%{$query}%",
                'OR:introtext:LIKE' => "%{$query}%",
                'OR:description:LIKE' => "%{$query}%",
                'OR:keywords:LIKE' => "%{$query}%",
            ));
        }

        return $c;
    }


    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object)
    {
        $array = $object->toArray();
        $array['actions'] = array();

        // Edit
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-edit',
            'title' => $this->modx->lexicon('localizator_item_update'),
            //'multiple' => $this->modx->lexicon('localizator_items_update'),
            'action' => 'loadUpdateWin',
            'button' => true,
            'menu' => true,
        );

        if (!$array['active']) {
            $array['actions'][] = array(
                'cls' => '',
                'icon' => 'icon icon-power-off action-green',
                'title' => $this->modx->lexicon('localizator_item_enable'),
                'multiple' => $this->modx->lexicon('localizator_items_enable'),
                'action' => 'enableItem',
                'button' => true,
                'menu' => true,
            );
        } else {
            $array['actions'][] = array(
                'cls' => '',
                'icon' => 'icon icon-power-off action-gray',
                'title' => $this->modx->lexicon('localizator_item_disable'),
                'multiple' => $this->modx->lexicon('localizator_items_disable'),
                'action' => 'disableItem',
                'button' => true,
                'menu' => true,
            );
        }

        // Remove
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-trash-o action-red',
            'title' => $this->modx->lexicon('localizator_item_remove'),
            'multiple' => $this->modx->lexicon('localizator_items_remove'),
            'action' => 'removeItem',
            'button' => true,
            'menu' => true,
        );

        return $array;
    }
}

return 'localizatorContentGetListProcessor';
