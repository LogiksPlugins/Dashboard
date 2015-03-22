<?php
if(!defined('ROOT')) exit('No direct script access allowed');
$arr=array();
if(defined("APPS_PLUGINS_FOLDER"))
	$arr[]=APPROOT.APPS_PLUGINS_FOLDER."dashlets/";
$arr[]=ROOT.PLUGINS_FOLDER."dashlets/";

$dashletsArr=array();
foreach ($arr as $dir) {
	$fs=scandir($dir);
	unset($fs[0]);unset($fs[1]);
	foreach($fs as $fname) {
		if(substr(strtolower($fname), strlen($fname)-5)!=".json") continue;
		$f=$dir.$fname;
		$fid=substr(strtolower($fname), 0, strlen($fname)-5);

		$dashArr=json_decode(file_get_contents($f),true);

		if(!isset($dashArr['title'])) $dashArr['title']="";
		if(!isset($dashArr['descs'])) $dashArr['descs']="";
		if(!isset($dashArr['photo']) || strlen($dashArr['photo'])<=0) $dashArr['photo']=getWebpath(__FILE__)."/css/images/widget.png";
		else {
			if(file_exists(dirname($f)."/".$dashArr['photo'])) {
				$dashArr['photo']=getWebpath($f).$dashArr['photo'];
			} elseif(substr($dashArr['photo'], 0,7)=="http://" || 
					substr($dashArr['photo'], 0,8)=="https://") {
			} else {
				$dashArr['photo']=loadMedia($dashArr['photo']);
			}
		}
		if(!isset($dashArr['author'])) $dashArr['author']=array();
		if(!isset($dashArr['type'])) $dashArr['type']="self";
		if(!isset($dashArr['source'])) $dashArr['title']=$fid;
		if(!isset($dashArr['dependency'])) $dashArr['dependency']=array();
		if(!isset($dashArr['market'])) $dashArr['market']=array();

		$dashletsArr[$fid]=array(
				"path"=>$f,
				"id"=>$fid,
				"title"=>$dashArr['title'],
				"descs"=>$dashArr['descs'],
				"photo"=>$dashArr['photo'],
				"author"=>$dashArr['author'],
				"type"=>$dashArr['type'],
				"source"=>$dashArr['source'],
				"dependency"=>$dashArr['dependency'],
				"market"=>$dashArr['market'],
			);
	}
}

$dashCfg=loadFeature("dashboard");
$userCfg=array(
		"DASHLETS"=>$dashCfg['DEFAULT_DASHLETS'],
		"BACKGROUND"=>$dashCfg['BACKGROUND'],
		"LAYOUT"=>$dashCfg['LAYOUT'],
		"ALLOW_SERVER_MESSAGES"=>$dashCfg['ALLOW_SERVER_MESSAGES'],
		"CONFIGS"=>array()
	);
$userCfg=getSettings("Dashboard Configuration",json_encode($userCfg),"system");
$userCfg=stripslashes($userCfg);
if(strlen($userCfg)>2) $userCfg=json_decode($userCfg,true);
else $userCfg=array();
if(isset($userCfg['DASHLETS']))
	$dashlets=$userCfg['DASHLETS'];
else
	$dashlets="";

$userDashlets=explode(",", $dashlets);

$webPath=getWebPath(__FILE__);
?>
<link href='<?=$webPath?>css/dashletSelector.css' rel='stylesheet' type='text/css' media='all' />
<style>
.ui-dialog-content.ui-widget-content {
	margin: 0px;padding: 0px;
	overflow:hidden;
}
</style>
<div class='dlgSpace'>
	<ul>
		<li><a href='#gallery'>Gallery</a></li>
	</ul>
	<div id='gallery' class='tabspace'>
		<ul id='dashletSpace'>
		<?php
			foreach ($dashletsArr as $arr) {
				if(in_array($arr['id'], $userDashlets)) continue;
				createDashletField($arr,$userDashlets);
			}
		?>
		</ul>
	</div>
</div>
<script>
$(function() {
	$(".dlgSpace").tabs();
	$("#dashletSpace input.dashletEnabler").change(function() {
		d=$(this).attr("rel");
		l=getServiceCMD("dashboard")+"&action=addDashlet";
		q="dashlet="+d;
		processAJAXPostQuery(l,q,function(txt) {
			if(txt.trim().length>0) lgksAlert(txt);
		});
		$(this).parents(".dashletField:first").slideUp(function() {
			$(this).detach();
		});
	});
});
</script>
<?php
function createDashletField($arr,$userDashlets=array()) {
?>
<li class='dashletField ui-widget-content'>
	<div class='ui-widget-header' title='<?=$arr['title']?>'>
			<?=$arr['title']?>
			<input rel='<?=$arr['id']?>' type=checkbox class='dashletEnabler' style='float:right;' />
	</div>
	<div class='photo'>
		<img src='<?=$arr['photo']?>' class='' width=100px height=100px />
	</div>
	<div class='info'>
		<p><?=$arr['descs']?></p>
		<hr/>
		<b>Author Informations</b>
		<?php
			if(isset($arr['author']) && count($arr['author'])>0) {
				echo "<ul class='author'>";
				foreach($arr['author'] as $a=>$b) {
					if($a=="email" && strlen($b)>0) $b="<a href='mailto:$b'>$b</a>";
					$a=toTitle($a);
					echo "<li>$a :: $b</li>";
				}
				echo "</ul>";
			} else {
				echo "<ul class='author'><li>No Author Info Found</li></ul>";
			}
		?>
		<hr/>
	</div>
</li>
<?php
}
?>