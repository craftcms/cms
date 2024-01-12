(function ($) {
  /** global: Craft */
  /** global: Garnish */
  var $siteRows = $('#sites').children('tbody').children();
  var $lightswitches = $siteRows
    .children('th:nth-child(2)')
    .children('.lightswitch');
  var $singleHomepageCheckboxes = $siteRows.find('.single-homepage .checkbox');

  function updateSites() {
    $siteRows.each(function (i) {
      updateSite($(this), i);
    });
  }

  function updateSite($site, i) {
    // Is it disabled?
    var $lightswitch = $lightswitches.eq(i);
    if ($lightswitch.length) {
      const $tds = $lightswitch.parent().nextAll('td');
      const $inputs = $tds.find('textarea, input, .lightswitch');
      if (!$lightswitch.data('lightswitch').on) {
        $tds.addClass('disabled');
        $inputs.attr({
          tabindex: '-1',
          readonly: 'readonly',
        });
        $inputs.on('focus.preventFocus', (event) => {
          $(event.currentTarget).blur();
          event.preventDefault();
          event.stopPropagation();
        });
        return;
      }
      $tds.removeClass('disabled');
      $inputs.removeAttr('tabindex');
      $inputs.removeAttr('readonly');
      $inputs.off('focus.preventFocus');
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

  Garnish.$doc.ready(function () {
    updateSites();
  });
})(jQuery);
