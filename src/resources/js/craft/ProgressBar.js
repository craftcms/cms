/**
 * File Manager.
 */
Craft.ProgressBar = Garnish.Base.extend(
{
    $progressBar: null,
    $innerProgressBar: null,

    _itemCount: 0,
    _processedItemCount: 0,

    init: function($element)
    {
		this.$progressBar = $('<div class="progressbar pending hidden"/>').appendTo($element);
		this.$innerProgressBar = $('<div class="progressbar-inner"/>').appendTo(this.$progressBar);

        this.resetProgressBar();
    },

    /**
     * Reset the progress bar
     */
    resetProgressBar: function()
    {
		// Since setting the progress percentage implies that there is progress to be shown
		// It removes the pending class - we must add it back.
		this.setProgressPercentage(100);
		this.$progressBar.addClass('pending');

		// Reset all the counters
		this.setItemCount(1);
        this.setProcessedItemCount(0);
    },

    /**
     * Fade to invisible, hide it using a class and reset opacity to visible
     */
    hideProgressBar: function()
    {
        this.$progressBar.fadeTo('fast', 0.01, $.proxy(function() {
            this.$progressBar.addClass('hidden').fadeTo(1, 1, $.noop);
        }, this));
    },

    showProgressBar: function()
    {
        this.$progressBar.removeClass('hidden');
    },

    setItemCount: function(count)
    {
        this._itemCount = count;
    },

    incrementItemCount: function(count)
    {
        this._itemCount += count;
    },

    setProcessedItemCount: function(count)
    {
        this._processedItemCount = count;
    },

    incrementProcessedItemCount: function(count)
    {
        this._processedItemCount += count;
    },

    updateProgressBar: function()
    {
        // Only fools would allow accidental division by zero.
        this._itemCount = Math.max(this._itemCount, 1);

        var width = Math.min(100, Math.round(100 * this._processedItemCount / this._itemCount));

        this.setProgressPercentage(width);
    },

    setProgressPercentage: function(percentage, animate)
    {
		if (percentage == 0)
		{
			this.$progressBar.addClass('pending');
		}
		else
		{
			this.$progressBar.removeClass('pending');

            if (animate)
            {
                this.$innerProgressBar.velocity('stop').velocity({ width: percentage+'%' }, 'fast');
            }
            else
            {
                this.$innerProgressBar.velocity('stop').width(percentage+'%');
            }
		}
    }
});
