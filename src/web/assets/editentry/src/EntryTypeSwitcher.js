(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.EntryTypeSwitcher = Garnish.Base.extend({
        $typeSelect: null,
        $spinner: null,

        init: function() {
            this.$typeSelect = $('#entryType');
            this.$spinner = $('<div class="spinner hidden"/>').insertAfter(this.$typeSelect.parent());

            this.addListener(this.$typeSelect, 'change', 'onTypeChange');
        },

        onTypeChange: function(ev) {
            this.$spinner.removeClass('hidden');

            Craft.postActionRequest('entries/switch-entry-type', Craft.cp.$primaryForm.serialize(), $.proxy(function(response, textStatus) {
                this.$spinner.addClass('hidden');

                if (textStatus === 'success') {
                    this.trigger('beforeTypeChange');

                    var $tabs = $('#tabs');
                    if (response.tabsHtml) {
                        if ($tabs.length) {
                            $tabs.replaceWith(response.tabsHtml);
                        } else {
                            $(response.tabsHtml).insertBefore($('#content'))
                        }
                        Craft.cp.$mainContent.addClass('has-tabs');
                    } else {
                        $tabs.remove();
                        Craft.cp.$mainContent.removeClass('has-tabs');
                    }

                    $('#fields').html(response.fieldsHtml);
                    Craft.initUiElements($('#fields'));
                    Craft.appendHeadHtml(response.headHtml);
                    Craft.appendFootHtml(response.bodyHtml);

                    // Update the slug generator with the new title input
                    if (typeof slugGenerator !== 'undefined') {
                        slugGenerator.setNewSource('#title');
                    }

                    Craft.cp.initTabs();

                    this.trigger('typeChange');
                }
            }, this));
        }
    });
})(jQuery);
