var currentDashboardSavekey = null;

$(function() {
	$("#dashboardContainer .dashletPanel.ajaxloading").removeClass("ajaxloading8").removeClass("ajaxloading");

	$(".dashboardContainer").delegate(".dashboardSettingsIcon","click",function(e) {
		e.preventDefault();

		loadDashletList();

		$('#dashboardSettingsModal').modal({
			  keyboard: false
			});
	});
	
	$(".dashboardContainer").delegate(".dashboardSaveIcon","click",function(e) {
		e.preventDefault();

		if($(this).hasClass("development")) {
			if(currentDashboardSavekey==null) {
				lgksPrompt("Please give the name of the Dashboard.<br><citie style='font-size:10px;'>(No special characters, space allowed)</citie>","Name of Dashboard!", function(ans) {
		    			if(ans) {
		    				currentDashboardSavekey = ans;
		    				save_dashboard(currentDashboardSavekey);
		    			}
					});
			} else {
				lgksConfirm("Do you want to update current dashboard <b style='font-size:20px'>"+currentDashboardSavekey+"</b><br><br> Press cancel to create new Dashboard !", "Save Dashboard", function(ans1) {
				    if(ans1) {
				        save_dashboard(currentDashboardSavekey);
				    } else {
				        lgksPrompt("Please give the name of the Dashboard.<br><citie style='font-size:10px;'>(No special characters, space allowed)</citie>","Name of Dashboard!", function(ans) {
				    			if(ans) {
				    				currentDashboardSavekey = ans;
				    				save_dashboard(currentDashboardSavekey);
				    			}
							});
				    }
				})
			}
		}
	});

	$(".dashboardContainer").delegate(".dashboardDevIcon","click",function(e) {
		e.preventDefault();

		clear_dashboard();
	});

	$("#dashboardContainer").delegate(".dashletPanel .dashletOption.dashletHandle","click",function(e) {
		e.preventDefault();

		dPanel=$(this).closest(".dashletPanel");

		//dPanel.find(".dashletHandle")
		if(dPanel.hasClass("active")) {
			dPanel.find(".panel-body").hide();
			dPanel.removeClass('active');
			$(this).removeClass("glyphicon-triangle-bottom").addClass("glyphicon-triangle-top");
		} else {
			dPanel.find(".panel-body").show();
			dPanel.addClass('active');
			$(this).removeClass("glyphicon-triangle-top").addClass("glyphicon-triangle-bottom");
		}
		dashkey=dPanel.parent().data('dashkey');
		saveDashletState(dashkey, "active", dPanel.hasClass("active")?1:0);
	});

	$("#dashboardContainer").delegate(".dashletPanel .dashletOption[cmd]","click",function(e) {
		e.preventDefault();

		cmd=$(this).attr('cmd');
		dPanel=$(this).closest(".dashletPanel");

		dashboardAction(cmd,dPanel);
	});

	$("#dashboardContainer").delegate(".dashletPanel .panel-options select[name]","change",function(e) {
		e.preventDefault();

		attrName=$(this).attr('name');
		attrValue=$(this).val();
		dPanel=$(this).closest(".dashletContainer");
		dashkey=dPanel.data('dashkey');

		saveDashletState(dashkey, attrName, attrValue);
	});

	$("#dashboardContainer").delegate(".dashletPanel .panel-options input[name]","blur",function(e) {
		e.preventDefault();

		attrName=$(this).attr('name');
		attrValue=$(this).val();
		dPanel=$(this).closest(".dashletContainer");
		dashkey=dPanel.data('dashkey');

		saveDashletState(dashkey, attrName, attrValue);
	});

	$("#dashboardContainer.withDND").sortable({
		connectWith: "#dashboardContainer",
		scroll: true,
		opacity: 0.6,
		dropOnEmpty: true,
		forceHelperSize: true,
		opacity:0.5,
		forcePlaceholderSize: true,
		handle:".panel-heading",
		containment: "#dashboardContainer",
		//placeholder: "ui-sortable-placeholder",
		stop: function() {
			saveDashletOrder();
		}
	});

	$("body").delegate("#focus-overlay","click",function(e) {
		$(".dashletContainer-focus-enabled").removeClass("dashletContainer-focus-enabled");
		$(".dashletFocus.glyphicon-eye-close").removeClass("glyphicon-eye-close").addClass("glyphicon-eye-open");
		$("body").find("#focus-overlay").detach();
	});

	$("#dashboardContainer" ).disableSelection();
});

