<?php
if(!defined('ROOT')) exit('No direct script access allowed');
//$_GET['dboard']="commons.test";
include_once __DIR__."/api.php";

$_SESSION['DASHDATA']=[];

$slugs = _slug("dboard/b/c/d");
$dboard = false;
if(isset($_GET['dboard']) && strlen($_GET['dboard'])>0) {
	$dboard = $_GET['dboard'];
} elseif(strlen($slugs['dboard'])>0) {
	if($slugs['dboard']=="dashboard" && strlen($slugs['b'])>0) {
		$dboard = $slugs['b'];
	} else {
		$dboard = $slugs['dboard'];
	}
}
if(!$dboard || strlen($dboard)<=0) {
	$dboard = "default";
}

$dashboardConfig=getUserConfig("dashboard-".SITENAME."-".$dboard);

if(!isset($dashboardConfig['dashlets']) || count($dashboardConfig['dashlets'])<=0) {
	$dashFile = findDashboardFile($dboard);
	if($dashFile) {
		$dashboardConfig=json_decode(file_get_contents($dashFile),true);

		if(!$dashboardConfig) {
			echo "<h1 align=center>"._ling("Sorry, Dashboard configuration error")."</h1>";
			return;
		}
	}

	setUserConfig("dashboard-".SITENAME."-".$dboard,$dashboardConfig);
}

$dashboardConfig = processDashboardConfig($dashboardConfig);

if(!isset($dashboardConfig['access']) || $dashboardConfig['access']!="public") {
	if(!checkUserRoles("DASHBOARD","Boards",$dboard)) {
		echo "<h1 align=center>"._ling("Sorry, you don't have permission for accessing this Dashboard")."</h1>";
		return;
	}
}

if(strtolower(getConfig("APPS_STATUS"))!="production" && strtolower(getConfig("APPS_STATUS"))!="prod") {
	$dashboardConfig['params']['allow_controller']=1;
}
//echo json_encode($dashboardConfig);
//printArray($dashboardConfig);return;

foreach ($dashboardConfig['preload']['module'] as $module) {
	loadModule($module);
}

echo _css($dashboardConfig['preload']['css']);
echo _js($dashboardConfig['preload']['js']);
?>
<div id='dashboardContainer' class='dashboardContainer container-fluid <?=$dashboardConfig['params']['allow_dnd']?"withDND":""?>' data-dashboard="<?=$dboard?>">
	<?php
		foreach($dashboardConfig['order'] as $dashkey) { 
			if(!isset($dashboardConfig['dashlets'][$dashkey])) continue;
			printDashlet($dashkey, $dashboardConfig['dashlets'][$dashkey],$dashboardConfig);
		}
		if($dashboardConfig['params']['allow_controller']) {
			echo "<i class='fa fa-cog dashboardSetupIcon dashboardSettingsIcon' title='Dashboard Settings'></i>";
			include __DIR__."/settings.php";
		}
		if(strtolower(getConfig("APPS_STATUS"))!="production" && strtolower(getConfig("APPS_STATUS"))!="prod") {
			echo "<div class='dashboardName'>".$dboard."</div>";//."dashboard-".SITENAME."-"

			echo "<i class='fa fa-copy dashboardSetupIcon dashboardSaveAsIcon development' title='Copy into new Dashboard'></i>";
			echo "<i class='fa fa-refresh dashboardSetupIcon dashboardResetIcon development' title='Reset Dashboard'></i>";

			echo "<i class='fa fa-plus-square dashboardSetupIcon dashboardNewIcon development' title='Create New Dashboard'></i>";
			echo "<i class='fa fa-save dashboardSetupIcon dashboardSaveIcon development' title='Save Dashboard'></i>";
		}
	?>
</div>
<style>
<?php if(isset($dashboardConfig['params']['background']) && $dashboardConfig['params']['background']) { ?>
.dashboardContainer {
	background: <?=$dashboardConfig['params']['background']?>;
}
<?php } ?>
<?php if(isset($dashboardConfig['params']['force_style']) && $dashboardConfig['params']['force_style']) { ?>
<?=$dashboardConfig['params']['force_style']?>
<?php } ?>

</style>