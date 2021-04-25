var currentDashboardSavekey = null;

$(function() {
	$("#dashboardContainer .dashletPanel.ajaxloading").removeClass("ajaxloading8").removeClass("ajaxloading");

	$(".modal select[data-selected]").each(function() {
        $(this).val(""+$(this).data("selected"));
    });

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
			save_dashboard($(".dashboardContainer").data("dashboard"));

		}
	});

	$(".dashboardContainer").delegate(".dashboardSaveAsIcon","click",function(e) {
		e.preventDefault();

		if($(this).hasClass("development")) {
			save_as_dashboard();

		}
	});

	$(".dashboardContainer").delegate(".dashboardNewIcon","click",function(e) {
		e.preventDefault();

		createNewdashboard();
	});

	$(".dashboardContainer").delegate(".dashboardResetIcon","click",function(e) {
		e.preventDefault();

		resetdashboard();
	});
	
	$(".dashboardContainer").delegate(".dashboardEditIcon","click",function(e) {
		e.preventDefault();

		editdashboard();
	});

	$(".dashboardContainer").delegate(".dashboardReloadIcon","click",function(e) {
		e.preventDefault();

		reloaddashboard();
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
			// htmlOptions = "<table class='table table-striped dashlet-options' data-dashkey='"+dContainer.data("dashkey")+"'>"+dContainer.find(".panel-options").html()+"</table>";
   //          lgksAlert(htmlOptions, "Dashlet Configurator");
   //          setTimeout(function() {
   //              $(".modal select[data-selected]").each(function() {
   //                  $(this).val(""+$(this).data("selected"));
   //              });
   //          }, 1000);
		break;
		case "refresh":
		    var funcName = "refresh_"+dContainer.data("dashid");
		    if(typeof window[funcName]=="function") window[funcName](dContainer);
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
	lx=_service("dashboard","saveDashletOrder","json","&dboard="+$(".dashboardContainer").data("dashboard"));
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
	lx=_service("dashboard","saveDashletState","json","&dboard="+$(".dashboardContainer").data("dashboard"));
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
	//console.log("RELOAD CALLED");
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
	if(dashcode==null || dashcode.length<=0) {
		lgksPrompt("Please give the name of the Dashboard.<br><citie style='font-size:10px;'>(No special characters, space allowed)</citie>","Name of Dashboard!", function(ans) {
			if(ans) {
				ans = ans.replace(/[^a-z0-9]+|\s+/gmi, " ").trim().replace(" ",".");
				dashcode = ans;
				
				$(".dashboardContainer").data("dashboard",dashcode);
				$("#dashboardNameBox").html(dashcode);
				
				lgksConfirm("This will save the current dashboard into : "+ dashcode, "Save Dashboard !",function(ans) {
			        if(ans) {
			            q=[];
			            $(".dashboardContainer>.dashletContainer").each(function() {q.push($(this).data('dashkey'));});
			            lx=_service("dashboard","saveDashletFile","json","&dboard="+$(".dashboardContainer").data("dashboard"));
			            processAJAXPostQuery(lx,"neworder="+q.join(",")+"&dashcode="+dashcode,function(txt) {
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
			    });
			}
		});
	} else {
		lgksConfirm("This will save the current dashboard into : "+ dashcode, "Save Dashboard !",function(ans) {
	        if(ans) {
	            q=[];
	            $(".dashboardContainer>.dashletContainer").each(function() {q.push($(this).data('dashkey'));});
	            lx=_service("dashboard","saveDashletFile","json","&dboard="+$(".dashboardContainer").data("dashboard"));
	            processAJAXPostQuery(lx,"neworder="+q.join(",")+"&dashcode="+dashcode,function(txt) {
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
	    });
	}
}

function save_as_dashboard() {
	lgksPrompt("Please give the name of the Dashboard.<br><citie style='font-size:10px;'>(No special characters, space allowed)</citie>","Name of Dashboard!", function(ans) {
		if(ans) {
			ans = ans.replace(/[^a-z0-9]+|\s+/gmi, " ").trim().replace(" ",".");
			newDashCode = ans;
			q=[];
            $(".dashboardContainer>.dashletContainer").each(function() {q.push($(this).data('dashkey'));});
            lx=_service("dashboard","saveDashletFile","json","&dboard="+newDashCode);
            processAJAXPostQuery(lx,"neworder="+q.join(",")+"&dashcode="+newDashCode,function(txt) {
                try {
                    json=$.parseJSON(txt);
                    if(json.error!=null) {
                        lgksToast(json.error.msg);

                        if(typeof openLinkFrame == "function") {
			            	openLinkFrame("Edit-"+newDashCode,_link("modules/dashboard/"+newDashCode));
						} else if(typeof top['openLinkFrame'] == "function") {
							top.openLinkFrame("Edit-"+newDashCode,_link("modules/dashboard/"+newDashCode));
						} else {
							window.open(_link("modules/dashboard/"+newDashCode));
						}
                    } else if(json.Data.msg!=null) {
                        lgksToast(json.Data.msg);
                    }
                } catch(e) {
                    console.error(e);
                }
            });
		}
	});
}

function reloaddashboard() {
	window.location = window.location.href.replace("&reset=true","")+"&reset=true";
}

function resetdashboard() {
	lx=_service("dashboard","resetDashlet","json","&dboard="+$(".dashboardContainer").data("dashboard"));
	processAJAXQuery(lx,function(txt) {
// 		window.location.reload();
		$(".dashletContainer").detach();
	});
}

function editdashboard() {
	dboard = $(".dashboardContainer").data("dashboard");
	if(dboard!=null && dboard.length>0) {
		if(typeof openLinkFrame == "function") {
			openLinkFrame("DashEditor : "+dboard,_link("modules/dashboard/dashedit/"+dboard));
		} else if(typeof top['openLinkFrame'] == "function") {
			top.openLinkFrame("DashEditor : "+dboard,_link("modules/dashboard/dashedit/"+dboard));
		} else {
			window.open(_link("modules/dashboard/dashedit/"+dboard));
		}
	}
}

function createNewdashboard() {
	lgksPrompt("Please give the name of the Dashboard.<br><citie style='font-size:10px;'>(No special characters, space allowed)</citie>","Name of Dashboard!", function(ans) {
		if(ans) {
			ans = ans.replace(/[^a-z0-9]+|\s+/gmi, " ").trim().replace(" ",".");
			newDashCode = ans;
			if(typeof openLinkFrame == "function") {
            	openLinkFrame("Edit-"+newDashCode,_link("modules/dashboard/"+newDashCode));
			} else if(typeof top['openLinkFrame'] == "function") {
				top.openLinkFrame("Edit-"+newDashCode,_link("modules/dashboard/"+newDashCode));
			} else {
				window.open(_link("modules/dashboard/"+newDashCode));
			}
		}
	});
}