jQuery(document).ready(function($){

	$('input[type=radio][name*=csls-settings]').click(function(){
		var value = $(this).val();
		if (value != 'custom')
			$('#csls-settings-custom').val( value );
		else
			$('#csls-settings-custom').focus();
	});

	$('#csls-settings-custom').bind('focus change', function(){
		$('input[type=radio][name*=csls-settings][value=custom]').attr('checked','checked');
	});

	if ($('input.tog[type=radio][value=]:checked').length > 0)
		$('.csls-warning').next('.form-table').hide();

});