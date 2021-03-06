<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2013 The facileManager Team                               |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | facileManager: Easy System Administration                               |
 | fmDNS: Easily manage one or more ISC BIND servers                       |
 +-------------------------------------------------------------------------+
 | http://www.facilemanager.com/modules/fmdns/                             |
 +-------------------------------------------------------------------------+
 | Processes server options management page                                |
 | Author: Jon LaBass                                                      |
 +-------------------------------------------------------------------------+
*/

if (!currentUserCan(array('manage_servers', 'view_all'), $_SESSION['module'])) unAuth();

include(ABSPATH . 'fm-modules/fmDNS/classes/class_options.php');

$option_type = $display_option_type = $display_option_type_sql = (isset($_GET['option_type'])) ? sanitize(ucfirst($_GET['option_type'])) : 'Global';
$display_option_type_sql = "global";
$server_serial_no = (isset($_REQUEST['server_serial_no'])) ? sanitize($_REQUEST['server_serial_no']) : 0;
$response = isset($response) ? $response : null;

/* Configure options for a view */
if (array_key_exists('view_id', $_GET)) {
	$view_id = (isset($_GET['view_id'])) ? sanitize($_GET['view_id']) : null;
	if (!$view_id) header('Location: ' . $GLOBALS['basename']);
	
	basicGet('fm_' . $__FM_CONFIG['fmDNS']['prefix'] . 'views', $view_id, 'view_', 'view_id');
	if (!$fmdb->num_rows) header('Location: config-views.php');
	$view_info = $fmdb->last_result;
	
	$display_option_type = $view_info[0]->view_name;
	$display_option_type_sql .= "' AND view_id='$view_id";
	
	$name = 'view_id';
	$rel = $view_id;
/* Configure options for a zone */
} elseif (array_key_exists('domain_id', $_GET)) {
	$domain_id = (isset($_GET['domain_id'])) ? sanitize($_GET['domain_id']) : null;
	if (!$domain_id) header('Location: ' . $GLOBALS['basename']);
	
	basicGet('fm_' . $__FM_CONFIG['fmDNS']['prefix'] . 'domains', $domain_id, 'domain_', 'domain_id');
	if (!$fmdb->num_rows) header('Location: zones.php');
	$domain_info = $fmdb->last_result;
	
	$display_option_type = displayFriendlyDomainName($domain_info[0]->domain_name);
	$display_option_type_sql .= "' AND domain_id='$domain_id";
	
	$name = 'domain_id';
	$rel = $domain_id;
} else {
	$view_id = $domain_id = $name = $rel = null;
	$display_option_type_sql .= "' AND view_id='0' AND domain_id='0";
}

if (currentUserCan('manage_servers', $_SESSION['module'])) {
	$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'add';
	$type_id_uri = (array_key_exists('view_id', $_GET)) ? '?view_id=' . $view_id : null;
	$type_id_uri = (array_key_exists('domain_id', $_GET)) ? '?domain_id=' . $domain_id : $type_id_uri;
	if (array_key_exists('server_serial_no', $_REQUEST) && $server_serial_no) {
		$server_serial_no_uri = ($type_id_uri) ? '&' : '?';
		$server_serial_no_uri .= 'server_serial_no=' . $server_serial_no;
	} else $server_serial_no_uri = null;
	switch ($action) {
	case 'add':
		if (!empty($_POST)) {
			$result = $fm_module_options->add($_POST);
			if ($result !== true) {
				$response = $result;
				$form_data = $_POST;
			} else {
				setBuildUpdateConfigFlag($server_serial_no, 'yes', 'build');
				header('Location: ' . $GLOBALS['basename'] . $type_id_uri . $server_serial_no_uri);
			}
		}
		break;
	case 'edit':
		if (!empty($_POST)) {
			$result = $fm_module_options->update($_POST);
			if ($result !== true) {
				$response = $result;
				$form_data = $_POST;
			} else {
				setBuildUpdateConfigFlag($server_serial_no, 'yes', 'build');
				header('Location: ' . $GLOBALS['basename'] . $type_id_uri . $server_serial_no_uri);
			}
		}
		if (isset($_GET['status'])) {
			if (!updateStatus('fm_' . $__FM_CONFIG['fmDNS']['prefix'] . 'config', $_GET['id'], 'cfg_', $_GET['status'], 'cfg_id')) {
				$response = 'This item could not be ' . $_GET['status'] . '.';
			} else {
				setBuildUpdateConfigFlag($server_serial_no, 'yes', 'build');
				$tmp_name = getNameFromID($_GET['id'], 'fm_' . $__FM_CONFIG['fmDNS']['prefix'] . 'config', 'cfg_', 'cfg_id', 'cfg_name');
				addLogEntry("Set option '$tmp_name' status to " . $_GET['status'] . '.');
				header('Location: ' . $GLOBALS['basename'] . $type_id_uri . $server_serial_no_uri);
			}
		}
	}
}

printHeader();
@printMenu();

$avail_servers = buildServerSubMenu($server_serial_no);

$sort_direction = null;
$sort_field = 'cfg_name';
if (isset($_SESSION[$_SESSION['module']][$GLOBALS['path_parts']['filename']])) {
	extract($_SESSION[$_SESSION['module']][$GLOBALS['path_parts']['filename']], EXTR_OVERWRITE);
}

echo printPageHeader($response, $display_option_type . ' ' . getPageTitle(), currentUserCan('manage_servers', $_SESSION['module']), $name, $rel);
echo "$avail_servers\n";
	
$result = basicGetList('fm_' . $__FM_CONFIG['fmDNS']['prefix'] . 'config', array($sort_field, 'cfg_name'), 'cfg_', "AND cfg_type='$display_option_type_sql' AND server_serial_no=$server_serial_no", null, false, $sort_direction);
$fm_module_options->rows($result);

printFooter();



?>
