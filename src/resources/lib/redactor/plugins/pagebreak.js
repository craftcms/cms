if (!RedactorPlugins) var RedactorPlugins = {};

RedactorPlugins.pagebreak = function()
{
	return {
		init: function()
		{
			var $btn = this.button.add('pagebreak', 'Insert Page Break');
			this.button.addCallback($btn, this.pagebreak.insertPageBreak);
		},

		insertPageBreak: function()
		{
			var $pagebreakNode = $('<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />'),
				$currentNode = $(this.selection.getCurrent());

			if ($currentNode.length && !$currentNode.is('div.redactor_editor'))
			{
				// Find the closest element to div.redactor_editor
				while ($currentNode.parent().length && !$currentNode.parent().is('div.redactor-editor'))
				{
					$currentNode = $currentNode.parent();
				}

				$pagebreakNode.insertAfter($currentNode);
			}
			else
			{
				// Just append it to the end
				$pagebreakNode.appendTo(this.$editor);
			}

			var $p = $('<p><br/></p>').insertAfter($pagebreakNode);

			this.$editor.focus();

			Garnish.requestAnimationFrame($.proxy(function()
			{
				this.code.sync();
			}, this));
			//this.setSelection($p[0], 0, $p[0], 0);
		}
	};
};
