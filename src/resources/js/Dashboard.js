/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.resources
 */

(function($) {


/**
 * Dashboard class
 */
Craft.Dashboard = Garnish.Base.extend(
{
    $widgets: [],
    settings: null,
    manageWidgetHud: null,
    widgetSettingsModal: null,

    init: function(settings)
    {
        this.settings = settings;

        this.$widgets = $('.widget');

        this.$newWidgetMenu = $('#newwidgetmenu');
        this.$newWidgetMenuItems = $('#newwidgetmenu li');
        this.$manageWidgetsTrigger = $('#managewidgetstrigger');

        this.addListener(this.$newWidgetMenuItems, 'click', 'newWidget');
        this.addListener(this.$manageWidgetsTrigger, 'click', 'showManageWidgetsHud');
    },

    showManageWidgetsHud: function()
    {
        if(!this.manageWidgetHud)
        {
            if(typeof(this.settings.manageHtml) != 'undefined')
            {
                this.manageHtml = this.settings.manageHtml
            }

            var $form = $(
                    '<form class="managewidgetshud" method="post" accept-charset="UTF-8">' +
                        Craft.getCsrfInput() +
                        '<input type="hidden" name="action" value="widgets/saveWidget"/>' +
                        '<input type="hidden" name="redirect" value="'+(Craft.edition == Craft.Pro ? 'users' : 'dashboard')+'"/>' +
                    '</form>'
                ).appendTo(Garnish.$bod);

            $(this.manageHtml).appendTo($form);

            this.manageWidgetHud = new Garnish.HUD(this.$manageWidgetsTrigger, $form);

            new Craft.AdminTable({
                tableSelector: '#widgets',
                noObjectsSelector: '#nowidgets',
                sortable: true,
                reorderAction: 'dashboard/reorderUserWidgets',
                deleteAction: 'dashboard/deleteUserWidget',
                onAfterReorderObjects: $.proxy(function(ids)
                {
                    var $widgets = [];

                    $.each(this.$widgets, function(k, widget) {

                        var $widget = $(widget);

                        $widgets[$widget.data('id')] = $widget;

                        $widget.detach();
                    });

                    $.each(ids, function(k, id) {
                        $('#main > .grid').append($widgets[id]);
                    });

                    // $('#main .grid').data('grid').$items = $('.item');
                    // $('#main .grid').data('grid').setItems();
                    // $('#main .grid').data('grid').refreshCols(true);

                }, this)
            });

            Craft.initUiElements($form);
        }
        else
        {
            this.manageWidgetHud.show();
        }
    },

    newWidget: function(ev)
    {
        this.$newWidgetMenu.hide();

        var item = $(ev.currentTarget);
        var widgetType = item.data('widget-type');

        if(!this.widgetSettingsModal)
        {
            this.widgetSettingsModal = new Craft.WidgetSettingsModal(this.settings);
            this.widgetSettingsModal.setWidgetType(widgetType);

        }
        else
        {
            this.widgetSettingsModal.setWidgetType(widgetType);
            this.widgetSettingsModal.show();
        }
    }
});


/**
 * Dashboard Widget Settings Modal class
 */
Craft.WidgetSettingsModal = Garnish.Modal.extend(
{
    settingsHtml: null,

    init: function(settings)
    {
        if(typeof(settings.settingsHtml) != 'undefined')
        {
            this.settingsHtml = settings.settingsHtml
        }

        var $form = $(
                '<form class="modal fitted widgetsettingsmodal" method="post" accept-charset="UTF-8">' +
                    Craft.getCsrfInput() +
                    '<input type="hidden" name="action" value="widgets/saveWidget"/>' +
                    '<input type="hidden" name="redirect" value="'+(Craft.edition == Craft.Pro ? 'users' : 'dashboard')+'"/>' +
                '</form>'
            ).appendTo(Garnish.$bod),

            $body = $(
                '<div class="body">' +
                this.settingsHtml +
                '</div>'
            ).appendTo($form),

            $footer = $('<div class="footer"/>').appendTo($form),
            $buttons = $('<div class="buttons right"/>').appendTo($footer),
            $cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
            $saveBtn = $('<input type="submit" class="btn submit" value="'+Craft.t('Save')+'" />').appendTo($buttons);

        Craft.initUiElements($form);

        this.$typeSelect = $('#type');

        this.addListener($cancelBtn, 'click', $.proxy(function() {
            this.hide();
        }, this));

        this.base($form);
    },

    setWidgetType: function(widgetType)
    {
        this.$typeSelect.val(widgetType);
        this.$typeSelect.trigger('change');
    }
});

/**
 * Dashboard Widget class
 */
Craft.Widget = Garnish.Base.extend(
{
    editModal: null,

    init: function(widget, settings)
    {
        this.settings = settings;

        this.$widget = $(widget);
        this.$settingsBtn = $('.settings', this.$widget);

        this.addListener(this.$settingsBtn, 'click', 'editWidget');
    },

    editWidget: function()
    {
        if(!this.editModal)
        {
            var $form = $(
                    '<form class="modal fitted widgetsettingsmodal" method="post" accept-charset="UTF-8">' +
                        Craft.getCsrfInput() +
                        '<input type="hidden" name="action" value="widgets/saveWidget"/>' +
                        '<input type="hidden" name="redirect" value="dashboard"/>' +
                    '</form>'
                ).appendTo(Garnish.$bod),

                $body = $(
                    '<div class="body">' +
                    this.settings.settingsHtml +
                    '</div>'
                ).appendTo($form),

                $footer = $('<div class="footer"/>').appendTo($form),
                $buttons = $('<div class="buttons right"/>').appendTo($footer),
                $cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
                $saveBtn = $('<input type="submit" class="btn submit" value="'+Craft.t('Save')+'" />').appendTo($buttons);

            this.editModal = new Garnish.Modal($form, {});

            this.addListener($cancelBtn, 'click', $.proxy(function() {
                this.editModal.hide();
            }, this));

            Craft.initUiElements($form);
        }
        else
        {
            this.editModal.show();
        }
    }
});

})(jQuery)