function dashboardAction(cmd, dashlet) {
	dContainer=dashlet.closest(".dashletContainer");
	switch(cmd) {
		case "remove":
			dContainer.hide().detach();
			saveDashletOrder();
		break;
		case "settings":
			dContainer.find(".panel-options").toggle();
		break;
		case "focus":
			if($("body").find("#focus-overlay").length<=0) {
				$("body").append("<div id='focus-overlay'></div>");
				dContainer.addClass("dashletContainer-focus-enabled");
				dContainer.find(".dashletFocus").removeClass("glyphicon-eye-open").addClass("glyphicon-eye-close");
			} else {
				dContainer.removeClass("dashletContainer-focus-enabled");
				$("body").find("#focus-overlay").detach();
				dContainer.find(".dashletFocus").removeClass("glyphicon-eye-close").addClass("glyphicon-eye-open");
			}
		break;
	}
}

function saveDashletOrder() {
	q=[];
	$(".dashboardContainer>.dashletContainer").each(function() {q.push($(this).data('dashkey'));});
	lx=_service("dashboard","saveDashletOrder");
	processAJAXPostQuery(lx,"neworder="+q.join(","),function(txt) {
		try {
			json=$.parseJSON(txt);
			if(json.error!=null) {
				lgksToast(json.error.msg);
			}
		} catch(e) {
			console.error(e);
		}
	});
}

function saveDashletState(dashkey, attrName, attrValue) {
	lx=_service("dashboard","saveDashletState");
	q=["dashkey="+dashkey,attrName+"="+attrValue];
	processAJAXPostQuery(lx,q.join("&"),function(txt) {
		try {
			json=$.parseJSON(txt);
			if(json.error!=null) {
				lgksToast(json.error.msg);
			} else {
				func="change_"+attrName.toLowerCase();
				if(typeof window[func]=="function") {
					dashlet=$(".dashletContainer[data-dashkey='"+dashkey+"']");
					window[func](dashlet,attrValue);
				} else {
					lgksToast("Dashlet Saved, reload to see the changes.");
				}
			}
		} catch(e) {
			console.error(e);
		}
	});
}

function updateDashboard() {
	window.location.reload();
}

//Specific Dashlet Support Functions
function change_column(dashlet,attrValue) {
	dashlet.alterClass("col-md*","col-md-"+attrValue).alterClass("col-lg*","col-lg-"+attrValue);
}
function change_forcenewrow(dashlet,attrValue) {
	if(attrValue=="true") {
		dashlet.addClass("clear-left");
	} else {
		dashlet.removeClass("clear-left");
	}
}
function change_active(dashlet,attrValue) {
	
}

function save_dashboard(dashcode) {
	q=[];
	$(".dashboardContainer>.dashletContainer").each(function() {q.push($(this).data('dashkey'));});
	lx=_service("dashboard","saveDashletNew");
	processAJAXPostQuery(lx,"neworder="+q.join(",")+"&dashkey="+dashcode,function(txt) {
		try {
			json=$.parseJSON(txt);
			if(json.error!=null) {
				lgksToast(json.error.msg);
			} else if(json.Data.msg!=null) {
				lgksToast(json.Data.msg);
			}
		} catch(e) {
			console.error(e);
		}
	});
}

function clear_dashboard() {
	
}