<?php
$xpdo_meta_map['localizatorContent'] = array(
  'package' => 'localizator',
  'version' => '1.1',
  'table' => 'localizator_content',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' =>
  array(
    'engine' => 'MyISAM',
  ),
  'fields' =>
  array(
    'resource_id' => 0,
    'key' => '',
    'pagetitle' => '',
    'longtitle' => '',
    'menutitle' => '',
    'seotitle' => '',
    'keywords' => '',
    'introtext' => '',
    'description' => '',
    'content' => NULL,
    'active' => 1,
  ),
  'fieldMeta' =>
  array(
    'resource_id' =>
    array(
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => true,
      'default' => 0,
    ),
    'key' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'pagetitle' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'longtitle' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'menutitle' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'seotitle' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'keywords' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'introtext' =>
    array(
      'dbtype' => 'text',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'description' =>
    array(
      'dbtype' => 'text',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'content' =>
    array(
      'dbtype' => 'mediumtext',
      'phptype' => 'string',
      'null' => true,
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
    'resource_id' =>
    array(
      'alias' => 'resource_id',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' =>
      array(
        'resource_id' =>
        array(
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
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
  ),
  'aggregates' =>
  array(
    'Resource' =>
    array(
      'class' => 'modResource',
      'local' => 'resource_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);
