

function sure(message) {
	return confirm(message);
}


jQuery(document).ready(function(){


if (jQuery('#form_zightaccess').length) {


	jQuery('#reset_to_default').click(function(){
		if (sure('Are you sure you want to overwrite with the default WordPress content?\nYou will still have to click the Save button to save the changes.')) {
			jQuery('#content').val(jQuery('#default_content').val());
		}
		return false;
	});

}


});

