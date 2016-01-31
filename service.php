<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($_REQUEST["action"])) {
	$_REQUEST["action"]="";
}

include_once "api.php";

$dashboardConfig=getUserConfig("dashboard");

switch($_REQUEST["action"]) {
	case "saveDashletState":
		if(isset($_POST['dashkey'])) {
			$dashkey=$_POST['dashkey'];
			unset($_POST['dashkey']);
			if(isset($dashboardConfig['dashlets'][$dashkey])) {
				$config=$dashboardConfig['dashlets'][$dashkey];

				foreach ($_POST as $key => $value) {
					if(isset($config['config'][$key])) {
						$config['config'][$key]=$value;
					} else {
						$config[$key]=$value;
					}
				}

				$dashboardConfig['dashlets'][$dashkey]=$config;

				//printArray($dashboardConfig['dashlets'][$dashkey]);
				setUserConfig("dashboard",$dashboardConfig);

				printServiceMSG(array("status"=>"success","msg"=>"Successfully saved dashlet."));
			} else {
				trigger_error("Dashkey not in use.");
			}
		} else {
			trigger_error("Dashkey not found.");
		}
	break;

	case "resetDashlet":
		if(isset($_POST['dashkey'])) {
			$dashkey=$_POST['dashkey'];
			unset($_POST['dashkey']);
			if(isset($dashboardConfig['dashlets'][$dashkey])) {
				$config=$dashboardConfig['dashlets'][$dashkey];

				

				//$dashboardConfig['dashlets'][$dashkey]=$config;

				//printArray($dashboardConfig['dashlets'][$dashkey]);
				//setUserConfig("dashboard",$dashboardConfig);

				printServiceMSG(array("status"=>"success","msg"=>"Successfully saved dashlet."));
			} else {
				trigger_error("Dashkey not in use.");
			}
		} else {
			trigger_error("Dashkey not found.");
		}
	break;

	case "saveDashletOrder":
		if(isset($_POST['neworder'])) {
			$dashboardConfig['order']=explode(",", $_POST['neworder']);

			foreach ($dashboardConfig['dashlets'] as $dashkey => $dashlet) {
				if(!in_array($dashkey, $dashboardConfig['order'])) {
					$dashboardConfig['dashlets'][$dashkey];
				}
			}

			setUserConfig("dashboard",$dashboardConfig);

			printServiceMSG(array("status"=>"success","msg"=>"Successfully reordered dashboard."));
		} else {
			trigger_error("New Order not found.");
		}
	break;

	default:
		trigger_error("Action Not Defined or Not Supported");
}

?>
