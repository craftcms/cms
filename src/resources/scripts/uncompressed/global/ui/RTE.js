(function($){

/**
 * Rich Text Editor
 */
blx.ui.RTE = blx.Base.extend({

	/**
	 * Constructor
	 */
	init: function(id, settings)
	{
		this.id = id;
		this.settings = $.extend({}, blx.ui.RTE.defaults, settings);

		this.focussed = false;

		// -------------------------------------------
		//  Setup the DOM
		// -------------------------------------------

		this.dom = {};

		// find the textarea
		this.dom.textarea = document.getElementById(this.id);

		// add the toolbar
		this.dom.toolbar = document.createElement('div');
		this.dom.textarea.parentNode.insertBefore(this.dom.toolbar, this.dom.textarea);

		// add the container div
		this.dom.container = document.createElement('div');
		this.dom.container.className = 'rte input';
		this.dom.textarea.parentNode.insertBefore(this.dom.container, this.dom.textarea);

		// add the styles button
		this.dom.stylesBtn = document.createElement('div');
		this.dom.stylesBtn.className = 'btn disabled';
		this.dom.stylesBtn.innerHTML = '<span class="label">'+this.settings.styles[0].label+'</span><span class="arrow"></span>';
		this.dom.toolbar.appendChild(this.dom.stylesBtn);

		// add the iframe
		this.dom.iframe = document.createElement('iframe');
		this.dom.iframe.style.width = '100%';
		this.dom.iframe.style.height = '200px';
		this.dom.container.appendChild(this.dom.iframe);

		// hide the textarea
		this.dom.textarea.style.display = 'none';

		// -------------------------------------------
		//  Setup the iframe
		// -------------------------------------------

		// get the iframe's window and document objects
		this.iWin = this.dom.iframe.contentWindow;
		this.iDoc = this.dom.iframe.contentDocument || this.iWin.document || this.dom.iframe.document;

		// turn on design mode
		this.iDoc.designMode = 'on';

		var html = '<html>';
		if (this.settings.css) html += '<head><link rel="stylesheet" type="text/css" href="'+this.settings.css+'"></head>';
		html += '<body>'+this.dom.textarea.value+'</body></html>';

		this.iDoc.open();
		this.iDoc.write(html);
		this.iDoc.close();

		this.iWin.onload = $.proxy(this, '_buildStylesMenu');

		this.addListener(this.iWin, 'focus', '_onFocus');
		this.addListener(this.iWin, 'blur', '_onBlur');

		// keep the button states up-to-date with the selection
		this.addListener(this.iDoc, 'keypress', '_onKeypress');
		//$(this.dom.iframe).on('focus blur', $.proxy(this, 'setButtonStates'));
		//$(this.iDoc).on('mouseup keydown keypress keyup textInput focus blur', $.proxy(this, 'setButtonStates'));

		// add the formatting buttons
		//this.buttons = {
		//	bold:   this.addFormattingButton('bold', '<b>B</b>'),
		//	italic: this.addFormattingButton('italic', '<i>I</i>')
		//};

		blx.ui.RTE.instances.push(this);
	},

	/**
	 * Build Styles Menu
	 */
	_buildStylesMenu: function()
	{
		var options = [];

		for (var i = 0; i < this.settings.styles.length; i++)
		{
			var style = this.settings.styles[i];

			// place a sample element in the editor so we can copy its font styles
			var $sample = $(document.createElement(style.elem));
			$sample.appendTo(this.iDoc.body);

			var $label = $('<span />');
			$label.text(style.label);
			$label.css({
				fontFamily: $sample.css('fontFamily'),
				fontSize: $sample.css('fontSize'),
				fontWeight: $sample.css('fontWeight'),
				fontStyle: $sample.css('fontStyle'),
				letterSpacing: $sample.css('letterSpacing')
			});

			// remove the sample element
			$sample.remove();

			options.push({ label: $label.prop('outerHTML') });
		}

		this.styleSelect = new blx.ui.SelectMenu(this.dom.stylesBtn, options, $.proxy(this, 'selectStyle'));

		this.styleSelect.select(0);
	},

	/**
	 * On Focus
	 */
	_onFocus: function()
	{
		this.focussed = true;

		this._forceChildNode();

		this.setButtonStates();
	},

	/**
	 * On Blur
	 */
	_onBlur: function()
	{
		this.focussed = false;
		this.setButtonStates();
	},

	/**
	 * Force Child Node
	 */
	_forceChildNode: function()
	{
		if (!this.iDoc.body.childNodes.length)
		{
			// create a new paragraph
			var p = this.iDoc.createElement('p');
			this.iDoc.body.appendChild(p);
			var br = this.iDoc.createElement('br');
			p.appendChild(br);

			// move the cursor to the new paragraph
			var range = this._createRange();
			range.setStart(p, 0);
			range.setEnd(p, 0);
			var sel = this._getSelection();
			sel.setSingleRange(range);
		}
	},

	/**
	 * Select Style
	 */
	selectStyle: function(style)
	{},

	/**
	 * Add Formatting Button
	 */
	//addFormattingButton: function(command, label)
	//{
	//	return new FormattingButton(this, command, label);
	//},

	/**
	 * Get Selection
	 */
	_getSelection: function()
	{
		return rangy.getIframeSelection(this.dom.iframe);
	},

	/**
	 * Create Range
	 */
	_createRange: function()
	{
		return rangy.createRange(this.iDoc);
	},

	/**
	 * Get Range
	 */
	_getRange: function()
	{
		var sel = this._getSelection();
		return sel.getRangeAt(0);
	},

	/**
	 * Get Selected Element
	 */
	_getSelectedElement: function()
	{
		var range = this._getRange();
		var elem = range.commonAncestorContainer;

		if (!range.collapsed)
		{
			if (range.startContainer.nodeType === 3 && range.endContainer.nodeType === 3)
			{
				var start = range.startContainer,
					end = range.endContainer;

				function skipEmptyTextNodes(n, forwards)
				{
					var orig = n;
					while (n && n.nodeType === 3 && n.length === 0) {
						n = forwards ? n.nextSibling : n.previousSibling;
					}
					return n || orig;
				}

				if (start.length === range.startOffset)
				{
					start = skipEmptyTextNodes(start.nextSibling, true);
				}
				else
				{
					start = start.parentNode;
				}

				if (range.endOffset === 0)
				{
					end = skipEmptyTextNodes(end.previousSibling, false);
				}
				else
				{
					end = end.parentNode;
				}

				if (start && start === end)
					return start;
			}
		}

		return (node && node.nodeType == 3) ? node.parentNode : node;
	},

	/**
	 * On Keypress
	 */
	_onKeypress: function(event)
	{
		this._forceChildNode();

		switch (event.charCode)
		{
			case 13: // return

				// prevent the browser from creating a <div> / <p>
				event.preventDefault();

				var range = this._getRange();

				if (range.endContainer.nodeType != 3)
				{
					console.log('not a text node', range.endContainer);
					return;
				}

				var parent = range.endContainer.parentNode;

				// delete the selection if there is one
				if (!range.collapsed)
					range.deleteContents();

				// extract the remaining text
				var parent = range.endContainer.parentNode;
				var end = parent.childNodes[parent.childNodes.length-1];
				var range2 = range.cloneRange();
				range2.setStart(range.endContainer, range.endOffset);
				range2.setEnd(end, end.length);
				var extracted = range2.extractContents();

				if (event.altKey)
				{
					// place a <br> where the cursor is, followed by the extracted text
					var br = this.iDoc.createElement('br');
					parent.appendChild(br);
					parent.appendChild(extracted);

					// now place the cursor after the <br>
					var range2 = range.cloneRange();
					var text = br.nextSibling;
					range2.setStart(text, 0);
					range2.setEnd(text, 0);
					var sel = this._getSelection();
					sel.setSingleRange(range2);
				}
				else
				{
					// create a new paragraph after the current selection
					var p = this.iDoc.createElement('p');
					$(p).insertAfter(parent);
					p.appendChild(extracted);

					// add a <br> so that the <p> is selectable
					// (the browser should remove this automatically when they start typing) 
					var br = this.iDoc.createElement('br');
					p.appendChild(br);

					// now select the new paragraph
					var range = this._createRange();
					range.setStart(p, 0);
					range.setEnd(p, 0);
					var sel = this._getSelection();
					sel.setSingleRange(range);
				}

				break;

			default:

				
		}
	},

	/**
	 * Set Button States
	 */
	setButtonStates: function(event)
	{
		if (!this.focussed)
		{
			$(this.dom.stylesBtn).addClass('disabled');
			return
		}

		$(this.dom.stylesBtn).removeClass('disabled');

		
	}

}, {
	instances: []
});

blx.ui.RTE.defaults = {
	bold: true,
	italic: true,

	styles: [
		{ label: 'Paragraph', elem: 'p' },
		{ label: 'Heading 1', elem: 'h1' },
		{ label: 'Heading 2', elem: 'h2' },
		{ label: 'Heading 3', elem: 'h3' },
		{ label: 'Heading 4', elem: 'h4' },
		{ label: 'Heading 5', elem: 'h5' },
		{ label: 'Heading 6', elem: 'h6' }
	],

	css: null
};

/**
 * Formatting Button
 */
var FormattingButton = blx.Base.extend({
	/**
	 * Constructor
	 */
	init: function(editor, command, btnLabel)
	{
		this.editor = editor;
		this.command = command;

		// add the button
		this.$btn = $('<a class="btn" href=""><span class="label">'+btnLabel+'</span></a>');
		this.$btn.insertBefore(this.editor.iframe);
		this.addListener(this.$btn, 'click', 'toggleCommand');

		this.setButtonState();
	},

	/**
	 * Set Button State
	 */
	setButtonState: function()
	{
		if (this.queryCommandEnabled())
		{
			this.$btn.removeClass('disabled');

			if (this.queryCommandState())
				this.$btn.addClass('sel');
			else
				this.$btn.removeClass('sel');
		}
		else
		{
			this.$btn.removeClass('sel');
			this.$btn.addClass('disabled');
		}
	},

	/**
	 * Query Command Enabled
	 */
	queryCommandEnabled: function()
	{
		return this.editor.iDoc.queryCommandEnabled(this.command);
	},

	/**
	 * Query Command State
	 */
	queryCommandState: function()
	{
		return this.editor.iDoc.queryCommandState(this.command);
	},

	/**
	 * Execute Command
	 */
	execCommand: function()
	{
		this.editor.iDoc.execCommand(this.command, null, true);
		this.setButtonState(true);
		this.editor.iframe.focus();
	},

	/**
	 * Unexecute Command
	 */
	unexecCommand: function()
	{
		this.editor.iDoc.execCommand(this.command, null, false);
		this.setButtonState(false);
	},

	/**
	 * Toggle Command
	 */
	toggleCommand: function(event)
	{
		// prevent the editor from loosing focus
		event.preventDefault();

		if (this.queryCommandState())
			this.unexecCommand();
		else
			this.execCommand();
	}
});

})(jQuery);
