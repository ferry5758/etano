$(function() {
//	get_reason();
	oFCKeditor=new FCKeditor('reject_reason');
	oFCKeditor.BasePath=document.location.pathname.substring(0,document.location.pathname.lastIndexOf('/'))+'/fckeditor/';
	oFCKeditor.Config["CustomConfigurationsPath"] = oFCKeditor.BasePath+'../js/fckconfig.js';
	oFCKeditor.Config['FullPage']=true ;
	oFCKeditor.ToolbarSet='Datemill';
	oFCKeditor.Height=500;
	oFCKeditor.ReplaceTextarea();

});

function get_reason() {
	myval=$("#reason").val();
	if (myval!='') {
		$.ajax({url: 'ajax/get_reject_reason.php',
				type: 'POST',
				dataType: 'xml',
				data: 'reason='+myval,
				success: function(xml) {
							set_reason(xml);
						}
				});
	}
}

function set_reason(xml) {
	$("#reason_title").val(unescape($("reason_title",xml).text()));
	$("#reject_reason").val(unescape($("reject_reason",xml).text()));
}

function check_form(theform) {
	return confirm('Are you sure?');
}

function save_template() {
	$.ajax({url:'ajax/save_admin_mtpl.php',
			type: 'POST',
			dataType:'html',
			data:'amtpl_type='+$('#t').val()+'&amtpl_id='+$('#amtpl_id').val()+'&subject='+escape($('#reason_title').val())+'&message_body='+escape(FCKeditorAPI.GetInstance('reject_reason').GetXHTML()),
			success: function(data) {
						alert(data);
					}
			});
}
