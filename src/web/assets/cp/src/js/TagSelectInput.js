/** global: Craft */
/** global: Garnish */
/**
 * Tag select input
 */
Craft.TagSelectInput = Craft.BaseElementSelectInput.extend(
  {
    searchTimeout: null,
    searchMenu: null,

    $container: null,
    $elementsContainer: null,
    $elements: null,
    $addTagInput: null,
    $spinner: null,

    _ignoreBlur: false,

    init: function (settings) {
      // Normalize the settings
      // ---------------------------------------------------------------------

      // Are they still passing in a bunch of arguments?
      if (!$.isPlainObject(settings)) {
        // Loop through all of the old arguments and apply them to the settings
        var normalizedSettings = {},
          args = ['id', 'name', 'tagGroupId', 'sourceElementId'];

        for (var i = 0; i < args.length; i++) {
          if (typeof arguments[i] !== 'undefined') {
            normalizedSettings[args[i]] = arguments[i];
          } else {
            break;
          }
        }

        settings = normalizedSettings;
      }

      this.base($.extend({}, Craft.TagSelectInput.defaults, settings));

      this.$addTagInput = this.$container.children('.add').children('.text');
      this.$spinner = this.$addTagInput.next();

      this.addListener(this.$addTagInput, 'input', () => {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(this.searchForTags.bind(this), 500);
      });

      this.addListener(this.$addTagInput, 'keydown', function (ev) {
        if (ev.keyCode === Garnish.RETURN_KEY) {
          ev.preventDefault();
        }

        let $option;

        switch (ev.keyCode) {
          case Garnish.RETURN_KEY: {
            ev.preventDefault();
            if (this.searchMenu) {
              this.selectTag(this.searchMenu.$options.filter('.hover'));
            }
            return;
          }

          case Garnish.DOWN_KEY: {
            ev.preventDefault();
            if (this.searchMenu) {
              let $hoverOption = this.searchMenu.$options.filter('.hover');
              if ($hoverOption.length) {
                let $nextOption = $hoverOption
                  .parent()
                  .nextAll()
                  .find('a:not(.disabled)')
                  .first();
                if ($nextOption.length) {
                  this.focusOption($nextOption);
                }
              } else {
                this.focusOption(this.searchMenu.$options.eq(0));
              }
            }
            return;
          }

          case Garnish.UP_KEY: {
            ev.preventDefault();
            if (this.searchMenu) {
              let $hoverOption = this.searchMenu.$options.filter('.hover');
              if ($hoverOption.length) {
                let $prevOption = $hoverOption
                  .parent()
                  .prevAll()
                  .find('a:not(.disabled)')
                  .last();
                if ($prevOption.length) {
                  this.focusOption($prevOption);
                }
              } else {
                this.focusOption(
                  this.searchMenu.$options.eq(
                    this.searchMenu.$options.length - 1
                  )
                );
              }
            }
            return;
          }
        }
      });

      this.addListener(this.$addTagInput, 'focus', function () {
        if (this.searchMenu) {
          this.searchMenu.show();
        }
      });

      this.addListener(this.$addTagInput, 'blur', function () {
        if (this._ignoreBlur) {
          this._ignoreBlur = false;
          return;
        }

        setTimeout(() => {
          if (this.searchMenu) {
            this.searchMenu.hide();
          }
        }, 1);
      });
    },

    focusOption: function ($option) {
      this.searchMenu.$options.removeClass('hover');
      $option.addClass('hover');
      this.searchMenu.$menuList.attr(
        'aria-activedescendant',
        $option.attr('id')
      );
    },

    // No "add" button
    getAddElementsBtn: $.noop,

    getElementSortAxis: function () {
      return null;
    },

    searchForTags: function () {
      if (this.searchMenu) {
        this.killSearchMenu();
      }

      var val = this.$addTagInput.val();

      if (val) {
        this.$spinner.removeClass('hidden');

        var excludeIds = [];

        for (var i = 0; i < this.$elements.length; i++) {
          var id = $(this.$elements[i]).data('id');

          if (id) {
            excludeIds.push(id);
          }
        }

        if (this.settings.sourceElementId) {
          excludeIds.push(this.settings.sourceElementId);
        }

        var data = {
          search: this.$addTagInput.val(),
          tagGroupId: this.settings.tagGroupId,
          excludeIds: excludeIds,
        };

        Craft.sendActionRequest('POST', 'tags/search-for-tags', {data})
          .then((response) => {
            if (this.searchMenu) {
              this.killSearchMenu();
            }
            this.$spinner.addClass('hidden');
            var $menu = $('<div class="menu tagmenu"/>').appendTo(Garnish.$bod),
              $ul = $('<ul/>').appendTo($menu);

            var $li;

            for (var i = 0; i < response.data.tags.length; i++) {
              $li = $('<li/>').appendTo($ul);

              $('<a data-icon="tag"/>')
                .appendTo($li)
                .text(response.data.tags[i].title)
                .data('id', response.data.tags[i].id)
                .addClass(response.data.tags[i].exclude ? 'disabled' : '');
            }

            if (!response.data.exactMatch) {
              $li = $('<li/>').appendTo($ul);
              $('<a data-icon="plus"/>').appendTo($li).text(data.search);
            }

            $ul.find('a:not(.disabled):first').addClass('hover');

            this.searchMenu = new Garnish.Menu($menu, {
              attachToElement: this.$addTagInput,
              onOptionSelect: this.selectTag.bind(this),
            });

            this.addListener($menu, 'mousedown', () => {
              this._ignoreBlur = true;
            });

            this.searchMenu.show();
          })
          .catch(({response}) => {
            // Just in case
            if (this.searchMenu) {
              this.killSearchMenu();
            }

            this.$spinner.addClass('hidden');
          });
      } else {
        this.$spinner.addClass('hidden');
      }
    },

    selectTag: function (option) {
      var $option = $(option);

      if ($option.hasClass('disabled')) {
        return;
      }

      var id = $option.data('id');
      var title = $option.text();

      var $element = $('<div/>', {
        class: 'element small removable',
        'data-id': id,
        'data-site-id': this.settings.targetSiteId,
        'data-label': title,
        'data-editable': '1',
      }).appendTo(this.$elementsContainer);

      var $input = $('<input/>', {
        type: 'hidden',
        name: this.settings.name + '[]',
        value: id,
      }).appendTo($element);

      $('<button/>', {
        class: 'delete icon',
        title: Craft.t('app', 'Remove'),
        type: 'button',
        'aria-label': Craft.t('app', 'Remove {label}', {
          label: title,
        }),
      }).appendTo($element);

      var $titleContainer = $('<div/>', {
        class: 'label',
      }).appendTo($element);

      $('<span/>', {
        class: 'title',
        text: title,
      }).appendTo($titleContainer);

      var margin = -($element.outerWidth() + 10);
      this.$addTagInput.css('margin-' + Craft.left, margin + 'px');

      var animateCss = {};
      animateCss['margin-' + Craft.left] = 0;
      this.$addTagInput.velocity(animateCss, 'fast');

      this.$elements = this.$elements.add($element);

      this.addElements($element);

      this.killSearchMenu();
      this.$addTagInput.val('');
      this.$addTagInput.trigger('focus');

      if (!id) {
        // We need to create the tag first
        $element.addClass('loading disabled');

        var data = {
          groupId: this.settings.tagGroupId,
          title: title,
        };

        Craft.sendActionRequest('POST', 'tags/create-tag', {data})
          .then((response) => {
            $element.attr('data-id', response.data.id);
            $input.val(response.data.id);

            $element.removeClass('loading disabled');
          })
          .catch(({response}) => {
            this.removeElement($element);
            Craft.cp.displayError(Craft.t('app', 'A server error occurred.'));
          });
      }
    },

    killSearchMenu: function () {
      this.searchMenu.hide();
      this.searchMenu.destroy();
      this.searchMenu = null;
    },
  },
  {
    defaults: {
      tagGroupId: null,
    },
  }
);
