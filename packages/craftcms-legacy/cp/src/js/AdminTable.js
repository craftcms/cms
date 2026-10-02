/** global: Craft */
/** global: Garnish */
/**
 * Admin table class
 */
Craft.AdminTable = Garnish.Base.extend(
  {
    settings: null,
    totalItems: null,
    sorter: null,

    $noItems: null,
    $table: null,
    $tbody: null,
    $deleteBtns: null,

    init: function (settings) {
      this.setSettings(settings, Craft.AdminTable.defaults);

      if (!this.settings.allowDeleteAll) {
        this.settings.minItems = 1;
      }

      this.$noItems = $(this.settings.noItemsSelector);
      this.$table = $(this.settings.tableSelector);
      this.$tbody = this.$table.children('tbody');
      this.totalItems = this.$tbody.children().length;

      if (this.settings.sortable && Craft.hasMousePointerEvents()) {
        this.sorter = new Craft.DataTableSorter(this.$table, {
          onSortChange: this.reorderItems.bind(this),
        });
      }

      this.$deleteBtns = this.$table.find('.delete:not(.disabled)');
      this.addListener(this.$deleteBtns, 'click', 'handleDeleteBtnClick');
      this.addListener(this.$deleteBtns, 'keydown', (event) => {
        if (
          event.keyCode === Garnish.SPACE_KEY ||
          event.keyCode === Garnish.RETURN_KEY
        ) {
          event.preventDefault();
          this.handleDeleteBtnClick(event);
        }
      });

      this.$tbody.children('tr').each((key, row) => {
        this.initRow(row);
      });

      this.updateUI();
    },

    initRow: function (row) {
      return new Craft.AdminTable.Row(this, row);
    },

    addRow: function (row) {
      if (this.settings.maxItems && this.totalItems >= this.settings.maxItems) {
        // Sorry pal.
        return;
      }

      var $row = $(row).appendTo(this.$tbody),
        $deleteBtn = $row.find('.delete');

      if (this.settings.sortable && Craft.hasMousePointerEvents()) {
        this.sorter?.addItems($row);
      }

      this.$deleteBtns = this.$deleteBtns.add($deleteBtn);

      this.addListener($deleteBtn, 'click', 'handleDeleteBtnClick');
      this.totalItems++;

      this.updateUI();
    },

    reorderItems: function () {
      if (!this.settings.sortable) {
        return;
      }

      // Get the new field order
      let ids = this.getRowOrder();

      // Send it to the server
      var data = {
        ids: JSON.stringify(ids),
      };

      Craft.sendActionRequest('POST', this.settings.reorderAction, {data})
        .then((response) => {
          this.onReorderItems(ids);
          Craft.cp.displaySuccess(
            Craft.t('app', this.settings.reorderSuccessMessage)
          );
        })
        .catch(({response}) => {
          Craft.cp.displayError(
            Craft.t('app', this.settings.reorderFailMessage)
          );
        });
    },

    getRowOrder: function () {
      var ids = [];
      // Get the new field order
      this.$tbody.children('tr').each((key, row) => {
        let id = $(row).attr(this.settings.idAttribute);
        ids.push(id);
      });

      return ids;
    },

    handleDeleteBtnClick: function (event) {
      if (this.settings.minItems && this.totalItems <= this.settings.minItems) {
        // Sorry pal.
        return;
      }

      var $row = $(event.target).closest('tr');

      if (this.confirmDeleteItem($row)) {
        this.deleteItem($row);
      }
    },

    confirmDeleteItem: function ($row) {
      if (!this.settings.confirmDeleteMessage) {
        return true;
      }

      var name = this.getItemName($row);
      return confirm(
        Craft.t('app', this.settings.confirmDeleteMessage, {name})
      );
    },

    deleteItem: function ($row) {
      var data = {
        id: this.getItemId($row),
      };

      Craft.sendActionRequest('POST', this.settings.deleteAction, {data})
        .then((response) => this.handleDeleteItemSuccess(response.data, $row))
        .catch(({response}) =>
          this.handleDeleteItemFailure(response.data, $row)
        );
    },

    handleDeleteItemFailure: function (data, $row) {
      var id = this.getItemId($row),
        name = this.getItemName($row);

      Craft.cp.displayError(
        Craft.t('app', this.settings.deleteFailMessage, {name})
      );
    },

    handleDeleteItemSuccess: function (data, $row) {
      var id = this.getItemId($row),
        name = this.getItemName($row);

      if (this.sorter) {
        this.sorter.removeItems($row);
      }

      $row.remove();
      this.totalItems--;
      this.updateUI();
      this.onDeleteItem(id);

      if (this.settings.deleteSuccessMessage) {
        Craft.cp.displaySuccess(
          Craft.t('app', this.settings.deleteSuccessMessage, {name})
        );
      }
    },

    onReorderItems: function (ids) {
      this.settings.onReorderItems(ids);
    },

    onDeleteItem: function (id) {
      this.settings.onDeleteItem(id);
    },

    getItemId: function ($row) {
      return $row.attr(this.settings.idAttribute);
    },

    getItemName: function ($row) {
      return Craft.escapeHtml($row.attr(this.settings.nameAttribute));
    },

    updateUI: function () {
      // Show the "No Whatever Exists" message if there aren't any
      if (this.totalItems === 0) {
        this.$table.hide();
        this.$noItems.removeClass('hidden');
      } else {
        this.$table.show();
        this.$noItems.addClass('hidden');
      }

      // Disable the sort buttons if there's only one row
      if (this.settings.sortable) {
        if (!Craft.hasMousePointerEvents()) {
          this.$table.find('.move').hide();
        } else {
          var $moveButtons = this.$table.find('.move');

          if (this.totalItems === 1) {
            $moveButtons.addClass('disabled');
          } else {
            $moveButtons.removeClass('disabled');
          }
        }
      }

      // Disable the delete buttons if we've reached the minimum items
      if (this.settings.minItems && this.totalItems <= this.settings.minItems) {
        this.$deleteBtns.addClass('disabled');
      } else {
        this.$deleteBtns.removeClass('disabled');
      }

      // Hide the New Whatever button if we've reached the maximum items
      if (this.settings.newItemBtnSelector) {
        if (
          this.settings.maxItems &&
          this.totalItems >= this.settings.maxItems
        ) {
          $(this.settings.newItemBtnSelector).addClass('hidden');
        } else {
          $(this.settings.newItemBtnSelector).removeClass('hidden');
        }
      }
    },
  },
  {
    defaults: {
      tableSelector: null,
      noItemsSelector: null,
      newItemBtnSelector: null,
      idAttribute: 'data-id',
      nameAttribute: 'data-name',
      sortable: false,
      allowDeleteAll: true,
      minItems: 0,
      maxItems: null,
      reorderAction: null,
      deleteAction: null,
      reorderSuccessMessage: Craft.t('app', 'New order saved.'),
      reorderFailMessage: Craft.t('app', 'Couldn’t save new order.'),
      confirmDeleteMessage: Craft.t(
        'app',
        'Are you sure you want to delete “{name}”?'
      ),
      deleteSuccessMessage: Craft.t('app', '“{name}” deleted.'),
      deleteFailMessage: Craft.t('app', 'Couldn’t delete “{name}”.'),
      onReorderItems: $.noop,
      onDeleteItem: $.noop,
    },
  }
);

