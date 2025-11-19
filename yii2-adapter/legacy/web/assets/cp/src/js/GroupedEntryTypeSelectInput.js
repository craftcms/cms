(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Grouped entry type select input
   */
  Craft.GroupedEntryTypeSelectInput = Craft.EntryTypeSelectInput.extend({
    init: function (settings = {}) {
      this.base(settings);
    },

    /**
     * @returns {Craft.GroupedEntryTypeManager.Group|null}
     */
    get group() {
      return this.$container
        .closest('.entry-type-group')
        ?.data('entryTypeGroup');
    },

    /**
     * @returns {Craft.GroupedEntryTypeManager|null}
     */
    get manager() {
      return this.group?.manager;
    },

    initComponentSort: function () {
      // use the manager's entryTypeSort instead
    },

    renderSettings: function (id) {
      return Object.assign(this.base(), {
        inputValue: JSON.stringify({
          id,
          group: this.group.name,
        }),
      });
    },

    insertComponent: function ($component) {
      $('<li/>')
        .append($component)
        .insertBefore(this.$list.find('.entry-type-group--caboose'));
    },

    addComponents: function ($components) {
      this.base($components);
      this.manager?.entryTypeSort.addItems($components.parent('li'));
    },

    addComponentInternal: function ($component) {
      this.base($component);

      if (this.settings.addItemsToActionMenus) {
        const disclosureMenu = this.getDisclosureMenu($component);
        const moveToPreviousGroupBtn = disclosureMenu.$container.find(
          '[data-move-to-previous-group]'
        )[0];
        const moveToNextGroupBtn = disclosureMenu.$container.find(
          '[data-move-to-next-group]'
        )[0];

        disclosureMenu.on('show', () => {
          const $group = $component.closest('.entry-type-group');
          const $prev = $group.prev('li.entry-type-group');
          const $next = $group.next('li.entry-type-group');

          if (moveToPreviousGroupBtn) {
            disclosureMenu.toggleItem(moveToPreviousGroupBtn, $prev.length);
          }
          if (moveToNextGroupBtn) {
            disclosureMenu.toggleItem(moveToNextGroupBtn, $next.length);
          }
        });
      }
    },

    defineComponentActions: function ($component) {
      const actions = this.base($component);

      actions.push({
        icon: async () =>
          await Craft.ui.icon(
            Craft.orientation === 'ltr' ? 'arrow-left' : 'arrow-right'
          ),
        label: Craft.t('app', 'Move to previous group'),
        onActivate: (el) => {
          // don't use `this` in case the chip ends up getting assigned to a different component select
          $(el)
            .closest('.menu')
            .data('disclosureMenu')
            .$trigger.closest('.componentselect')
            .data('componentSelect')
            .moveEntryTypeToPreviousGroup($component);
        },
        attributes: {
          'data-move-to-previous-group': true,
        },
      });

      actions.push({
        icon: async () =>
          await Craft.ui.icon(
            Craft.orientation === 'ltr' ? 'arrow-right' : 'arrow-left'
          ),
        label: Craft.t('app', 'Move to next group'),
        callback: (el) => {
          // don't use `this` in case the chip ends up getting assigned to a different component select
          $(el)
            .closest('.menu')
            .data('disclosureMenu')
            .$trigger.closest('.componentselect')
            .data('componentSelect')
            .moveEntryTypeToNextGroup($component);
        },
        attributes: {
          'data-move-to-next-group': true,
        },
      });

      return actions;
    },

    moveEntryTypeToPreviousGroup: function ($entryType) {
      const $li = $entryType.closest('li');
      const $group = $li.closest('.entry-type-group');
      const $prev = $group.prev('.entry-type-group');
      if ($prev.length) {
        $li.insertBefore(
          $prev
            .data('entryTypeGroup')
            .componentSelect.$list.find('.entry-type-group--caboose')
        );
        this.manager.refresh();
      }
    },

    moveEntryTypeToNextGroup: function ($entryType) {
      const $li = $entryType.closest('li');
      const $group = $li.closest('.entry-type-group');
      const $next = $group.next('.entry-type-group');
      if ($next.length) {
        $li.insertBefore(
          $next
            .data('entryTypeGroup')
            .componentSelect.$list.find('.entry-type-group--caboose')
        );
        this.manager.refresh();
      }
    },

    showOption: function (id, propagate = true) {
      this.base(id);

      // also show in the other component selects
      if (propagate) {
        const updateOtherGroups = () => {
          this.manager.groups.forEach((group) => {
            if (group.componentSelect !== this) {
              group.componentSelect.showOption(id, false);
            }
          });
        };

        if (this.manager) {
          updateOtherGroups();
        } else {
          // try again once the UI is done initializing
          setTimeout(() => {
            if (this.manager) {
              updateOtherGroups();
            }
          }, 1);
        }
      }
    },

    hideOption: function (id, propagate = true) {
      this.base(id);

      if (propagate) {
        // also hide in the other component selects
        setTimeout(() => {
          this.manager.groups.forEach((group) => {
            if (group.componentSelect !== this) {
              group.componentSelect.hideOption(id, false);
            }
          });
        }, 1);
      }
    },
  });
})(jQuery);
