jQuery(document).ready(function($) {

    $('.zoom-group-calendar').on('click', function(e) {

        let group_calendar_slug = ( $( this )[0].classList[2]).trim();
        //let ttoday = new Date();
        //ttoday.setDate(ttoday.getDate()-1);
        //let ddate = ttoday.getFullYear()+'-'+(ttoday.getMonth()+1)+'-'+ttoday.getDate();
        //window.open( bbzec.home_url + '/events/category/' + group_calendar_slug + '?tribe-bar-date=' + ddate, '_blank');
        window.open( bbzec.home_url + '/events/category/' + group_calendar_slug, '_blank');

    });

});