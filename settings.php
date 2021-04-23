<?php
if(!defined('ROOT')) exit('No direct script access allowed');
?>
<div id="dashboardSettingsModal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
		  <div class="modal-title" style='overflow: hidden;'>
		  	<input type="search" name="searchDashboardDashlets" class='form-control search-dashlet pull-right' placeholder='Search ...' />
		  	<div class="panel-heading">
		  		<?php
		  			if(strtolower(getConfig("APPS_STATUS"))=="production" || strtolower(getConfig("APPS_STATUS"))=="prod") {
		  				echo "Manage My Dashlets";
		  			} else {
		  				echo "Manage Dashlets : {$dboard}";
		  			}
		  		?>
		  	</div>
		  </div>
		  <div class="modal-body">
		  		<?php
		  			if(strtolower(getConfig("APPS_STATUS"))=="production" || strtolower(getConfig("APPS_STATUS"))=="prod") {
		  				?>
		  				<div class="row noinfo nowMargin">
					  		<div class="dashletList list-group col-md-4">
							</div>
							<div class='dashletInfo col-md-8'>
								
							</div>
					  	</div>
		  				<?php
		  			} else {
		  				?>
		  				<div class="row noinfo nowMargin">
			  				<ul class="nav nav-tabs">
							  <li class="active"><a data-toggle="tab" href="#dashletList">Dashlets</a></li>
							  <li><a data-toggle="tab" href="#dashConfigure">Configure</a></li>
							</ul>
							<div class="tab-content">
							  <div id="dashletList" class="tab-pane fade in active">
							    <div class="row">
							  		<div class="dashletList list-group col-md-4">
									</div>
									<div class='dashletInfo col-md-8'>
										
									</div>
							  	</div>
							  </div>
							  <div id="dashConfigure" class="tab-pane fade">
							  	<?php if(isset($dashboardConfig['params'])) { ?>
							    <div class="well">
							    	<form id='dashboardConfigureForm' class="form-horizontal">
									  <?php
									  	foreach ($dashboardConfig['params'] as $key => $value) {
									  		$title = _ling(toTitle($key));

									  		if(is_bool($value) || (is_integer($value) && $value===1) || (is_integer($value) && $value===0)) {
									  			$checked1 = $value?"checked=checked":"";
									  			$checked2 = $value?"":"checked=checked";
									  			echo "<div class='form-group col-md-4'>
												    <label class='control-label col-sm-4'>{$title}:</label>
												    <div class='col-sm-8'>
												      <label>
												      	<input type='radio' class='form-control' name='{$key}' value='true' {$checked1}>
												      	 True
												      </label>
												      <label>
												      	<input type='radio' class='form-control' name='{$key}' value='false' {$checked2}>
												      	 False
												      </label>
												    </div>
												  </div>";
									  		} else {
									  			echo "<div class='form-group col-md-6'>
												    <label class='control-label col-sm-4' for='{$key}'>{$title}:</label>
												    <div class='col-sm-8'>
												      <input type='text' class='form-control' id='{$key}' name='{$key}' placeholder='Enter {$title}' value='{$value}'>
												    </div>
												  </div>";
									  		}
									  	}
									  ?>
									  <div class="form-group">
									    <div class="col-sm-offset-2 col-sm-10">
									      <button type="submit" class="btn btn-success">Update</button>
									    </div>
									  </div>
									</form>
							    </div>
							<?php } else { ?>
								<h2 align="center">No parameters to configure</h2>
							<?php } ?>
							  </div>
							</div>
						</div>
		  				<?php
		  			}
		  		?>
		  </div>
		  <div class="modal-footer">
		  	<button type="button" class="btn btn-info pull-left"  onclick="loadDashletList(true)">Refresh List</button>
		  	<button type="button" class="btn btn-danger"  data-dismiss="modal">Close</button>
		  	<button type="button" class="btn btn-primary" onclick='updateDashletAddition()'>Update</button>
		  </div>
		</div>
	</div>
</div>
<script>
$(function() {
	$("#dashboardSettingsModal .dashletInfo").delegate(".removeDashletInfo","click",function(e) {
		$("#dashboardSettingsModal .dashletInfo").html("");
		$("#dashboardSettingsModal .row").addClass("noinfo");
		$("#dashboardSettingsModal .row .list-group-item.active").removeClass("active");
	});
	$("#dashboardSettingsModal .dashletList").delegate(".list-group-item>span","click",function(e) {
		e.preventDefault();

		$("#dashboardSettingsModal .row").removeClass("noinfo");
		$("#dashboardSettingsModal .row .list-group-item.active").removeClass("active");
		$(this).closest(".list-group-item").addClass('active');

		$("#dashboardSettingsModal .dashletInfo").load(_service("dashboard","dashletInfo")+"&d="+$(this).closest(".list-group-item").data('key'),function() {
			$("#dashboardSettingsModal .dashletInfo").prepend('<i class="fa fa-times pull-right removeDashletInfo"></i>');
		});
	});
	$("#dashboardSettingsModal input[name=searchDashboardDashlets]").keyup(function() {
		sTxt = $(this).val();
		if(sTxt==null || sTxt.length<=0) {
			$(".dashletList .list-group-item").show();
		} else {
			$(".dashletList .list-group-item").hide();
			$(".dashletList .list-group-item").filter(function() {
				return $(this).text().toLowerCase().indexOf(sTxt.toLowerCase())>=0;
			}).show();
		}
	});

	$("form#dashboardConfigureForm").submit(function() {
		saveDashboardParams();
		$("form#dashboardConfigureForm")
		return false;
	});
});

