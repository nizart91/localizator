<?php
return array(
    'modTemplateVar' =>
    array(
        'fields' =>
        array(
            'localizator_enabled' => 0,
        ),
        'fieldMeta' =>
        array(
            'localizator_enabled' => array(
                'dbtype'        => 'tinyint',
                'precision'     => 1,
                'attributes'    => 'unsigned',
                'phptype'       => 'boolean',
                'null'          => false,
                'default'       => 0,
                'index'         => 'index',
            ),
        ),
        'indexes' =>
        array(
            'localizator_enabled' =>
            array(
                'alias'     => 'localizator_enabled',
                'primary'   => false,
                'unique'    => false,
                'type'      => 'BTREE',
                'columns'   =>
                array(
                    'localizator_enabled' =>
                    array(
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            )
        ),
    ),
);
