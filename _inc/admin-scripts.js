jQuery(document).ready(function(){

	var status = jQuery('input[type=checkbox].bpge_allgroups').attr('checked');

	if ( status && status == 'checked'){
		jQuery('input[type=checkbox].bpge_allgroups').change( function(){
			jQuery('input[type=checkbox].bpge_groups').removeAttr('checked');
			jQuery('input[type=checkbox].bpge_allgroups').removeAttr('checked');
		});
	}

	if ( !status || status == ''){
		jQuery('input[type=checkbox].bpge_allgroups').change( function(){
			jQuery('input[type=checkbox].bpge_groups').attr('checked', 'checked');
			jQuery('input[type=checkbox].bpge_allgroups').attr('checked', 'checked');
		});
	}

	jQuery('input[type=checkbox].bpge_groups').change( function(){
		jQuery('input[type=checkbox].bpge_allgroups').removeAttr('checked');
	});


});
