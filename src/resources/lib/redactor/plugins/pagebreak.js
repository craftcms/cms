if (!RedactorPlugins) var RedactorPlugins = {};

RedactorPlugins.pagebreak = function()
{
	return {
		langs: {
			en: {
				"insert-page-break": "Insert Page Break"
			}
		},
		init: function()
		{
			var $btn = this.button.add('pagebreak', this.lang.get('insert-page-break'));
			this.button.addCallback($btn, this.pagebreak.insertPageBreak);
			this.button.setIcon($btn, '<i class="icon"></i>');
		},

		insertPageBreak: function()
		{
			var $pagebreakNode = $('<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />'),
				$currentNode = $(this.selection.current());

			if ($currentNode.length && $.contains(this.$editor.get(0), $currentNode.get(0)))
			{
				// Find the closest element to div.redactor-editor
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
