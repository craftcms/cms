/** global: Craft */
/** global: Garnish */
/**
 * Structure drag class
 */
Craft.StructureDrag = Garnish.Drag.extend({
    structure: null,
    maxLevels: null,
    draggeeLevel: null,

    $helperLi: null,
    $targets: null,
    draggeeHeight: null,

    init: function(structure, maxLevels) {
        this.structure = structure;
        this.maxLevels = maxLevels;

        this.$insertion = $('<li class="draginsertion"/>');

        var $items = this.structure.$container.find('li');

        this.base($items, {
            handle: '.element:first, .move:first',
            helper: $.proxy(this, 'getHelper')
        });
    },

    getHelper: function($helper) {
        this.$helperLi = $helper;
        var $ul = $('<ul class="structure draghelper"/>').append($helper);
        $helper.css('padding-' + Craft.left, this.$draggee.css('padding-' + Craft.left));
        $helper.find('.move').removeAttr('title');
        return $ul;
    },

    onDragStart: function() {
        this.$targets = $();

        // Recursively find each of the targets, in the order they appear to be in
        this.findTargets(this.structure.$container);

        // How deep does the rabbit hole go?
        this.draggeeLevel = 0;
        var $level = this.$draggee;
        do {
            this.draggeeLevel++;
            $level = $level.find('> ul > li');
        } while ($level.length);

        // Collapse the draggee
        this.draggeeHeight = this.$draggee.height();
        this.$draggee.velocity({
            height: 0
        }, 'fast', $.proxy(function() {
            this.$draggee.addClass('hidden');
        }, this));
        this.base();

        this.addListener(Garnish.$doc, 'keydown', function(ev) {
            if (ev.keyCode === Garnish.ESC_KEY) {
                this.cancelDrag();
            }
        });
    },

    findTargets: function($ul) {
        var $lis = $ul.children().not(this.$draggee);

        for (var i = 0; i < $lis.length; i++) {
            var $li = $($lis[i]);
            this.$targets = this.$targets.add($li.children('.row'));

            if (!$li.hasClass('collapsed')) {
                this.findTargets($li.children('ul'));
            }
        }
    },

    onDrag: function() {
        if (this._.$closestTarget) {
            this._.$closestTarget.removeClass('draghover');
            this.$insertion.remove();
        }

        // First let's find the closest target
        this._.$closestTarget = null;
        this._.closestTargetPos = null;
        this._.closestTargetYDiff = null;
        this._.closestTargetOffset = null;
        this._.closestTargetHeight = null;

        for (this._.i = 0; this._.i < this.$targets.length; this._.i++) {
            this._.$target = $(this.$targets[this._.i]);
            this._.targetOffset = this._.$target.offset();
            this._.targetHeight = this._.$target.outerHeight();
            this._.targetYMidpoint = this._.targetOffset.top + (this._.targetHeight / 2);
            this._.targetYDiff = Math.abs(this.mouseY - this._.targetYMidpoint);

            if (this._.i === 0 || (this.mouseY >= this._.targetOffset.top + 5 && this._.targetYDiff < this._.closestTargetYDiff)) {
                this._.$closestTarget = this._.$target;
                this._.closestTargetPos = this._.i;
                this._.closestTargetYDiff = this._.targetYDiff;
                this._.closestTargetOffset = this._.targetOffset;
                this._.closestTargetHeight = this._.targetHeight;
            } else {
                // Getting colder
                break;
            }
        }

        if (!this._.$closestTarget) {
            return;
        }

        // Are we hovering above the first row?
        if (this._.closestTargetPos === 0 && this.mouseY < this._.closestTargetOffset.top + 5) {
            this.$insertion.prependTo(this.structure.$container);
        } else {
            this._.$closestTargetLi = this._.$closestTarget.parent();
            this._.closestTargetLevel = this._.$closestTargetLi.data('level');

            // Is there a next row?
            if (this._.closestTargetPos < this.$targets.length - 1) {
                this._.$nextTargetLi = $(this.$targets[this._.closestTargetPos + 1]).parent();
                this._.nextTargetLevel = this._.$nextTargetLi.data('level');
            } else {
                this._.$nextTargetLi = null;
                this._.nextTargetLevel = null;
            }

            // Are we hovering between this row and the next one?
            this._.hoveringBetweenRows = (this.mouseY >= this._.closestTargetOffset.top + this._.closestTargetHeight - 5);

            /**
             * Scenario 1: Both rows have the same level.
             *
             *     * Row 1
             *     ----------------------
             *     * Row 2
             */

            if (this._.$nextTargetLi && this._.nextTargetLevel == this._.closestTargetLevel) {
                if (this._.hoveringBetweenRows) {
                    if (!this.maxLevels || this.maxLevels >= (this._.closestTargetLevel + this.draggeeLevel - 1)) {
                        // Position the insertion after the closest target
                        this.$insertion.insertAfter(this._.$closestTargetLi);
                    }
                } else {
                    if (!this.maxLevels || this.maxLevels >= (this._.closestTargetLevel + this.draggeeLevel)) {
                        this._.$closestTarget.addClass('draghover');
                    }
                }
            }

            /**
             * Scenario 2: Next row is a child of this one.
             *
             *     * Row 1
             *     ----------------------
             *         * Row 2
             */

            else if (this._.$nextTargetLi && this._.nextTargetLevel > this._.closestTargetLevel) {
                if (!this.maxLevels || this.maxLevels >= (this._.nextTargetLevel + this.draggeeLevel - 1)) {
                    if (this._.hoveringBetweenRows) {
                        // Position the insertion as the first child of the closest target
                        this.$insertion.insertBefore(this._.$nextTargetLi);
                    } else {
                        this._.$closestTarget.addClass('draghover');
                        this.$insertion.appendTo(this._.$closestTargetLi.children('ul'));
                    }
                }
            }

            /**
             * Scenario 3: Next row is a child of a parent node, or there is no next row.
             *
             *         * Row 1
             *     ----------------------
             *     * Row 2
             */

            else {
                if (this._.hoveringBetweenRows) {
                    // Determine which <li> to position the insertion after
                    this._.draggeeX = this.mouseX - this.targetItemMouseDiffX;

                    if (Craft.orientation === 'rtl') {
                        this._.draggeeX += this.$helperLi.width();
                    }

                    this._.$parentLis = this._.$closestTarget.parentsUntil(this.structure.$container, 'li');
                    this._.$closestParentLi = null;
                    this._.closestParentLiXDiff = null;
                    this._.closestParentLevel = null;

                    for (this._.i = 0; this._.i < this._.$parentLis.length; this._.i++) {
                        this._.$parentLi = $(this._.$parentLis[this._.i]);
                        this._.parentLiX = this._.$parentLi.offset().left;

                        if (Craft.orientation === 'rtl') {
                            this._.parentLiX += this._.$parentLi.width();
                        }

                        this._.parentLiXDiff = Math.abs(this._.parentLiX - this._.draggeeX);
                        this._.parentLevel = this._.$parentLi.data('level');

                        if ((!this.maxLevels || this.maxLevels >= (this._.parentLevel + this.draggeeLevel - 1)) && (
                            !this._.$closestParentLi || (
                                this._.parentLiXDiff < this._.closestParentLiXDiff &&
                                (!this._.$nextTargetLi || this._.parentLevel >= this._.nextTargetLevel)
                            )
                        )) {
                            this._.$closestParentLi = this._.$parentLi;
                            this._.closestParentLiXDiff = this._.parentLiXDiff;
                            this._.closestParentLevel = this._.parentLevel;
                        }
                    }

                    if (this._.$closestParentLi) {
                        this.$insertion.insertAfter(this._.$closestParentLi);
                    }
                } else {
                    if (!this.maxLevels || this.maxLevels >= (this._.closestTargetLevel + this.draggeeLevel)) {
                        this._.$closestTarget.addClass('draghover');
                    }
                }
            }
        }
    },

    cancelDrag: function() {
        this.$insertion.remove();

        if (this._.$closestTarget) {
            this._.$closestTarget.removeClass('draghover');
        }

        this.onMouseUp();
    },

    onDragStop: function() {
        // Are we repositioning the draggee?
        if (this._.$closestTarget && (this.$insertion.parent().length || this._.$closestTarget.hasClass('draghover'))) {
            var $draggeeParent,
                moved;

            // Are we about to leave the draggee's original parent childless?
            if (!this.$draggee.siblings().length) {
                $draggeeParent = this.$draggee.parent();
            }

            if (this.$insertion.parent().length) {
                // Make sure the insertion isn't right next to the draggee
                var $closestSiblings = this.$insertion.next().add(this.$insertion.prev());

                if ($.inArray(this.$draggee[0], $closestSiblings) === -1) {
                    this.$insertion.replaceWith(this.$draggee);
                    moved = true;
                } else {
                    this.$insertion.remove();
                    moved = false;
                }
            } else {
                var $ul = this._.$closestTargetLi.children('ul');

                // Make sure this is a different parent than the draggee's
                if (!$draggeeParent || !$ul.length || $ul[0] !== $draggeeParent[0]) {
                    if (!$ul.length) {
                        var $toggle = $('<div class="toggle" title="' + Craft.t('app', 'Show/hide children') + '"/>').prependTo(this._.$closestTarget);
                        this.structure.initToggle($toggle);

                        $ul = $('<ul>').appendTo(this._.$closestTargetLi);
                    } else if (this._.$closestTargetLi.hasClass('collapsed')) {
                        this._.$closestTarget.children('.toggle').trigger('click');
                    }

                    this.$draggee.appendTo($ul);
                    moved = true;
                } else {
                    moved = false;
                }
            }

            // Remove the class either way
            this._.$closestTarget.removeClass('draghover');

            if (moved) {
                // Now deal with the now-childless parent
                if ($draggeeParent) {
                    this.structure._removeUl($draggeeParent);
                }

                // Has the level changed?
                var newLevel = this.$draggee.parentsUntil(this.structure.$container, 'li').length + 1;

                var animateCss;

                if (newLevel != this.$draggee.data('level')) {
                    // Correct the helper's padding if moving to/from level 1
                    if (this.$draggee.data('level') == 1) {
                        animateCss = {};
                        animateCss['padding-' + Craft.left] = 38;
                        this.$helperLi.velocity(animateCss, 'fast');
                    } else if (newLevel == 1) {
                        animateCss = {};
                        animateCss['padding-' + Craft.left] = Craft.Structure.baseIndent;
                        this.$helperLi.velocity(animateCss, 'fast');
                    }

                    this.setLevel(this.$draggee, newLevel);
                }

                // Make it real
                var $element = this.$draggee.children('.row').children('.element');

                var data = {
                    structureId: this.structure.id,
                    elementId: $element.data('id'),
                    siteId: $element.data('site-id'),
                    prevId: this.$draggee.prev().children('.row').children('.element').data('id'),
                    parentId: this.$draggee.parent('ul').parent('li').children('.row').children('.element').data('id')
                };

                Craft.postActionRequest('structures/move-element', data, function(response, textStatus) {
                    if (textStatus === 'success') {
                        Craft.cp.displayNotice(Craft.t('app', 'New order saved.'));
                    }
                });
            }
        }

        // Animate things back into place
        this.$draggee.velocity('stop').removeClass('hidden').velocity({
            height: this.draggeeHeight
        }, 'fast', $.proxy(function() {
            this.$draggee.css('height', 'auto');
        }, this));

        this.returnHelpersToDraggees();

        this.base();
    },

    setLevel: function($li, level) {
        $li.data('level', level);

        var indent = this.structure.getIndent(level);

        var css = {};
        css['margin-' + Craft.left] = '-' + indent + 'px';
        css['padding-' + Craft.left] = indent + 'px';
        this.$draggee.children('.row').css(css);

        var $childLis = $li.children('ul').children();

        for (var i = 0; i < $childLis.length; i++) {
            this.setLevel($($childLis[i]), level + 1);
        }
    }
});
