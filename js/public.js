$('#remote_address').on("change keyup paste", function() {
 			if ($(this).val() === '') {
 				$('#save-button-confirm').prop('disabled', true);
 			} else {
 				$('#save-button-confirm').prop('disabled', false);
 			}
});