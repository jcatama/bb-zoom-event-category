jQuery(document).ready(function($) {

    $('.zoom-group-calendar').on('click', function(e) {

        let group_calendar_slug = ( $( this )[0].classList[2]).trim();
        window.location = bbzec.home_url + '/events/category/' + group_calendar_slug;

    });

});