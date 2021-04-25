<?php
if(!defined('ROOT')) exit('No direct script access allowed');
//$_GET['dboard']="commons.test";
include_once __DIR__."/api.php";

$_SESSION['DASHDATA']=[];

$slugs = _slug("a/b/c/d");
$dboard = false;
$mode = "viewer";

if(isset($_GET['dboard']) && strlen($_GET['dboard'])>0) {
	$dboard = $_GET['dboard'];
}

if($slugs["a"]==basename(__DIR__)) {
	$slugs["a"]=$slugs["b"];
	$slugs["b"]=$slugs["c"];
	$slugs["c"]=$slugs["d"];
}

switch($slugs["a"]) {
	case "dashedit":
		if(!$dboard) {
			if(strlen($slugs['b'])>0) {
				$dboard = $slugs['b'];
			} else {
				$dboard = "";
			}
		}
		$mode = "editor";
		break;
	default:
		if(!$dboard) {
			if(strlen($slugs['b'])>0) {
				$dboard = $slugs['b'];
			} elseif(strlen($slugs['a'])>0) {
				$dboard = $slugs['a'];
			} else {
				$dboard = "default";
			}
		}
}

$dashboardConfig = [];
switch($mode) {
	case "viewer":
		if(!isset($_GET['reset']) || $_GET['reset']!="true") {
			$dashboardConfig = getUserConfig("dashboard-".SITENAME."-".$dboard);
		} else {
			$dashboardConfig = [];
		}

		if(!isset($dashboardConfig['dashlets']) || count($dashboardConfig['dashlets'])<=0) {
			$dashFile = findDashboardFile($dboard);
			if($dashFile) {
				$dashboardConfig=json_decode(file_get_contents($dashFile),true);

				if(!$dashboardConfig) {
					echo "<h1 align=center>"._ling("Sorry, Dashboard configuration error")."</h1>";
					return;
				}
			}

			if(!isset($_GET['nocache']) || $_GET['nocache']!="false") {
				setUserConfig("dashboard-".SITENAME."-".$dboard,$dashboardConfig);
			}
		}
		break;
	case "editor":
		$dashFile = findDashboardFile($dboard);
		if($dashFile) {
			$dashboardConfig=json_decode(file_get_contents($dashFile),true);
			
			if(!$dashboardConfig) {
				echo "<h1 align=center>"._ling("Sorry, Dashboard configuration error")."</h1>";
				return;
			}
		}

		setUserConfig("dashboard-".SITENAME."-".$dboard,$dashboardConfig);
		break;
	default:
		echo "<h1 align=center>Mode not supported</h1>";
		return;
}
//echo json_encode($dashboardConfig);

$dashboardConfig = processDashboardConfig($dashboardConfig);
//printArray($dashboardConfig);return;

if(!isset($dashboardConfig['access']) || $dashboardConfig['access']!="public") {
	if(!checkUserRoles("DASHBOARD","Boards",$dboard)) {
		echo "<h1 align=center>"._ling("Sorry, you don't have permission for accessing this Dashboard")."</h1>";
		return;
	}
}

if(isset($dashboardConfig['preload'])) {
	if(isset($dashboardConfig['preload']['module'])) {
		foreach ($dashboardConfig['preload']['module'] as $module) {
			loadModule($module);
		}
	}
	if(isset($dashboardConfig['preload']['vendor'])) {
		foreach ($dashboardConfig['preload']['vendor'] as $vendor) {
			loadVendor($vendor);
		}
	}
}

// printArray($dashboardConfig);

echo _css($dashboardConfig['preload']['css']);
echo _js($dashboardConfig['preload']['js']);
?>
<div id='dashboardContainer' class='dashboardContainer container-fluid <?=$dashboardConfig['params']['allow_dnd']?"withDND":""?>' data-dashboard="<?=$dboard?>">
	<?php
		foreach($dashboardConfig['order'] as $dashkey) { 
			if(!isset($dashboardConfig['dashlets'][$dashkey])) continue;
			printDashlet($dashkey, $dashboardConfig['dashlets'][$dashkey],$dashboardConfig);
		}
		if($mode=="viewer") {
			if($dashboardConfig['params']['allow_reset']) {
				echo "<i class='fa fa-refresh dashboardSetupIcon dashboardReloadIcon' title='Refresh Dashboard'></i>";
			}
			// if(strtolower(getConfig("APPS_STATUS"))!="production" && strtolower(getConfig("APPS_STATUS"))!="prod") {
			// 	$dashboardConfig['params']['allow_controller']=1;
			// 	echo "<i class='fa fa-pencil dashboardSetupIcon dashboardEditIcon development' title='Edit this Dashboard'></i>";
			// }
			if($dashboardConfig['params']['allow_edit']) {
				echo "<i class='fa fa-pencil dashboardSetupIcon dashboardEditIcon development' title='Edit this Dashboard'></i>";
			}
			if($dashboardConfig['params']['allow_controller']) {
				echo "<i class='fa fa-cog dashboardSetupIcon dashboardSettingsIcon' title='Dashboard Settings'></i>";
				include __DIR__."/settings.php";
			}
		} elseif($mode=="editor") {
			echo "<i class='fa fa-cog dashboardSetupIcon dashboardSettingsIcon' title='Dashboard Settings'></i>";
			include __DIR__."/settings.php";
				
			echo "<div id='dashboardNameBox' class='dashboardName'>".$dboard."</div>";//."dashboard-".SITENAME."-"

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