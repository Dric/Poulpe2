/**
 * Created by cedric.gallard on 03/06/14.
 */
$(function() {
	$('#field_select_serverFrom').change(function(){
		var val = $(this).val();
		disableServerFrom(val);
	});

	function disableServerFrom(serverFrom){
		$('.serversList input').removeAttr('disabled');
		$('.serversList').show();
		$('#checkboxList_'+serverFrom+' input').attr('disabled', true);
		$('#checkboxList_'+serverFrom).fadeOut();
	}

	disableServerFrom($('#field_select_serverFrom').val());

});