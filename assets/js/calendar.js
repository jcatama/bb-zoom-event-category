jQuery(document).ready(function($) {

    if (typeof $.fn.datetimepicker !== 'undefined') {

        $('#bp-group-calendar-start-date').datetimepicker({
            format: 'Y-m-d',
            timepicker: false,
            mask: true,
            minDate: 0,
            yearStart: new Date().getFullYear(),
            defaultDate: new Date(),
            scrollMonth: false,
            scrollTime: false,
            scrollInput: false,
            onSelectDate: function (date,element) {
                $('#bp-group-calendar-start-date').datetimepicker({
                    minDate: element.val(),
                });
            }
        });

        $('#bp-group-calendar-start-time').datetimepicker({
            format: 'h:i',
            formatTime:	'h:i',
            datepicker: false,
            hours12: true,
            step: 30,
        });

    }

	$('#save_calendar_group').on('click', function(e) {
        if($('input#bp-group-calendar-event-category').length > 0) {

            if ( !($('input#bp-group-calendar-event-category').val()).trim().length ) {
                return;
            }

            let bbzg_button = $('#save_calendar_group');
            bbzg_button.html( 'Saving...' );
            e.preventDefault();
            $.ajax({
                type : 'POST',
                url  : bbzec.ajaxurl,
                data : {
                    zoom_group_cat: $('input#bp-group-calendar-event-category').val(),
                    action: 'bbzec_save_calendar_group_cat',
                    nonce:   bbzec.nonce
                },
                success: function(data){ /** */ bbzg_button.html( 'Saved!' ); setTimeout(() => { bbzg_button.html( 'Save' ); location.reload(); }, 1500); /** */}
            });
        }
	});

    $('#save_calendar_group_delete').on('click', function(e) {
        if($('select#bp-group-calendar-event-category-delete').length > 0) {

            if ( !($('select#bp-group-calendar-event-category-delete').val()).trim().length ) {
                return;
            }

            let event_name = $('select#bp-group-calendar-event-category-delete').find(":selected").text();
            if (!confirm("Do you want to delete " + event_name + "?")) {
                return false;
            }

            let bbzg_button = $('#save_calendar_group_delete');
            bbzg_button.html( 'Deleting ' + event_name + '...' );
            e.preventDefault();
            $.ajax({
                type : 'POST',
                url  : bbzec.ajaxurl,
                data : {
                    event_id: $('select#bp-group-calendar-event-category-delete').val(),
                    action: 'bbzec_delete_calendar_group_cat',
                    nonce:   bbzec.nonce
                },
                success: function(data){ /** */ bbzg_button.html( event_name + ' has been deleted.' ); setTimeout(() => { bbzg_button.html( 'Delete' ); location.reload(); }, 1500); /** */}
            });
        }
	});

    $('#bp-group-edit-calendar-group-event-submit-wrapper').detach().insertAfter('#main-bbze-button');
    $('#bp-group-edit-calendar-group-event-submit-wrapper').show();

});