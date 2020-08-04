/** global: Craft */
/** global: Garnish */
/**
 * FieldToggle
 */
Craft.FieldToggle = Garnish.Base.extend(
    {
        $toggle: null,
        targetPrefix: null,
        targetSelector: null,
        reverseTargetSelector: null,

        _$target: null,
        _$reverseTarget: null,
        type: null,

        init: function(toggle) {
            this.$toggle = $(toggle);

            // Is this already a field toggle?
            if (this.$toggle.data('fieldtoggle')) {
                Garnish.log('Double-instantiating a field toggle on an element');
                this.$toggle.data('fieldtoggle').destroy();
            }

            this.$toggle.data('fieldtoggle', this);

            this.type = this.getType();

            if (this.type === 'select' || this.type === 'fieldset') {
                this.targetPrefix = (this.$toggle.attr('data-target-prefix') || '');
            } else {
                this.targetSelector = this.normalizeTargetSelector(this.$toggle.data('target'));
                this.reverseTargetSelector = this.normalizeTargetSelector(this.$toggle.data('reverse-target'));
            }

            this.findTargets();

            switch (this.type) {
                case 'link':
                    this.addListener(this.$toggle, 'click', 'onToggleChange');
                    break;
                case 'fieldset':
                    this.addListener(this.$toggle.find('input'), 'change', 'onToggleChange');
                    break;
                default:
                    this.addListener(this.$toggle, 'change', 'onToggleChange');
                    this.onToggleChange();
            }
        },

        normalizeTargetSelector: function(selector) {
            if (selector && !selector.match(/^[#\.]/)) {
                selector = '#' + selector;
            }

            return selector;
        },

        getType: function() {
            let nodeName = this.$toggle.prop('nodeName');
            if (
                (nodeName === 'INPUT' && this.$toggle.attr('type') === 'checkbox') ||
                this.$toggle.attr('role') === 'checkbox' ||
                this.$toggle.attr('role') === 'switch'
            ) {
                return 'checkbox';
            }

            switch (nodeName) {
                case 'SELECT':
                    return 'select';
                case 'A':
                    return 'link';
                case 'FIELDSET':
                    return 'fieldset';
            }

            console.warn('Unexpected field toggle:', this.$toggle[0]);
        },

        findTargets: function() {
            if (this.type === 'select' || this.type === 'fieldset') {
                var toggleVal = this.getToggleVal();
                this._$target = $(this.normalizeTargetSelector(this.targetPrefix + this.getToggleVal()));
            } else {
                if (this.targetSelector) {
                    this._$target = $(this.targetSelector);
                }

                if (this.reverseTargetSelector) {
                    this._$reverseTarget = $(this.reverseTargetSelector);
                }
            }
        },

        getToggleVal: function() {
            if (this.type === 'checkbox') {
                if (typeof this.$toggle.prop('checked') !== 'undefined') {
                    return this.$toggle.prop('checked');
                }
                return this.$toggle.attr('aria-checked') === 'true';
            }

            let postVal;
            if (this.type === 'fieldset') {
                postVal = this.$toggle.find('input:checked:first').val();
            } else {
                postVal = Garnish.getInputPostVal(this.$toggle);
            }

            // Normalize the value
            return (typeof postVal === 'undefined' || postVal === null) ? null : postVal.replace(/[^\w]+/g, '-');
        },

        onToggleChange: function() {
            if (this.type === 'select' || this.type === 'fieldset') {
                this.hideTarget(this._$target);
                this.findTargets();
                this.showTarget(this._$target);
            } else {
                this.findTargets();

                if (this.type === 'link') {
                    this.onToggleChange._show = this.$toggle.hasClass('collapsed') || !this.$toggle.hasClass('expanded');
                } else {
                    this.onToggleChange._show = !!this.getToggleVal();
                }

                if (this.onToggleChange._show) {
                    this.showTarget(this._$target);
                    this.hideTarget(this._$reverseTarget);
                } else {
                    this.hideTarget(this._$target);
                    this.showTarget(this._$reverseTarget);
                }

                delete this.onToggleChange._show;
            }
        },

        showTarget: function($target) {
            if ($target && $target.length) {
                this.showTarget._currentHeight = $target.height();

                $target.removeClass('hidden');

                if (this.type !== 'select' && this.type !== 'fieldset') {
                    if (this.type === 'link') {
                        this.$toggle.removeClass('collapsed');
                        this.$toggle.addClass('expanded');
                    }

                    for (let i = 0; i < $target.length; i++) {
                        ($t => {
                            if ($t.prop('nodeName') !== 'SPAN') {
                                $t.height('auto');
                                this.showTarget._targetHeight = $t.height();
                                $t.css({
                                    height: this.showTarget._currentHeight,
                                    overflow: 'hidden'
                                });

                                $t.velocity('stop');

                                $t.velocity({height: this.showTarget._targetHeight}, 'fast', function() {
                                    $t.css({
                                        height: '',
                                        overflow: ''
                                    });
                                });
                            }
                        })($target.eq(i));
                    }

                    delete this.showTarget._targetHeight;
                }

                delete this.showTarget._currentHeight;

                // Trigger a resize event in case there are any grids in the target that need to initialize
                Garnish.$win.trigger('resize');
            }
        },

        hideTarget: function($target) {
            if ($target && $target.length) {
                if (this.type === 'select' || this.type === 'fieldset') {
                    $target.addClass('hidden');
                } else {
                    if (this.type === 'link') {
                        this.$toggle.removeClass('expanded');
                        this.$toggle.addClass('collapsed');
                    }

                    for (let i = 0; i < $target.length; i++) {
                        ($t => {
                            if ($t.hasClass('hidden')) {
                                return;
                            }
                            if ($t.prop('nodeName') === 'SPAN') {
                                $t.addClass('hidden');
                            } else {
                                $t.css('overflow', 'hidden');
                                $t.velocity('stop');
                                $t.velocity({height: 0}, 'fast', function() {
                                    $t.addClass('hidden');
                                });
                            }
                        })($target.eq(i));
                    }
                }
            }
        }
    });
