<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

$_SESSION['DASHDATA']=[];

$slugs = _slug("dboard/b/c");
$dashkey = false;
if(isset($_GET['dashkey']) && strlen($_GET['dashkey'])>0) {
	$dashkey = $_GET['dashkey'];
} elseif(strlen($slugs['dboard'])>0) {
	$dashkey = $slugs['dboard'];
}

if($dashkey) {
	$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/{$dashkey}.json";
	if(is_file($dashFile)) {
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
		echo "<h1 align=center>"._ling("Sorry, Dashboard not configured yet")."</h1>";
		return;
	}
} else {
	if(isset($_GET['debug']) && $_GET['debug']=="true") {
		$dashboardConfig=getUserConfig("dashboard-".SITENAME,__DIR__,true);
	} else {
		$dashboardConfig=getUserConfig("dashboard-".SITENAME);
	}
}

$dashboardConfig = processDashboardConfig($dashboardConfig);

//$dashboardConfig['params']['allow_controller']=0;
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
	?>
</div>
