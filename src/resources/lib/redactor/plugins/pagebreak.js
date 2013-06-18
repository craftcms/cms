if (typeof RedactorPlugins === 'undefined') var RedactorPlugins = {};

RedactorPlugins.pagebreak = {

	init: function()
	{
		this.fullscreen = false;
		this.buttonAdd('pagebreak', 'Insert Page Break', $.proxy(this, 'insertPageBreak'));
	},

	insertPageBreak: function()
	{
		var $pagebreakNode = $('<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />'),
			$currentNode = $(this.getCurrent());

		if ($currentNode.length && !$currentNode.is('div.redactor_editor'))
		{
			// Find the closest element to div.redactor_editor
			while ($currentNode.parent().length && !$currentNode.parent().is('div.redactor_editor'))
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
		this.sync();
		this.setSelection($p[0], 0, $p[0], 0);
	}
};
