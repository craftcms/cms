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
      $addEntryBtn: null,
      $addEntryMenuBtn: null,
      $statusMessage: null,

      entrySort: null,
      entrySelect: null,

      /**
       * @type {Craft.ElementEditor|null}
       */
      elementEditor: null,

      addingEntry: false,

      init: function (id, entryTypes, inputNamePrefix, settings) {
        this.id = id;
        this.entryTypes = entryTypes;
        this.inputNamePrefix = inputNamePrefix;
        this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);

        // see if settings was actually set to the maxEntries value
        if (typeof settings === 'number') {
          settings = {maxEntries: settings};
        }
        this.setSettings(settings, Craft.MatrixInput.defaults);

        this.$container = $('#' + this.id);
        this.$form = this.$container.closest('form');
        this.$entriesContainer = this.$container.children('.blocks');
        this.$addEntryBtnContainer = this.$container.children('.buttons');
        this.$addEntryBtn =
          this.$addEntryBtnContainer.children('.btn:not(.menubtn)');
        this.$addEntryMenuBtn = this.$addEntryBtnContainer.children('.menubtn');
        this.$statusMessage = this.$container.find('[data-status-message]');

        this.$container.data('matrix', this);

        this.entryTypesByHandle = {};

        for (let i = 0; i < this.entryTypes.length; i++) {
          const entryType = this.entryTypes[i];
          this.entryTypesByHandle[entryType.handle] = entryType;
        }

        const $entries = this.$entriesContainer.children('.matrixblock');
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
            handle: '> .actions > .checkbox, > .titlebar',
            filter: (target) => {
              return !$(target).closest('.tab-label').length;
            },
            checkboxMode: true,
          }
        );

        for (let i = 0; i < $entries.length; i++) {
          const $entry = $($entries[i]);
          const entry = new Craft.MatrixInput.Entry(this, $entry);

          if (entry.id && $.inArray('' + entry.id, collapsedEntries) !== -1) {
            entry.collapse();
          }
        }

        this.addListener(this.$addEntryBtn, 'activate', async function () {
          this.$addEntryBtn.addClass('loading');
          Craft.cp.announce(Craft.t('app', 'Loading'));
          try {
            await this.addEntry(this.$addEntryBtn.data('type'));
          } finally {
            this.$addEntryBtn.removeClass('loading');
          }
        });

        if (this.$addEntryMenuBtn.length) {
          this.$addEntryMenuBtn
            .disclosureMenu()
            .data('disclosureMenu')
            .$container.find('button')
            .on('activate', async (ev) => {
              this.$addEntryMenuBtn.addClass('loading');
              Craft.cp.announce(Craft.t('app', 'Loading'));
              try {
                await this.addEntry($(ev.currentTarget).data('type'));
              } finally {
                this.$addEntryMenuBtn.removeClass('loading');
              }
            });
        }

        this.updateAddEntryBtn();

        setTimeout(() => {
          this.elementEditor = this.$container
            .closest('form')
            .data('elementEditor');

          if (this.elementEditor) {
            this.elementEditor.on('update', () => {
              this.settings.ownerId = this.elementEditor.getDraftElementId(
                this.settings.ownerId
              );
            });
          }

          this.trigger('afterInit');
        }, 100);
      },

      canAddMoreEntries: function () {
        return (
          !this.maxEntries ||
          this.$entriesContainer.children().length < this.maxEntries
        );
      },

      updateAddEntryBtn: function () {
        if (this.canAddMoreEntries()) {
          this.$addEntryBtn.removeClass('disabled').removeAttr('aria-disabled');
          this.$addEntryMenuBtn.removeClass('disabled');

          for (let i = 0; i < this.entrySelect.$items.length; i++) {
            const entry = this.entrySelect.$items.eq(i).data('entry');

            if (entry) {
              entry.$actionMenu
                .find('button[data-action=add]')
                .parent()
                .removeClass('disabled');
              entry.$actionMenu
                .find('button[data-action=add]')
                .removeAttr('aria-disabled');
            }
          }
        } else {
          this.$addEntryBtn.addClass('disabled').attr('aria-disabled', 'true');
          this.$addEntryMenuBtn.addClass('disabled');

          for (let i = 0; i < this.entrySelect.$items.length; i++) {
            const entry = this.entrySelect.$items.eq(i).data('entry');

            if (entry) {
              entry.$actionMenu
                .find('button[data-action=add]')
                .parent()
                .addClass('disabled');
              entry.$actionMenu
                .find('button[data-action=add]')
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

      async addEntry(type, $insertBefore, autofocus) {
        if (!this.canAddMoreEntries()) {
          this.updateStatusMessage();
          return;
        }

        if (this.elementEditor) {
          // First ensure we're working with drafts for all elements leading up
          // to this field’s element
          await this.elementEditor.setFormValue(
            this.settings.baseInputName,
            '*'
          );
        }

        await Craft.queue.push(async () => {
          if (this.addingEntry) {
            // only one new entry at a time
            return;
          }

          this.addingEntry = true;

          const {data} = await Craft.sendActionRequest(
            'POST',
            'matrix/create-entry',
            {
              data: {
                fieldId: this.settings.fieldId,
                entryTypeId: this.entryTypesByHandle[type].id,
                ownerId: this.settings.ownerId,
                ownerElementType: this.settings.ownerElementType,
                siteId: this.settings.siteId,
                namespace: this.settings.namespace,
                staticEntries: this.settings.staticEntries,
              },
            }
          );

          const $entry = $(data.blockHtml);

          // Pause the element editor
          await this.elementEditor?.pause();

          if ($insertBefore) {
            $entry.insertBefore($insertBefore);
          } else {
            $entry.appendTo(this.$entriesContainer);
          }

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
            async () => {
              $entry.css('margin-bottom', '');
              Craft.initUiElements($entry.children('.fields'));
              await Craft.appendHeadHtml(data.headHtml);
              await Craft.appendBodyHtml(data.bodyHtml);
              new Craft.MatrixInput.Entry(this, $entry);
              this.entrySort.addItems($entry);
              this.entrySelect.addItems($entry);
              this.updateAddEntryBtn();

              Garnish.requestAnimationFrame(() => {
                if (typeof autofocus === 'undefined' || autofocus) {
                  // Scroll to the entry
                  Garnish.scrollContainerToElement($entry);
                  // Focus on the first focusable element
                  $entry.find('.flex-fields :focusable').first().focus();
                }

                // Resume the element editor
                this.elementEditor?.resume();
              });
            }
          );

          this.addingEntry = false;
        });
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

      get maxEntries() {
        return this.settings.maxEntries;
      },
    },
    {
      defaults: {
        fieldId: null,
        maxEntries: null,
        namespace: null,
        baseInputName: null,
        ownerElementType: null,
        ownerId: null,
        siteId: null,
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

      initTabs(container) {
        const $tabs = $(container).children('.pane-tabs');
        if (!$tabs.length) {
          return;
        }

        // init tab manager
        let tabManager = new Craft.Tabs($tabs);

        // prevent items in the disclosure menu from changing the URL
        let disclosureMenu = tabManager.$menuBtn.data('trigger');
        $(disclosureMenu.$container)
          .find('li, a')
          .on('click', function (ev) {
            ev.preventDefault();
          });

        tabManager.on('selectTab', (ev) => {
          const href = ev.$tab.attr('href');

          // Show its content area
          if (href && href.charAt(0) === '#') {
            $(href).removeClass('hidden');
          }

          // Trigger a resize event to update any UI components that are listening for it
          Garnish.$win.trigger('resize');

          // Fixes Redactor fixed toolbars on previously hidden panes
          Garnish.$doc.trigger('scroll');
        });

        tabManager.on('deselectTab', (ev) => {
          const href = ev.$tab.attr('href');
          if (href && href.charAt(0) === '#') {
            // Hide its content area
            $(ev.$tab.attr('href')).addClass('hidden');
          }
        });

        return tabManager;
      },
    }
  );

  Craft.MatrixInput.Entry = Garnish.Base.extend({
    /**
     * @type {Craft.MatrixInput}
     */
    matrix: null,
    $container: null,
    $titlebar: null,
    $tabContainer: null,
    $fieldsContainer: null,
    $previewContainer: null,
    $actionMenu: null,
    $collapsedInput: null,

    tabManager: null,
    actionDisclosure: null,
    formObserver: null,
    visibleLayoutElements: null,
    cancelToken: null,
    ignoreFailedRequest: false,

    isNew: null,
    id: null,

    collapsed: false,

    init: function (matrix, $container) {
      this.matrix = matrix;
      this.$container = $container;
      this.$titlebar = $container.children('.titlebar');
      this.$tabContainer = this.$titlebar.children('.matrixblock-tabs');
      this.$previewContainer = this.$titlebar.children('.preview');
      this.$fieldsContainer = $container.children('.fields');

      this.$container.data('entry', this);

      this.id = this.$container.data('id');
      this.isNew =
        !this.id ||
        (typeof this.id === 'string' && this.id.substring(0, 3) === 'new');

      if (this.$tabContainer.length) {
        this.tabManager = Craft.MatrixInput.initTabs(this.$tabContainer);
      }

      const $actionMenuBtn = this.$container.find('> .actions .action-btn');
      const actionDisclosure =
        $actionMenuBtn.data('trigger') ||
        new Garnish.DisclosureMenu($actionMenuBtn);

      this.$actionMenu = actionDisclosure.$container;
      this.actionDisclosure = actionDisclosure;

      actionDisclosure.on('show', () => {
        this.$container.addClass('active');
        if (this.$container.prev('.matrixblock').length) {
          this.$actionMenu
            .find('button[data-action=moveUp]:first')
            .parent()
            .removeClass('hidden');
        } else {
          this.$actionMenu
            .find('button[data-action=moveUp]:first')
            .parent()
            .addClass('hidden');
        }
        if (this.$container.next('.matrixblock').length) {
          this.$actionMenu
            .find('button[data-action=moveDown]:first')
            .parent()
            .removeClass('hidden');
        } else {
          this.$actionMenu
            .find('button[data-action=moveDown]:first')
            .parent()
            .addClass('hidden');
        }
      });

      actionDisclosure.on('hide', () => {
        this.$container.removeClass('active');
      });

      this.$actionMenuOptions = this.$actionMenu.find('button[data-action]');

      this.addListener(
        this.$actionMenuOptions,
        'activate',
        this.handleActionClick
      );

      // Was this entry already collapsed?
      if (Garnish.hasAttr(this.$container, 'data-collapsed')) {
        this.collapse();
      }

      this._handleTitleBarClick = function (ev) {
        // don't expand/collapse the matrix "block" if double tapping the tabs
        if (!$(ev.target).closest('.tab-label').length) {
          ev.preventDefault();
          this.toggle();
        }
      };

      this.addListener(this.$titlebar, 'doubletap', this._handleTitleBarClick);

      this.visibleLayoutElements = this.$container.data(
        'visible-layout-elements'
      );
      this.formObserver = new Craft.FormObserver(this.$container, (data) => {
        this.updateFieldLayout(data);
      });
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

          if (Array.isArray(value)) {
            value = value.join(', ');
          }

          if (value) {
            value = Craft.escapeHtml(value).trim();

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
        this.$container.velocity({height: 34}, 'fast');
      } else {
        this.$previewContainer.show();
        this.$fieldsContainer.hide();
        this.$container.css({height: 34});
      }

      this.$tabContainer.hide();

      setTimeout(() => {
        this.$actionMenu
          .find('button[data-action=collapse]:first')
          .parent()
          .addClass('hidden');
        this.$actionMenu
          .find('button[data-action=expand]:first')
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
          this.$tabContainer.show();
        }
      );

      setTimeout(() => {
        this.$actionMenu
          .find('button[data-action=collapse]:first')
          .parent()
          .removeClass('hidden');
        this.$actionMenu
          .find('button[data-action=expand]:first')
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
      this.$container.addClass('disabled-entry');

      setTimeout(() => {
        this.$actionMenu
          .find('button[data-action=disable]:first')
          .parent()
          .addClass('hidden');
        this.$actionMenu
          .find('button[data-action=enable]:first')
          .parent()
          .removeClass('hidden');
      }, 200);

      this.collapse(true);
    },

    enable: function () {
      this.$container.children('input[name$="[enabled]"]:first').val('1');
      this.$container.removeClass('disabled-entry');

      setTimeout(() => {
        this.$actionMenu
          .find('button[data-action=disable]:first')
          .parent()
          .removeClass('hidden');
        this.$actionMenu
          .find('button[data-action=enable]:first')
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

    updateFieldLayout(data) {
      return new Promise((resolve, reject) => {
        const elementEditor = this.matrix.elementEditor;
        const baseInputName = this.$container.data('base-input-name');

        // Ignore if we're already submitting the main form
        if (elementEditor?.submittingForm) {
          reject('Form already being submitted.');
          return;
        }

        if (this.cancelToken) {
          this.ignoreFailedRequest = true;
          this.cancelToken.cancel();
        }

        const param = (n) => Craft.namespaceInputName(n, baseInputName);
        const extraData = {
          [param('visibleLayoutElements')]: this.visibleLayoutElements,
          [param('elementType')]: 'craft\\elements\\Entry',
          [param('ownerId')]: this.matrix.settings.ownerId,
          [param('fieldId')]: this.matrix.settings.fieldId,
          [param('sortOrder')]: this.$container.index() + 1,
          [param('typeId')]: this.$container.data('type-id'),
          [param('elementUid')]:
            elementEditor?.getDraftElementUid(this.$container.data('uid')) ??
            this.$container.data('uid'),
        };

        const selectedTabId = this.$fieldsContainer
          .children('[data-layout-tab]:not(.hidden)')
          .data('id');
        if (selectedTabId) {
          extraData[param('selectedTab')] = selectedTabId;
        }

        data += `&${$.param(extraData)}`;

        this.cancelToken = axios.CancelToken.source();

        Craft.sendActionRequest('POST', 'elements/update-field-layout', {
          cancelToken: this.cancelToken.token,
          headers: {
            'content-type': 'application/x-www-form-urlencoded',
            'X-Craft-Namespace': baseInputName,
          },
          data,
        })
          .then((response) => {
            this._afterUpdateFieldLayout(
              data,
              selectedTabId,
              baseInputName,
              response
            );
            resolve();
          })
          .catch((e) => {
            if (!this.ignoreFailedRequest) {
              reject(e);
            }
            this.ignoreFailedRequest = false;
          })
          .finally(() => {
            this.cancelToken = null;
          });
      });
    },

    async _afterUpdateFieldLayout(
      data,
      selectedTabId,
      baseInputName,
      response
    ) {
      // capture the new selected tab ID, in case it just changed
      const newSelectedTabId = this.$fieldsContainer
        .children('[data-layout-tab]:not(.hidden)')
        .data('id');

      // Update the visible elements
      let $allTabContainers = $();
      const visibleLayoutElements = {};
      let changedElements = false;

      for (const tabInfo of response.data.missingElements) {
        let $tabContainer = this.$fieldsContainer.children(
          `[data-layout-tab="${tabInfo.uid}"]`
        );

        if (!$tabContainer.length) {
          $tabContainer = $('<div/>', {
            id: Craft.namespaceId(tabInfo.id, baseInputName),
            class: 'flex-fields',
            'data-id': tabInfo.id,
            'data-layout-tab': tabInfo.uid,
          });
          if (tabInfo.id !== selectedTabId) {
            $tabContainer.addClass('hidden');
          }
          $tabContainer.appendTo(this.$fieldsContainer);
        }

        $allTabContainers = $allTabContainers.add($tabContainer);

        for (const elementInfo of tabInfo.elements) {
          if (elementInfo.html !== false) {
            if (!visibleLayoutElements[tabInfo.uid]) {
              visibleLayoutElements[tabInfo.uid] = [];
            }
            visibleLayoutElements[tabInfo.uid].push(elementInfo.uid);

            if (typeof elementInfo.html === 'string') {
              const $oldElement = $tabContainer.children(
                `[data-layout-element="${elementInfo.uid}"]`
              );
              const $newElement = $(elementInfo.html);
              if ($oldElement.length) {
                $oldElement.replaceWith($newElement);
              } else {
                $newElement.appendTo($tabContainer);
              }
              Craft.initUiElements($newElement);
              changedElements = true;
            }
          } else {
            const $oldElement = $tabContainer.children(
              `[data-layout-element="${elementInfo.uid}"]`
            );
            if (
              !$oldElement.length ||
              !Garnish.hasAttr($oldElement, 'data-layout-element-placeholder')
            ) {
              const $placeholder = $('<div/>', {
                class: 'hidden',
                'data-layout-element': elementInfo.uid,
                'data-layout-element-placeholder': '',
              });

              if ($oldElement.length) {
                $oldElement.replaceWith($placeholder);
              } else {
                $placeholder.appendTo($tabContainer);
              }

              changedElements = true;
            }
          }
        }
      }

      // Remove any unused tab content containers
      // (`[data-layout-tab=""]` == unconditional containers, so ignore those)
      const $unusedTabContainers = this.$fieldsContainer
        .children('[data-layout-tab]')
        .not($allTabContainers)
        .not('[data-layout-tab=""]');
      if ($unusedTabContainers.length) {
        $unusedTabContainers.remove();
        changedElements = true;
      }

      // Make the first tab visible if no others are
      if (!$allTabContainers.filter(':not(.hidden)').length) {
        $allTabContainers.first().removeClass('hidden');
      }

      this.visibleLayoutElements = visibleLayoutElements;

      // Update the tabs
      if (this.tabManager) {
        this.tabManager.destroy();
        this.tabManager = null;
        this.$tabContainer.html('');
      }

      this.hasTabs = !!response.data.tabs;

      if (this.hasTabs) {
        this.$tabContainer.append(response.data.tabs);
        this.tabManager = Craft.MatrixInput.initTabs(this.$tabContainer);

        // was a new tab selected after the request was kicked off?
        if (
          selectedTabId &&
          newSelectedTabId &&
          selectedTabId !== newSelectedTabId
        ) {
          const $newSelectedTab = this.tabManager.$tabs.filter(
            `[data-id="${newSelectedTabId}"]`
          );
          if ($newSelectedTab.length) {
            // if the new tab is visible - switch to it
            this.tabManager.selectTab($newSelectedTab);
          } else {
            // if the new tab is not visible (e.g. hidden by a condition)
            // switch to the first tab
            this.tabManager.selectTab(this.tabManager.$tabs.first());
          }
        }
      }

      await Craft.appendHeadHtml(response.data.headHtml);
      await Craft.appendBodyHtml(response.data.bodyHtml);

      // re-grab dismissible tips, re-attach listener, hide on re-load
      this.matrix.elementEditor?.handleDismissibleTips();
    },
  });
})(jQuery);
