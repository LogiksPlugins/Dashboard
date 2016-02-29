<div id="dashboardSettingsModal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
		  <div class="modal-body">
			  <div class="row noinfo">
			  		<div class="dashletList list-group col-md-4">
					</div>
					<div class='dashletInfo col-md-8'>
						
					</div>
			  </div>

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
					$("#dashboardSettingsModal .dashletList").html("<h3>Sorry, no dashlets found for you.</h3>");
					return;
				}
				html="";
				$.each(json.Data,function(k,v) {
					html+="<div class='list-group-item' data-key='"+k+"'>";
					if(v.active!=null && v.active>0) {
						html+="<span><i class='fa fa-info-circle'></i> &nbsp;"+v.title+" ["+v.active+"]</span>";
					} else {
						html+="<span><i class='fa fa-info-circle'></i> &nbsp;"+v.title+"</span>";
					}
					if(v.disabled===true) {
						html+=" <input type='checkbox' name='dashlets[]' value="+k+" class='pull-right' disabled /></div>";
					} else {
						html+=" <input type='checkbox' name='dashlets[]' value="+k+" class='pull-right' /></div>";
					}
				});
				$("#dashboardSettingsModal .dashletList").html(html);
			} catch(e) {
				$("#dashboardSettingsModal .dashletList").html("<h3>Sorry, no dashlets found for you.</h3>");
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

	lx=_service("dashboard","addDashlets")+"&d="+q.join(",");
	processAJAXQuery(lx,function(txt) {
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
</script>