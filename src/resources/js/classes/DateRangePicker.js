
/**
 * Chart Date Range Picker
 */
Craft.DateRangePicker = Garnish.Base.extend(
{
    hud: null,
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

    init: function(container, options)
    {
        this.$container = container;

        this.options = options;

        this.value = options.value;

        this.startDate = this.presets[this.value].startDate;
        this.endDate = this.presets[this.value].endDate;

        this.$dateRangeWrapper = $('<div class="datewrapper"></div>');
        this.$dateRangeWrapper.appendTo(this.$container);

        var dateRangeValue = this.presets[this.value].label;

        this.$dateRange = $('<input type="text" class="text" size="20" autocomplete="off" value="'+dateRangeValue+'" />');
        this.$dateRange.appendTo(this.$dateRangeWrapper);

        this.addListener(this.$dateRange, 'focus', 'showHud');
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
            var $startDateInput = $('<input type="text" class="text" size="20" autocomplete="off" value="" />').appendTo($startDateWrapper);
            $startDateInput.datepicker();

            var $endDateWrapper = $('<div class="datewrapper"></div>').appendTo($customRange);
            var $endDateInput = $('<input type="text" class="text" size="20" autocomplete="off" value="" />').appendTo($endDateWrapper);
            $endDateInput.datepicker();


            // items

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

                        this.$dateRange.val(label);

                        this.onAfterSelect(value, startDate, endDate);
                    }
                }, this));
            }


            // instiantiate hud

            this.hud = new Garnish.HUD(this.$dateRange, $hudBody, {
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
            startDate = $startDateInput.val();
            endDate = $endDateInput.val();
        }

        startDate = (startDate != 'undefined' ? startDate : null);
        endDate = (endDate != 'undefined' ? endDate : null);

        $item.addClass('sel');

        this.$dateRange.val(label);

        this.hud.hide();

        this.onAfterSelect(value, startDate, endDate);
    },

    onAfterSelect: function(value, startDate, endDate)
    {
        if(typeof(this.options.onAfterSelect) == 'function')
        {
            this.options.onAfterSelect(value, startDate, endDate);
        }
    }
});



/**
 * Chart Date Range Picker Basic
 */
Craft.DateRangePickerBasic = Garnish.Base.extend(
{
    init: function(container, options)
    {
        this.$container = container;
        this.options = options;

        this.startDate = options.startDate;
        this.endDate = options.endDate;

        this.$dateRangeWrapper = $('<div class="datewrapper"></div>');
        this.$dateRangeWrapper.appendTo(this.$container);

        var dateRangeString =
            (this.startDate.getMonth() + 1)+'/'+this.startDate.getDate()+'/'+this.startDate.getFullYear()+
            '-'+
            (this.endDate.getMonth() + 1)+'/'+this.endDate.getDate()+'/'+this.endDate.getFullYear();

        this.$dateRange = $('<input type="text" class="text" size="20" autocomplete="off" value="'+dateRangeString+'" />');
        this.$dateRange.appendTo(this.$dateRangeWrapper);


        // var cur = -1, prv = -1;

        var cur = this.startDate.getTime(), prv = this.endDate.getTime();
        var selectionStarted = false;

        this.$dateRange.datepicker($.extend({

            // defaultDate: new Date(2015, 11, 2),

            beforeShowDay: function ( date )
            {
                return [true, ( (date.getTime() >= Math.min(prv, cur) && date.getTime() <= Math.max(prv, cur)) ? 'date-range-selected' : '')];
            },

            onSelect: $.proxy(function ( dateText, inst )
            {
                inst.inline = true;

                prv = cur;
                cur = (new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay)).getTime();

                if ( prv == -1 || prv == cur || !selectionStarted)
                {
                    selectionStarted = true;
                    prv = cur;
                    this.$dateRange.val( dateText );
                }
                else
                {
                    this.startDate = $.datepicker.formatDate( 'mm/dd/yy', new Date(Math.min(prv,cur)), {} );
                    this.endDate = $.datepicker.formatDate( 'mm/dd/yy', new Date(Math.max(prv,cur)), {} );
                    this.$dateRange.val( this.startDate+' - '+this.endDate );
                    this.$dateRange.datepicker('hide');

                    selectionStarted = false;
                    // cur = -1, prv = -1;

                    this.onAfterSelect();
                }


            }, this),

            onClose: $.proxy(function ( dateText, inst )
            {
                inst.inline = false;
            }, this),

        }, Craft.datepickerOptions));
    },

    onAfterSelect: function()
    {
        if(typeof(this.options.onAfterSelect) == 'function')
        {
            this.options.onAfterSelect();
        }
    },
});