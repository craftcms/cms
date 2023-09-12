(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Matrix input class
   */
  Craft.MatrixInput = Garnish.Base.extend(
    {
      id: null,
      blockTypes: null,
      blockTypesByHandle: null,
      inputNamePrefix: null,
      inputIdPrefix: null,

      showingAddBlockMenu: false,
      addBlockBtnGroupWidth: null,
      addBlockBtnContainerWidth: null,

      $container: null,
      $form: null,
      $blockContainer: null,
      $addBlockBtnContainer: null,
      $addBlockBtnGroup: null,
      $addBlockBtnGroupBtns: null,
      $statusMessage: null,

      blockSort: null,
      blockSelect: null,
      totalNewBlocks: 0,

      init: function (id, blockTypes, inputNamePrefix, settings) {
        this.id = id;
        this.blockTypes = blockTypes;
        this.inputNamePrefix = inputNamePrefix;
        this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);

        // see if settings was actually set to the maxBlocks value
        if (typeof settings === 'number') {
          settings = {maxBlocks: settings};
        }
        this.setSettings(settings, Craft.MatrixInput.defaults);

        this.$container = $('#' + this.id);
        this.$form = this.$container.closest('form');
        this.$blockContainer = this.$container.children('.blocks');
        this.$addBlockBtnContainer = this.$container.children('.buttons');
        this.$addBlockBtnGroup =
          this.$addBlockBtnContainer.children('.btngroup');
        this.$addBlockBtnGroupBtns = this.$addBlockBtnGroup.children('.btn');
        this.$addBlockMenuBtn = this.$addBlockBtnContainer.children('.menubtn');
        this.$statusMessage = this.$container.find('[data-status-message]');

        this.$container.data('matrix', this);

        this.setNewBlockBtn();

        this.blockTypesByHandle = {};

        var i;

        for (i = 0; i < this.blockTypes.length; i++) {
          var blockType = this.blockTypes[i];
          this.blockTypesByHandle[blockType.handle] = blockType;
        }

        var $blocks = this.$blockContainer.children(),
          collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds();

        this.blockSort = new Garnish.DragSort($blocks, {
          handle: '> .actions > .move',
          axis: 'y',
          filter: () => {
            // Only return all the selected items if the target item is selected
            if (this.blockSort.$targetItem.hasClass('sel')) {
              return this.blockSelect.getSelectedItems();
            } else {
              return this.blockSort.$targetItem;
            }
          },
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          helperOpacity: 0.9,
          onDragStop: () => {
            this.trigger('blockSortDragStop');
          },
          onSortChange: () => {
            this.blockSelect.resetItemOrder();
          },
        });

        this.blockSelect = new Garnish.Select(this.$blockContainer, $blocks, {
          multi: true,
          vertical: true,
          handle: '> .checkbox, > .titlebar',
          checkboxMode: true,
        });

        for (i = 0; i < $blocks.length; i++) {
          var $block = $($blocks[i]),
            blockId = $block.data('id');

          // Is this a new block?
          var newMatch =
            typeof blockId === 'string' && blockId.match(/new(\d+)/);

          if (newMatch && newMatch[1] > this.totalNewBlocks) {
            this.totalNewBlocks = parseInt(newMatch[1]);
          }

          var block = new MatrixBlock(this, $block);

          if (block.id && $.inArray('' + block.id, collapsedBlocks) !== -1) {
            block.collapse();
          }
        }

        this.addListener(this.$addBlockBtnGroupBtns, 'click', function (ev) {
          var type = $(ev.target).data('type');
          this.addBlock(type);
        });

        if (this.$addBlockMenuBtn.length) {
          this.$addBlockMenuBtn.menubtn();
          this.$addBlockMenuBtn.data('menubtn').on('optionSelect', (ev) => {
            this.addBlock($(ev.option).data('type'));
          });
        }

        this.updateAddBlockBtn();

        this.addListener(this.$container, 'resize', 'setNewBlockBtn');
        Garnish.$doc.ready(this.setNewBlockBtn.bind(this));

        this.trigger('afterInit');
      },

      setNewBlockBtn: function () {
        // Do we know what the button group width is yet?
        if (!this.addBlockBtnGroupWidth) {
          this.addBlockBtnGroupWidth = this.$addBlockBtnGroup.width();

          if (!this.addBlockBtnGroupWidth) {
            return;
          }
        }

        // Only check if the container width has resized
        if (
          this.addBlockBtnContainerWidth !==
          (this.addBlockBtnContainerWidth = this.$addBlockBtnContainer.width())
        ) {
          if (this.addBlockBtnGroupWidth > this.addBlockBtnContainerWidth) {
            if (!this.showingAddBlockMenu) {
              this.$addBlockBtnGroup.addClass('hidden');
              this.$addBlockMenuBtn.removeClass('hidden');
              this.showingAddBlockMenu = true;
            }
          } else {
            if (this.showingAddBlockMenu) {
              this.$addBlockMenuBtn.addClass('hidden');
              this.$addBlockBtnGroup.removeClass('hidden');
              this.showingAddBlockMenu = false;

              // Because Safari is awesome
              if (navigator.userAgent.indexOf('Safari') !== -1) {
                Garnish.requestAnimationFrame(() => {
                  this.$addBlockBtnGroup.css('opacity', 0.99);

                  Garnish.requestAnimationFrame(() => {
                    this.$addBlockBtnGroup.css('opacity', '');
                  });
                });
              }
            }
          }
        }
      },

      canAddMoreBlocks: function () {
        return (
          !this.maxBlocks ||
          this.$blockContainer.children().length < this.maxBlocks
        );
      },

      updateAddBlockBtn: function () {
        var i, block;

        if (this.canAddMoreBlocks()) {
          this.$addBlockBtnGroup.removeClass('disabled');
          this.$addBlockMenuBtn.removeClass('disabled');

          this.$addBlockBtnGroupBtns.each(function () {
            $(this).removeAttr('aria-disabled');
          });

          for (i = 0; i < this.blockSelect.$items.length; i++) {
            block = this.blockSelect.$items.eq(i).data('block');

            if (block) {
              block.$actionMenu
                .find('a[data-action=add]')
                .parent()
                .removeClass('disabled');
              block.$actionMenu
                .find('a[data-action=add]')
                .removeAttr('aria-disabled');
            }
          }
        } else {
          this.$addBlockBtnGroup.addClass('disabled');
          this.$addBlockMenuBtn.addClass('disabled');

          this.$addBlockBtnGroupBtns.each(function () {
            $(this).attr('aria-disabled', 'true');
          });

          for (i = 0; i < this.blockSelect.$items.length; i++) {
            block = this.blockSelect.$items.eq(i).data('block');

            if (block) {
              block.$actionMenu
                .find('a[data-action=add]')
                .parent()
                .addClass('disabled');
              block.$actionMenu
                .find('a[data-action=add]')
                .attr('aria-disabled', 'true');
            }
          }
        }
      },

      updateStatusMessage: function () {
        this.$statusMessage.empty();
        let message;

        if (!this.canAddMoreBlocks()) {
          message = Craft.t(
            'app',
            'Matrix block could not be added. Maximum number of blocks reached.'
          );
        }

        setTimeout(() => {
          this.$statusMessage.text(message);
        }, 250);
      },

      addBlock: function (type, $insertBefore, autofocus) {
        if (!this.canAddMoreBlocks()) {
          this.updateStatusMessage();
          return;
        }

        this.totalNewBlocks++;

        const id = `new${this.totalNewBlocks}`;
        const typeName = this.blockTypesByHandle[type].name;
        const actionMenuId = `matrixblock-action-menu-${id}`;

        var html = `
                <div class="matrixblock" data-id="${id}" data-type="${type}" data-type-name="${typeName}" role="listitem">
                  <input type="hidden" name="${
                    this.inputNamePrefix
                  }[sortOrder][]" value="${id}"/>
                  <input type="hidden" name="${
                    this.inputNamePrefix
                  }[blocks][${id}][type]" value="${type}"/>
                  <input type="hidden" name="${
                    this.inputNamePrefix
                  }[blocks][${id}][enabled]" value="1"/>
                  <div class="titlebar">
                    <div class="blocktype">${
                      this.getBlockTypeByHandle(type).name
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

        if (!this.settings.staticBlocks) {
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

          for (var i = 0; i < this.blockTypes.length; i++) {
            var blockType = this.blockTypes[i];
            html += `
                            <li><a data-icon="plus" data-action="add" data-type="${
                              blockType.handle
                            }" href="#" aria-label="${Craft.t(
              'app',
              'Add {type} above',
              {type: blockType.name}
            )}" type="button" role="button">${Craft.t(
              'app',
              'Add {type} above',
              {type: blockType.name}
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

        var $block = $(html);

        // Pause the draft editor
        const elementEditor = this.$form.data('elementEditor');
        if (elementEditor) {
          elementEditor.pause();
        }

        if ($insertBefore) {
          $block.insertBefore($insertBefore);
        } else {
          $block.appendTo(this.$blockContainer);
        }

        var $fieldsContainer = $('<div class="fields"/>').appendTo($block),
          bodyHtml = this.getParsedBlockHtml(
            this.blockTypesByHandle[type].bodyHtml,
            id
          ),
          js = this.getParsedBlockHtml(this.blockTypesByHandle[type].js, id);

        $(bodyHtml).appendTo($fieldsContainer);

        this.trigger('blockAdded', {
          $block: $block,
        });

        // Animate the block into position
        $block.css(this.getHiddenBlockCss($block)).velocity(
          {
            opacity: 1,
            'margin-bottom': 10,
          },
          'fast',
          () => {
            $block.css('margin-bottom', '');
            Garnish.$bod.append(js);
            Craft.initUiElements($fieldsContainer);
            new MatrixBlock(this, $block);
            this.blockSort.addItems($block);
            this.blockSelect.addItems($block);
            this.updateAddBlockBtn();

            Garnish.requestAnimationFrame(() => {
              if (typeof autofocus === 'undefined' || autofocus) {
                // Scroll to the block
                Garnish.scrollContainerToElement($block);
                // Focus on the first focusable element
                $block.find('.flex-fields :focusable').first().trigger('focus');
              }

              // Resume the draft editor
              if (elementEditor) {
                elementEditor.resume();
              }
            });
          }
        );
      },

      getBlockTypeByHandle: function (handle) {
        for (var i = 0; i < this.blockTypes.length; i++) {
          if (this.blockTypes[i].handle === handle) {
            return this.blockTypes[i];
          }
        }
      },

      collapseSelectedBlocks: function () {
        this.callOnSelectedBlocks('collapse');
      },

      expandSelectedBlocks: function () {
        this.callOnSelectedBlocks('expand');
      },

      disableSelectedBlocks: function () {
        this.callOnSelectedBlocks('disable');
      },

      enableSelectedBlocks: function () {
        this.callOnSelectedBlocks('enable');
      },

      deleteSelectedBlocks: function () {
        this.callOnSelectedBlocks('selfDestruct');
      },

      callOnSelectedBlocks: function (fn) {
        for (var i = 0; i < this.blockSelect.$selectedItems.length; i++) {
          this.blockSelect.$selectedItems.eq(i).data('block')[fn]();
        }
      },

      getHiddenBlockCss: function ($block) {
        return {
          opacity: 0,
          marginBottom: -$block.outerHeight(),
        };
      },

      getParsedBlockHtml: function (html, id) {
        if (typeof html === 'string') {
          return html.replace(
            new RegExp(`__BLOCK_${this.settings.placeholderKey}__`, 'g'),
            id
          );
        } else {
          return '';
        }
      },

      get maxBlocks() {
        return this.settings.maxBlocks;
      },
    },
    {
      defaults: {
        placeholderKey: null,
        maxBlocks: null,
        staticBlocks: false,
      },

      collapsedBlockStorageKey:
        'Craft-' + Craft.systemUid + '.MatrixInput.collapsedBlocks',

      getCollapsedBlockIds: function () {
        if (
          typeof localStorage[Craft.MatrixInput.collapsedBlockStorageKey] ===
          'string'
        ) {
          return Craft.filterArray(
            localStorage[Craft.MatrixInput.collapsedBlockStorageKey].split(',')
          );
        } else {
          return [];
        }
      },

      setCollapsedBlockIds: function (ids) {
        localStorage[Craft.MatrixInput.collapsedBlockStorageKey] =
          ids.join(',');
      },

      rememberCollapsedBlockId: function (id) {
        if (typeof Storage !== 'undefined') {
          var collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds();

          if ($.inArray('' + id, collapsedBlocks) === -1) {
            collapsedBlocks.push(id);
            Craft.MatrixInput.setCollapsedBlockIds(collapsedBlocks);
          }
        }
      },

      forgetCollapsedBlockId: function (id) {
        if (typeof Storage !== 'undefined') {
          var collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds(),
            collapsedBlocksIndex = $.inArray('' + id, collapsedBlocks);

          if (collapsedBlocksIndex !== -1) {
            collapsedBlocks.splice(collapsedBlocksIndex, 1);
            Craft.MatrixInput.setCollapsedBlockIds(collapsedBlocks);
          }
        }
      },
    }
  );

  var MatrixBlock = Garnish.Base.extend({
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

      this.$container.data('block', this);

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

      // Was this block already collapsed?
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

      var previewHtml = '',
        $fields = this.$fieldsContainer.children().children();

      for (var i = 0; i < $fields.length; i++) {
        var $field = $($fields[i]),
          $inputs = $field
            .children('.input')
            .find('select,input[type!="hidden"],textarea,.label'),
          inputPreviewText = '';

        for (var j = 0; j < $inputs.length; j++) {
          var $input = $($inputs[j]),
            value;

          if ($input.hasClass('label')) {
            var $maybeLightswitchContainer = $input.parent().parent();

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
        Craft.MatrixInput.rememberCollapsedBlockId(this.id);
      } else {
        if (!this.$collapsedInput) {
          this.$collapsedInput = $(
            '<input type="hidden" name="' +
              this.matrix.inputNamePrefix +
              '[blocks][' +
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

      var collapsedContainerHeight = this.$container.height();
      this.$container.height('auto');
      this.$fieldsContainer.show();
      var expandedContainerHeight = this.$container.height();
      var displayValue = this.$fieldsContainer.css('display') || 'block';
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
        var collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds(),
          collapsedBlocksIndex = $.inArray('' + this.id, collapsedBlocks);

        if (collapsedBlocksIndex !== -1) {
          collapsedBlocks.splice(collapsedBlocksIndex, 1);
          Craft.MatrixInput.setCollapsedBlockIds(collapsedBlocks);
        }
      }

      if (!this.isNew) {
        Craft.MatrixInput.forgetCollapsedBlockId(this.id);
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
      this.matrix.trigger('beforeMoveBlockUp', {
        block: this,
      });
      let $prev = this.$container.prev('.matrixblock');
      if ($prev.length) {
        this.$container.insertBefore($prev);
        this.matrix.blockSelect.resetItemOrder();
      }
      this.matrix.trigger('moveBlockUp', {
        block: this,
      });
    },

    moveDown: function () {
      this.matrix.trigger('beforeMoveBlockDown', {
        block: this,
      });
      let $next = this.$container.next('.matrixblock');
      if ($next.length) {
        this.$container.insertAfter($next);
        this.matrix.blockSelect.resetItemOrder();
      }
      this.matrix.trigger('moveBlockDown', {
        block: this,
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
          this.matrix.blockSelect.totalSelected > 1 &&
          this.matrix.blockSelect.isSelected(this.$container),
        $option = $(option);

      switch ($option.data('action')) {
        case 'collapse': {
          if (batchAction) {
            this.matrix.collapseSelectedBlocks();
          } else {
            this.collapse(true);
          }

          break;
        }

        case 'expand': {
          if (batchAction) {
            this.matrix.expandSelectedBlocks();
          } else {
            this.expand();
          }

          break;
        }

        case 'disable': {
          if (batchAction) {
            this.matrix.disableSelectedBlocks();
          } else {
            this.disable();
          }

          break;
        }

        case 'enable': {
          if (batchAction) {
            this.matrix.enableSelectedBlocks();
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
          var type = $option.data('type');
          this.matrix.addBlock(type, this.$container);
          break;
        }

        case 'delete': {
          if (batchAction) {
            if (
              confirm(
                Craft.t(
                  'app',
                  'Are you sure you want to delete the selected blocks?'
                )
              )
            ) {
              this.matrix.deleteSelectedBlocks();
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
        this.matrix.getHiddenBlockCss(this.$container),
        'fast',
        () => {
          this.$container.remove();
          this.matrix.updateAddBlockBtn();

          this.matrix.trigger('blockDeleted', {
            $block: this.$container,
          });
        }
      );
    },
  });
})(jQuery);
