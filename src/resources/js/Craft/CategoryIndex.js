/**
 * Category index class
 */
Craft.CategoryIndex = Craft.BaseElementIndex.extend(
{
	$newCategoryBtn: null,

	onAfterHtmlInit: function()
	{
		// Get the New Category button
		this.$newCategoryBtn = this.$sidebar.find('> .buttons > .btn');

		this.base();
	},

	getDefaultSourceKey: function()
	{
		// Did they request a specific category group in the URL?
		if (this.settings.context == 'index' && typeof defaultGroupHandle != typeof undefined)
		{
			for (var i = 0; i < this.$sources.length; i++)
			{
				var $source = $(this.$sources[i]);

				if ($source.data('handle') == defaultGroupHandle)
				{
					return $source.data('key');
				}
			}
		}

		return this.base();
	},

	onSelectSource: function()
	{
		if (this.settings.context == 'index')
		{
			// Get the handle of the selected source
			var handle = this.$source.data('handle');

			// Update the URL
			if (typeof history != typeof undefined)
			{
				var uri = 'categories';

				if (handle)
				{
					uri += '/'+handle;
				}

				history.replaceState({}, '', Craft.getUrl(uri));
			}

			// Update the New Category button
			this.$newCategoryBtn.attr('href', Craft.getUrl('categories/'+handle+'/new'));
		}

		this.base();
	}

});

// Register it!
Craft.registerElementIndexClass('Category', Craft.CategoryIndex);