function loadDashletList(relist) {
	$("#dashboardSettingsModal .row").addClass("noinfo");
	$("#dashboardSettingsModal .dashletList").html("<div class='ajaxloading5'></div>");
	$("#dashboardSettingsModal .dashletInfo").html("Loading...");
	lx=_service("dashboard","listDashlets");
	if(relist===true) {
		lx=_service("dashboard","relistDashlets");
	}
	$("#dashboardSettingsModal .dashletList").load(lx,function(txt) {
			$("#dashboardSettingsModal .dashletList").html("");
			try {
				json=$.parseJSON(txt);
				if(json.Data.length<=0) {
					$("#dashboardSettingsModal .dashletList").html("<h3 class='text-center'>Sorry, no dashlets found for you.</h3>");
					return;
				}
				htmlGroups = {"No Group":[]};
				$.each(json.Data,function(k,v) {
					if(v.title==null) {
						if(Object.keys(v).length>0) {
							$.each(v,function(k1,v1) {
								v1.active = 0;
								html1="<div class='list-group-item' data-key='"+k1+"'>";
                                if(v.active!=null && v.active>0) {
                                    html1+="<span><i class='fa fa-info-circle'></i> &nbsp;"+v1.title+" ["+v1.active+"]</span>";
                                } else {
                                    html1+="<span><i class='fa fa-info-circle'></i> &nbsp;"+v1.title+"</span>";
                                }
                                if(v1.disabled===true) {
                                    html1+=" <input type='checkbox' name='dashlets[]' value="+k1+" class='pull-right' disabled /></div>";
                                } else {
                                    html1+=" <input type='checkbox' name='dashlets[]' value="+k1+" class='pull-right' /></div>";
                                }
								if(htmlGroups[k]==null) htmlGroups[k] = [];
                                htmlGroups[k].push(html1);
							});
							
						}
					} else {
						html1="<div class='list-group-item' data-key='"+k+"'>";
                        if(v.active!=null && v.active>0) {
                            html1+="<span><i class='fa fa-info-circle'></i> &nbsp;"+v.title+" ["+v.active+"]</span>";
                        } else {
                            html1+="<span><i class='fa fa-info-circle'></i> &nbsp;"+v.title+"</span>";
                        }
                        if(v.disabled===true) {
                            html1+=" <input type='checkbox' name='dashlets[]' value="+k+" class='pull-right' disabled /></div>";
                        } else {
                            html1+=" <input type='checkbox' name='dashlets[]' value="+k+" class='pull-right' /></div>";
                        }
                        htmlGroups["No Group"].push(html1);
					}
				});
				html = "";
                $.each(htmlGroups, function(a,b) {
                    htmlTemp = "<div class='row row-dashlets-groups noinfo'>";
                    if(a!="No Group") {
                    	htmlTemp += "<h5 class='category-title'>"+toTitle(a)+"</h5>";
                    }
                    htmlTemp += b.join("");
                    htmlTemp += "</div>";
                    html+=htmlTemp;
                });
				$("#dashboardSettingsModal .dashletList").html(html);
			} catch(e) {
				$("#dashboardSettingsModal .dashletList").html("<h3 class='text-center'>Sorry, no dashlets found for you.</h3>");
			}
		  	return false;
		});
}
function updateDashletAddition() {
	q=[];
	$(".dashletList .list-group-item input[type=checkbox]:not(:disabled):checked").each(function() {
		dk=this.value;
		$(".dashletList .list-group-item[data-key='"+dk+"']>span").append("<i class='ajaxloading ajaxloading8'></i>");
		q.push(dk);
	});

	lx=_service("dashboard","addDashlets")+"&dboard="+$(".dashboardContainer").data("dashboard");
	processAJAXPostQuery(lx,"d="+q.join(","),function(txt) {
		$(".dashletList .list-group-item .ajaxloading").detach();

		try {
			json=$.parseJSON(txt);
			if(json.Data.status=="success") {
				updateDashboard();
			}
		} catch(e) {
			console.error(e);
		}
	});
}

function saveDashboardParams() {
	lx=_service("dashboard","saveBoardParams")+"&dboard="+$(".dashboardContainer").data("dashboard");
	processAJAXPostQuery(lx,$("form#dashboardConfigureForm").serialize(),function(txt) {
		lgksToast("Dashboard configured successfully");
	});
}
</script>