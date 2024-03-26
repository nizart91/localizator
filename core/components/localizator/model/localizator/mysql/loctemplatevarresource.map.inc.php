<?php
$xpdo_meta_map['locTemplateVarResource'] = array(
  'package' => 'localizator',
  'version' => '1.1',
  'table' => 'localizator_tmplvar_contentvalues',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' =>
  array(
    'engine' => 'MyISAM',
  ),
  'fields' =>
  array(
    'key' => '',
    'tmplvarid' => 0,
    'contentid' => 0,
    'value' => NULL,
  ),
  'fieldMeta' =>
  array(
    'key' =>
    array(
      'dbtype' => 'varchar',
      'precision' => '100',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
      'index' => 'index',
    ),
    'tmplvarid' =>
    array(
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
      'index' => 'index',
    ),
    'contentid' =>
    array(
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
      'index' => 'index',
    ),
    'value' =>
    array(
      'dbtype' => 'mediumtext',
      'phptype' => 'string',
      'null' => false,
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
    'tmplvarid' =>
    array(
      'alias' => 'tmplvarid',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' =>
      array(
        'tmplvarid' =>
        array(
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'contentid' =>
    array(
      'alias' => 'contentid',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' =>
      array(
        'contentid' =>
        array(
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'tv_cnt' =>
    array(
      'alias' => 'tv_cnt',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' =>
      array(
        'key' =>
        array(
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
        'tmplvarid' =>
        array(
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
        'contentid' =>
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
    'TemplateVar' =>
    array(
      'class' => 'modTemplateVar',
      'local' => 'tmplvarid',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Resource' =>
    array(
      'class' => 'modResource',
      'local' => 'contentid',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);
