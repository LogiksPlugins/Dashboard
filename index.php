<?php
if(!defined('ROOT')) exit('No direct script access allowed');

_js(array("jquery.cookie","dialogs"));

//Initialize System And User Configuration
$dashCfg=loadFeature("dashboard",true);
$userCfg=array(
		"DASHLETS"=>$dashCfg['DEFAULT_DASHLETS'],
		"BACKGROUND"=>$dashCfg['BACKGROUND'],
		"LAYOUT"=>$dashCfg['LAYOUT'],
		"ALLOW_SERVER_MESSAGES"=>$dashCfg['ALLOW_SERVER_MESSAGES'],
		"CONFIGS"=>array(),
		"PARAMS"=>array(),
	);
$userCfg=getSettings("Dashboard Configuration",json_encode($userCfg),"system");
$userCfg=stripslashes($userCfg);
if(strlen($userCfg)>2) $userCfg=json_decode($userCfg,true);
else $userCfg=array();

foreach($userCfg as $a=>$b) {
	if(substr($a, 0,5)=="FORCE" || substr($a, 0,3)=="CSS" || substr($a, 0,3)=="SYS" || substr($a, 0,7)=="DEFAULT" || substr($a, 0,17)=="AUTOLOAD_MODULES") continue;
	$dashCfg[$a]=$b;
}
$_SESSION['DASHBOARD']=$dashCfg;
//printArray($dashCfg);exit();

//Variable Configuration
$layout=explode(",", $dashCfg['LAYOUT']);
$dashlets=$dashCfg['DASHLETS'];
$webPath=getWebPath(__FILE__);

//Background Configuration
if(isset($dashCfg['FORCE_BACKGROUND']) && strlen($dashCfg['FORCE_BACKGROUND'])>0)
	$dashCfg['BACKGROUND']=$dashCfg['FORCE_BACKGROUND'];
if(strlen($dashCfg['BACKGROUND'])) {
	if(substr($dashCfg['BACKGROUND'], 0,7)!="http://" ||
		substr($dashCfg['BACKGROUND'], 0,8)!="https://") {
		$dashCfg['BACKGROUND']=loadMedia($dashCfg['BACKGROUND']);
	}
}
if(strlen($dashCfg['BACKGROUND'])>0) {
	echo "<style>#dashboard {background:url({$dashCfg['BACKGROUND']}) repeat left top;}</style>";
}
?>
<link href='<?=$webPath?>css/dashboard.css' rel='stylesheet' type='text/css' media='all' />
<link href='<?=$webPath?>css/layout1.css' rel='stylesheet' type='text/css' media='all' />
<script src='<?=$webPath?>dashboard.js' type='text/javascript' language='javascript'></script>
<?php
if(isset($dashCfg['AUTOLOAD_MODULES'])) {
	$modules=explode(",",$dashCfg['AUTOLOAD_MODULES']);
	foreach ($modules as $m) {
		if(substr($m, 0,1)!="~") {
			loadModules($m);
		}
	}
}
_css(array("dashboard"));
?>
<ul id=dashboard>
</ul>
<div id=msgboard title='Click Me For Next Message'>
</div>
<div id=controller title='Click To Add New Dashlets'>
</div>
<iframe id='reloadFrame' src='' style='display:none;'></iframe>
<iframe id='msgFrame' src='' style='display:none;'></iframe>
<script language=javascript>
var dashlets=null;
<?php
	if(isset($dashCfg['CSS_PORTLET_BODY']) && strlen($dashCfg['CSS_PORTLET_BODY'])>0)
		echo "clz_portlet_body='{$dashCfg['CSS_PORTLET_BODY']}';";
	if(isset($dashCfg['CSS_PORTLET_HEADER']) && strlen($dashCfg['CSS_PORTLET_HEADER'])>0)
		echo "clz_portlet_header='{$dashCfg['CSS_PORTLET_HEADER']}';";
?>
$(function() {
	setTimeout(function() {
		$("#reloadFrame").load(function (){
			   document.location.reload();
			});
		$("#msgFrame").load(function (){
			   lgksAlert($("#msgFrame").contents().find("body").html());
			});
	},200);
	dashlets="<?=$dashlets?>";

	initPortletSystem();
	loadDashlets(dashlets);

	<?php
		if($dashCfg['ALLOW_SERVER_MESSAGES']=="true") {
			echo "fetchServerMsgs(0);";
		}
		if(!isset($dashCfg['SYS_SHOW_CONTROLLER']) || $dashCfg['SYS_SHOW_CONTROLLER']=="true") {
			echo '$("#controller").click(function() {loadDashletController();});';
		} else {
			echo '$("#controller").hide();';
		}
	?>

	$("body").disableSelection();
});
function loadDashlets(dashletsStr) {
	dashlets=dashletsStr.split(",");
	$.each(dashlets,function(m,n) {
		$("#dashboard").append("<div rel='"+n+"' class='dashletLoader dashletPlaceholder'></div>");
		loadDashlet(n);
	});
}
function loadDashlet(dashlet) {
	if(dashlet==null || dashlet.length<=1) return;
	ref=getServiceCMD("dashboard")+"&action=loadDashlet&format=html&dashlet="+dashlet;
	$.ajax({
			url:ref,
			content:document.body,
			success:function(txt) {
				if(txt.trim().length<=0) {
					return;
				}
				a=$(txt);
				b=$("#dashboard div.dashletPlaceholder[rel='"+a.attr("rel")+"']");
				if(a.children().length>0) {
					if(b.length>0) {
						try {
							$(b).replaceWith(a);
						} catch(e) {
							console.log(e);
							$("#dashboard .portlet[rel='"+dashlet+"']").find(".portlet-content").html("Error Loading Dashlet ...");
						}
					} else {
						$("#dashboard").append(b);
					}
					loadPortletUI(a);
					initPortletEvents(a);
					activatePortlets(a);
				} else {
					$(b).replaceWith("<div class='message'>"+txt+"</div>");
				}
			},
			error:function() {

			}
		});
}
function fetchServerMsgs(index) {
	lnk=getServiceCMD("dashboard")+"&action=servermsgs&index="+index;
	$("#msgboard").html("<div class='msg ajaxloading4'></div>");
	processAJAXQuery(lnk, function(msg) {
			if(msg.length>0) {
				if(msg!="0 Messages") {
					newMsg(msg);
					$("#msgboard .msg").click(function() {
						fetchServerMsgs(index+1);
					});
				} else {
					$("#msgboard").html("");
				}
			}
		});
}
function loadDashletController() {
	lnk=getServiceCMD("dashboard")+"&action=dashletSelector";
	w=$(window).width()-100;
	h=$(window).height()-100;
	jqPopupURL(lnk,"Select Dashlet",function(x) {
		if(x=="OK") {
			document.location.reload();
		} else {
			saveDashlets();
		}
	},true,w,h,"fade");
}
</script>
