<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("getDefaultDashletConfig")) {

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
				"config"=>[],
				"schema"=>[],
				"column"=>6,
				"forcenewrow"=>false,
				"header"=>true,
				"footer"=>false,
				"active"=>true,
				"containerClass"=>""
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

		switch ($config['type']) {
			case 'select':
				$html.="<select name='$key' data-value='{$config['value']}' class='form-control'>";
				if(isset($config['options'])) {
					if(!is_array($config['options'])) {
						$config['options']=explode(",", $config['options']);
						foreach ($config['options'] as $key => $value) {
							if($value==$config['value'])
								$html.="<option value='$value' selected>"._ling($value)."</option>";
							else
								$html.="<option value='$value'>"._ling($value)."</option>";
						}
					} else {
						foreach ($config['options'] as $key => $value) {
							if($key==$config['value'])
								$html.="<option value='$key' selected>"._ling($value)."</option>";
							else
								$html.="<option value='$key'>"._ling($value)."</option>";
						}
					}
				}
				$html.="</select>";
				break;
			case 'dataSelector':
				$html.="<select name='$key' data-value='{$config['value']}' class='form-control'>";
				$html.=createDataSelector($config['options']);
				$html.="</select>";
				break;
			default:
				$html.="<input name='$key' type='text' class='form-control' data-value='{$config['value']}' value='{$config['value']}' />";
				break;
		}

		return $html;
	}

	function getDashletBody($type, $source, $dashletConfig=[]) {
		//echo "$type $source";
		switch ($type) {
			case 'widget':
				loadWidget($source,$dashletConfig);
				break;
			
			case 'module':
				loadModule($source);
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
				if(file_exists(APPROOT.$source)) {
					include APPROOT.$source;
				} else {
					echo "<h3 align=center>Dashlet Source Not Found</h3>";
				}
				break;

			default:
				echo "<h3 align=center>Dashlet '$type' Not Supported</h3>";
				break;
		}
	}

	function findDashlets($recache=false) {
		$searchFS=array(
			);
	}
}
?>