(function($) {
    /** global: Craft */
    /** global: Garnish */
    var $siteRows = $('#sites').children('tbody').children();
    var $lightswitches = $siteRows.children('th:nth-child(2)').children('.lightswitch');
    var $singleHomepageCheckboxes = $siteRows.find('.single-homepage .checkbox');

    function updateSites() {
        $siteRows.each(function(i) {
            updateSite($(this), i);
        });
    }

    function updateSite($site, i) {
        // Is it disabled?
        var $lightswitch = $lightswitches.eq(i);
        if ($lightswitch.length) {
            if (!$lightswitch.data('lightswitch').on) {
                $lightswitch.parent().nextAll('td').addClass('disabled').find('textarea,div.lightswitch,input').attr('tabindex', '-1');
                return;
            }
            $lightswitch.parent().nextAll('td').removeClass('disabled').find('textarea,div.lightswitch,input').attr('tabindex', '0');
        }

        // If it's a single, make sure the URI is enabled/disabled per the homepage checkbox
        var $checkbox = $site.children('.single-homepage').find('.checkbox');
        var $uriCell = $site.children('.single-uri');

        if ($checkbox.prop('checked')) {
            $uriCell.addClass('disabled').find('textarea').attr('tabindex', '-1');
        } else {
            $uriCell.removeClass('disabled').find('textarea').attr('tabindex', '0');
        }
    }

    $lightswitches.on('change', updateSites);
    $singleHomepageCheckboxes.on('change', updateSites);

    Garnish.$doc.ready(function() {
        updateSites();
    });
})(jQuery);
