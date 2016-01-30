<?php
if(!defined('ROOT')) exit('No direct script access allowed');
//session_check();

if(!isset($_REQUEST["action"])) {
	$_REQUEST["action"]="";
}

//include_once "api.php";

switch($_REQUEST["action"]) {
	case "fetchGrid":
		//printArray($_POST);
	break;
	default:
		trigger_error("Action Not Defined or Not Supported");
}

?>
