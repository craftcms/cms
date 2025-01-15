/** global: Craft */
/** global: Garnish */
/**
 * Sortable checkbox select class
 */
Craft.SortableCheckboxSelect = Garnish.Base.extend({
  $container: null,
  dragSort: null,

  init: function (container) {
    this.$container = $(container);
    this.$container.data('sortableCheckboxSelect', this);

    const $sortItems = this.$container.children(
      '.checkbox-select-item:not(.all)'
    );

    this.initDrag($sortItems);

    if ($sortItems.length) {
      $sortItems.each((key, item) => {
        this.initItem(item);
      });
    }
  },

  initDrag: function ($sortItems = []) {
    if ($sortItems.length === 0) {
      $sortItems = this.$container.children('.checkbox-select-item:not(.all)');
    }

    if ($sortItems.length) {
      this.dragSort = new Garnish.DragSort($sortItems, {
        axis: Garnish.Y_AXIS,
        handle: '.draggable-handle',
      });
    }
  },

  initItem: function (item) {
    return new Craft.SortableCheckboxSelect.Item(this, item);
  },
});

Craft.SortableCheckboxSelect.Item = Garnish.Base.extend({
  select: null,
  $item: null,
  $moveHandle: null,
  $checkbox: null,
  $actionMenuBtn: null,
  $actionMenu: null,
  actionDisclosure: null,
  moveUpBtn: null,
  moveDownBtn: null,

  init: function (select, item) {
    this.select = select;
    this.$item = $(item);
    this.$moveHandle = this.$item.children('.move');
    this.$checkbox = this.$item.children('input[type=checkbox]');

    this.addListener(this.$checkbox, 'change', () => {
      this.handleCheckboxChange();
    });

    this.handleCheckboxChange();
  },

  handleCheckboxChange: function () {
    if (this.$checkbox.prop('checked')) {
      this.onCheck();
    } else {
      this.onUncheck();
    }
  },

  onCheck: function () {
    if (this.$actionMenuBtn) {
      this.onUncheck();
    }

    this.$moveHandle.removeClass('disabled');

    const menuId = 'menu-' + Math.floor(Math.random() * 1000000000);
    this.$actionMenuBtn = $('<button/>', {
      class: 'btn action-btn',
      'aria-controls': menuId,
      'aria-label': Craft.t('app', 'Actions'),
      'data-disclosure-trigger': '',
      'data-icon': 'ellipsis',
    }).appendTo(this.$item);
    this.$actionMenu = $('<div/>', {
      id: menuId,
      class: 'menu menu--disclosure',
    }).appendTo(this.$item);

    this.actionDisclosure = new Garnish.DisclosureMenu(this.$actionMenuBtn);
    this.moveUpBtn = this.actionDisclosure.addItem({
      icon: 'arrow-up',
      label: Craft.t('app', 'Move up'),
      onActivate: () => {
        this.moveUp();
      },
    });
    this.moveDownBtn = this.actionDisclosure.addItem({
      icon: 'arrow-down',
      label: Craft.t('app', 'Move down'),
      onActivate: () => {
        this.moveDown();
      },
    });

    this.actionDisclosure.on('show', () => {
      if (this.getPrevCheckedItem()) {
        this.actionDisclosure.showItem(this.moveUpBtn);
      } else {
        this.actionDisclosure.hideItem(this.moveUpBtn);
      }
      if (this.getNextCheckedItem()) {
        this.actionDisclosure.showItem(this.moveDownBtn);
      } else {
        this.actionDisclosure.hideItem(this.moveDownBtn);
      }
    });

    const allCheckedSiblings = this.getAllCheckedSiblings();
    // if there are no other checked siblings, hide current item's action menu btn
    if (allCheckedSiblings.length == 0) {
      this.$actionMenuBtn.hide();
    } else if (allCheckedSiblings.length == 1) {
      // if there's only one other checked sibling,
      // we need to show that sibling's action menu btn as it would have been hidden so far
      $(allCheckedSiblings[0]).find('.btn.action-btn').show();
    }

    this.$item.trigger('checked');
  },

  onUncheck: function () {
    const allCheckedSiblings = this.getAllCheckedSiblings();
    // if there's only one other checked sibling, hide that sibling's action menu btn
    if (allCheckedSiblings.length == 1) {
      $(allCheckedSiblings[0]).find('.btn.action-btn').hide();
    }

    this.$moveHandle?.addClass('disabled');
    this.$actionMenuBtn?.remove();
    this.$actionMenu?.remove();
    this.actionDisclosure?.destroy();
    this.$actionMenuBtn = this.actionDisclosure = null;
    this.$item.trigger('unchecked');
  },

  getPrevCheckedItem: function () {
    const $item = this.$item.prevAll(
      '.checkbox-select-item:not(.all):has(input[type=checkbox]:checked):first'
    );
    return $item.length ? $item : null;
  },

  getNextCheckedItem: function () {
    const $item = this.$item.nextAll(
      '.checkbox-select-item:not(.all):has(input[type=checkbox]:checked):first'
    );
    return $item.length ? $item : null;
  },

  getAllCheckedSiblings: function () {
    return this.$item.siblings(
      '.checkbox-select-item:not(.all):has(input[type=checkbox]:checked)'
    );
  },

  moveUp: function () {
    const $prev = this.getPrevCheckedItem();
    if ($prev) {
      this.$item.insertBefore($prev);
      this.$item.trigger('movedUp');
    }
  },

  moveDown: function () {
    const $next = this.getNextCheckedItem();
    if ($next) {
      this.$item.insertAfter($next);
      this.$item.trigger('movedDown');
    }
  },
});
