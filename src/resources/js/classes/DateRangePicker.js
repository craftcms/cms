
/**
 * Chart Date Range Picker
 */
Craft.DateRangePicker = Garnish.Base.extend(
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