<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* db_wep.php - I'm gonna give you 500$ if you guess what it		*
*				does(In your dreams)							*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

global $db_name;

// Load SSI
if (!defined('SMF'))
{
	require_once(dirname(__FILE__) . '/SSI.php');

	// Why, oh why?
	mysql_select_db($db_name);
}

// Extend the base system to include classes we need
wesql::extend();
wesql::extend('packages');

// Create the block's table
wedbPackages::create_table('{db_prefix}wep_contents',
	array(
		array('name' => 'id_object', 'type' => 'int', 'auto' => true),
		array('name' => 'holder', 'type' => 'varcha', 'size' => 30, 'default' => 'block'),
		array('name' => 'title', 'type' => 'varchar', 'size' => 150, 'default' => '-no title-'),
		array('name' => 'controller', 'type' => 'varchar', 'size' => 50),
		array('name' => 'bar', 'type' => 'varchar', 'size' => 25),
		array('name' => 'position', 'type' => 'int', 'default' => 0),
		array('name' => 'adjustable', 'type' => 'enum(\'0\', \'1\')', 'default' => 1),
		array('name' => 'parameters', 'type' => 'text', 'default' => ''), // Parameters adjustable by ACP which are passed to the ContentProvider controller
		array('name' => 'enabled', 'type' => 'enum(\'0\', \'1\')', 'default' => 0),
		array('name' => 'groups', 'type' => 'text', 'default' => ''),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_block'),
		),
	),
	array(),
	'update'
);

// Create the user's block adjustment table
wedbPackages::create_table('{db_prefix}wep_block_adjustments',
	array(
		array('name' => 'id_member', 'type' => 'int'),
		array('name' => 'id_block', 'type' => 'int'),
		array('name' => 'bar', 'type' => 'varchar', 'size' => 25),
		array('name' => 'position', 'type' => 'int', 'default' => 0),
		array('name' => 'enabled', 'type' => 'enum(\'0\', \'1\')', 'default' => 1),
	),
	array(
		array(
			'type' => 'primary',
			'columns' => array('id_member', 'id_block'),
		),
	),
	array(),
	'update'
);
?>