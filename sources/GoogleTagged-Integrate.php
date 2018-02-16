<?php
/**************************************************
	GoogleTagged Mod v2.1 - GoogleTaggged-Integrate.php
**************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

function gt_load()
{
	loadLanguage('GoogleTagged');
}

function gt_admin(&$admin_areas)
{
	global $txt;
		
	$admin_areas['config']['areas']['modsettings']['subsections']['googletagged'] = array($txt['googletagged_menu']);
}

function gt_modifications(&$subActions)
{	
	$subActions['googletagged'] = 'gt_settings';
}

function gt_action(&$actions)
{
	$actions['tagged'] = array('GoogleTagged.php', 'GoogleTagged');
}

function gt_menu(&$button)
{
	global $txt, $scripturl, $modSettings;
	
	$button['tagged'] = array(
		'title' => $txt['googletagged'],
		'href' => $scripturl . '?action=tagged',
		'show' => allowedTo('googletagged_view') && !empty($modSettings['googletagged']),
		'sub_buttons' => array(
		),
	);
}

function gt_permissions(&$permissionGroups, &$permissionList)
{
    $permissionGroups['membergroup']['simple'] = array('googletagged');
    $permissionGroups['membergroup']['classic'] = array('googletagged');
    $permissionList['membergroup'] = array_merge(array(
			'googletagged_view' => array(false, 'googletagged', 'view_basic_info'),
			'googletagged_manage' => array(false, 'googletagged', 'administrate'),
		), $permissionList['membergroup']
	);
}

function gt_settings()
{
	global $sourcedir;
	
	require_once($sourcedir . '/GoogleTagged.php');
	ShowGoogleTaggedAdmin();
}