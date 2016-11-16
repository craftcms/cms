
/**
 * Chart Date Range Picker
 */
Craft.DateRangePicker = Garnish.Base.extend(
{
    hud: null,
    value: null,
    presets: null,

    startDate: null,

    $startDateInput: null,
    $endDateInput: null,

    init: function(trigger, settings)
    {
        this.$trigger = trigger;

        this.setSettings(settings, Craft.DateRangePicker.defaults);

        if(this.settings.customRangeStartDate)
        {
            this.customRangeStartDate = new Date(this.settings.customRangeStartDate);
        }

        if(this.settings.customRangeEndDate)
        {
            this.customRangeEndDate = new Date(this.settings.customRangeEndDate);
        }

        this.value = this.settings.value;
        this.presets = this.settings.presets;


        if(this.value == 'customrange')
        {
            this.startDate = this.customRangeStartDate;
            this.endDate = this.customRangeEndDate;
        }
        else
        {
            this.startDate = this.presets[this.value].startDate;
            this.endDate = this.presets[this.value].endDate;
        }


        var dateRangeValue = this.presets[this.value].label;
        this.$trigger.data('value', dateRangeValue);

        this.addListener(this.$trigger, 'click', 'showHud');
    },

    getStartDate: function()
    {
        return this.startDate;
    },

    getEndDate: function()
    {
        return this.endDate;
    },

    showHud: function()
    {
        this.$trigger.addClass('active');

        if (!this.hud)
        {
            this.createHud();

            // default value

            if(this.value)
            {
                var $item = this.$items.filter('[data-value='+this.value+']');
                var value = $item.data('value');
                var label = $item.data('label');
                var startDate = ($item.data('start-date') != 'undefined' ? $item.data('start-date') : null);
                var endDate = ($item.data('end-date') != 'undefined' ? $item.data('end-date') : null);

                $item.addClass('sel');

                this.$trigger.data('value', label);
            }
        }
        else
        {
            this.hud.show();
        }
    },

    createHud: function()
    {
        this.$hudBody = $('<div></div>');

        this.createPresets();

        $('<hr />').appendTo(this.$hudBody);

        this.createCustomRangeFields();


        // initialize items

        this.$items = $('a.item', this.$hudBody);

        this.addListener(this.$items, 'click', 'selectItem');

        // instiantiate hud
        this.hud = new Garnish.HUD(this.$trigger, this.$hudBody, {
            hudClass: 'hud daterange-hud',
            onSubmit: $.proxy(this, 'save'),
            onShow: $.proxy(function()
            {
                this.$trigger.addClass('active');
            }, this),
            onHide: $.proxy(function()
            {
                this.$trigger.removeClass('active');
            }, this)
        });
    },

    createPresets: function()
    {
        var $presets = $('<div class="daterange-items" />').appendTo(this.$hudBody);
        var $presetsUl = $('<ul />').appendTo($presets);

        $.each(this.presets, function(key, item)
        {
            if(key != 'customrange')
            {
                $('<li><a class="item" data-value="'+key+'" data-label="'+item.label+'" data-start-date="'+item.startDate+'" data-end-date="'+item.endDate+'">'+item.label+'</a></li>').appendTo($presetsUl);
            }
        });
    },

    createCustomRangeFields: function()
    {
        var $customRange = $('<div class="daterange-items" />').appendTo(this.$hudBody),
            $customRangeUl = $('<ul />').appendTo($customRange),
            $customRangeLi = $('<li />').appendTo($customRangeUl),
            $customRangeLink = $('<a class="item" data-value="customrange" data-label="'+this.presets.customrange.label+'">'+this.presets.customrange.label+'</a>').appendTo($customRangeLi);

        var $dateRangeFields =  $('<div class="daterange-fields"></div>').appendTo($customRange),
            $startDateWrapper = $('<div class="datewrapper"></div>').appendTo($dateRangeFields),
            $endDateWrapper = $('<div class="datewrapper"></div>').appendTo($dateRangeFields);

        // custom range startDate

        if(!this.customRangeStartDate)
        {
            var date = new Date();
            date = date.getTime() - (60 * 60 * 24 * 7 * 1000);
            this.customRangeStartDate = new Date(date);
        }

        this.$startDateInput = $('<input type="text" value="'+Craft.formatDate(this.customRangeStartDate)+'" class="text" size="20" autocomplete="off" value="" />').appendTo($startDateWrapper);
        this.$startDateInput.datepicker($.extend({
            onClose: $.proxy(function(dateText, inst)
            {
                this.$items.removeClass('sel');
                $('[data-value=customrange]').addClass('sel');

                var selectedDate = new Date(inst.currentYear, inst.currentMonth, inst.currentDay);

                this.customRangeStartDate = selectedDate;

                if(selectedDate.getTime() > this.customRangeEndDate.getTime())
                {
                    // if selectedDate > endDate, set endDate at selectedDate plus 7 days
                    var newEndDate = selectedDate.getTime() + (60 * 60 * 24 * 7 * 1000);
                    newEndDate = new Date(newEndDate);
                    this.customRangeEndDate = newEndDate;
                    this.$endDateInput.val(Craft.formatDate(this.customRangeEndDate));
                }

                this.showCustomRangeApplyButton();

            }, this)
        }, Craft.datepickerOptions));

        // custom range endDate

        if(!this.customRangeEndDate)
        {
            this.customRangeEndDate = new Date();
        }

        this.$endDateInput = $('<input type="text" value="'+Craft.formatDate(this.customRangeEndDate)+'" class="text" size="20" autocomplete="off" value="" />').appendTo($endDateWrapper);
        this.$endDateInput.datepicker($.extend({
            onClose: $.proxy(function(dateText, inst)
            {
                this.$items.removeClass('sel');
                $('[data-value=customrange]').addClass('sel');

                var selectedDate = new Date(inst.currentYear, inst.currentMonth, inst.currentDay);

                this.customRangeEndDate = selectedDate;

                if(selectedDate.getTime() < this.customRangeStartDate.getTime())
                {
                    // if selectedDate < startDate, set startDate at selectedDate minus 7 days
                    var newStartDate = selectedDate.getTime() - (60 * 60 * 24 * 7 * 1000);
                    newStartDate = new Date(newStartDate);
                    this.customRangeStartDate = newStartDate;
                    this.$startDateInput.val(Craft.formatDate(this.customRangeStartDate));
                }

                this.showCustomRangeApplyButton();

            }, this)
        }, Craft.datepickerOptions));
    },

    selectItem: function(ev)
    {
        var $item = $(ev.currentTarget);
        this._selectItem($item);
    },

    _selectItem: function($item)
    {
        this.$items.removeClass('sel');

        var label = $item.data('label');
        var value = $item.data('value');

        if(value != 'customrange')
        {
            this.startDate = $item.data('start-date');
            this.endDate = $item.data('end-date');
        }
        else
        {
            this.startDate = this.customRangeStartDate;
            this.endDate = this.customRangeEndDate;
        }

        this.startDate = (this.startDate != 'undefined' ? this.startDate : null);
        this.endDate = (this.endDate != 'undefined' ? this.endDate : null);

        $item.addClass('sel');

        this.$trigger.data('value', label);

        this.hud.hide();

        this.hideCustomRangeApplyButton();

        this.onAfterSelect(value, this.startDate, this.endDate, this.customRangeStartDate, this.customRangeEndDate);
    },

    hideCustomRangeApplyButton: function()
    {
        if(this.$applyBtn)
        {
            this.$applyBtn.parent().addClass('hidden');
        }
    },

    showCustomRangeApplyButton: function()
    {
        if(!this.$applyBtn)
        {
            var $buttons = $('<div class="buttons" />').appendTo(this.$hudBody);
            this.$applyBtn = $('<input type="button" class="btn" value="'+Craft.t('Apply')+'" />').appendTo($buttons);

            this.addListener(this.$applyBtn, 'click', 'applyCustomRange');
        }
        else
        {
            this.$applyBtn.parent().removeClass('hidden');
        }
    },

    applyCustomRange: function()
    {
        var $item = this.$items.filter('[data-value=customrange]');

        this._selectItem($item);
    },

    onAfterSelect: function(value, startDate, endDate, customRangeStartDate, customRangeEndDate)
    {
        this.settings.onAfterSelect(value, startDate, endDate, customRangeStartDate, customRangeEndDate);
    }
},
{
    defaults: {
        value: null,
        presets: {
            d7 : {
                label: Craft.t('Last 7 days'),
                startDate: '-7 days'
            },
            d30: {
                label: Craft.t('Last 30 days'),
                startDate: '-30 days'
            },
            lastweek: {
                label: Craft.t('Last Week'),
                startDate: '-14 days',
                endDate: '-7 days',
            },
            lastmonth: {
                label: Craft.t('Last Month'),
                startDate: '-60 days',
                endDate: '-30 days',
            },

            customrange: {
                label: Craft.t('Custom Range'),
            }
        },
        customRangeStartDate: null,
        customRangeEndDate: null,
        onAfterSelect: $.noop
    }
});
