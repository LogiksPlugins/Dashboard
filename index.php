<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

$_SESSION['DASHDATA']=[];

$slugs = _slug("dboard/b/c");
$dashkey = false;
if(isset($_GET['dashkey']) && strlen($_GET['dashkey'])>0) {
	$dashkey = $_GET['dashkey'];
} elseif(strlen($slugs['dboard'])>0) {
	if($slugs['dboard']=="dashboard" && strlen($slugs['b'])>0) {
		$dashkey = $slugs['b'];
	} else {
		$dashkey = $slugs['dboard'];
	}
}

$dashFile = false;
if($dashkey) {
	$dashkey = str_replace(".","/",$dashkey);
	$dashFile = false;
	
	if(file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/{$dashkey}.json")) {
		$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/{$dashkey}.json";
	} elseif(file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/commons/{$dashkey}.json")) {
		$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/commons/{$dashkey}.json";
	} elseif(isset($_SESSION['SESS_PRIVILEGE_NAME']) && file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}/{$dashkey}.json")) {
		$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}/{$dashkey}.json";
	} else {
		echo "<h1 align=center>"._ling("Sorry, Dashboard not configured yet")."</h1>";
		return;
	}
} elseif(isset($_SESSION['SESS_PRIVILEGE_NAME']) && file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}.json")) {
	$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}.json";
} elseif(file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/default.json")) {
	$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/default.json";
}

if($dashFile && is_file($dashFile)) {
	$dashboardConfig=json_decode(file_get_contents($dashFile),true);
	if(!$dashboardConfig) {
		echo "<h1 align=center>"._ling("Sorry, Dashboard not found")."</h1>";
		return;
	}
	if(!isset($dashboardConfig['access']) || $dashboardConfig['access']!="public") {
		if(!checkUserRoles("DASHBOARD","Boards",$dashkey)) {
			echo "<h1 align=center>"._ling("Sorry, you don't have permission for accessing this Dashboard")."</h1>";
			return;
		}
	}
} else {
	if(isset($_GET['debug']) && $_GET['debug']=="true") {
		$dashboardConfig=getUserConfig("dashboard-".SITENAME,__DIR__,true);
	} else {
		$dashboardConfig=getUserConfig("dashboard-".SITENAME);
	}
}

$dashboardConfig = processDashboardConfig($dashboardConfig);

$dashboardConfig['params']['allow_controller']=1;
//echo json_encode($dashboardConfig);
// printArray($dashboardConfig);return;

foreach ($dashboardConfig['preload']['module'] as $module) {
	loadModule($module);
}

//printArray($dashboardConfig);return;

echo _css($dashboardConfig['preload']['css']);
echo _js($dashboardConfig['preload']['js']);
?>
<div id='dashboardContainer' class='dashboardContainer container-fluid <?=$dashboardConfig['params']['allow_dnd']?"withDND":""?>'>
	<?php
		foreach($dashboardConfig['order'] as $dashkey) { 
			if(!isset($dashboardConfig['dashlets'][$dashkey])) continue;
			printDashlet($dashkey, $dashboardConfig['dashlets'][$dashkey],$dashboardConfig);
		}
		if($dashboardConfig['params']['allow_controller']) {
			echo "<i class='fa fa-cog dashboardSettingsIcon'></i>";
			include __DIR__."/settings.php";
		}
		if(strtolower(getConfig("APPS_STATUS"))!="production" && strtolower(getConfig("APPS_STATUS"))!="prod") {
			echo "<i class='fa fa-save dashboardSaveIcon development'></i>";
		}
	?>
</div>
