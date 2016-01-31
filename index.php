<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

$dashboardConfig=getUserConfig("dashboard");

if(!isset($dashboardConfig['order']) || count($dashboardConfig['order'])<=0) {
	$dashboardConfig['order']=array_keys($dashboardConfig['dashlets']);
}
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
			$dashlet=array_merge(getDefaultDashletConfig(),$dashboardConfig['dashlets'][$dashkey]);
		?>
			<div data-dashkey='<?=$dashkey?>' class='dashletContainer col-xs-12 col-sm-12 col-md-<?=$dashlet['column']?> col-lg-<?=$dashlet['column']?> <?=$dashlet['forcenewrow']?"clear-left":''?> <?=$dashlet['containerClass']?>'>
				<div class="dashletPanel <?=$dashlet['active']?"active":''?> panel panel-default ajaxloading ajaxloading8">
					<?php if($dashlet['header']===true) { ?>
					<div class="panel-heading">
						<div class="dashletOption dashletHandle glyphicon <?=$dashlet['active']?"glyphicon-triangle-top":'glyphicon-triangle-bottom'?> pull-left"></div>

						<div class="dashletOption dashletRemove glyphicon glyphicon-remove pull-right" cmd='remove'></div>
						<div class="dashletOption dashletSettings glyphicon glyphicon-cog pull-right" cmd='settings'></div>
						<div class="dashletOption dashletFocus glyphicon glyphicon-eye-open pull-right" cmd='focus'></div>

						<h3 class="panel-title"><?=$dashlet['title']?></h3>
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
					
					<div class="panel-body">
					<?php 
						if(!isset($dashlet['config'])) $dashlet['config']=[];
						echo getDashletBody($dashlet['type'],$dashlet['source'],$dashlet['config']);
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
						<tr> <th>Force New Row</th> <td><select name='forcenewrow' data-value='<?=$dashlet['forcenewrow']?>' class='form-control'>
							<?php
								if($dashlet['forcenewrow']) {
									echo "<option value='true' selected>Yes</option><option value='false'>No</option>";
								} else {
									echo "<option value='true'>Yes</option><option value='false' selected>No</option>";
								}
							?>
						</select></td> </tr>
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
	?>
</div>
