<?php
/**************************************************
	GoogleTagged Mod v2.1 - uninstall.php
**************************************************/

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');
db_extend('packages');

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
	remove_integration_function($hook, $function);

// If we're using SSI, tell them we're done
if(SMF == 'SSI')
   echo 'Database changes are complete!';
?>