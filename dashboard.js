var clz_portlet_body="ui-widget ui-widget-content";
var clz_portlet_header="ui-widget-header ui-corner-top";

function initPortletSystem() {
	$( "#dashboard" ).sortable({
		connectWith: "#dashboard",
		scroll: true,
		opacity: 0.6,
		dropOnEmpty: true,
		forceHelperSize: true,
		opacity:0.5,
		forcePlaceholderSize: true,
		handle:".portlet-header",
		containment: "#dashboard",
		//placeholder: "ui-sortable-placeholder",
		stop: function() {
			saveDashlets();
		}
	});

	$("#dashboard" ).disableSelection();
	//$(".portlet").fadeIn();
}
function loadPortletUI(ui) {
	$(ui).addClass("ui-helper-clearfix ui-corner-all" )
		.find(".portlet-header")
			.addClass( clz_portlet_header )
			.prepend( "<span class='ui-icon ui-icon-refresh'></span>")
			.prepend( "<span class='ui-icon ui-icon-minusthick'></span>")
			.prepend( "<span class='ui-icon ui-icon-close'></span>")
			.end()
		.find(".portlet-body").addClass(clz_portlet_body).end();
		//.find( ".portlet-config" ).addClass(clz_portlet_body);
	$(ui).find(".portlet-config").each(function() {
		$(this).parents(".portlet:first").find(".portlet-header").
			prepend( "<span class='ui-icon ui-icon-gear' style='float:left'></span>")
	});
	$(ui).find(".portlet-info").each(function() {
		$(this).parents(".portlet:first").find(".portlet-header").
			prepend( "<span class='ui-icon ui-icon-help' style='float:left'></span>")
	});
	$(".portlet-body:hidden",ui).parents(".portlet").find(".ui-icon-minusthick").toggleClass("ui-icon-minusthick").toggleClass( "ui-icon-plusthick" );

	$("select:not(.nostyle)",ui).addClass("ui-widget").addClass("ui-state-default");
	$("button:not(.nostyle)",ui).button();
	$(".datefield",ui).datepicker();
	$(".progressbar",ui).progressbar({value:37});
	$(".slider",ui).slider();
	$(".draggable",ui).draggable();
	$(".accordion",ui).accordion({
			fillSpace: true
		});
	$(".portlet-content",ui).enableSelection();

	if($(".portlet-body",ui).is(":visible"))
		$(".portlet-body",ui).css("height",($(ui).height()-$(".portlet-header",ui).height()-5)+"px");
	else {
		if($(".portlet-body .portlet-content",ui).find(">iframe").length==
			$(".portlet-body .portlet-content",ui).children().length) {
			$(".portlet-body",ui).css("height",(parseInt($(ui).css("max-height"))-$(".portlet-header",ui).height())+"px");
		}
	}
	//console.log($(ui).attr("rel")+" "+$(".portlet-body",ui).height());
}
function initPortletEvents(ui) {
	$(".portlet-header .ui-icon.ui-icon-minusthick",ui).click(function() {
		h=$(this).parents( ".portlet:first" ).find( ".portlet-content" ).height();
		$(this).parents( ".portlet:first" ).find( ".portlet-content" ).height(h);

		$(this).toggleClass("ui-icon-minusthick").toggleClass( "ui-icon-plusthick" );
		$(this).parents( ".portlet:first" ).find( ".portlet-body" ).slideToggle();

		saveState($(this).parents( ".portlet:first" ).attr("rel"),"autoOpen","false");
	});
	$(".portlet-header .ui-icon.ui-icon-plusthick",ui).click(function() {
		$(this).toggleClass("ui-icon-plusthick").toggleClass( "ui-icon-minusthick" );
		$(this).parents( ".portlet:first" ).find( ".portlet-body" ).slideToggle();

		saveState($(this).parents( ".portlet:first" ).attr("rel"),"autoOpen","true");
	});
	$(".portlet-header .ui-icon.ui-icon-refresh",ui).click(function() {
		$(this).parents( ".portlet:first" ).find( ".portlet-body" ).slideDown(function() {
			rel=$(this).parents(".portlet:first").attr("rel");
			reloadDashlet(rel);
		});
	});
	$(".portlet-header .ui-icon.ui-icon-close",ui).click(function() {
		var a=$(this).parents(".portlet:first");
		var na=a.attr("name");
		a.slideUp(function() {
				a.detach();
				saveDashlets();
			});
	});
	$(".portlet-header .ui-icon.ui-icon-gear",ui).click(function() {
		$(this).parents( ".portlet:first" ).find( ".portlet-body" ).slideDown();
		if($(this).parents(".portlet:first").find(".portlet-config").css('display')!="none") {
			//Save Settings
		}
		if(!$(this).parents(".portlet:first").find(".portlet-config").is(":visible")) {
			$(this).parents(".portlet:first").find(".portlet-config input,.portlet-config select,.portlet-config textarea").
				each(function() {
					$(this).val($(this).attr("value"));
				});

			$(this).parents(".portlet:first").
				find(".portlet-container:visible").hide('blind').end().
				find(".portlet-config").show('blind');
		} else {
			$(this).parents(".portlet:first").
				find(".portlet-config").hide('blind').end().
				find(".portlet-content").show('blind');
			$(this).parents(".portlet:first").find(".portlet-content").find("iframe.hidden").hide();
		}
	});
	$(".portlet-header .ui-icon.ui-icon-help",ui).click(function() {
		$(this).parents( ".portlet:first" ).find( ".portlet-body" ).slideDown();
		if(!$(this).parents(".portlet:first").find(".portlet-info").is(":visible")) {
			$(this).parents(".portlet:first").
				find(".portlet-container:visible").hide('blind').end().
				find(".portlet-info").show('blind');
		} else {
			$(this).parents(".portlet:first").
				find(".portlet-info").hide('blind').end().
				find(".portlet-content").show('blind');
		}
	});
	$(".portlet-config .saveConfig",ui).click(function() {
		var aa=[];
		dashlet=$(this).parents(".portlet:first").attr("rel");
		$(this).parents(".portlet:first").find(".portlet-config input,.portlet-config select,.portlet-config textarea").
			each(function() {
				nm=$(this).attr("name");
				vx=$(this).val();
				if($(this).attr("value")!=vx)
					aa.push(nm+"="+vx);
			});

		//if(aa.length>0) {
			l=getServiceCMD("dashboard")+"&action=saveConfig&dashlet="+dashlet;
			q=aa.join("&");
			processAJAXPostQuery(l,q,function(txt) {
				if(txt.trim().length>0) lgksAlert(txt);
				reloadDashlet(dashlet);
			});
		//}
	});
	$(".portlet-config .resetConfig",ui).click(function() {
		$(this).parents(".portlet:first").find(".portlet-config input,.portlet-config select,.portlet-config textarea").
			each(function() {
				$(this).val($(this).attr("value"));
			});
	});
}
function activatePortlets(ui) {
	$(ui).each(function(element){
		  //$(this).children(".portlet-content").load("content.php?id="+element);
		  portlet=$(this).children(".portlet-content");
		  if(portlet.attr('href')!=null) {
			  href=$(this).children(".portlet-content").attr('href');
			  if(href="##") {
				  href=getServiceCMD("widget")+"&wid="+portlet.parents(".portlet:first").attr('name');
			  }
			  portlet.load(href);
		  }
	});
}
function saveState(dashlet,name,value) {
	l=getServiceCMD("dashboard")+"&action=saveParam&dashlet="+dashlet;
	q=name+"="+value;
	processAJAXPostQuery(l,q,function(txt) {
			if(txt.trim().length>0) lgksAlert(txt);
		});
}
function saveDashlets() {
	var aa=[];
	$("#dashboard .portlet").each(function() {
			aa.push($(this).attr('rel'));
		});
	if(dashlets!=aa.join(",")) {
		l=getServiceCMD("dashboard")+"&action=save";
		q="DASHLETS="+aa.join(",");
		processAJAXPostQuery(l,q,function(txt) {
			if(txt.trim().length>0) lgksAlert(txt);
		});
	}
}
function reloadDashlet(dashlet) {
	d=$("#dashboard .portlet[rel="+dashlet+"]");
	clz=d.attr("class");
	$("#dashboard .portlet[rel='"+dashlet+"']").replaceWith("<div rel='"+dashlet+"' class='dashletPlaceholder "+clz+" ajaxloading'><br/><br/><br/>Loading ...</div>");
	loadDashlet(dashlet);
}
function newMsg(msg) {
	s="<div class='msg ui-widget-content'>";
	s+=msg;
	s+="</div>";
	$("#msgboard .msg.ajaxloading4").detach();
	$("#msgboard").append(s);
	$("#msgboard .msg").click(function() {
			$(this).detach();
		});
}

