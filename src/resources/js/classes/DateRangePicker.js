
/**
 * Chart Date Range Picker
 */
Craft.DateRangePicker = Garnish.Base.extend(
{
    hud: null,

    init: function(container, options)
    {
        this.$container = container;
        this.options = options;

        this.startDate = options.startDate;
        this.endDate = options.endDate;

        this.$dateRangeWrapper = $('<div class="datewrapper"></div>');
        this.$dateRangeWrapper.appendTo(this.$container);

        var dateRangeValue = (this.startDate.getMonth() + 1)+'/'+this.startDate.getDate()+'/'+this.startDate.getFullYear()+'-'+(this.endDate.getMonth() + 1)+'/'+this.endDate.getDate()+'/'+this.endDate.getFullYear();

        this.$dateRange = $('<input type="text" class="text" size="20" autocomplete="off" value="'+dateRangeValue+'" />');
        this.$dateRange.appendTo(this.$dateRangeWrapper);

        this.addListener(this.$dateRange, 'focus', 'showHud');
    },

    showHud: function()
    {
        if (!this.hud)
        {
            var $hudBody = $('<div class="daterange-hud"></div>');

            // Presets

            var menu = [
                'Last 7 days',
                'Last 30 days',
                'Last 90 days',
                'Last Week',
                'Last Month',
            ];

            var $presets = $('<div class="daterange-items" />').appendTo($hudBody);
            var $presetsUl = $('<ul />').appendTo($presets);

            $.each(menu, function(key, item)
            {
                $('<li><a class="item" data-preset="'+key+'">'+item+'</a></li>').appendTo($presetsUl);
            });




            // HR

            $('<hr />').appendTo($hudBody);


            // Custom Range

            var $customRange = $('<div class="daterange-items" />').appendTo($hudBody);

            $('<ul><li><a class="item">Custom Range</a></li></ul>').appendTo($customRange);

            var $startDateWrapper = $('<div class="datewrapper"></div>').appendTo($customRange);
            var $startDateInput = $('<input type="text" class="text" size="20" autocomplete="off" value="" />').appendTo($startDateWrapper);
            $startDateInput.datepicker();

            var $endDateWrapper = $('<div class="datewrapper"></div>').appendTo($customRange);
            var $endDateInput = $('<input type="text" class="text" size="20" autocomplete="off" value="" />').appendTo($endDateWrapper);
            $endDateInput.datepicker();


            // items

            var $items = $('a.item', $hudBody);

            this.addListener($items, 'click', function(ev)
            {
                $items.removeClass('sel');

                $(ev.currentTarget).addClass('sel');
            });



            this.hud = new Garnish.HUD(this.$dateRange, $hudBody, {
                onSubmit: $.proxy(this, 'save')
            });
        }
        else
        {
            this.hud.show();
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