(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Matrix input class
   */
  Craft.MatrixInput = Garnish.Base.extend(
    {
      id: null,
      entryTypes: null,
      entryTypesByHandle: null,
      inputNamePrefix: null,
      inputIdPrefix: null,

      showingAddEntryMenu: false,
      addEntryBtnGroupWidth: null,
      addEntryBtnContainerWidth: null,

      $container: null,
      $form: null,
      $entriesContainer: null,
      $addEntryBtnContainer: null,
      $addEntryBtnGroup: null,
      $addEntryBtnGroupBtns: null,
      $addEntryMenuBtn: null,
      $statusMessage: null,

      entrySort: null,
      entrySelect: null,
      totalNewEntries: 0,

      init: function (id, entryTypes, inputNamePrefix, settings) {
        this.id = id;
        this.entryTypes = entryTypes;
        this.inputNamePrefix = inputNamePrefix;
        this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);

        // see if settings was actually set to the maxEntriesvalue
        if (typeof settings === 'number') {
          settings = {maxEntries: settings};
        }
        this.setSettings(settings, Craft.MatrixInput.defaults);

        this.$container = $('#' + this.id);
        this.$form = this.$container.closest('form');
        this.$entriesContainer = this.$container.children('.blocks');
        this.$addEntryBtnContainer = this.$container.children('.buttons');
        this.$addEntryBtnGroup =
          this.$addEntryBtnContainer.children('.btngroup');
        this.$addEntryBtnGroupBtns = this.$addEntryBtnGroup.children('.btn');
        this.$addEntryMenuBtn = this.$addEntryBtnContainer.children('.menubtn');
        this.$statusMessage = this.$container.find('[data-status-message]');

        this.$container.data('matrix', this);

        this.setNewEntryBtn();

        this.entryTypesByHandle = {};

        for (let i = 0; i < this.entryTypes.length; i++) {
          const entryType = this.entryTypes[i];
          this.entryTypesByHandle[entryType.handle] = entryType;
        }

        const $entries = this.$entriesContainer.children();
        const collapsedEntries = Craft.MatrixInput.getCollapsedEntryIds();

        this.entrySort = new Garnish.DragSort($entries, {
          handle: '> .actions > .move',
          axis: 'y',
          filter: () => {
            // Only return all the selected items if the target item is selected
            if (this.entrySort.$targetItem.hasClass('sel')) {
              return this.entrySelect.getSelectedItems();
            } else {
              return this.entrySort.$targetItem;
            }
          },
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          helperOpacity: 0.9,
          onDragStop: () => {
            this.trigger('entrySortDragStop');
          },
          onSortChange: () => {
            this.entrySelect.resetItemOrder();
          },
        });

        this.entrySelect = new Garnish.Select(
          this.$entriesContainer,
          $entries,
          {
            multi: true,
            vertical: true,
            handle: '> .checkbox, > .titlebar',
            checkboxMode: true,
          }
        );

        for (let i = 0; i < $entries.length; i++) {
          const $entry = $($entries[i]);
          const entryId = $entry.data('id');

          // Is this a new entry?
          const newMatch =
            typeof entryId === 'string' && entryId.match(/new(\d+)/);

          if (newMatch && newMatch[1] > this.totalNewEntries) {
            this.totalNewEntries = parseInt(newMatch[1]);
          }

          const entry = new Entry(this, $entry);

          if (entry.id && $.inArray('' + entry.id, collapsedEntries) !== -1) {
            entry.collapse();
          }
        }

        this.addListener(this.$addEntryBtnGroupBtns, 'click', function (ev) {
          const type = $(ev.target).data('type');
          this.addEntry(type);
        });

        if (this.$addEntryMenuBtn.length) {
          this.$addEntryMenuBtn.menubtn();
          this.$addEntryMenuBtn.data('menubtn').on('optionSelect', (ev) => {
            this.addEntry($(ev.option).data('type'));
          });
        }

        this.updateAddEntryBtn();

        this.addListener(this.$container, 'resize', 'setNewEntryBtn');
        Garnish.$doc.ready(this.setNewEntryBtn.bind(this));

        this.trigger('afterInit');
      },

      setNewEntryBtn: function () {
        // Do we know what the button group width is yet?
        if (!this.addEntryBtnGroupWidth) {
          this.addEntryBtnGroupWidth = this.$addEntryBtnGroup.width();

          if (!this.addEntryBtnGroupWidth) {
            return;
          }
        }

        // Only check if the container width has resized
        if (
          this.addEntryBtnContainerWidth !==
          (this.addEntryBtnContainerWidth = this.$addEntryBtnContainer.width())
        ) {
          if (this.addEntryBtnGroupWidth > this.addEntryBtnContainerWidth) {
            if (!this.showingAddEntryMenu) {
              this.$addEntryBtnGroup.addClass('hidden');
              this.$addEntryMenuBtn.removeClass('hidden');
              this.showingAddEntryMenu = true;
            }
          } else {
            if (this.showingAddEntryMenu) {
              this.$addEntryMenuBtn.addClass('hidden');
              this.$addEntryBtnGroup.removeClass('hidden');
              this.showingAddEntryMenu = false;

              // Because Safari is awesome
              if (navigator.userAgent.indexOf('Safari') !== -1) {
                Garnish.requestAnimationFrame(() => {
                  this.$addEntryBtnGroup.css('opacity', 0.99);

                  Garnish.requestAnimationFrame(() => {
                    this.$addEntryBtnGroup.css('opacity', '');
                  });
                });
              }
            }
          }
        }
      },

      canAddMoreEntries: function () {
        return (
          !this.maxEntries ||
          this.$entriesContainer.children().length < this.maxEntries
        );
      },

      updateAddEntryBtn: function () {
        if (this.canAddMoreEntries()) {
          this.$addEntryBtnGroup.removeClass('disabled');
          this.$addEntryMenuBtn.removeClass('disabled');

          this.$addEntryBtnGroupBtns.each(function () {
            $(this).removeAttr('aria-disabled');
          });

          for (let i = 0; i < this.entrySelect.$items.length; i++) {
            const entry = this.entrySelect.$items.eq(i).data('entry');

            if (entry) {
              entry.$actionMenu
                .find('a[data-action=add]')
                .parent()
                .removeClass('disabled');
              entry.$actionMenu
                .find('a[data-action=add]')
                .removeAttr('aria-disabled');
            }
          }
        } else {
          this.$addEntryBtnGroup.addClass('disabled');
          this.$addEntryMenuBtn.addClass('disabled');

          this.$addEntryBtnGroupBtns.each(function () {
            $(this).attr('aria-disabled', 'true');
          });

          for (let i = 0; i < this.entrySelect.$items.length; i++) {
            const entry = this.entrySelect.$items.eq(i).data('entry');

            if (entry) {
              entry.$actionMenu
                .find('a[data-action=add]')
                .parent()
                .addClass('disabled');
              entry.$actionMenu
                .find('a[data-action=add]')
                .attr('aria-disabled', 'true');
            }
          }
        }
      },

      updateStatusMessage: function () {
        this.$statusMessage.empty();
        let message;

        if (!this.canAddMoreEntries()) {
          message = Craft.t(
            'app',
            'Entry could not be added. Maximum number of entries reached.'
          );
        }

        setTimeout(() => {
          this.$statusMessage.text(message);
        }, 250);
      },

      addEntry: function (type, $insertBefore, autofocus) {
        if (!this.canAddMoreEntries()) {
          this.updateStatusMessage();
          return;
        }

        this.totalNewEntries++;

        const id = `new${this.totalNewEntries}`;
        const typeName = this.entryTypesByHandle[type].name;
        const actionMenuId = `matrixblock-action-menu-${id}`;

        let html = `
                <div class="matrixblock" data-id="${id}" data-type="${type}" data-type-name="${typeName}" role="listitem">
                  <input type="hidden" name="${
                    this.inputNamePrefix
                  }[sortOrder][]" value="${id}"/>
                  <input type="hidden" name="${
                    this.inputNamePrefix
                  }[entries][${id}][type]" value="${type}"/>
                  <input type="hidden" name="${
                    this.inputNamePrefix
                  }[entries][${id}][enabled]" value="1"/>
                  <div class="titlebar">
                    <div class="entrytype">${
                      this.getEntryTypeByHandle(type).name
                    }</div>
                    <div class="preview"></div>
                  </div>
                  <div class="checkbox" title="${Craft.t(
                    'app',
                    'Select'
                  )}"></div>
                  <div class="actions">
                    <div class="status off" title="${Craft.t(
                      'app',
                      'Disabled'
                    )}"></div>
                    <div>
                      <button type="button" class="btn settings icon menubtn" title="${Craft.t(
                        'app',
                        'Actions'
                      )}" aria-controls="${actionMenuId}" data-disclosure-trigger></button>
                        <div id="${actionMenuId}" class="menu menu--disclosure">
                         <ul class="padded">
                            <li><a data-icon="collapse" data-action="collapse" href="#" aria-label="${Craft.t(
                              'app',
                              'Collapse'
                            )}" type="button" role="button">${Craft.t(
          'app',
          'Collapse'
        )}</a></li>
                            <li class="hidden"><a data-icon="expand" data-action="expand" href="#" aria-label="${Craft.t(
                              'app',
                              'Expand'
                            )}" type="button" role="button">${Craft.t(
          'app',
          'Expand'
        )}</a></li>
                            <li><a data-icon="disabled" data-action="disable" href="#" aria-label="${Craft.t(
                              'app',
                              'Disable'
                            )}" type="button" role="button">${Craft.t(
          'app',
          'Disable'
        )}</a></li>
                            <li class="hidden"><a data-icon="enabled" data-action="enable" href="#" aria-label="${Craft.t(
                              'app',
                              'Enable'
                            )}" type="button" role="button">${Craft.t(
          'app',
          'Enable'
        )}</a></li>
                            <li><a data-icon="uarr" data-action="moveUp" href="#" aria-label="${Craft.t(
                              'app',
                              'Move up'
                            )}" type="button" role="button">${Craft.t(
          'app',
          'Move up'
        )}</a></li>
                            <li><a data-icon="darr" data-action="moveDown" href="#" aria-label="${Craft.t(
                              'app',
                              'Move down'
                            )}" type="button" role="button">${Craft.t(
          'app',
          'Move down'
        )}</a></li>
                          </ul>`;

        if (!this.settings.staticEntries) {
          html += `
                          <hr class="padded"/>
                          <ul class="padded">
                            <li><a class="error" data-icon="remove" data-action="delete" href="#" aria-label="${Craft.t(
                              'app',
                              'Delete'
                            )}" type="button" role="button">${Craft.t(
            'app',
            'Delete'
          )}</a></li>
                          </ul>
                          <hr class="padded"/>
                          <ul class="padded">`;

          for (let i = 0; i < this.entryTypes.length; i++) {
            const entryType = this.entryTypes[i];
            html += `
                            <li><a data-icon="plus" data-action="add" data-type="${
                              entryType.handle
                            }" href="#" aria-label="${Craft.t(
              'app',
              'Add {type} above',
              {type: entryType.name}
            )}" type="button" role="button">${Craft.t(
              'app',
              'Add {type} above',
              {type: entryType.name}
            )}</a></li>`;
          }

          html += `
                          </ul>`;
        }

        html += `
                        </div>
                      </div>
                    <a class="move icon" title="${Craft.t(
                      'app',
                      'Reorder'
                    )}" role="button"></a>
                  </div>
                </div>`;

        const $entry = $(html);

        // Pause the draft editor
        const elementEditor = this.$form.data('elementEditor');
        if (elementEditor) {
          elementEditor.pause();
        }

        if ($insertBefore) {
          $entry.insertBefore($insertBefore);
        } else {
          $entry.appendTo(this.$entriesContainer);
        }

        const $fieldsContainer = $('<div class="fields"/>').appendTo($entry);
        const bodyHtml = this.getParsedEntryHtml(
            this.entryTypesByHandle[type].bodyHtml,
            id
          ),
          js = this.getParsedEntryHtml(this.entryTypesByHandle[type].js, id);

        $(bodyHtml).appendTo($fieldsContainer);

        this.trigger('entryAdded', {
          $entry: $entry,
        });

        // Animate the entry into position
        $entry.css(this.getHiddenEntryCss($entry)).velocity(
          {
            opacity: 1,
            'margin-bottom': 10,
          },
          'fast',
          () => {
            $entry.css('margin-bottom', '');
            Garnish.$bod.append(js);
            Craft.initUiElements($fieldsContainer);
            new Entry(this, $entry);
            this.entrySort.addItems($entry);
            this.entrySelect.addItems($entry);
            this.updateAddEntryBtn();

            Garnish.requestAnimationFrame(() => {
              if (typeof autofocus === 'undefined' || autofocus) {
                // Scroll to the entry
                Garnish.scrollContainerToElement($entry);
                // Focus on the first focusable element
                $entry.find('.flex-fields :focusable').first().trigger('focus');
              }

              // Resume the draft editor
              if (elementEditor) {
                elementEditor.resume();
              }
            });
          }
        );
      },

      getEntryTypeByHandle: function (handle) {
        for (let i = 0; i < this.entryTypes.length; i++) {
          if (this.entryTypes[i].handle === handle) {
            return this.entryTypes[i];
          }
        }
      },

      collapseSelectedEntries: function () {
        this.callOnSelectedEntries('collapse');
      },

      expandSelectedEntries: function () {
        this.callOnSelectedEntries('expand');
      },

      disableSelectedEntries: function () {
        this.callOnSelectedEntries('disable');
      },

      enableSelectedEntries: function () {
        this.callOnSelectedEntries('enable');
      },

      deleteSelectedEntries: function () {
        this.callOnSelectedEntries('selfDestruct');
      },

      callOnSelectedEntries: function (fn) {
        for (let i = 0; i < this.entrySelect.$selectedItems.length; i++) {
          this.entrySelect.$selectedItems.eq(i).data('entry')[fn]();
        }
      },

      getHiddenEntryCss: function ($entry) {
        return {
          opacity: 0,
          marginBottom: -$entry.outerHeight(),
        };
      },

      getParsedEntryHtml: function (html, id) {
        if (typeof html === 'string') {
          return html.replace(
            new RegExp(`__ENTRY_${this.settings.placeholderKey}__`, 'g'),
            id
          );
        } else {
          return '';
        }
      },

      get maxEntries() {
        return this.settings.maxEntries;
      },
    },
    {
      defaults: {
        placeholderKey: null,
        maxEntries: null,
        staticEntries: false,
      },

      collapsedEntryStorageKey:
        'Craft-' + Craft.systemUid + '.MatrixInput.collapsedEntries',

      getCollapsedEntryIds: function () {
        if (
          typeof localStorage[Craft.MatrixInput.collapsedEntryStorageKey] ===
          'string'
        ) {
          return Craft.filterArray(
            localStorage[Craft.MatrixInput.collapsedEntryStorageKey].split(',')
          );
        } else {
          return [];
        }
      },

      setCollapsedEntryIds: function (ids) {
        localStorage[Craft.MatrixInput.collapsedEntryStorageKey] =
          ids.join(',');
      },

      rememberCollapsedEntryId: function (id) {
        if (typeof Storage !== 'undefined') {
          const collapsedEntries = Craft.MatrixInput.getCollapsedEntryIds();

          if ($.inArray('' + id, collapsedEntries) === -1) {
            collapsedEntries.push(id);
            Craft.MatrixInput.setCollapsedEntryIds(collapsedEntries);
          }
        }
      },

      forgetCollapsedEntryId: function (id) {
        if (typeof Storage !== 'undefined') {
          const collapsedEntries = Craft.MatrixInput.getCollapsedEntryIds();
          const collapsedEntriesIndex = $.inArray('' + id, collapsedEntries);

          if (collapsedEntriesIndex !== -1) {
            collapsedEntries.splice(collapsedEntriesIndex, 1);
            Craft.MatrixInput.setCollapsedEntryIds(collapsedEntries);
          }
        }
      },
    }
  );

  const Entry = Garnish.Base.extend({
    matrix: null,
    $container: null,
    $titlebar: null,
    $fieldsContainer: null,
    $previewContainer: null,
    $actionMenu: null,
    $collapsedInput: null,

    actionDisclosure: null,

    isNew: null,
    id: null,

    collapsed: false,

    init: function (matrix, $container) {
      this.matrix = matrix;
      this.$container = $container;
      this.$titlebar = $container.children('.titlebar');
      this.$previewContainer = this.$titlebar.children('.preview');
      this.$fieldsContainer = $container.children('.fields');

      this.$container.data('entry', this);

      this.id = this.$container.data('id');
      this.isNew =
        !this.id ||
        (typeof this.id === 'string' && this.id.substring(0, 3) === 'new');

      const $actionMenuBtn = this.$container.find(
        '> .actions [data-disclosure-trigger]'
      );
      const actionDisclosure =
        $actionMenuBtn.data('trigger') ||
        new Garnish.DisclosureMenu($actionMenuBtn);

      this.$actionMenu = actionDisclosure.$container;
      this.actionDisclosure = actionDisclosure;

      actionDisclosure.on('show', () => {
        this.$container.addClass('active');
        if (this.$container.prev('.matrixblock').length) {
          this.$actionMenu
            .find('a[data-action=moveUp]:first')
            .parent()
            .removeClass('hidden');
        } else {
          this.$actionMenu
            .find('a[data-action=moveUp]:first')
            .parent()
            .addClass('hidden');
        }
        if (this.$container.next('.matrixblock').length) {
          this.$actionMenu
            .find('a[data-action=moveDown]:first')
            .parent()
            .removeClass('hidden');
        } else {
          this.$actionMenu
            .find('a[data-action=moveDown]:first')
            .parent()
            .addClass('hidden');
        }
      });

      actionDisclosure.on('hide', () => {
        this.$container.removeClass('active');
      });

      this.$actionMenuOptions = this.$actionMenu.find('a[data-action]');

      this.addListener(
        this.$actionMenuOptions,
        'click',
        this.handleActionClick
      );
      this.addListener(
        this.$actionMenuOptions,
        'keydown',
        this.handleActionKeydown
      );

      // Was this entry already collapsed?
      if (Garnish.hasAttr(this.$container, 'data-collapsed')) {
        this.collapse();
      }

      this._handleTitleBarClick = function (ev) {
        ev.preventDefault();
        this.toggle();
      };

      this.addListener(this.$titlebar, 'doubletap', this._handleTitleBarClick);
    },

    toggle: function () {
      if (this.collapsed) {
        this.expand();
      } else {
        this.collapse(true);
      }
    },

    collapse: function (animate) {
      if (this.collapsed) {
        return;
      }

      this.$container.addClass('collapsed');

      let previewHtml = '';
      const $fields = this.$fieldsContainer.children().children();

      for (let i = 0; i < $fields.length; i++) {
        const $field = $($fields[i]);
        const $inputs = $field
          .children('.input')
          .find('select,input[type!="hidden"],textarea,.label');
        let inputPreviewText = '';

        for (let j = 0; j < $inputs.length; j++) {
          const $input = $($inputs[j]);
          let value;

          if ($input.hasClass('label')) {
            const $maybeLightswitchContainer = $input.parent().parent();

            if (
              $maybeLightswitchContainer.hasClass('lightswitch') &&
              (($maybeLightswitchContainer.hasClass('on') &&
                $input.hasClass('off')) ||
                (!$maybeLightswitchContainer.hasClass('on') &&
                  $input.hasClass('on')))
            ) {
              continue;
            }

            value = $input.text();
          } else {
            value = Craft.getText(this._inputPreviewText($input));
          }

          if (value instanceof Array) {
            value = value.join(', ');
          }

          if (value) {
            value = Craft.trim(Craft.escapeHtml(value));

            if (value) {
              if (inputPreviewText) {
                inputPreviewText += ', ';
              }

              inputPreviewText += value;
            }
          }
        }

        if (inputPreviewText) {
          previewHtml +=
            (previewHtml ? ' <span>|</span> ' : '') + inputPreviewText;
        }
      }

      this.$previewContainer.html(previewHtml);

      this.$fieldsContainer.velocity('stop');
      this.$container.velocity('stop');

      if (animate && !Garnish.prefersReducedMotion()) {
        this.$fieldsContainer.velocity('fadeOut', {duration: 'fast'});
        this.$container.velocity({height: 32}, 'fast');
      } else {
        this.$previewContainer.show();
        this.$fieldsContainer.hide();
        this.$container.css({height: 32});
      }

      setTimeout(() => {
        this.$actionMenu
          .find('a[data-action=collapse]:first')
          .parent()
          .addClass('hidden');
        this.$actionMenu
          .find('a[data-action=expand]:first')
          .parent()
          .removeClass('hidden');
      }, 200);

      // Remember that?
      if (!this.isNew) {
        Craft.MatrixInput.rememberCollapsedEntryId(this.id);
      } else {
        if (!this.$collapsedInput) {
          this.$collapsedInput = $(
            '<input type="hidden" name="' +
              this.matrix.inputNamePrefix +
              '[entries][' +
              this.id +
              '][collapsed]" value="1"/>'
          ).appendTo(this.$container);
        } else {
          this.$collapsedInput.val('1');
        }
      }

      this.collapsed = true;
    },

    _inputPreviewText: function ($input) {
      if ($input.is('select,multiselect')) {
        const labels = [];
        const $options = $input.find('option:selected');
        for (let k = 0; k < $options.length; k++) {
          labels.push($options.eq(k).text());
        }
        return labels;
      }

      if (
        $input.is('input[type="checkbox"]:checked,input[type="radio"]:checked')
      ) {
        const id = $input.attr('id');
        const $label = $(`label[for="${id}"]`);
        if ($label.length) {
          return $label.text();
        }
      }

      return Garnish.getInputPostVal($input);
    },

    expand: function () {
      if (!this.collapsed) {
        return;
      }

      this.$container.removeClass('collapsed');

      this.$fieldsContainer.velocity('stop');
      this.$container.velocity('stop');

      const collapsedContainerHeight = this.$container.height();
      this.$container.height('auto');
      this.$fieldsContainer.show();
      const expandedContainerHeight = this.$container.height();
      const displayValue = this.$fieldsContainer.css('display') || 'block';
      this.$container.height(collapsedContainerHeight);
      this.$fieldsContainer
        .hide()
        .velocity('fadeIn', {duration: 'fast', display: displayValue});

      const animationDuration = Garnish.prefersReducedMotion() ? 0 : 'fast';
      this.$container.velocity(
        {height: expandedContainerHeight},
        animationDuration,
        () => {
          this.$previewContainer.html('');
          this.$container.height('auto');
          this.$container.trigger('scroll');
        }
      );

      setTimeout(() => {
        this.$actionMenu
          .find('a[data-action=collapse]:first')
          .parent()
          .removeClass('hidden');
        this.$actionMenu
          .find('a[data-action=expand]:first')
          .parent()
          .addClass('hidden');
      }, 200);

      // Remember that?
      if (!this.isNew && typeof Storage !== 'undefined') {
        const collapsedEntries = Craft.MatrixInput.getCollapsedEntryIds();
        const collapsedEntriesIndex = $.inArray('' + this.id, collapsedEntries);

        if (collapsedEntriesIndex !== -1) {
          collapsedEntries.splice(collapsedEntriesIndex, 1);
          Craft.MatrixInput.setCollapsedEntryIds(collapsedEntries);
        }
      }

      if (!this.isNew) {
        Craft.MatrixInput.forgetCollapsedEntryId(this.id);
      } else if (this.$collapsedInput) {
        this.$collapsedInput.val('');
      }

      this.collapsed = false;
    },

    disable: function () {
      this.$container.children('input[name$="[enabled]"]:first').val('');
      this.$container.addClass('disabled');

      setTimeout(() => {
        this.$actionMenu
          .find('a[data-action=disable]:first')
          .parent()
          .addClass('hidden');
        this.$actionMenu
          .find('a[data-action=enable]:first')
          .parent()
          .removeClass('hidden');
      }, 200);

      this.collapse(true);
    },

    enable: function () {
      this.$container.children('input[name$="[enabled]"]:first').val('1');
      this.$container.removeClass('disabled');

      setTimeout(() => {
        this.$actionMenu
          .find('a[data-action=disable]:first')
          .parent()
          .removeClass('hidden');
        this.$actionMenu
          .find('a[data-action=enable]:first')
          .parent()
          .addClass('hidden');
      }, 200);
    },

    moveUp: function () {
      this.matrix.trigger('beforeMoveEntryUp', {
        entry: this,
      });
      let $prev = this.$container.prev('.matrixblock');
      if ($prev.length) {
        this.$container.insertBefore($prev);
        this.matrix.entrySelect.resetItemOrder();
      }
      this.matrix.trigger('moveEntryUp', {
        entry: this,
      });
    },

    moveDown: function () {
      this.matrix.trigger('beforeMoveEntryDown', {
        entry: this,
      });
      let $next = this.$container.next('.matrixblock');
      if ($next.length) {
        this.$container.insertAfter($next);
        this.matrix.entrySelect.resetItemOrder();
      }
      this.matrix.trigger('moveEntryDown', {
        entry: this,
      });
    },

    handleActionClick: function (event) {
      event.preventDefault();
      this.onActionSelect(event.target);
    },

    handleActionKeydown: function (event) {
      const keyCode = event.keyCode;

      if (keyCode !== Garnish.SPACE_KEY) return;

      event.preventDefault();
      this.onActionSelect(event.target);
    },

    onActionSelect: function (option) {
      const batchAction =
          this.matrix.entrySelect.totalSelected > 1 &&
          this.matrix.entrySelect.isSelected(this.$container),
        $option = $(option);

      switch ($option.data('action')) {
        case 'collapse': {
          if (batchAction) {
            this.matrix.collapseSelectedEntries();
          } else {
            this.collapse(true);
          }

          break;
        }

        case 'expand': {
          if (batchAction) {
            this.matrix.expandSelectedEntries();
          } else {
            this.expand();
          }

          break;
        }

        case 'disable': {
          if (batchAction) {
            this.matrix.disableSelectedEntries();
          } else {
            this.disable();
          }

          break;
        }

        case 'enable': {
          if (batchAction) {
            this.matrix.enableSelectedEntries();
          } else {
            this.enable();
            this.expand();
          }

          break;
        }

        case 'moveUp': {
          this.moveUp();
          break;
        }

        case 'moveDown': {
          this.moveDown();
          break;
        }

        case 'add': {
          const type = $option.data('type');
          this.matrix.addEntry(type, this.$container);
          break;
        }

        case 'delete': {
          if (batchAction) {
            if (
              confirm(
                Craft.t(
                  'app',
                  'Are you sure you want to delete the selected entries?'
                )
              )
            ) {
              this.matrix.deleteSelectedEntries();
            }
          } else {
            this.selfDestruct();
          }

          break;
        }
      }

      this.actionDisclosure.hide();
    },

    selfDestruct: function () {
      // Remove any inputs from the form data
      $('[name]', this.$container).removeAttr('name');

      this.$container.velocity(
        this.matrix.getHiddenEntryCss(this.$container),
        'fast',
        () => {
          this.$container.remove();
          this.matrix.updateAddEntryBtn();

          this.matrix.trigger('entryDeleted', {
            $entry: this.$container,
          });
        }
      );
    },
  });
})(jQuery);
