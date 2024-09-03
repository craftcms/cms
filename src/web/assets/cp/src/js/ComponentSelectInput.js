/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

/**
 * Base component select input
 */
Craft.ComponentSelectInput = Garnish.Base.extend(
  {
    componentSelect: null,
    componentSort: null,

    $container: null,
    $form: null,
    $list: null,
    $components: null,
    $addBtn: null,
    $createBtn: null,

    _initialized: false,

    init: function (settings) {
      this.setSettings(settings, Craft.ComponentSelectInput.defaults);

      // No reason for this to be sortable if we're only allowing one selection
      if (this.settings.limit === 1) {
        this.settings.sortable = false;
      }

      this.$container = $(`#${this.settings.id}`);
      this.$form = this.$container.closest('form');

      // Store a reference to this class
      this.$container.data('componentSelect', this);

      this.$list = this.$container.children('ul');
      this.$addBtn = this.$container.find('.add-btn:first');
      this.$createBtn = this.$container.find('.create-btn:first');

      this.initComponentSelect();
      this.initComponentSort();
      this.resetComponents();

      if (this.$addBtn.length) {
        this.addListener(this.getOptions(), 'activate', (ev) => {
          const $button = $(ev.currentTarget);
          this.addComponent($button.data('type'), $button.data('id'));
        });
      }

      if (this.$createBtn.length && this.settings.createAction) {
        this.addListener(this.$createBtn, 'activate', () => {
          const slideout = new Craft.CpScreenSlideout(
            this.settings.createAction
          );
          slideout.on('submit', (ev) => {
            const data = ev.response.data;
            this.addComponent(data.modelClass, data.modelId, true);
          });
          slideout.on('close', () => {
            this.$createBtn.focus();
          });
        });
      }

      if (this.componentSelect) {
        this.addListener(Garnish.$win, 'mousedown', (ev) => {
          if (
            !this.$container.is(ev.target) &&
            !this.$container.find(ev.target).length
          ) {
            this.componentSelect.deselectAll();
          }
        });
      }

      this._initialized = true;
    },

    get totalSelected() {
      return this.$components.length;
    },

    getComponents() {
      return this.$list.find('.chip');
    },

    getOptions() {
      if (!this.$addBtn.length) {
        return $();
      }

      return this.$addBtn
        .disclosureMenu()
        .data('disclosureMenu')
        .$container.find('button');
    },

    getOption(id) {
      return this.getOptions().filter(`[data-id="${id}"]`);
    },

    showOption(id) {
      this.getOption(id).parent('li').removeClass('hidden');
    },

    hideOption(id) {
      this.getOption(id).parent('li').addClass('hidden');
    },

    initComponentSelect: function () {
      if (this.settings.selectable) {
        this.componentSelect = new Garnish.Select({
          multi: this.settings.sortable,
          filter: (target) => {
            return !$(target).closest('a[href],button,[role=button]').length;
          },
          // prevent keyboard focus since component selection is only needed for drag-n-drop
          makeFocusable: false,
        });
      }
    },

    initComponentSort: function () {
      if (this.settings.sortable) {
        this.componentSort = new Garnish.DragSort({
          container: this.$list,
          filter: this.settings.selectable
            ? () => {
                // Only return all the selected items if the target item is selected
                if (
                  this.componentSort.$targetItem
                    .children('.chip')
                    .hasClass('sel')
                ) {
                  return this.componentSelect.getSelectedItems().parent('li');
                } else {
                  return this.componentSort.$targetItem;
                }
              }
            : null,
          ignoreHandleSelector: '.delete',
          handle: '> .chip > .chip-content > .chip-actions > .move',
          axis: this.getComponentSortAxis(),
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          onSortChange: () => {
            this.onChange();
          },
        });
      }
    },

    getComponentSortAxis: function () {
      if (!this.$list.hasClass('inline-chips')) {
        return 'y';
      }
      return null;
    },

    canAddMoreComponents: function () {
      return (
        !this.settings.limit || this.$components.length < this.settings.limit
      );
    },

    updateButtons() {
      if (this.canAddMoreComponents()) {
        if (this.$addBtn.length) {
          if (this.getOptions().parent(':not(.hidden)').length) {
            this.$addBtn.removeClass('hidden');
          } else {
            this.$addBtn.addClass('hidden');
          }
        }

        if (this.$createBtn.length) {
          this.$createBtn.removeClass('hidden');
        }
      } else {
        if (this.$addBtn.length) {
          this.$addBtn.addClass('hidden');
        }
        if (this.$createBtn.length) {
          this.$createBtn.addClass('hidden');
        }
      }

      const $container = this.$addBtn.length && this.$addBtn.parent('.flex');
      if ($container && $container.length) {
        if ($container.children(':not(.hidden)').length) {
          $container.removeClass('hidden');
        } else {
          $container.addClass('hidden');
        }
      }
    },

    focusNextLogicalElement: function () {
      if (this.canAddMoreComponents()) {
        // If can add more components, focus ADD button
        if (this.$addBtn.length) {
          this.$addBtn.get(0).focus();
        }
      } else {
        // If can't add more components, focus on the final remove
        this.focusLastRemoveBtn();
      }
    },

    focusLastRemoveBtn: function () {
      const $removeBtns = this.$container.find('.delete');

      if (!$removeBtns.length) return;

      $removeBtns.last()[0].focus();
    },

    resetComponents: function () {
      if (this.$components !== null) {
        this.removeComponents(this.$components);
      } else {
        this.$components = $();
      }

      this.addComponents(this.getComponents());
    },

    addComponents: function ($components) {
      // add the action triggers
      for (let i = 0; i < $components.length; i++) {
        const $component = $components.eq(i);

        const actions = this.defineComponentActions($component);
        Craft.addActionsToChip($component, actions);

        const disclosureMenu = $component
          .find('> .chip-content > .chip-actions .action-btn')
          .disclosureMenu()
          .data('disclosureMenu');
        const moveForwardBtn = disclosureMenu.$container.find(
          '[data-move-forward]'
        )[0];
        const moveBackwardBtn = disclosureMenu.$container.find(
          '[data-move-backward]'
        )[0];

        disclosureMenu.on('show', () => {
          const $li = $component.parent();
          const $prev = $li.prev();
          const $next = $li.next();

          if (moveForwardBtn) {
            disclosureMenu.toggleItem(moveForwardBtn, $prev.length);
          }
          if (moveBackwardBtn) {
            disclosureMenu.toggleItem(moveBackwardBtn, $next.length);
          }
        });

        if (this.settings.sortable) {
          $('<button/>', {
            type: 'button',
            class: 'move icon',
            title: Craft.t('app', 'Reorder'),
            'aria-label': Craft.t('app', 'Reorder'),
            'aria-describedby': $component.find('.label').attr('id'),
          }).appendTo($component.find('.chip-actions'));
        }

        this.addListener($component, 'dblclick,taphold', (ev) => {
          // don't open the edit slideout if we are tapholding to drag
          if (ev.type === 'taphold' && ev.target.nodeName === 'BUTTON') {
            return;
          }
          disclosureMenu.$container.find('[data-edit-action]').click();
        });

        this.hideOption($component.data('id'));
      }

      if (this.settings.selectable) {
        this.componentSelect.addItems($components);
      }

      if (this.settings.sortable) {
        this.componentSort.addItems($components.parent('li'));
      }

      $components.on('keydown', (ev) => {
        if ([Garnish.BACKSPACE_KEY, Garnish.DELETE_KEY].includes(ev.keyCode)) {
          ev.stopPropagation();
          ev.preventDefault();
          const $selected = this.componentSelect.getSelectedItems();
          for (let i = 0; i < $selected.length; i++) {
            this.removeComponent($selected.eq(i));
          }
        }
      });

      this.$components = this.$components.add($components);

      this.onChange();
    },

    defineComponentActions: function ($component) {
      const actions = [];

      if (this.settings.sortable) {
        const axis = this.getComponentSortAxis();
        actions.push({
          icon:
            axis === 'y'
              ? 'arrow-up'
              : Craft.orientation === 'ltr'
                ? 'arrow-left'
                : 'arrow-right',
          label:
            axis === 'y'
              ? Craft.t('app', 'Move up')
              : Craft.t('app', 'Move forward'),
          callback: () => {
            this.moveComponentForward($component);
          },
          attributes: {
            'data-move-forward': true,
          },
        });
        actions.push({
          icon:
            axis === 'y'
              ? 'arrow-down'
              : Craft.orientation === 'ltr'
                ? 'arrow-right'
                : 'arrow-left',
          label:
            axis === 'y'
              ? Craft.t('app', 'Move down')
              : Craft.t('app', 'Move backward'),
          callback: () => {
            this.moveComponentBackward($component);
          },
          attributes: {
            'data-move-backward': true,
          },
        });
      }

      actions.push({
        icon: 'remove',
        label: Craft.t('app', 'Remove'),
        callback: () => {
          this.removeComponent($component);
        },
        destructive: true,
      });

      return actions;
    },

    onChange() {
      this.componentSelect?.resetItemOrder();
      this.$components = $().add(this.$components);

      this.updateButtons();

      if (this._initialized) {
        this.trigger('change');
      }
    },

    moveComponentForward($element) {
      const $li = $element.closest('li');
      const $prev = $li.prev();
      if ($prev.length) {
        $li.insertBefore($prev);
        this.onChange();
      }
    },

    moveComponentBackward($element) {
      const $li = $element.closest('li');
      const $next = $li.next();
      if ($next.length) {
        $li.insertAfter($next);
        this.onChange();
      }
    },

    removeComponents: function ($components) {
      if (this.settings.selectable) {
        this.componentSelect.removeItems($components);
      }

      // Disable the hidden input in case the form is submitted before this component gets removed from the DOM
      $components.children('input').prop('disabled', true);

      for (let i = 0; i < $components.length; i++) {
        this.showOption($components.eq(i).data('id'));
      }

      // Move the focus to the next component in the list, if there is one
      let $nextComponent;
      if (this.settings.selectable) {
        const lastComponentIndex = this.$components.index($components.last());
        $nextComponent = this.$components.eq(lastComponentIndex + 1);
      }
      if ($nextComponent.length) {
        $nextComponent.focus();
      } else {
        this.focusNextLogicalElement();
      }

      this.$components = this.$components.not($components);
      this.onChange();
    },

    removeComponent: function ($component) {
      // Remove any inputs from the form data
      $('[name]', $component).removeAttr('name');
      this.removeComponents($component);
      this.animateComponentAway($component, () => {
        $component.parent('li').remove();
      });
    },

    animateComponentAway: function ($component, callback) {
      $component.css('z-index', 0);

      var animateCss = {
        opacity: -1,
      };
      animateCss['margin-' + Craft.left] = -(
        $component.outerWidth() +
        parseInt($component.css('margin-' + Craft.right))
      );

      animateCss['margin-bottom'] = -(
        $component.outerHeight() + parseInt($component.css('margin-bottom'))
      );

      $component.velocity(
        animateCss,
        Craft.ComponentSelectInput.REMOVE_FX_DURATION,
        () => {
          if (callback) {
            callback();
          }
        }
      );
    },

    getSelectedComponentIds() {
      const ids = [];
      for (let i = 0; i < this.$components.length; i++) {
        ids.push(this.$components.eq(i).data('id'));
      }
      return ids;
    },

    async addComponent(type, id, addToMenu = false) {
      const disclosureMenu = this.$addBtn.length
        ? this.$addBtn.disclosureMenu().data('disclosureMenu')
        : null;

      const {data} = await Craft.sendActionRequest(
        'POST',
        'app/render-components',
        {
          data: {
            components: [
              {
                type,
                id,
                instances: [
                  {
                    showActionMenu: this.settings.showActionMenu,
                    showHandle: this.settings.showHandles,
                    inputName: this.settings.name,
                  },
                ],
              },
            ],
            withMenuItems: addToMenu,
            menuId: disclosureMenu?.$container.attr('id'),
          },
        }
      );

      const canAdd = this.canAddMoreComponents();

      if (canAdd) {
        const $component = $(data.components[type][id][0]);
        $('<li/>').append($component).appendTo(this.$list);
        this.addComponents($component);
      }

      if (addToMenu && disclosureMenu) {
        const $menuItem = $(data.menuItems[type][id]);
        disclosureMenu.addItem($menuItem);
        if (canAdd) {
          disclosureMenu.hideItem($menuItem.children()[0]);
        }
        this.addListener($menuItem.find('button'), 'activate', () => {
          this.addComponent(type, id);
        });
      }

      await Craft.appendHeadHtml(data.headHtml);
      await Craft.appendBodyHtml(data.bodyHtml);
    },
  },
  {
    REMOVE_FX_DURATION: 200,
    defaults: {
      id: null,
      name: null,
      limit: null,
      showHandles: false,
      sortable: true,
      selectable: true,
      showActionMenu: true,
      createAction: null,
    },
  }
);
