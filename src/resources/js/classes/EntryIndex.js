/**
 * Entry index class
 */
Craft.EntryIndex = Craft.BaseElementIndex.extend(
{
	getDefaultSourceKey: function()
	{
		if (this.settings.context == 'index' && typeof defaultSectionHandle != 'undefined')
		{
			if (defaultSectionHandle == 'singles')
			{
				return 'singles';
			}
			else
			{
				for (var i = 0; i < this.$sources.length; i++)
				{
					var $source = $(this.$sources[i]);

					if ($source.data('handle') == defaultSectionHandle)
					{
						return $source.data('key');
					}
				}
			}
		}

		return this.base();
	},

	onSelectSource: function()
	{
		if (this.settings.context == 'index' && typeof history != 'undefined')
		{
			if (this.$source.data('key') == 'singles')
			{
				var handle = 'singles';
			}
			else
			{
				var handle = this.$source.data('handle');
			}

			var uri = 'entries';

			if (handle)
			{
				uri += '/'+handle;
			}

			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	}

});

// Register it!
Craft.registerElementIndexClass('Entry', Craft.EntryIndex);
