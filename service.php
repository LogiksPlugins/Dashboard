<?php
if(!defined('ROOT')) exit('No direct script access allowed');
checkServiceSession();

include_once __DIR__."/api.php";

$dashboardConfig=getUserConfig("dashboard-".SITENAME);

switch($_REQUEST["action"]) {
  case "dashletData":
    if(isset($_POST['dashkey'])) {
      $dashkey=$_POST['dashkey'];
			unset($_POST['dashkey']);
			if(isset($_SESSION['DASHDATA'][$dashkey])) {
        $src=$_SESSION['DASHDATA'][$dashkey];
        $srcData=processDataQuery($src);
        
        printServiceMSG($srcData);
      }else {
				trigger_error("Dashkey not in use. {$dashkey}");
			}
    } else {
			trigger_error("Dashkey not found.");
		}
    break;
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
				setUserConfig("dashboard-".SITENAME,$dashboardConfig);

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
				//setUserConfig("dashboard-".SITENAME,$dashboardConfig);

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

			if(count($dashboardConfig['order'])==1 && strlen($dashboardConfig['order'][0])<=0) {
				$dashboardConfig['order']=[];
			}

			foreach ($dashboardConfig['dashlets'] as $dashkey => $dashlet) {
				if(!in_array($dashkey, $dashboardConfig['order'])) {
					unset($dashboardConfig['dashlets'][$dashkey]);
				}
			}

			setUserConfig("dashboard-".SITENAME,$dashboardConfig);

			printServiceMSG(array("status"=>"success","msg"=>"Successfully reordered dashboard."));
		} else {
			trigger_error("New Order not found.");
		}
	break;

	case "listDashlets":
		$dashlets=listDashlets();
		foreach($dashlets as $a=>$b) {
			unset($dashlets[$a]['source']);
		}
		printServiceMSG($dashlets);
	break;

	case "relistDashlets":
		$dashlets=listDashlets(true);
		foreach($dashlets as $a=>$b) {
			unset($dashlets[$a]['source']);
		}
		printServiceMSG($dashlets);
	break;

	case "dashletInfo":
		if(isset($_REQUEST['d'])) {
			$dashlets=listDashlets();
			if(array_key_exists($_REQUEST['d'],$dashlets)) {
				$cfg=$dashlets[$_REQUEST['d']];
				$src=$cfg['source'];
				$json=json_decode(file_get_contents($src),true);

				$baseDir=dirname($src);
				$screenshots=[
						"{$baseDir}/{$_REQUEST['d']}.png",
						"{$baseDir}/{$_REQUEST['d']}.jpg",
						"{$baseDir}/{$_REQUEST['d']}.jpeg",
						"{$baseDir}/{$_REQUEST['d']}.gif",
					];

				$html="<div class='infobox'>";
				$html.="<h4>{$json['title']}</h4>";

				if(isset($json['author']['name'])) {
					if(isset($json['author']['email'])) {
						$html.="<citie>{$json['author']['name']} [{$json['author']['email']}]</citie>";
					} else {
						$html.="<citie>{$json['author']['name']}</citie>";
					}
				}
				
				$html.="<p>{$json['descs']}</p>";

				$imgs=[];
				foreach ($screenshots as $f) {
					if(file_exists($f)) {
						$imgs[]=str_replace(ROOT, SiteLocation, $f);
					}
				}
				if(count($imgs)>0) {
					$html.="<hr><div>";
					$html.="<img src='{$imgs[0]}' class='img-responsive' />";
					$html.="</div>";
				}
				$html.="</div>";

				echo $html;
			} else {
				echo "Dashlet Not Found";
			}
		} else {
			echo "Dashlet Not Defined";
		}
	break;
	case "addDashlets":
		if(isset($_REQUEST['d'])) {
			$dxs=explode(",", $_REQUEST['d']);
			$dashlets=listDashlets();

			foreach ($dxs as $d) {
				if(array_key_exists($d,$dashlets)) {
					$cfg=$dashlets[$d];
					$src=$cfg['source'];
					$json=json_decode(file_get_contents($src),true);

					$dashkey=md5(basename($src).time());

					$json['dashid']=$d;

					$dashboardConfig['order'][]=$dashkey;
					$dashboardConfig['dashlets'][$dashkey]=$json;
				}
			}
			setUserConfig("dashboard-".SITENAME,$dashboardConfig);
			printServiceMSG(['status'=>"success","new"=>$dxs]);
		} else {
			trigger_error("Dashkey not found.");
		}
	break;	

	default:
		trigger_error("Action Not Defined or Not Supported");
}

function processDataQuery($source) {
  if(!isset($source['type'])) {
		trigger_error("Corrupt Data Configuration");
	}
  
  if(isset($source['dbkey'])) {
		$dbKey=$source['dbkey'];
	} else {
		$dbKey="app";
	}
  
  if(!isset($source['limit'])) $source['limit']=100;
  if(!isset($source['index'])) $source['index']=0;
  
  $data=[];$meta=[];
	switch ($source['type']) {
      case 'sql':
        $sql=QueryBuilder::fromArray($source,_db($dbKey));
        if(isset($source['DEBUG']) && $source['DEBUG']==true) {
          exit($sql->_SQL());
        }
        $data=$sql->_GET();
        $meta=[
          "index"=>$source['index'],
          "limit"=>$source['limit'],
          "count"=>count($data),
        ];
      break;
      case 'php':
        if(isset($source['file'])) {
          $file=APPROOT.$source['file'];
          if(file_exists($file) && is_file($file)) {
            $data=include_once($file);
          }
        }
      break;
      case 'jsonuri':
      if(isset($source['uri'])) {
        $data=file_get_contents($source['uri']);
        $data=json_decode($data,true);
      }
      break;
      case 'xmluri':
      break;
  }
  
  return ["DATA"=>$data,"META"=>$meta];
}

?>
