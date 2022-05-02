import './dashboard.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Dashboard class
   */
  Craft.Dashboard = Garnish.Base.extend({
    $grid: null,
    $widgetManagerBtn: null,

    widgetTypes: null,
    grid: null,
    widgets: null,
    widgetManager: null,
    widgetAdminTable: null,
    widgetSettingsModal: null,

    init: function (widgetTypes) {
      this.widgetTypes = widgetTypes;
      this.widgets = {};

      this.$widgetManagerBtn = $('#widgetManagerBtn');

      this.addListener(this.$widgetManagerBtn, 'click', 'showWidgetManager');

      Garnish.$doc.ready(() => {
        this.$grid = $('#dashboard-grid');
        this.grid = this.$grid.data('grid');
        $('#newwidgetmenubtn')
          .data('menubtn')
          .menu.on('optionselect', this.handleNewWidgetOptionSelect.bind(this));
      });
    },

    getTypeInfo: function (type, property, defaultValue) {
      if (property) {
        if (typeof this.widgetTypes[type][property] === 'undefined') {
          return defaultValue;
        } else {
          return this.widgetTypes[type][property];
        }
      } else {
        return this.widgetTypes[type];
      }
    },

    handleNewWidgetOptionSelect: function (e) {
      var $option = $(e.selectedOption),
        type = $option.data('type'),
        settingsNamespace =
          'newwidget' + Math.floor(Math.random() * 1000000000) + '-settings',
        settingsHtml = this.getTypeInfo(type, 'settingsHtml', '').replace(
          /__NAMESPACE__/g,
          settingsNamespace
        ),
        settingsJs = this.getTypeInfo(type, 'settingsJs', '').replace(
          /__NAMESPACE__/g,
          settingsNamespace
        ),
        $gridItem = $(
          '<div class="item" data-colspan="1" style="display: block">'
        ),
        $container = $('<div/>', {
          class: 'widget new loading-new scaleout',
          'data-type': type,
        })
          .addClass(type.toLowerCase())
          .append(
            $('<div/>', {class: 'front'}).append(
              $('<div/>', {class: 'pane'})
                .append($('<div/>', {class: 'spinner body-loading'}))
                .append(
                  $('<div/>', {class: 'widget-heading'})
                    .append('<h2/>')
                    .append('<h5/>')
                )
                .append($('<div/>', {class: 'body'}))
                .append($('<div/>', {class: 'settings icon hidden'}))
            )
          )
          .append(
            $('<div/>', {class: 'back'}).append(
              $('<form/>', {class: 'pane'})
                .append(
                  $('<input/>', {
                    type: 'hidden',
                    name: 'type',
                    value: type,
                  })
                )
                .append(
                  $('<input/>', {
                    type: 'hidden',
                    name: 'settingsNamespace',
                    value: settingsNamespace,
                  })
                )
                .append(
                  $('<h2/>', {
                    class: 'first',
                    text: Craft.t('app', '{type} Settings', {
                      type: $option.data('name'),
                    }),
                  })
                )
                .append($('<div/>', {class: 'settings'}))
                .append('<hr/>')
                .append(
                  $('<div/>', {class: 'buttons clearafter'})
                    .append(
                      Craft.ui.createSubmitButton({
                        label: Craft.t('app', 'Save'),
                        spinner: true,
                      })
                    )
                    .append(
                      $('<button/>', {
                        type: 'button',
                        class: 'btn',
                        text: Craft.t('app', 'Cancel'),
                      })
                    )
                )
            )
          )
          .appendTo($gridItem);

      if (settingsHtml) {
        $container.addClass('flipped');
        $container.children('.front').addClass('hidden');
      } else {
        $container.addClass('loading');
        $container.children('.back').addClass('hidden');
      }

      var widget = new Craft.Widget(
        $container,
        settingsHtml.replace(/__NAMESPACE__/g, settingsNamespace),
        function () {
          eval(settingsJs);
        }
      );

      // Append the new widget after the last one
      // (can't simply append it to the grid container, since that will place it after the resize listener object)

      if (this.grid.$items.length) {
        $gridItem.insertAfter(this.grid.$items.last());
      } else {
        $gridItem.prependTo(this.grid.$container);
      }

      this.grid.addItems($gridItem);
      Garnish.scrollContainerToElement($gridItem);

      $container.removeClass('scaleout');

      if (!settingsHtml) {
        var data = {
          type: type,
        };

        Craft.queue.push(
          () =>
            new Promise((resolve) => {
              Craft.sendActionRequest('POST', 'dashboard/create-widget', {data})
                .then((response) => {
                  $container.removeClass('loading');
                  widget.update(response.data);
                })
                .catch(() => {
                  widget.destroy();
                })
                .finally(resolve);
            })
        );
      }
    },

    showWidgetManager: function () {
      if (!this.widgetManager) {
        var $widgets = this.$grid.find('> .item > .widget'),
          $form = $(
            '<form method="post" accept-charset="UTF-8">' +
              '<input type="hidden" name="action" value="widgets/save-widget"/>' +
              '</form>'
          ).appendTo(Garnish.$bod),
          $noWidgets = $(
            '<p id="nowidgets"' +
              ($widgets.length ? ' class="hidden"' : '') +
              '>' +
              Craft.t('app', 'You don’t have any widgets yet.') +
              '</p>'
          ).appendTo($form),
          $table = $(
            '<table class="data' + (!$widgets.length ? ' hidden' : '') + '"/>'
          ).appendTo($form),
          $tbody = $('<tbody/>').appendTo($table);

        for (var i = 0; i < $widgets.length; i++) {
          var $widget = $widgets.eq(i),
            widget = $widget.data('widget');

          // Make sure it's actually saved
          if (!widget || !widget.id) {
            continue;
          }

          widget.getManagerRow().appendTo($tbody);
        }

        this.widgetManager = new Garnish.HUD(this.$widgetManagerBtn, $form, {
          hudClass: 'hud widgetmanagerhud',
          onShow: () => {
            this.$widgetManagerBtn.addClass('active');
          },
          onHide: () => {
            this.$widgetManagerBtn.removeClass('active');
          },
        });

        this.widgetAdminTable = new Craft.AdminTable({
          tableSelector: $table,
          noObjectsSelector: $noWidgets,
          sortable: true,
          reorderAction: 'dashboard/reorder-user-widgets',
          deleteAction: 'dashboard/delete-user-widget',
          onReorderItems: (ids) => {
            var lastWidget = null;

            for (var i = 0; i < ids.length; i++) {
              var widget = this.widgets[ids[i]];

              if (!lastWidget) {
                widget.$gridItem.prependTo(this.$grid);
              } else {
                widget.$gridItem.insertAfter(lastWidget.$gridItem);
              }

              lastWidget = widget;
            }

            this.grid.resetItemOrder();
          },
          onDeleteItem: (id) => {
            this.widgets[id].destroy();
          },
        });
      } else {
        this.widgetManager.show();
      }
    },
  });

  /**
   * Dashboard Widget class
   */
  Craft.Widget = Garnish.Base.extend({
    $container: null,
    $gridItem: null,

    $front: null,
    $settingsBtn: null,
    $title: null,
    $subtitle: null,
    $heading: null,
    $bodyContainer: null,

    $back: null,
    $settingsForm: null,
    $settingsContainer: null,
    $saveBtn: null,
    $settingsErrorList: null,

    id: null,
    type: null,
    title: null,
    subtitle: null,

    totalCols: null,
    settingsHtml: null,
    initSettingsFn: null,
    showingSettings: false,

    colspanPicker: null,

    init: function (container, settingsHtml, initSettingsFn) {
      this.$container = $(container);
      this.$gridItem = this.$container.parent();

      // Store a reference to this object on the container element
      this.$container.data('widget', this);

      // Do a little introspection
      this.id = this.$container.data('id');
      this.type = this.$container.data('type');
      this.title = this.$container.data('title');

      if (this.id) {
        // Store a reference to this object on the main Dashboard object
        window.dashboard.widgets[this.id] = this;
      }

      this.$front = this.$container.children('.front');
      this.$settingsBtn = this.$front.find('> .pane > .icon.settings');
      this.$heading = this.$front.find('> .pane > .widget-heading');
      this.$title = this.$heading.find('> h2');
      this.$subtitle = this.$heading.find('> h5');
      this.$bodyContainer = this.$front.find('> .pane > .body');

      this.setSettingsHtml(settingsHtml, initSettingsFn);

      if (!this.$container.hasClass('flipped')) {
        this.onShowFront();
      } else {
        this.initBackUi();
        this.refreshSettings();
        this.onShowBack();
      }

      this.addListener(this.$settingsBtn, 'click', 'showSettings');
    },

    initBackUi: function () {
      this.$back = this.$container.children('.back');
      this.$settingsForm = this.$back.children('form');
      this.$settingsContainer = this.$settingsForm.children('.settings');
      var $btnsContainer = this.$settingsForm.children('.buttons');
      this.$saveBtn = $btnsContainer.children('button[type=submit]');

      this.addListener(
        $btnsContainer.children('.btn:nth-child(2)'),
        'click',
        'cancelSettings'
      );
      this.addListener(this.$settingsForm, 'submit', 'saveSettings');
    },

    getColspan: function () {
      return this.$gridItem.data('colspan');
    },

    setColspan: function (colspan) {
      this.$gridItem.data('colspan', colspan).attr('data-colspan', colspan);
      window.dashboard.grid.refreshCols(true);
    },

    getTypeInfo: function (property, defaultValue) {
      return window.dashboard.getTypeInfo(this.type, property, defaultValue);
    },

    setSettingsHtml: function (settingsHtml, initSettingsFn) {
      this.settingsHtml = settingsHtml;
      this.initSettingsFn = initSettingsFn;

      if (this.settingsHtml) {
        this.$settingsBtn.removeClass('hidden');
      } else {
        this.$settingsBtn.addClass('hidden');
      }
    },

    refreshSettings: function () {
      this.$settingsContainer.html(this.settingsHtml);

      Garnish.requestAnimationFrame(() => {
        Craft.initUiElements(this.$settingsContainer);
        this.initSettingsFn();
      });
    },

    showSettings: function () {
      if (!this.$back) {
        this.initBackUi();
      }

      // Refresh the settings every time
      this.refreshSettings();

      this.$back.removeClass('hidden');
      setTimeout(() => {
        this.$container.addClass('flipped').velocity(
          {height: this.$back.height()},
          {
            complete: this.onShowBack.bind(this),
          }
        );
      }, 100);
    },

    hideSettings: function () {
      this.$front.removeClass('hidden');

      setTimeout(() => {
        this.$container.removeClass('flipped').velocity(
          {height: this.$front.height()},
          {
            complete: this.onShowFront.bind(this),
          }
        );
      }, 100);
    },

    saveSettings: function (e) {
      e.preventDefault();

      if (this.$saveBtn.hasClass('loading')) {
        return;
      }

      this.$saveBtn.addClass('loading');

      Craft.queue.push(
        () =>
          new Promise((resolve) => {
            const action = this.$container.hasClass('new')
                ? 'dashboard/create-widget'
                : 'dashboard/save-widget-settings',
              data = this.$settingsForm.serialize();

            Craft.sendActionRequest('POST', action, {data})
              .then((response) => {
                if (this.$settingsErrorList) {
                  this.$settingsErrorList.remove();
                  this.$settingsErrorList = null;
                }

                Craft.cp.displayNotice(Craft.t('app', 'Widget saved.'));

                // Make sure the widget is still allowed to be shown, just in case
                if (!response.data.info) {
                  this.destroy();
                } else {
                  this.update(response.data);
                  this.hideSettings();
                }
              })
              .catch(({response}) => {
                if (this.$settingsErrorList) {
                  this.$settingsErrorList.remove();
                  this.$settingsErrorList = null;
                }

                Craft.cp.displayError(Craft.t('app', 'Couldn’t save widget.'));

                if (response.data.errors) {
                  this.$settingsErrorList = Craft.ui
                    .createErrorList(response.data.errors)
                    .insertAfter(this.$settingsContainer);
                }
              })
              .finally(() => {
                this.$saveBtn.removeClass('loading');
                resolve();
              });
          })
      );
    },

    update: function (response) {
      this.title = response.info.title;
      this.subtitle = response.info.subtitle;

      // Is this a new widget?
      if (this.$container.hasClass('new')) {
        // Discover ourself
        this.id = response.info.id;

        this.$container
          .attr('id', 'widget' + this.id)
          .removeClass('new loading-new');

        if (this.$settingsForm) {
          this.$settingsForm.prepend(
            '<input type="hidden" name="widgetId" value="' + this.id + '"/>'
          );
        }

        // Store a reference to this object on the main Dashboard object, now that the widget actually exists
        window.dashboard.widgets[this.id] = this;

        if (window.dashboard.widgetAdminTable) {
          window.dashboard.widgetAdminTable.addRow(this.getManagerRow());
        }
      } else {
        if (window.dashboard.widgetAdminTable) {
          window.dashboard.widgetAdminTable.$tbody
            .children('[data-id="' + this.id + '"]:first')
            .children('td:nth-child(2)')
            .html(this.getManagerRowLabel());
        }
      }

      if (!this.title && !this.subtitle) {
        this.$heading.remove();
      } else {
        if (this.title) {
          this.$title.text(this.title);
        } else {
          this.$title.remove();
        }

        if (this.subtitle) {
          this.$subtitle.text(this.subtitle);
        } else {
          this.$subtitle.remove();
        }
      }

      this.$bodyContainer.html(response.info.bodyHtml);

      // New colspan?
      if (response.info.colspan != this.getColspan()) {
        this.setColspan(response.info.colspan);
        Garnish.scrollContainerToElement(this.$gridItem);
      }

      Craft.initUiElements(this.$bodyContainer);
      Craft.appendHeadHtml(response.headHtml);
      Craft.appendBodyHtml(response.bodyHtml);

      this.setSettingsHtml(response.info.settingsHtml, function () {
        eval(response.info.settingsJs);
      });
    },

    cancelSettings: function () {
      if (this.id) {
        this.hideSettings();
      } else {
        this.destroy();
      }
    },

    onShowFront: function () {
      this.showingSettings = false;
      this.removeListener(this.$back, 'resize');
      this.addListener(this.$front, 'resize', 'updateContainerHeight');
      if (this.$back) {
        this.$back.addClass('hidden');
      }
    },

    onShowBack: function () {
      this.showingSettings = true;
      this.removeListener(this.$front, 'resize');
      this.addListener(this.$back, 'resize', 'updateContainerHeight');
      this.$front.addClass('hidden');

      // Focus on the first input
      setTimeout(() => {
        this.$settingsForm.find(':focusable:first').trigger('focus');
      }, 1);
    },

    updateContainerHeight: function () {
      this.$container.height(
        (this.showingSettings ? this.$back : this.$front).height()
      );
    },

    getManagerRow: function () {
      var $row = $(
        '<tr data-id="' +
          this.id +
          '" data-name="' +
          Craft.escapeHtml(this.title) +
          '">' +
          '<td class="widgetmanagerhud-icon">' +
          this.getTypeInfo('iconSvg') +
          '</td>' +
          '<td>' +
          this.getManagerRowLabel() +
          '</td>' +
          '<td class="widgetmanagerhud-col-colspan-picker thin"></td>' +
          '<td class="widgetmanagerhud-col-move thin"><a class="move icon" title="' +
          Craft.t('app', 'Reorder') +
          '" role="button"></a></td>' +
          '<td class="thin"><a class="delete icon" title="' +
          Craft.t('app', 'Delete') +
          '" role="button"></a></td>' +
          '</tr>'
      );

      // Initialize the colspan picker
      this.colspanPicker = new Craft.SlidePicker(this.getColspan(), {
        min: 1,
        max: () => {
          return window.dashboard.grid.totalCols;
        },
        step: 1,
        valueLabel: (colspan) => {
          return Craft.t(
            'app',
            '{num, number} {num, plural, =1{column} other{columns}}',
            {
              num: colspan,
            }
          );
        },
        onChange: (colspan) => {
          // Update the widget and grid
          this.setColspan(colspan);
          window.dashboard.grid.refreshCols(true);

          // Save the change
          let data = {
            id: this.id,
            colspan: colspan,
          };

          Craft.sendActionRequest('POST', 'dashboard/change-widget-colspan', {
            data,
          })
            .then((response) => {
              Craft.cp.displayNotice(Craft.t('app', 'Widget saved.'));
            })
            .catch(({response}) => {
              Craft.cp.displayError(Craft.t('app', 'Couldn’t save widget.'));
            });
        },
      });

      this.colspanPicker.$container.appendTo(
        $row.find('> td.widgetmanagerhud-col-colspan-picker')
      );
      window.dashboard.grid.on('refreshCols', () => {
        this.colspanPicker.refresh();
      });

      return $row;
    },

    getManagerRowLabel: function () {
      var typeName = this.getTypeInfo('name');

      return (
        Craft.escapeHtml(this.title) +
        (this.title !== typeName
          ? ' <span class="light">(' + typeName + ')</span>'
          : '')
      );
    },

    destroy: function () {
      delete window.dashboard.widgets[this.id];
      this.$container.addClass('scaleout');
      this.base();

      setTimeout(() => {
        window.dashboard.grid.removeItems(this.$gridItem);
        this.$gridItem.remove();
      }, 200);
    },
  });
})(jQuery);
