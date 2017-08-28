<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

if(isset($_GET['debug']) && $_GET['debug']=="true") {
	$dashboardConfig=getUserConfig("dashboard-".SITENAME,__DIR__,true);
} else {
	$dashboardConfig=getUserConfig("dashboard-".SITENAME);
}

if(!isset($dashboardConfig['order']) || count($dashboardConfig['order'])<=0 || 
	(count($dashboardConfig['order'])==1 && strlen($dashboardConfig['order'][0])<=0)) {
	$dashboardConfig['order']=array_keys($dashboardConfig['dashlets']);
}

//printArray($dashboardConfig);

if(!isset($dashboardConfig['preload'])) {
	$dashboardConfig['preload']=["module"=>[],"css"=>[],"js"=>[]];
}

$dashboardConfig['preload']['css'][]="dashboard";
$dashboardConfig['preload']['js'][]="dashboard";
$dashboardConfig['preload']['js'][]="jquery.alterclass";

foreach ($dashboardConfig['dashlets'] as $key => $dashlet) {
	if(isset($dashlet['preload'])) {
		if(isset($dashlet['preload']['module'])) {
			if(!is_array($dashlet['preload']['module'])) $dashlet['preload']['module']=explode(",", $dashlet['preload']['module']);
			$dashboardConfig['preload']['module']=array_merge($dashboardConfig['preload']['module'],$dashlet['preload']['module']);
		}
		if(isset($dashlet['preload']['css'])) {
			if(!is_array($dashlet['preload']['css'])) $dashlet['preload']['css']=explode(",", $dashlet['preload']['css']);
			$dashboardConfig['preload']['css']=array_merge($dashboardConfig['preload']['css'],$dashlet['preload']['css']);
		}
		if(isset($dashlet['preload']['js'])) {
			if(!is_array($dashlet['preload']['js'])) $dashlet['preload']['js']=explode(",", $dashlet['preload']['js']);
			$dashboardConfig['preload']['js']=array_merge($dashboardConfig['preload']['js'],$dashlet['preload']['js']);
		}
	}
}

$dashboardConfig['preload']['module']=array_unique($dashboardConfig['preload']['module']);
$dashboardConfig['preload']['css']=array_unique($dashboardConfig['preload']['css']);
$dashboardConfig['preload']['js']=array_unique($dashboardConfig['preload']['js']);

foreach ($dashboardConfig['preload']['module'] as $module) {
	loadModule($module);
}

//printArray($dashboardConfig);return;

echo _css($dashboardConfig['preload']['css']);
echo _js($dashboardConfig['preload']['js']);
?>
<div id='dashboardContainer' class='dashboardContainer container-fluid'>
	<?php
		foreach($dashboardConfig['order'] as $dashkey) { 
			if(!isset($dashboardConfig['dashlets'][$dashkey])) continue;
			printDashlet($dashkey, $dashboardConfig['dashlets'][$dashkey]);
		}
		if($dashboardConfig['params']['allow_controller']) {
			echo "<i class='fa fa-cog dashboardSettingsIcon'></i>";
			include __DIR__."/settings.php";
		}
	?>
</div>
