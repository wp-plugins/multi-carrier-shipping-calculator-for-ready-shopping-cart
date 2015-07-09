jQuery(document).ready(function($) {

    toggle_closed();

    $(document).on('click', '#ai_packaging', function(e) {
        toggle_closed();
    });

    function toggle_closed() {
	    if( $('#ai_packaging').hasClass('closed') ) {
	    	$('#ai_packaging').removeClass('closed');	    	
	    }
	    else {
	    	$('#ai_packaging').addClass('closed');
	    }
    }

});