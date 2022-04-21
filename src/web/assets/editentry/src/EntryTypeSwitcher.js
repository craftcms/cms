(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.EntryTypeSwitcher = Garnish.Base.extend({
    $typeSelect: null,
    $spinner: null,

    init: function () {
      this.$typeSelect = $('#entryType');
      this.$spinner = $('<div class="spinner hidden"/>').insertAfter(
        this.$typeSelect.parent()
      );

      this.addListener(this.$typeSelect, 'change', 'onTypeChange');
    },

    onTypeChange: function (ev) {
      this.$spinner.removeClass('hidden');

      Craft.postActionRequest(
        'entries/switch-entry-type',
        Craft.cp.$primaryForm.serialize(),
        (response, textStatus) => {
          this.$spinner.addClass('hidden');

          if (textStatus === 'success') {
            this.trigger('beforeTypeChange');

            const $tabs = $('#tabs');

            if (response.tabsHtml) {
              if ($tabs.length) {
                $tabs.replaceWith(response.tabsHtml);
              } else {
                const $contentHeader = $('<header/>', {
                  id: 'content-header',
                  class: 'pane-header',
                }).prependTo($('#content'));
                $(response.tabsHtml)
                  .appendTo($contentHeader)
                  .attr('id', 'tabs');
              }
            } else {
              $('#content-header').remove();
            }

            Craft.cp.initTabs();

            $('#fields').html(response.fieldsHtml);
            Craft.initUiElements($('#fields'));
            Craft.appendHeadHtml(response.headHtml);
            Craft.appendFootHtml(response.bodyHtml);

            // Update the slug generator with the new title input
            if (typeof slugGenerator !== 'undefined') {
              slugGenerator.setNewSource('#title');
            }

            this.trigger('typeChange');

            // Trigger an autosave with the new form data
            window.draftEditor.checkForm();
          }
        }
      );
    },
  });
})(jQuery);
