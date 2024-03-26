<?php
$xpdo_meta_map['localizatorLanguage'] = array(
  'package' => 'localizator',
  'version' => '1.1',
  'table' => 'localizator_languages',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' =>
  array(
    'engine' => 'MyISAM',
  ),
  'fields' =>
  array(
    'name' => '',
    'key' => '',
    'cultureKey' => '',
    'http_host' => '',
    'description' => '',
    'active' => 1,
  ),
  'fieldMeta' =>
  array(
    'name' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'key' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'cultureKey' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'http_host' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'description' =>
    array(
      'dbtype' => 'text',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'active' =>
    array(
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'boolean',
      'null' => true,
      'default' => 1,
    ),
  ),
  'indexes' =>
  array(
    'key' =>
    array(
      'alias' => 'key',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' =>
      array(
        'key' =>
        array(
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'cultureKey' =>
    array(
      'alias' => 'cultureKey',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' =>
      array(
        'cultureKey' =>
        array(
          'length' => '',
          'collation' => 'A',
          'null' => true,
        ),
      ),
    ),
  ),
);
