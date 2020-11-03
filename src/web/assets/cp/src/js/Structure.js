/** global: Craft */
/** global: Garnish */
/**
 * Structure class
 */
Craft.Structure = Garnish.Base.extend(
    {
        id: null,

        $container: null,
        state: null,
        structureDrag: null,

        /**
         * Init
         */
        init: function(id, container, settings) {
            this.id = id;
            this.$container = $(container);
            this.setSettings(settings, Craft.Structure.defaults);

            // Is this already a structure?
            if (this.$container.data('structure')) {
                Garnish.log('Double-instantiating a structure on an element');
                this.$container.data('structure').destroy();
            }

            this.$container.data('structure', this);

            this.state = {};

            if (this.settings.storageKey) {
                $.extend(this.state, Craft.getLocalStorage(this.settings.storageKey, {}));
            }

            if (typeof this.state.collapsedElementIds === 'undefined') {
                this.state.collapsedElementIds = [];
            }

            var $parents = this.$container.find('ul').prev('.row');

            for (var i = 0; i < $parents.length; i++) {
                var $row = $($parents[i]),
                    $li = $row.parent(),
                    $toggle = $('<div class="toggle" title="' + Craft.t('app', 'Show/hide children') + '"/>').prependTo($row);

                if ($.inArray($row.children('.element').data('id'), this.state.collapsedElementIds) !== -1) {
                    $li.addClass('collapsed');
                }

                this.initToggle($toggle);
            }

            if (this.settings.sortable) {
                this.structureDrag = new Craft.StructureDrag(this, this.settings.maxLevels);
            }

            if (this.settings.newChildUrl) {
                this.initNewChildMenus(this.$container.find('.add'));
            }
        },

        initToggle: function($toggle) {
            $toggle.on('click', $.proxy(function(ev) {
                var $li = $(ev.currentTarget).closest('li'),
                    elementId = $li.children('.row').find('.element:first').data('id'),
                    viewStateKey = $.inArray(elementId, this.state.collapsedElementIds);

                if ($li.hasClass('collapsed')) {
                    $li.removeClass('collapsed');

                    if (viewStateKey !== -1) {
                        this.state.collapsedElementIds.splice(viewStateKey, 1);
                    }
                }
                else {
                    $li.addClass('collapsed');

                    if (viewStateKey === -1) {
                        this.state.collapsedElementIds.push(elementId);
                    }
                }

                if (this.settings.storageKey) {
                    Craft.setLocalStorage(this.settings.storageKey, this.state);
                }
            }, this));
        },

        initNewChildMenus: function($addBtns) {
            this.addListener($addBtns, 'click', 'onNewChildMenuClick');
        },

        onNewChildMenuClick: function(ev) {
            var $btn = $(ev.currentTarget);

            if (!$btn.data('menubtn')) {
                var elementId = $btn.parent().children('.element').data('id'),
                    newChildUrl = Craft.getUrl(this.settings.newChildUrl, 'parentId=' + elementId);

                $('<div class="menu"><ul><li><a href="' + newChildUrl + '">' + Craft.t('app', 'New child') + '</a></li></ul></div>').insertAfter($btn);

                var menuBtn = new Garnish.MenuBtn($btn);
                menuBtn.showMenu();
            }
        },

        getIndent: function(level) {
            return Craft.Structure.baseIndent + (level - 1) * Craft.Structure.nestedIndent;
        },

        addElement: function($element) {
            var $li = $('<li data-level="1"/>').appendTo(this.$container),
                $row = $('<div class="row" style="margin-' + Craft.left + ': -' + Craft.Structure.baseIndent + 'px; padding-' + Craft.left + ': ' + Craft.Structure.baseIndent + 'px;">').appendTo($li);

            $row.append($element);

            if (this.settings.sortable) {
                $row.append('<a class="move icon" title="' + Craft.t('app', 'Move') + '"></a>');
                this.structureDrag.addItems($li);
            }

            if (this.settings.newChildUrl) {
                var $addBtn = $('<a class="add icon" title="' + Craft.t('app', 'New child') + '"></a>').appendTo($row);
                this.initNewChildMenus($addBtn);
            }

            $row.css('margin-bottom', -30);
            $row.velocity({'margin-bottom': 0}, 'fast');
        },

        removeElement: function($element) {
            var $li = $element.parent().parent();

            if (this.settings.sortable) {
                this.structureDrag.removeItems($li);
            }

            var $parentUl;

            if (!$li.siblings().length) {
                $parentUl = $li.parent();
            }

            $li.css('visibility', 'hidden').velocity({marginBottom: -$li.height()}, 'fast', $.proxy(function() {
                $li.remove();

                if (typeof $parentUl !== 'undefined') {
                    this._removeUl($parentUl);
                }
            }, this));
        },

        _removeUl: function($ul) {
            $ul.siblings('.row').children('.toggle').remove();
            $ul.remove();
        }
    },
    {
        baseIndent: 8,
        nestedIndent: 35,

        defaults: {
            storageKey: null,
            sortable: false,
            newChildUrl: null,
            maxLevels: null
        }
    });
