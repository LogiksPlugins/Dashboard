<?php
if(!defined('ROOT')) exit('No direct script access allowed');
checkServiceSession();

if(isset($_REQUEST["action"])) {
	$dashCfg=loadFeature("dashboard");
	switch($_REQUEST["action"]) {
		case "servermsgs":
			fetchServerMsgs($dashCfg['MESSAGE_SOURCE']);
			break;
		case "dashletSelector":
			include "dashletSelector.php";
			break;
		case "loadDashlet":
			if(isset($_REQUEST["dashlet"])) {
				include "dashlets.php";

				$forceParams=initForceParams($dashCfg);
				createDashlet($_REQUEST["dashlet"],$forceParams);
			}
			break;
		case "script":
			if(isset($_REQUEST["src"])) {
				$src=base64_decode($_REQUEST["src"]);
				echo "<div style='width:100%;height:100%;overflow:hidden;'>
						<script src=\"{$src}\">
						</script>
					</div>";
			}
			break;
		case "save":
			$userCfg=getDashboardSettings();
			foreach ($_POST as $key => $value) {
				$userCfg[$key]=$value;
			}
			saveUserSettings($userCfg);
			break;
		case "saveConfig":
			if(isset($_REQUEST["dashlet"])) {
				$userCfg=getDashboardSettings();
				if(!isset($userCfg['CONFIGS'][$_REQUEST["dashlet"]])) $userCfg['CONFIGS'][$_REQUEST["dashlet"]]=array();
				foreach ($_POST as $key => $value) {
					$userCfg['CONFIGS'][$_REQUEST["dashlet"]][$key]=$value;
					$_SESSION['DASHBOARD']['CONFIGS'][$_REQUEST["dashlet"]][$key]=$value;
				}
				saveUserSettings($userCfg);
			}
			break;
		case "saveParam":
			if(isset($_REQUEST["dashlet"])) {
				$userCfg=getDashboardSettings();
				if(!isset($userCfg['PARAMS'][$_REQUEST["dashlet"]])) $userCfg['PARAMS'][$_REQUEST["dashlet"]]=array();
				foreach ($_POST as $key => $value) {
					if($value=="true") $value=true;
					elseif($value=="false") $value=false;
					$userCfg['PARAMS'][$_REQUEST["dashlet"]][$key]=$value;
					$_SESSION['DASHBOARD']['PARAMS'][$_REQUEST["dashlet"]][$key]=$value;
				}
				saveUserSettings($userCfg);
			}
			break;
		case "addDashlet":
			if(isset($_REQUEST["dashlet"])) {
				$userCfg=getDashboardSettings();

				$userCfg['DASHLETS']=explode(",", $userCfg['DASHLETS']);
				if(!in_array($_REQUEST["dashlet"], $userCfg['DASHLETS'])) {
					array_push($userCfg['DASHLETS'], $_REQUEST["dashlet"]);
				}
				$userCfg['DASHLETS']=implode(",", $userCfg['DASHLETS']);
				$_SESSION['DASHBOARD']['DASHLETS']=$userCfg['DASHLETS'];

				saveUserSettings($userCfg);
			}
			break;
	}
}
function getDashboardSettings() {
	if(!isset($_SESSION['DASHBOARD'])) {
		$userCfg=getSettings("Dashboard Configuration",json_encode($userCfg),"system");
		$userCfg=stripslashes($userCfg);
		if(strlen($userCfg)>2) $userCfg=json_decode($userCfg,true);
		else $userCfg=array();
		$_SESSION['DASHBOARD']=$userCfg;
	}
	return $_SESSION['DASHBOARD'];
}
function fetchServerMsgs($src,$limit=1) {
	$site=SITENAME;
	$user=$_SESSION["SESS_USER_ID"];
	$date=date("Y-m-d");
	if(isset($_REQUEST["index"])) $index=$_REQUEST["index"]; else $index=0;

	if($src==null || strlen($src)<=0) {
		$q="SELECT msgtxt,by_site,by_user,dated FROM "._dbtable("server_msgs",true)." WHERE ";
		$q.="(for_site='$site' OR for_site='*') ";
		$q.="AND (for_user='$user' OR for_user='*') ";
		$q.="AND (by_user<>'$user') ";
		$q.="AND (till_date>='$date') ";
		$q.="AND (viewable='true') ";
		$q.="AND (obsolate='false') ";
		$msgTemplate="%s <div align=right>[<b>%s</b>::<span style='color:blue'>%s</span>]</div>";
	} else {
		$q="";
		$msgTemplate="%s <div align=right>[<b>%s</b>::<span style='color:blue'>%s</span>]</div>";
	}
	if(strlen($q)<=0) {
		echo "Server Messages Not Configured";
		return;
	}
	$q.="limit $index,$limit";

	$r=_dbQuery($q,true);
	if($r) {
		$data = _dbData($r);
		if(count($data)==0) {
			echo "0 Messages Found For You";
		} else {
			$msg=sprintf($msgTemplate,$data[0]["msgtxt"],ucwords($data[0]["by_user"]),$data[0]["dated"]);
			echo $msg;
		}
		_db()->freeResult($r);
	} else {
		echo "No More Server Messages";
	}
}
function initForceParams($dashCfg) {
	$forceParams=array();
	if(isset($dashCfg['FORCE_SPAN']) && strlen($dashCfg['FORCE_SPAN'])>0)
			$forceParams["spanClass"]=$dashCfg['FORCE_SPAN'];
	if(isset($dashCfg['FORCE_STYLE']) && strlen($dashCfg['FORCE_STYLE'])>0)
		$forceParams["style"]=$dashCfg['FORCE_STYLE'];
	if(isset($dashCfg['FORCE_STYLECONTENT']) && strlen($dashCfg['FORCE_STYLECONTENT'])>0)
		$forceParams["styleContent"]=$dashCfg['FORCE_STYLECONTENT'];
	if(isset($dashCfg['FORCE_OPEN']) && strlen($dashCfg['FORCE_OPEN'])>0)
		$forceParams["autoOpen"]=(strtolower($dashCfg['FORCE_OPEN'])=="true")?true:false;
	if(isset($dashCfg['FORCE_NOHEADER']) && strlen($dashCfg['FORCE_NOHEADER'])>0)
		$forceParams["noheader"]=(strtolower($dashCfg['FORCE_NOHEADER'])=="true")?true:false;

	return $forceParams;
}
function saveUserSettings($userCfg) {
	foreach($userCfg as $a=>$b) {
		if(substr($a, 0,5)=="FORCE" || substr($a, 0,3)=="CSS" || substr($a, 0,3)=="SYS" || substr($a, 0,7)=="DEFAULT")
			unset($userCfg[$a]);
	}
	setSettings("Dashboard Configuration",json_encode($userCfg),"system");
}
?>

