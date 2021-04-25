<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("getDefaultDashletConfig")) {

	function generateDashletID($dashkey, $dboard) {
		return md5(session_id().time().$_SERVER['REMOTE_ADDR'].$dashkey.$dboard);
	}

	function findDashboardFile($dashkey) {
		$dashFile = false;
		if(!$dashkey) {
			$dashkey = "default";
		}
		$dashkey = str_replace(".","/",$dashkey);
		$dashFile = false;
		
		if(is_file($dashkey.".json") && file_exists($dashkey.".json")) {
			return $dashkey.".json";
		}

		if(isset($_SESSION['SESS_PRIVILEGE_NAME']) && file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}/{$dashkey}.json")) {
			$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}/{$dashkey}.json";
		} elseif(file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/commons/{$dashkey}.json")) {
			$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/commons/{$dashkey}.json";
		} elseif(file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}.json")) {
			$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/{$_SESSION['SESS_PRIVILEGE_NAME']}.json";
		} elseif(file_exists(APPROOT.APPS_MISC_FOLDER."dashboards/{$dashkey}.json")) {
			$dashFile = APPROOT.APPS_MISC_FOLDER."dashboards/{$dashkey}.json";
		} else {
			// echo "<h1 align=center>"._ling("Sorry, Dashboard not configured yet")."</h1>";
			return false;
		}

		if($dashFile && is_file($dashFile)) {
			return $dashFile;
		} else {
			return false;
		}
	}

	function processDashboardConfig($dashboardConfig = []) {
		if($dashboardConfig==null || !is_array($dashboardConfig)) $dashboardConfig = [];
		
		//Initiating default variables
		if(!isset($dashboardConfig['params'])) $dashboardConfig['params'] = [];
		if(!isset($dashboardConfig['order'])) $dashboardConfig['order'] = [];
		if(!isset($dashboardConfig['dashlets'])) $dashboardConfig['dashlets'] = [];

		if(!isset($dashboardConfig['preload'])) {
			$dashboardConfig['preload']=["module"=>[],"css"=>[],"js"=>[]];
		}
		if(!isset($dashboardConfig['preload']['css'])) {
			$dashboardConfig['preload']['css'] = [];
		}
		if(!isset($dashboardConfig['preload']['js'])) {
			$dashboardConfig['preload']['js'] = [];
		}
		if(!isset($dashboardConfig['preload']['module'])) {
			$dashboardConfig['preload']['module'] = [];
		}

		//Populating default variables
		$dashboardConfig['params'] = array_merge([
				"background"=> "",
				"force_style"=> "",
				"force_span"=> "",
				"force_open"=> "",
				//"allow_server_messages"=> true,
				"allow_closing"=> false,
				"allow_dnd"=>true,
				"allow_controller"=> true,
				"allow_reset"=> true,
				"allow_edit"=> true,
				"dashlet_allow_minimize"=>true,
				"dashlet_allow_focus"=>true,
				"dashlet_allow_closing"=> true,
				"dashlet_allow_configure"=> true,
				"dashlet_header"=>true
			], $dashboardConfig['params']);

		if(!isset($dashboardConfig['order']) || count($dashboardConfig['order'])<=0 || 
			(count($dashboardConfig['order'])==1 && strlen($dashboardConfig['order'][0])<=0)) {
			$dashboardConfig['order']=array_keys($dashboardConfig['dashlets']);
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

		return $dashboardConfig;
	}
	
	function setDashData($dataSource) {
	    $key=md5(time().rand(0,100000));
	    $_SESSION['DASHDATA'][$key]=$dataSource;
	    return $key;
	}
  	function getDashData($key) {
	    if(isset($_SESSION['DASHDATA']) && isset($_SESSION['DASHDATA'][$key])) {
	      return $_SESSION['DASHDATA'][$key];
	    }
	    return false;
  	}
	function getDefaultDashletConfig() {
		return [
				"title"=>"",
				"descs"=>"",
				"photo"=>"",
				"author"=>false,
				"market"=>[
					"mid"=>false,
					"dependency"=>false,
				],
				"preload"=>[
					"module"=>[],
					"css"=>[],
					"js"=>[],
				],
				"type"=>false,
				"source"=>false,
				
				"config"=>[],				//Editable fields
				"schema"=>[],				//Form schema

				"column"=>6,
				"column-lg"=>"",
				"forcenewrow"=>false,
				"header"=>true,
				"footer"=>false,
				"active"=>true,
				"containerClass"=>"",

				// "autoOpen"=> true,
				// "style"=> "",
				// "styleContent"=> "text-align:center%3B",
			];
	}

	function getDashConfigEditor($key,$config) {
		if(!is_array($config)) {
			$config=[
					"type"=>"text",
					"value"=>$config
				];
		}
		if(!isset($config['type'])) $config['type']="";
		if(!isset($config['value'])) $config['value']="";

		$html="";
		
		loadModuleLib("forms", "api");

		$config['fieldkey'] = $key;
		$data = [];
		$data[$key] = $config['value'];
		$html.=getFormField($config, $data, "app");
		return $html;

		// switch ($config['type']) {
		// 	case 'select':
		// 		$html.="<select name='$key' data-value='{$config['value']}' class='form-control'>";
		// 		if(isset($config['options'])) {
		// 			if(!is_array($config['options'])) {
		// 				$config['options']=explode(",", $config['options']);
		// 				foreach ($config['options'] as $key => $value) {
		// 					if($value==$config['value'])
		// 						$html.="<option value='$value' selected>"._ling($value)."</option>";
		// 					else
		// 						$html.="<option value='$value'>"._ling($value)."</option>";
		// 				}
		// 			} else {
		// 				foreach ($config['options'] as $key => $value) {
		// 					if($key==$config['value'])
		// 						$html.="<option value='$key' selected>"._ling($value)."</option>";
		// 					else
		// 						$html.="<option value='$key'>"._ling($value)."</option>";
		// 				}
		// 			}
		// 		}
		// 		$html.="</select>";
		// 		break;
		// 	case 'dataSelector':
		// 		$html.="<select name='$key' data-value='{$config['value']}' class='form-control'>";
		// 		$html.=createDataSelector($config['options']);
		// 		$html.="</select>";
		// 		break;
		// 	case 'dataSelectorFromTable':
		// 		$html.="<select name='$key' data-value='{$config['value']}' class='form-control'>";
		// 		$html.=generateSelectOptions($config, $key, "app");
		// 		$html.="</select>";
		// 		break;
		// 	case 'dataSelectorMethod':
		// 		$html.="<select name='$key' data-value='{$config['value']}' class='form-control'>";
		// 		if(isset($config['method'])) {
		// 			$html.=call_user_func($config['method'], $config);
		// 		}
		// 		$html.="</select>";
		// 		break;
		// 	default:
		// 		$html.="<input name='$key' type='text' class='form-control' data-value='{$config['value']}' value='{$config['value']}' />";
		// 		break;
		// }

		// return $html;
	}

	function printDashlet($dashkey, $dashletConfig, $dashboardConfig) {
		$dashlet=array_merge(getDefaultDashletConfig(),$dashletConfig);
		if(!checkUserRoles("DASHBOARD","Dashlets",$dashlet['source'])) {
			return false;
		}
		if(!isset($dashlet['column-lg']) || strlen($dashlet['column-lg'])<=0) {
			$dashlet['column-lg'] = $dashlet['column'];
		}
		$spanClass = "col-xs-12 col-sm-12 col-md-{$dashlet['column']} col-lg-{$dashlet['column-lg']}";
		if(isset($dashboardConfig['params']['force_span']) && strlen($dashboardConfig['params']['force_span'])>0) {
			$spanClass = $dashboardConfig['params']['force_span'];
		}
		$dashletConfig['dashletid'] = $dashkey;
		?>
			<div data-dashkey='<?=$dashkey?>' class='dashletContainer <?=$spanClass?> <?=$dashlet['forcenewrow']?"clear-left":''?> <?=$dashlet['containerClass']?>' data-dashid='<?=$dashlet['dashid']?>'>
				<div class="dashletPanel <?=$dashlet['active']?"active":''?> panel panel-default ajaxloading ajaxloading8">
					<?php if($dashboardConfig['params']['dashlet_header'] && $dashlet['header']) { ?>
					<?php if($dashlet['header']===true) { ?>
					<div class="panel-heading">
						<?php if($dashboardConfig['params']['dashlet_allow_closing']) { ?>
						<div class="dashletOption dashletRemove glyphicon glyphicon-remove pull-right" cmd='remove'></div>
						<?php } ?>
						<?php if($dashboardConfig['params']['dashlet_allow_configure']) { ?>
						<div class="dashletOption dashletSettings glyphicon glyphicon-cog pull-right" cmd='settings'></div>
						<?php } ?>
						<?php if($dashboardConfig['params']['dashlet_allow_focus']) { ?>
						<div class="dashletOption dashletFocus glyphicon glyphicon-eye-open pull-right" cmd='focus'></div>
						<?php } ?>
						<div class="dashletOption dashletRefresh glyphicon glyphicon-refresh pull-right" cmd='refresh'></div>
						<?php if($dashboardConfig['params']['dashlet_allow_minimize']) { ?>
						<div class="dashletOption dashletHandle glyphicon <?=$dashlet['active']?"glyphicon-triangle-top":'glyphicon-triangle-bottom'?> pull-left"></div>
						<h3 class="panel-title"><?=_ling($dashlet['title'])?></h3>
						<?php } else { ?>
						<h3 class="panel-title panel-title-nominimize"><?=_ling($dashlet['title'])?></h3>
						<?php } ?>
					</div>
					<?php 
						} elseif(is_file(APPROOT.$dashlet['header'])) { 
							echo '<div class="panel-heading">';
							include_once APPROOT.$dashlet['header'];
							echo '</div>';
						} elseif(is_file($dashlet['header'])) { 
							echo '<div class="panel-heading">';
							include_once $dashlet['header'];
							echo '</div>';
						} 
					?>
					<?php } ?>
					<div class="panel-body">
					<?php 
						if(!isset($dashlet['config'])) $dashlet['config']=[];
						echo getDashletBody($dashlet['type'],$dashlet['source'],$dashlet['config'],$dashlet);
					?>
					</div>
					
					<table class="panel-options table"> <tbody>
						<tr> <th>Width (1-10)</th> <td><select name='column' data-value='<?=$dashlet['column']?>' class='form-control'>
							<?php
								for ($col=1; $col <= 12; $col++) {
									$colText="Columns";
									if($col==1) $colText="Column";
									if($col==$dashlet['column']) {
										echo "<option value='$col' selected>$col $colText</option>";
									} else {
										echo "<option value='$col'>$col $colText</option>";
									}
								}
							?>
						</select></td> </tr>
						<tr> <th><?=_ling("Force New Row")?></th> <td><select name='forcenewrow' data-value='<?=$dashlet['forcenewrow']?>' class='form-control'>
							<?php
								if($dashlet['forcenewrow']) {
									echo "<option value='true' selected>"._ling("Yes")."</option><option value='false'>"._ling("No")."</option>";
								} else {
									echo "<option value='true'>"._ling("Yes")."</option><option value='false' selected>"._ling("No")."</option>";
								}
							?>
						</select></td> </tr>
						<tr> <th>
							Dashlet Title
						</th><td>
							<?=getDashConfigEditor("title",[
								"title"=>"Dashlet Title",
								"value"=>$dashlet['title']
							]);?>
						</td> </tr>
						<?php
							if(!isset($dashlet['schema'])) $dashlet['schema']=[];
							foreach ($dashlet['config'] as $key => $value) {
								if(isset($dashlet['schema'][$key])) {
									$config=$dashlet['schema'][$key];
								} else {
									$config=[];
								}
								$title=$key;
								if(isset($config['title'])) $title=$config['title'];
								else $title=toTitle(_ling($title));

								$config['value']=$value;
								$config['title']=$title;

								echo "<tr><th>$title</th><td>";
								echo getDashConfigEditor($key,$config);
								echo "</td></tr>";
							}
						?>
					</tbody> </table>

					<?php 
						if($dashlet['footer']) {
							if(is_file(APPROOT.$dashlet['footer'])) { 
								echo '<div class="panel-footer">';
								include_once APPROOT.$dashlet['footer'];
								echo '</div>';
							} elseif(is_file($dashlet['footer'])) { 
								echo '<div class="panel-footer">';
								include_once $dashlet['footer'];
								echo '</div>';
							} elseif(strlen($dashlet['footer'])>2) {
								echo '<div class="panel-footer">';
								echo $dashlet['footer'];
								echo '</div>';
							}
						}
					?>
				</div>
			</div>
		<?php
	}

	function getDashletBody($type, $source, $dashletConfig=[], $dashlet = []) {
		// echo "$type $source";
		switch ($type) {
			case 'widget':
				loadWidget($source,$dashletConfig);
				break;
			
			case 'module':
				loadModuleLib($source,"dashlet",$dashletConfig);
				break;

			case 'iframe':
				if(!(substr($source, 0,7)=="http://" || substr($source, 0,8)=="https://")) {
					$source=_link($source);
				}
				echo "<iframe src='{$source}' allowTransparency='true' frameborder='0' scrolling='auto'></iframe>";
				break;

			case 'script':
				echo "<script src=\"{$source}\"></script>";
				break;

			case 'php':
				if(isset($dashlet['folder']) && strlen($dashlet['folder'])>1) {
					$source = "{$dashlet['folder']}/$source";
				}
				if(file_exists(APPROOT.APPS_PLUGINS_FOLDER."dashlets/{$source}.php")) {
					include APPROOT.APPS_PLUGINS_FOLDER."dashlets/{$source}.php";
				} elseif(file_exists(APPROOT."pluginsDev/dashlets/{$source}.php")) {
					include APPROOT."pluginsDev/dashlets/{$source}.php";
				} else {
					echo "<h3 align=center>Dashlet Source Not Found</h3>";
				}
				break;

			default:
				echo "<h3 align=center>Dashlet '$type' Not Supported</h3>";
				break;
		}
	}

	function listDashlets($recache=false,$flatList=false) {
		$paths=getLoaderFolders('pluginPaths',"dashlets");
		$currentDashlets=getUserConfig("dashboard-".SITENAME)['dashlets'];

		$loadedDashlets=[];
		foreach ($currentDashlets as $dash) {
			if(isset($dash['dashid'])) {
				if(isset($loadedDashlets[$dash['dashid']])) $loadedDashlets[$dash['dashid']]++;
				else $loadedDashlets[$dash['dashid']]=1;
			}
		};

		$dashlets=[];
		foreach ($paths as $dir) {
			$dir=ROOT.$dir;
			if(!is_dir($dir)) continue;

			$fs=scandir($dir);
			$fs=array_splice($fs,2);
			foreach ($fs as $fx) {
				if(substr($fx,0,1)=="~") continue;
				$file=$dir.$fx;
				$extension = pathinfo($file, PATHINFO_EXTENSION);
				if($extension=="json") {
					$dKey=str_replace(".json", '', $fx);
					$dashConfig=[
								"title"=>_ling(toTitle($dKey)),
								"category"=>_ling("General"),
								"source"=>$file,
								"active"=>0,
							];
					if(array_key_exists($dKey, $loadedDashlets)) {
						$dashConfig['active']=$loadedDashlets[$dKey];
					}
					if(checkUserRoles("DASHBOARD","Dashlets",$dKey)) {
						$dashlets[$dKey]=$dashConfig;
					}
				} elseif(is_dir($file)) {
					$fs1=scandir($file);
					$fs1=array_splice($fs1,2);
					foreach ($fs1 as $fx1) {
						if(substr($fx1,0,1)=="~") continue;
						$file1=$file."/".$fx1;
						$extension = pathinfo($file1, PATHINFO_EXTENSION);
						if($extension=="json") {
							$dKey=basename($file)."/".str_replace(".json", '', $fx1);
							$title = explode("/", $dKey);
							$dashConfig=[
										"title"=>_ling(toTitle(end($title))),
										"category"=>_ling(toTitle(basename($file))),
										"source"=>$file1,
										"active"=>0,
									];
							if(array_key_exists($dKey, $loadedDashlets)) {
								$dashConfig['active']=$loadedDashlets[$dKey];
							}
							if(checkUserRoles("DASHBOARD","Dashlets",$dKey)) {
								if($flatList) {
									$dashlets[$dKey]=$dashConfig;
								} else {
									$dashlets[basename($file)][$dKey]=$dashConfig;
								}
							}
						}
					}
				}
			}
		}
		return $dashlets;
	}
}
?>