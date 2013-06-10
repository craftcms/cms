// define the Assets global
if (typeof Assets == 'undefined')
{
    Assets = {};
}


/**
 * File Manager.
 */
Assets.ProgressBar = Garnish.Base.extend({

    $uploadProgress: null,
    $uploadProgressBar: null,

    _itemCount: 0,
    _processedItemCount: 0,


    init: function($element)
    {
        this.$uploadProgress = $element;
        this.$uploadProgressBar = $('.assets-pb-bar', this.$uploadProgress);

        this.resetProgressBar();
    },

    /**
     * Reset the progress bar
     */
    resetProgressBar: function ()
    {
        // Set it to 1 so that 0 is not 100%
        this.setItemCount(1);
        this.setProcessedItemCount(0);
        this.updateProgressBar();
    },

    /**
     * Fade to invisible, hide it using a class and reset opacity to visible
     */
    hideProgressBar: function ()
    {
        this.$uploadProgress.fadeTo('fast', 0.01, $.proxy(function() {
            this.$uploadProgress.addClass('hidden').fadeTo(1, 1, function () {});
        }, this));
    },

    showProgressBar: function ()
    {
        this.$uploadProgress.removeClass('hidden');
    },

    setItemCount: function (count)
    {
        this._itemCount = count;
    },

    incrementItemCount: function (count)
    {
        this._itemCount += count;
    },

    setProcessedItemCount: function (count)
    {
        this._processedItemCount = count;
    },

    incrementProcessedItemCount: function (count)
    {
        this._processedItemCount += count;
    },

    updateProgressBar: function ()
    {
        // Only fools would allow accidental division by zero.
        this._processedItemCount = Math.max(this._processedItemCount, 1);
        this._itemCount = Math.max(this._itemCount, 1);

        var width = Math.min(100, Math.round(100 * this._processedItemCount / this._itemCount));

        this.setProgressPercentage(width);
    },

    setProgressPercentage: function (percentage)
    {
        this.$uploadProgressBar.width(percentage + '%');
    }
});