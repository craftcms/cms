/**
 * Entry index class
 */
Craft.EntryIndex = Craft.BaseElementIndex.extend(
{
	$newEntryBtnGroup: null,
	$newEntryMenuBtn: null,
	newEntryLabel: null,

	onAfterHtmlInit: function()
	{
		// Figure out if there are multiple sections that entries can be created in
		this.$newEntryBtnGroup = this.$sidebar.find('> .buttons > .btngroup');

		if (this.$newEntryBtnGroup.length)
		{
			this.$newEntryMenuBtn = this.$newEntryBtnGroup.children('.menubtn');
			this.newEntryLabel = this.$newEntryMenuBtn.text();
		}

		this.base();
	},

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
			// Update the URI
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

			// Update the New Entry button
			if (handle == 'singles' || !handle)
			{
				if (this.$newEntryBtn)
				{
					this.$newEntryBtn.remove();
					this.$newEntryBtn = null;
					this.$newEntryMenuBtn.addClass('add icon').text(this.newEntryLabel);
				}
			}
			else
			{
				if (this.$newEntryBtn)
				{
					this.$newEntryBtn.remove();
				}
				else
				{
					this.$newEntryMenuBtn.removeClass('add icon').text('');
				}

				this.$newEntryBtn = $('<a class="btn submit add icon"/>').text(this.newEntryLabel).prependTo(this.$newEntryBtnGroup);
				this.$newEntryBtn.attr('href', Craft.getUrl('entries/'+handle+'/new'));
			}
		}

		this.base();
	}

});

// Register it!
Craft.registerElementIndexClass('Entry', Craft.EntryIndex);