Craft.AdminTable.Row = Garnish.Base.extend({
  table: null,
  $row: null,
  $moveHandle: null,
  $actionMenuBtn: null,
  $actionMenu: null,
  actionDisclosure: null,
  moveUpBtn: null,
  moveDownBtn: null,

  init: function (table, row) {
    this.table = table;
    this.$row = $(row);

    this.initSortActions();
  },

  initSortActions: function () {
    if (!this.table.settings.sortable) {
      return;
    }

    // find the delete button and add the actions menu before it
    let $deleteButtonWrapper = this.$row.find('.delete').parent('td');

    const menuId = 'menu-' + Math.floor(Math.random() * 1000000000);
    let $actionMenuBtnWrapper = this.$row.find('.actions-container');

    if ($actionMenuBtnWrapper.length > 0) {
      this.$actionMenuBtn = $('<button/>', {
        class: 'btn action-btn',
        'aria-controls': menuId,
        'aria-label': Craft.t('app', 'Actions'),
        'data-disclosure-trigger': '',
        'data-icon': 'ellipsis',
      }).appendTo($actionMenuBtnWrapper);
      this.$actionMenu = $('<div/>', {
        id: menuId,
        class: 'menu menu--disclosure',
      }).appendTo($actionMenuBtnWrapper);

      this.actionDisclosure = new Garnish.DisclosureMenu(this.$actionMenuBtn);
      this.moveUpBtn = this.actionDisclosure.addItem({
        icon: async () => await Craft.ui.icon('arrow-up'),
        label: Craft.t('app', 'Move up'),
        onActivate: () => {
          this.moveUp();
        },
      });
      this.moveDownBtn = this.actionDisclosure.addItem({
        icon: async () => await Craft.ui.icon('arrow-down'),
        label: Craft.t('app', 'Move down'),
        onActivate: () => {
          this.moveDown();
        },
      });

      this.actionDisclosure.on('show', () => {
        if (this.getPrevItem()) {
          this.actionDisclosure.showItem(this.moveUpBtn);
        } else {
          this.actionDisclosure.hideItem(this.moveUpBtn);
        }
        if (this.getNextItem()) {
          this.actionDisclosure.showItem(this.moveDownBtn);
        } else {
          this.actionDisclosure.hideItem(this.moveDownBtn);
        }
      });
    }
  },

  moveUp: function () {
    const $prev = this.getPrevItem();
    if ($prev) {
      this.$row.insertBefore($prev);
      this.$row.trigger('movedUp');
      this.table.reorderItems();
    }
  },

  moveDown: function () {
    const $next = this.getNextItem();
    if ($next) {
      this.$row.insertAfter($next);
      this.$row.trigger('movedDown');
      this.table.reorderItems();
    }
  },

  getPrevItem: function () {
    //const $row = this.$row.prevAll('tr');
    const $row = this.$row.prevAll('tr:has(.actions-container):first');

    return $row.length ? $row : null;
  },

  getNextItem: function () {
    const $row = this.$row.nextAll('tr:has(.actions-container):first');

    return $row.length ? $row : null;
  },
});
