<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("createDashlet")) {
	function createDashlet($dashlet,$forceParams=array(),$debug=false) {
		$dName=$dashlet;
		$dPath=findDashlet($dashlet);
		if(!$dPath) {
			if($debug) echo "<div class='errMsg'>Dashlet Not Installed :: $dashlet</div>";
			return;
		}
		$dashArr=json_decode(file_get_contents($dPath),true);
		if(count($dashArr)<=0) {
			if($debug) echo "<div class='errMsg'>Dashlet Configuration Error :: $dashlet</div>";
			return;
		}
		if(!isset($dashArr['title'])) $dashArr['title']="";
		
		$defParams=array(
				"autoOpen"=>true,
				"spanClass"=>"span_1",
				"iframe"=>false,
				"style"=>"",
				"styleContent"=>"",
				"noheader"=>false
				);
		foreach($defParams as $a=>$b) {
			if(!isset($dashArr['params'][$a])) {
				$dashArr['params'][$a]=$b;
			}
		}
		if(isset($dashArr['enabled']) && $dashArr['enabled']===false) {
			return;
		}
		if(isset($_SESSION['DASHBOARD']['PARAMS'])) {
			if(isset($_SESSION['DASHBOARD']['PARAMS'][$dName])) {
				$tempParams=$_SESSION['DASHBOARD']['PARAMS'][$dName];
				foreach($tempParams as $a=>$b) {
					$dashArr['params'][$a]=$b;
				}
			}
		}
		foreach($forceParams as $a=>$b) {
			$dashArr['params'][$a]=$b;
		}
		
		if(!isset($dashArr['schema'])) $dashArr['schema']=array();

		echo "<div id='dashlet_{$dName}' name='{$dName}' rel='{$dName}' class='portlet {$dashArr['params']['spanClass']}' style='{$dashArr['params']['style']}'>";
		if(!$dashArr['params']['noheader']) {
			echo "<div class='portlet-header'><div class='title'>{$dashArr['title']}</div></div>";
		}
		if(!$dashArr['params']['autoOpen'] && !$dashArr['params']['noheader']) {
			echo "<div class='portlet-body' style='display:none;'>";
		} else {
			echo "<div class='portlet-body'>";
		}

		if(isset($_SESSION['DASHBOARD']['CONFIGS']) && count($_SESSION['DASHBOARD']['CONFIGS'][$dName])>0) {
			if(isset($_SESSION['DASHBOARD']['CONFIGS'][$dName])) {
				$dashArr['config']=$_SESSION['DASHBOARD']['CONFIGS'][$dName];
			} else {
				if(!isset($dashArr['config'])) $dashArr['config']=array();
				$_SESSION['DASHBOARD']['CONFIGS'][$dName]=$dashArr['config'];
				saveUserSettings($_SESSION['DASHBOARD']);
			}
		}
		if(isset($dashArr['config']) && count($dashArr['config'])>0) {
			echo "<div class='portlet-config portlet-container'>";
			echo "<ul class='configurations'>";
			foreach($dashArr['config'] as $a=>$b) {
				$t=toTitle($a);
				if(isset($dashArr['schema'][$a])) $schema=$dashArr['schema'][$a];
				else $schema=array("type"=>"text");
				echo "<li><label>$t</label> :: ".getField($a,$b,$schema)."</li>";
			}
			echo "</ul>";
			echo "<div align=center><button class='saveConfig nostyle clr_green'>Save</button></div>";
			echo "</div>";
		} else {
			$dashArr['config']=array();
		}
		if(isset($dashArr['descs']) && strlen($dashArr['descs'])>0) {
			echo "<div class='portlet-info portlet-container'>";
			echo $dashArr['descs'];
			if(isset($dashArr['author']) && count($dashArr['author'])>0) {
				echo "<ul class='author'>";
				foreach($dashArr['author'] as $a=>$b) {
					if($a=="email" && strlen($b)>0) $b="<a href='mailto:$b'>$b</a>";
					$a=toTitle($a);
					echo "<li>$a :: $b</li>";
				}
				echo "</ul>";
			}
			echo "</div>";
		}
		printDashletContent($dashArr['type'],$dashArr['source'],$dPath,$dashArr['config'],$dashArr['params']['styleContent']);
		if(isset($dashArr['footer']) && count($dashArr['footer'])>0) {
			echo "<div class='portlet-footer portlet-container'>";
			echo "Dashlet Footer";
			echo "</div>";
		}
		echo "</div></div>";
	}

	function findDashlet($dashlet) {
		$arr=array();
		if(defined("APPS_PLUGINS_FOLDER"))
			$arr[]=APPROOT.APPS_PLUGINS_FOLDER."dashlets/$dashlet.json";
		$arr[]=ROOT.PLUGINS_FOLDER."dashlets/$dashlet.json";

		foreach($arr as $f) {
			if(file_exists($f)) {
				return $f;
			}
		}
		return false;
	}
	function getField($a,$v,$schema=array("type"=>"text")) {
		if(!isset($schema['type'])) $schema['type']="text";
		switch ($schema['type']) {
			case 'select':
				if(isset($schema['values'])) {
					$s="<select name='$a' value='$v' >";
					if(is_array($schema['values'])) {
						foreach($schema['values'] as $a=>$b) {
							$s.="<option value='$a'>$b</option>";
						}
					} else {
						$schema['values']=explode(",", $schema['values']);
						foreach($schema['values'] as $a=>$b) {
							$s.="<option value='$b'>$b</option>";
						}
					}
					$s.="</select>";
				} else {
					$s="<input type=text class='textfield' name='$a' value='$v' >";
				}
				return $s;
			case 'text':
			default:
				return "<input type=text class='textfield' name='$a' value='$v' >";
		}
	}
	function printDashletContent($type,$src,$dPath,$cfg=array(),$style="") {
		$dashDir=dirname($dPath)."/";
		$dName=substr(basename($dPath), 0,strlen(basename($dPath))-5);
		$fp=dirname(__FILE__)."/dTypes/{$type}.php";
		if(file_exists($fp)) include $fp;
		else return false;
		return true;
	}
	function getLink($src) {
		if(substr($src, 0,7)=="http://" || substr($src, 0,8)=="https://" ||
				substr($src, 0,6)=="ftp://") {
			return $src;
		} elseif(substr($src, 0,1)=="?") {
			return SiteLocation.$src;
		} elseif(substr($src, 0,1)=="@") {
			return _link(substr($src, 1));
		} elseif(substr($src, 0,1)=="#") {
			return _service(substr($src, 1));
		}
		return $src;
	}
}
?>
