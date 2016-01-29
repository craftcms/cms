
/**
 * Chart Date Range Picker
 */
Craft.DateRangePicker = Garnish.Base.extend(
{
    hud: null,
    value: null,
    presets: null,

    $startDateInput: null,
    $endDateInput: null,

    init: function(input, settings)
    {
        this.$input = input;

        this.setSettings(settings, Craft.DateRangePicker.defaults);

        this.value = this.settings.value;
        this.presets = this.settings.presets;

        this.startDate = this.presets[this.value].startDate;
        this.endDate = this.presets[this.value].endDate;

        var dateRangeValue = this.presets[this.value].label;
        this.$input.val(dateRangeValue);

        this.addListener(this.$input, 'focus', 'showHud');
    },

    showHud: function()
    {
        if (!this.hud)
        {
            var $hudBody = $('<div></div>');


            // presets

            var $presets = $('<div class="daterange-items" />').appendTo($hudBody);
            var $presetsUl = $('<ul />').appendTo($presets);

            $.each(this.presets, function(key, item)
            {
                if(key != 'customrange')
                {
                    $('<li><a class="item" data-value="'+key+'" data-label="'+item.label+'" data-start-date="'+item.startDate+'" data-end-date="'+item.endDate+'">'+item.label+'</a></li>').appendTo($presetsUl);
                }
            });


            // custom range

            var $customRange = $('<div class="daterange-items" />').appendTo($hudBody);
            var $customRangeUl = $('<ul />').appendTo($customRange);

            var customRangeItem = this.presets.customrange;
            $('<li><a class="item" data-value="customrange" data-label="'+customRangeItem.label+'">'+customRangeItem.label+'</a></li>').appendTo($customRangeUl);

            var $startDateWrapper = $('<div class="datewrapper"></div>').appendTo($customRange);
            this.$startDateInput = $('<input type="text" class="text" size="20" autocomplete="off" value="" />').appendTo($startDateWrapper);
            this.$startDateInput.datepicker();

            var $endDateWrapper = $('<div class="datewrapper"></div>').appendTo($customRange);
            this.$endDateInput = $('<input type="text" class="text" size="20" autocomplete="off" value="" />').appendTo($endDateWrapper);
            this.$endDateInput.datepicker();


            // initialize items

            this.$items = $('a.item', $hudBody);

            this.addListener(this.$items, 'click', 'selectItem');


            // default value

            if(this.value)
            {
                $.each(this.$items, $.proxy(function(key, item) {
                    var $item = $(item);
                    var value = $item.data('value');
                    var label = $item.data('label');
                    var startDate = ($item.data('start-date') != 'undefined' ? $item.data('start-date') : null);
                    var endDate = ($item.data('end-date') != 'undefined' ? $item.data('end-date') : null);

                    if(this.value == value)
                    {
                        $item.addClass('sel');

                        this.$input.val(label);

                        this.onAfterSelect(value, startDate, endDate);
                    }
                }, this));
            }


            // instiantiate hud

            this.hud = new Garnish.HUD(this.$input, $hudBody, {
                hudClass: 'hud daterange-hud',
                onSubmit: $.proxy(this, 'save')
            });
        }
        else
        {
            this.hud.show();
        }
    },

    selectItem: function(ev)
    {
        this.$items.removeClass('sel');

        var $item = $(ev.currentTarget);

        var label = $item.data('label');
        var value = $item.data('value');
        var startDate = $item.data('start-date');
        var endDate = $item.data('end-date');

        if(value == 'customrange')
        {
            startDate = this.$startDateInput.val();
            endDate = this.$endDateInput.val();
        }

        startDate = (startDate != 'undefined' ? startDate : null);
        endDate = (endDate != 'undefined' ? endDate : null);

        $item.addClass('sel');

        this.$input.val(label);

        this.hud.hide();

        this.onAfterSelect(value, startDate, endDate);
    },

    onAfterSelect: function(value, startDate, endDate)
    {
        this.settings.onAfterSelect(value, startDate, endDate);
    }
},
{
    defaults: {
        value: null,
        presets: {
            d7 : {
                label: 'Last 7 days',
                startDate: '-7 days'
            },
            d30: {
                label: 'Last 30 days',
                startDate: '-30 days'
            },
            lastweek: {
                label: 'Last Week',
                startDate: '-14 days',
                endDate: '-7 days',
            },
            lastmonth: {
                label: 'Last Month',
                startDate: '-60 days',
                endDate: '-30 days',
            },

            customrange: {
                label: 'Custom Range',
            }
        },
        onAfterSelect: $.noop
    }
});
