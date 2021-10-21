jQuery(document).ready(function($) {

	$('#save_zoom_calendar_group').on('click', function(e) {
        if($('input#bp-group-zoom-event-category').length > 0) {

            if ( !($('input#bp-group-zoom-event-category').val()).trim().length ) {
                return;
            }

            let bbzg_button = $('#save_zoom_calendar_group');
            bbzg_button.html( 'Saving...' );
            e.preventDefault();
            $.ajax({
                type : 'GET',
                url  : bbzec.ajaxurl + '?zoom_group_cat=' + $('input#bp-group-zoom-event-category').val(),
                data : {
                    action: 'bbzec_save_zoom_group_cat'
                },
                success: function(data){ /** */ bbzg_button.html( 'Saved!' ); setTimeout(() => { bbzg_button.html( 'Save' ); location.reload();  }, 2000); /** */}
            });
        }
	});

});