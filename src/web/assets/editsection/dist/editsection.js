(function($) {
    /** global: Craft */
    /** global: Garnish */
    var $siteRows = $('#sites').children('tbody').children();
    var $lightswitches = $siteRows.children('th:nth-child(2)').children('.lightswitch');

    function updateSites() {
        $lightswitches.each(function() {
            if ($(this).data('lightswitch').on) {
                $(this).parent().nextAll('td').removeClass('disabled').find('textarea,div.lightswitch,input').attr('tabindex', '0');
            } else {
                $(this).parent().nextAll('td').addClass('disabled').find('textarea,div.lightswitch,input').attr('tabindex', '-1');
            }
        });
    }

    $lightswitches.on('change', updateSites);

    Garnish.$doc.ready(function() {
        updateSites();
    });
})(jQuery);
