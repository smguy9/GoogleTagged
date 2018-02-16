<?php
/**************************************************
	GoogleTagged Mod v2.1 - install.php
**************************************************/

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');
db_extend('packages');
   
$columns = array(
	array('name' => 'id_tag', 'type' => 'int', 'size' => '9', 'auto' => true),
	array('name' => 'tag', 'type' => 'text'),
	array('name' => 'id_topic', 'type' => 'int', 'size' => '8'),
	array('name' => 'hits', 'type' => 'int', 'size' => '8', 'default' => '1'),
	array('name' => 'status','type' => 'int','size' => '1','default' => '1'),	
);
$indexes = array(
	array('type' => 'primary','columns' => array('id_tag')),
);
$smcFunc['db_create_table']('{db_prefix}googletagged', $columns, $indexes, array(), 'ignore');

// Let's insert default settings.
$mod_settings = array(
	'googletagged' => '1',
	'googletagged_together' => '0',
	'googletagged_max_length' => '20',
	'googletagged_min_length' => '3',
	'googletagged_limit_max' => '50',
	'googletagged_limit_max_display' => '20',
);

foreach ($mod_settings as $variable => $value)
{
	if (empty($modSettings[$variable]))
		updateSettings(array($variable => $value));
}

$integrations = array(
	'integrate_pre_include' => 'Sources/GoogleTagged-Integrate.php',
	'integrate_pre_load' => 'gt_load',
	'integrate_admin_areas' => 'gt_admin',
	'integrate_modify_modifications' => 'gt_modifications',
	'integrate_actions' => 'gt_action',
	'integrate_menu_buttons' => 'gt_menu',
	'integrate_load_permissions' => 'gt_permissions',
);

foreach ($integrations as $hook => $function)
	add_integration_function($hook, $function);	
	
// If we're using SSI, tell them we're done
if(SMF == 'SSI')
   echo 'Database changes are complete!';
?>