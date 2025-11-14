(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Matrix settings class
   */
  Craft.GroupedEntryTypeManager = Garnish.Base.extend(
    {
      $container: null,
      $groupsContainer: null,
      $addGroupBtn: null,
      groups: null,
      groupSort: null,
      entryTypeSort: null,

      init: function ($container, settings = {}) {
        this.$container = $container;
        this.setSettings(settings, Craft.GroupedEntryTypeManager.defaults);

        this.$container.data('entryTypeManager', this);

        this.$groupsContainer = $container.find('.entry-type-groups');
        this.$addGroupBtn = Craft.ui
          .createButton({
            class: 'dashed',
            label: Craft.t('app', 'Add Group'),
            icon: 'plus',
          })
          .appendTo(this.$container);

        this.groupSort = new Garnish.DragSort({
          container: this.$groupsContainer,
          handle:
            '> .entry-type-group--titlebar > .entry-type-group--actions > .move',
          ignoreHandleSelector: null,
          magnetStrength: 4,
          helperLagBase: 1.5,
        });

        this.entryTypeSort = new Garnish.DragSort({
          container: this.$groupsContainer,
          ignoreHandleSelector: null,
          handle: '> .chip > .chip-content > .chip-actions > .move',
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          canInsertAfter: ($item) =>
            !$item.hasClass('entry-type-group--caboose'),
          onSortChange: () => {
            this.refresh();
          },
        });

        this.groups = [];

        const $groups = this.$groupsContainer.children('.entry-type-group');
        $groups.each((i, container) => {
          this.groups.push(
            new Craft.GroupedEntryTypeManager.Group(this, $(container))
          );
        });

        this.addListener(this.$addGroupBtn, 'activate', () => {
          this.addGroup();
        });
      },

      addGroup: async function () {
        const name = prompt(Craft.t('app', 'Group Name'));
        if (name === null || name === '') {
          return;
        }

        const $container = $('<li/>', {
          class: 'entry-type-group',
          'data-name': name,
        }).appendTo(this.$groupsContainer);
        const $titlebar = $('<div/>', {
          class: 'entry-type-group--titlebar',
        }).appendTo($container);
        $('<span/>', {
          text: name,
        }).appendTo($titlebar);

        const tempId = Craft.namespaceId('TEMP_ID', this.settings.namespace);
        const id = Craft.namespaceId(
          `entry-type-select-${Math.floor(Math.random() * 1000000)}`,
          this.settings.namespace
        );
        const selectContainerHtml =
          this.settings.entryTypeSelectHtml.replaceAll(tempId, id);
        const selectContainerJs = this.settings.entryTypeSelectJs.replaceAll(
          tempId,
          id
        );
        $(selectContainerHtml).appendTo($container);
        await Craft.appendBodyHtml(selectContainerJs);
        const group = new Craft.GroupedEntryTypeManager.Group(this, $container);
        this.groups.push(group);

        this.groups
          .filter((g) => g !== group)
          .forEach((g) => {
            g.componentSelect.$components.each((i, c) => {
              group.componentSelect.hideOption($(c).data('id'), false);
            });
          });
      },

      updateDefaultColumns: function () {
        const values = this.settings.$defaultColumnsContainer
          .find('input:checked')
          .toArray()
          .map((input) => input.value);
        Craft.sendActionRequest('POST', 'matrix/default-table-column-options', {
          data: {
            entryTypeIds: this.groups
              .map((g) => g.componentSelect.getSelectedComponentIds())
              .flat(),
          },
        }).then(({data}) => {
          this.settings.$defaultColumnsContainer.empty().append(
            Craft.ui.createCheckboxSelect({
              name: Craft.namespaceInputName(
                'defaultTableColumns',
                this.settings.namespace
              ),
              options: data.options,
              values: values,
              sortable: true,
            })
          );
        });
      },

      refresh: function () {
        this.groups.forEach((group) => {
          group.refresh();
        });
      },
    },
    {
      defaults: {
        $defaultColumnsContainer: null,
        namespace: null,
        entryTypeSelectHtml: null,
        entryTypeSelectJs: null,
      },
    }
  );

  Craft.GroupedEntryTypeManager.Group = Garnish.Base.extend({
    manager: null,
    $container: null,
    $titlebar: null,
    $headingContainer: null,
    $dragHandle: null,
    componentSelect: null,

    init: function (manager, $container) {
      this.manager = manager;
      this.$container = $container;

      this.$container.data('entryTypeGroup', this);

      this.$titlebar = $container.find('.entry-type-group--titlebar');
      this.$headingContainer = this.$titlebar.children('span');
      const $actionsContainer = $('<div/>', {
        class: 'entry-type-group--actions',
      }).appendTo(this.$titlebar);

      const menuId = `menu-${Math.floor(Math.random() * 1000000)}`;
      const $menuButton = Craft.ui
        .createButton({
          class: 'menubtn action-btn',
          controls: menuId,
          ariaLabel: Craft.t('app', 'Actions'),
        })
        .attr('data-disclosure-trigger', 'true')
        .appendTo($actionsContainer);
      $('<div/>', {
        id: menuId,
        class: 'menu menu--disclosure',
      }).appendTo($actionsContainer);
      const disclosureMenu = $menuButton
        .disclosureMenu()
        .data('disclosureMenu');

      disclosureMenu.addItem({
        icon: async () => await Craft.ui.icon('pencil'),
        label: Craft.t('app', 'Rename'),
        onActivate: () => {
          this.showNamePrompt();
        },
      });

      disclosureMenu.addGroup();

      const moveBackwardBtn = disclosureMenu.addItem({
        icon: async () =>
          await Craft.ui.icon(
            Craft.orientation === 'ltr' ? 'arrow-left' : 'arrow-right'
          ),
        label: Craft.t('app', 'Move backward'),
        onActivate: () => {
          this.moveBackward();
        },
        attributes: {
          'data-move-backward': true,
        },
      });

      const moveForwardBtn = disclosureMenu.addItem({
        icon: async () =>
          await Craft.ui.icon(
            Craft.orientation === 'ltr' ? 'arrow-right' : 'arrow-left'
          ),
        label: Craft.t('app', 'Move forward'),
        onActivate: () => {
          this.moveForward();
        },
        attributes: {
          'data-move-forward': true,
        },
      });

      disclosureMenu.addGroup();

      disclosureMenu.addItem({
        icon: 'remove',
        label: Craft.t('app', 'Remove'),
        destructive: true,
        onActivate: () => {
          this.remove();
        },
      });

      disclosureMenu.on('show', () => {
        const $prev = this.$container.prev('li.entry-type-group');
        const $next = this.$container.next('li.entry-type-group');
        disclosureMenu.toggleItem(moveBackwardBtn, $prev.length);
        disclosureMenu.toggleItem(moveForwardBtn, $next.length);
      });

      this.componentSelect = this.$container
        .find('.componentselect')
        .data('componentSelect');

      this.manager.entryTypeSort.addItems(
        this.componentSelect.$components.parent('li')
      );
      const $caboose = $('<li/>', {
        class: 'entry-type-group--caboose',
      }).appendTo(this.componentSelect.$list);
      this.manager.entryTypeSort.addItems($caboose);

      this.componentSelect.on('change', () => {
        this.manager.updateDefaultColumns();
      });

      this.$dragHandle = $('<button/>', {
        type: 'button',
        class: 'icon move',
        title: Craft.t('app', 'Reorder'),
        'aria-label': Craft.t('app', 'Reorder'),
      }).appendTo($actionsContainer);

      this.manager.groupSort.addItems(this.$container);
    },

    get name() {
      return this.$container.data('name');
    },

    showNamePrompt: function () {
      const name = prompt(Craft.t('app', 'Group Name'), this.name);
      if (name === null || name === '') {
        return;
      }

      this.$container.data('name', name);

      this.$headingContainer.text(name);

      this.componentSelect.$components.each((i, chip) => {
        const $chip = $(chip);
        const $input = $chip.find('input');
        const value = JSON.parse($input.val());
        value.group = name;
        $input.val(JSON.stringify(value));
      });
    },

    moveBackward: function () {
      const $prev = this.$container.prev('.entry-type-group');
      if ($prev.length) {
        this.$container.insertBefore($prev);
      }
    },

    moveForward: function () {
      const $next = this.$container.next('.entry-type-group');
      if ($next.length) {
        this.$container.insertAfter($next);
      }
    },

    refresh: function () {
      this.componentSelect.destroy();

      setTimeout(() => {
        this.componentSelect = new Craft.GroupedEntryTypeSelectInput(
          Object.assign(this.componentSelect.settings, {
            addItemsToActionMenus: false,
          })
        );

        this.componentSelect.$components.each((i, chip) => {
          const $input = $(chip).find('input');
          $input.val(
            JSON.stringify(
              Object.assign(JSON.parse($input.val()), {
                group: this.name,
              })
            )
          );
        });
      }, 1);
    },

    remove: function () {
      this.componentSelect.removeComponents(this.componentSelect.$components);
      this.manager.groups = this.manager.groups.filter((g) => g !== this);
      this.$container.remove();
      this.destroy();
    },

    destroy: function () {
      this.componentSelect.destroy();
      delete this.componentSelect;
      this.base();
    },
  });
})(jQuery);
