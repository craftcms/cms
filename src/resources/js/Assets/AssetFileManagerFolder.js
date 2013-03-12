/**
 * File Manager Folder
 */
Assets.FileManagerFolder = Garnish.Base.extend({

	/**
	 * Constructor
	 */
	init: function(fm, li, depth, parent)
	{
		this.fm = fm;
		this.li = li;
		this.depth = depth;
		this.parent = parent;

		this.$li = $(this.li);
		this.$a = $('> a', this.$li);
		this.$toggle;
		this.$ul;

		this.id = this.$a.attr('data-id');

		this.visible = false;
		this.visibleBefore = false;
		this.expanded = false;
		this.subfolders = [];

		this.fm.folders[this.id] = this;

		this.folderName = this.$a.text().replace(/\s+$/,"").replace(/^\s+/, '');

		// -------------------------------------------
		//  Make top-level folders visible
		// -------------------------------------------

		if (this.depth == 1)
		{
			this.onShow();
		}

		// -------------------------------------------
		//  Create the context menu
		// -------------------------------------------

		var menuOptions = [];

		if (this.fm.settings.mode == 'full' && this.depth > 1)
		{
			menuOptions.push({ label: Craft.t('Rename'), onClick: $.proxy(this, '_rename') });
			menuOptions.push('-');
		}

		menuOptions.push({ label: Craft.t('New subfolder'), onClick: $.proxy(this, '_createSubfolder') });

		if (this.fm.settings.mode == 'full' && this.depth > 1)
		{
			menuOptions.push('-');
			menuOptions.push({ label: Craft.t('Delete'), onClick: $.proxy(this, '_delete') });
		}

		new Garnish.ContextMenu(this.$a, menuOptions, {
			menuClass: 'assets-contextmenu'
		});
	},

	// -------------------------------------------
	//  Subfolders and the toggle button
	// -------------------------------------------

	/**
	 * Has Subfolders
	 */
	hasSubfolders: function()
	{
		return this.$li.find('ul li').length > 0;
	},

	/**
	 * Prep for Subfolders
	 */
	_prepForSubfolders: function()
	{
		// add the toggle
		if (! this.$toggle)
		{
			this.$toggle = $('<span class="assets-fm-toggle"></span>');
		}

		this.$toggle.prependTo(this.$a);

		// prevent toggle button clicks from triggering multi select functions
		this.addListener(this.$toggle, 'mouseup,mousedown,click', function(ev)
		{
			ev.stopPropagation();
		});

		// toggle click handling. unbind events beforehand, to avoid double-toggling
		this.removeListener(this.$toggle, 'click');
		this.addListener(this.$toggle, 'click', function (ev) {ev.stopPropagation(); this._toggle();});

		// add the $ul
		if (! this.$ul)
		{
			if (this.$li.children().filter('ul').length == 0)
			{
				this.$li.append('<ul></ul>');
			}
			this.$ul = this.$li.children().filter('ul');
		}

		this.$ul.appendTo(this.$li);
	},

	/**
	 * Unprep for Subfolders
	 */
	_unprepForSubfolders: function()
	{
		this.$toggle.remove();
		this.$ul.remove();
		this.collapse();
	},

	/**
	 * Add Subfolder
	 */
	addSubfolder: function(subfolder)
	{
		// is this our first subfolder?
		if (! this.hasSubfolders())
		{
			this._prepForSubfolders();

			var pos = 0;
		}
		else
		{
			var folders = [ {name: subfolder.folderName, id: subfolder.id} ];

			for (var i = 0; i < this.subfolders.length; i++)
			{

				folders.push({name: this.subfolders[i].folderName, id: this.subfolders[i].id});
			}

			folders.sort(Assets.FileManagerFolder.folderSort);

			for (i = 0; i < folders.length; i++)
			{
				if (folders[i].name == subfolder.folderName)
				{
					pos = i;
					break;
				}
			}
		}

		if (pos == 0)
		{
			subfolder.$li.prependTo(this.$ul);
			this.$ul.prepend(subfolder.$li);
		}
		else
		{
			var prevSibling = this.fm.folders[folders[pos-1].id];
			subfolder.$li.insertAfter(prevSibling.$li);
		}

		this.subfolders.push(subfolder);
	},

	/**
	 * Remove Subfolder
	 */
	removeSubfolder: function(subfolder)
	{
		this.subfolders.splice($.inArray(subfolder, this.subfolders), 1);

		// was this the only subfolder?
		if (! this.hasSubfolders())
		{
			this._unprepForSubfolders();
		}
	},

	/**
	 * Toggle
	 */
	_toggle: function()
	{
		if (this.expanded)
		{
			this.collapse();
		}
		else
		{
			this.expand();
		}
	},

	/**
	 * Expand
	 */
	expand: function()
	{
		if (this.expanded) return;

		this.expanded = true;

		this.$a.addClass('assets-fm-expanded');

		this.$ul.show();
		this._onShowSubfolders();

		// Store folder state
		this.fm.setFolderState(this.id, 'expanded');
	},

	/**
	 * Collapse
	 */
	collapse: function()
	{
		if (! this.expanded) return;

		this.expanded = false;
		this.$a.removeClass('assets-fm-expanded');

		this.$ul.hide();
		this._onHideSubfolders();

		// Store folder state
		this.fm.setFolderState(this.id, 'collapsed');
	},

	// -------------------------------------------
	//  Showing and hiding
	// -------------------------------------------

	/**
	 * On Show
	 */
	onShow: function()
	{
		this.visible = true;

		this.fm.folderSelect.addItems(this.$a);

		if (this.depth > 1)
		{
			if (this.fm.settings.mode == 'full')
			{
				this.fm.folderDrag.addItems(this.$li);
			}
		}

		if (! this.visibleBefore)
		{
			this.visibleBefore = true;


			if (this.hasSubfolders())
			{
				this._prepForSubfolders();

				// initialize sub folders
				var $lis = this.$ul.children().filter('li');

				for (var i = 0; i < $lis.length; i++)
				{
					var subfolder = new Assets.FileManagerFolder(this.fm, $lis[i], this.depth + 1, this);
					this.subfolders.push(subfolder);
				};
			}
		}

		if (this.expanded)
		{
			this._onShowSubfolders();
		}
	},

	/**
	 * On Hide
	 */
	onHide: function()
	{
		this.visible = false;
		this.fm.folderSelect.removeItems(this.$a);

		if (this.expanded)
		{
			this._onHideSubfolders();
		}
	},

	/**
	 * On Show Subfolders
	 */
	_onShowSubfolders: function()
	{
		for (var i in this.subfolders)
		{
			this.subfolders[i].onShow();
		}
	},

	/**
	 * On Hide Subfolders
	 */
	_onHideSubfolders: function()
	{
		for (var i in this.subfolders)
		{
			this.subfolders[i].onHide();
		}
	},

	/**
	 * On Delete
	 */
	onDelete: function(isTopDeletedFolder)
	{
		// remove the master record of this folder
		delete this.fm.folders[this.id];

		if (isTopDeletedFolder)
		{
			// remove the parent folder's record of this folder
			this.parent.removeSubfolder(this);

			// remove the LI
			this.$li.remove();
		}

		for (var i = 0; i < this.subfolders.length; i++)
		{
			this.subfolders[i].onDelete();
		}

		if (! this.parent.hasSubfolders())
		{
			this.parent._unprepForSubfolders();
		}

		if (this.fm.folderSelect.isSelected(this.$a)){
			this.parent.select();
			this.deselect();
		}
	},

	// -------------------------------------------
	//  Operations
	// -------------------------------------------

	deselect: function ()
	{
		this.fm.folderSelect.deselectItem(this.$a);
	},

	select: function ()
	{
		this.fm.folderSelect.selectItem(this.$a);
	},


	/**
	 * Move to...
	 */
	moveTo: function(newId)
	{
		var newParent = this.fm.folders[newId];

		// is the old boss the same as the new boss?
		if (newParent == this.parent) return;

		// add this to the new parent
		// (we need to do this first so that the <li> is always in the DOM, and keeps its events)
		newParent.addSubfolder(this);

		// remove this from the old parent
		this.parent.removeSubfolder(this);

		// make sure the new parent is expanded
		newParent.expand();

		this.parent = newParent;

	},

	/**
	 * Update Id
	 */
	updateId: function(id)
	{
		delete this.fm.folders[this.id];

		var selIndex = this.fm.selectedFolderIds.indexOf(this.id);
		if (selIndex != -1)
		{
			// update the selected folders array
			this.fm.selectedFolderIds[selIndex] = path;
		}

		this.id = id;
		this.$a.attr('data-id', this.id);
		this.fm.folders[this.id] = this;

		// update subfolders
		for (var i = 0; i < this.subfolders.length; i++)
		{
			var subfolder = this.subfolders[i],
				newId = this.id + subfolder.id+'/';

			subfolder.updateId(newId);
		}
	},

	/**
	 * Update Name
	 */
	updateName: function(name)
	{
		$('span.assets-folder-label', this.$a).html(name);

		// -------------------------------------------
		//  Re-sort this folder among its siblings
		// -------------------------------------------

		var folders = [ {name: name, id: this.id} ];

		for (var i = 0; i < this.parent.subfolders.length; i++)
		{
			if (this.parent.subfolders[i].folderName != this.folderName) {
				folders.push({name: this.parent.subfolders[i].folderName, id: this.parent.subfolders[i].id});
			}
		}

		folders.sort(Assets.FileManagerFolder.folderSort);

		for (i = 0; i < folders.length; i++) {
			if (folders[i].name == name) {
				pos = i;
				break;
			}
		}

		if (pos == 0)
		{
			this.$li.prependTo(this.parent.$ul);
		}
		else
		{
			var prevSibling = this.fm.folders[folders[pos-1].id];
			this.$li.insertAfter(prevSibling.$li);
		}

		this.folderName = name;
	},

	/**
	 * Rename
	 */
	_rename: function()
	{
		var oldName = this.folderName,
			newName = prompt(Craft.t('Rename folder'), oldName);

		if (newName && newName != oldName)
		{
			var params = {
				folderId: this.id,
				newName: newName
			};

			this.fm.setAssetsBusy();

			Craft.postActionRequest('assets/renameFolder', params, $.proxy(function(data)
			{
				this.fm.setAssetsAvailable();

				if (data.success)
				{
					this.updateName(data.newName);
				}

				if (data.error)
				{
					alert(data.error);
				}
			}, this), 'json');
		}
	},

	/**
	 * Create Subfolder
	 */
	_createSubfolder: function()
	{
		var subfolderName = prompt(Craft.t('Enter the name of the folder'));

		if (subfolderName)
		{
			var params = {
				parentId:  this.id,
				folderName: subfolderName
			};

			this.fm.setAssetsBusy();

			Craft.postActionRequest('assets/createFolder', params, $.proxy(function(data)
			{
				this.fm.setAssetsAvailable();

				if (data.success)
				{
                    var subfolderDepth = this.depth + 1,
                        padding = 20 + (18 * subfolderDepth),
					    subfolderName = data.folderName,
						$li = $('<li class="assets-fm-folder">'
							  +   '<a data-id="' + data.folderId + '" style="padding-left: '+padding+'px;">'
							  +     data.folderName
							  +   '</a>'
							  + '</li>'),
						subfolder = new Assets.FileManagerFolder(this.fm, $li[0], subfolderDepth, this);

					this.addSubfolder(subfolder);
					this.expand();
					subfolder.onShow();

				}

				if (data.error)
				{
					alert(data.error);
				}
			}, this));
		}
	},

	/**
	 * Delete
	 */
	_delete: function()
	{
		if (confirm(Craft.t('Really delete folder "{folder}"?', {folder: this.folderName})))
		{

			var params = {
				folderId: this.id
			}

			this.fm.setAssetsBusy();

			Craft.postActionRequest('assets/deleteFolder', params, $.proxy(function(data)
			{
				this.fm.setAssetsAvailable();

				if (data.success)
				{
					this.onDelete(true);

				}

				if (data.error)
				{
					alert(data.error);
				}

			}, this));
		}
	}
},
{
	folderSort: function (a, b) {
		a = a.name.toLowerCase();
		b = b.name.toLowerCase();
		return a < b ? -1 : (a > b ? 1 : 0);
	}
});
