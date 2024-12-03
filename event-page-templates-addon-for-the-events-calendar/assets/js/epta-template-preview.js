jQuery(document).ready(function ($) {
	$('#epta-template').on('click', function (event) {
        event.preventDefault(); // Prevent default action
        $('#epta-template option:not(:first)').prop('disabled', true); // Use .prop() instead of .attr()
	});
});


