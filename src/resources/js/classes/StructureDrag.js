/**
 * Structure drag class
 */
Craft.StructureDrag = Garnish.Drag.extend({

	elementIndex: null,
	moveAction: null,
	maxDepth: null,
	draggeeDepth: null,

	$targets: null,
	_: null,
	draggeeHeight: null,

	init: function(elementIndex, moveAction, maxDepth)
	{
		this.elementIndex = elementIndex;
		this.moveAction = moveAction;
		this.maxDepth = maxDepth;

		this.$insertion = $('<li class="draginsertion"/>');
		this._ = {};

		var $items = this.elementIndex.$elementContainer.find('li');

		this.base($items, {
			handle: '.element:first, .move:first',
			helper: $.proxy(this, 'getHelper')
		});
	},

	getHelper: function($helper)
	{
		var $ul = $('<ul class="structureview draghelper"/>').append($helper);
		$helper.css('padding-left', this.$draggee.css('padding-left'));
		$helper.find('.move').removeAttr('title');
		return $ul;
	},

	onDragStart: function()
	{
		this.$targets = $();

		// Recursively find each of the targets, in the order they appear to be in
		this.findTargets(this.elementIndex.$elementContainer);

		// How deep does the rabbit hole go?
		this.draggeeDepth = 0;
		var $level = this.$draggee;
		do {
			this.draggeeDepth++;
			$level = $level.find('> ul > li');
		} while($level.length);

		// Collapse the draggee
		this.draggeeHeight = this.$draggee.height();
		this.$draggee.animate({
			height: 0
		}, 'fast', $.proxy(function() {
			this.$draggee.addClass('hidden');
		}, this));
		this.base();

		this.addListener(Garnish.$doc, 'keydown', function(ev) {
			if (ev.keyCode == Garnish.ESC_KEY)
			{
				this.cancelDrag();
			}
		});
	},

	findTargets: function($ul)
	{
		var $lis = $ul.children().not(this.$draggee);

		for (var i = 0; i < $lis.length; i++)
		{
			var $li = $($lis[i]);
			this.$targets = this.$targets.add($li.children('.row'));

			if (!$li.hasClass('collapsed'))
			{
				this.findTargets($li.children('ul'));
			}
		}
	},

	onDrag: function()
	{
		if (this._.$closestTarget)
		{
			this._.$closestTarget.removeClass('draghover');
			this.$insertion.remove();
		}

		// First let's find the closest target
		this._.$closestTarget = null;
		this._.closestTargetPos = null;
		this._.closestTargetYDiff = null;
		this._.closestTargetOffset = null;
		this._.closestTargetHeight = null;

		for (this._.i = 0; this._.i < this.$targets.length; this._.i++)
		{
			this._.$target = $(this.$targets[this._.i]);
			this._.targetOffset = this._.$target.offset();
			this._.targetHeight = this._.$target.outerHeight();
			this._.targetYMidpoint = this._.targetOffset.top + (this._.targetHeight / 2);
			this._.targetYDiff = Math.abs(this.mouseY - this._.targetYMidpoint);

			if (this._.i == 0 || (this.mouseY >= this._.targetOffset.top + 5 && this._.targetYDiff < this._.closestTargetYDiff))
			{
				this._.$closestTarget = this._.$target;
				this._.closestTargetPos = this._.i;
				this._.closestTargetYDiff = this._.targetYDiff;
				this._.closestTargetOffset = this._.targetOffset;
				this._.closestTargetHeight = this._.targetHeight;
			}
			else
			{
				// Getting colder
				break;
			}
		}

		if (!this._.$closestTarget)
		{
			return;
		}

		// Are we hovering above the first row?
		if (this._.closestTargetPos == 0 && this.mouseY < this._.closestTargetOffset.top + 5)
		{
			this.$insertion.prependTo(this.elementIndex.$elementContainer);
		}
		else
		{
			this._.$closestTargetLi = this._.$closestTarget.parent();
			this._.closestTargetDepth = this._.$closestTargetLi.data('depth');

			// Is there a next row?
			if (this._.closestTargetPos < this.$targets.length - 1)
			{
				this._.$nextTargetLi = $(this.$targets[this._.closestTargetPos+1]).parent();
				this._.nextTargetDepth = this._.$nextTargetLi.data('depth');
			}
			else
			{
				this._.$nextTargetLi = null;
				this._.nextTargetDepth = null;
			}

			// Are we hovering between this row and the next one?
			this._.hoveringBetweenRows = (this.mouseY >= this._.closestTargetOffset.top + this._.closestTargetHeight - 5);

			/**
			 * Scenario 1: Both rows have the same depth.
			 *
			 *     * Row 1
			 *     ----------------------
			 *     * Row 2
			 */

			if (this._.$nextTargetLi && this._.nextTargetDepth == this._.closestTargetDepth)
			{
				if (this._.hoveringBetweenRows)
				{
					if (!this.maxDepth || this.maxDepth >= (this._.closestTargetDepth + this.draggeeDepth - 1))
					{
						// Position the insertion after the closest target
						this.$insertion.insertAfter(this._.$closestTargetLi);
					}

				}
				else
				{
					if (!this.maxDepth || this.maxDepth >= (this._.closestTargetDepth + this.draggeeDepth))
					{
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

			else if (this._.$nextTargetLi && this._.nextTargetDepth > this._.closestTargetDepth)
			{
				if (!this.maxDepth || this.maxDepth >= (this._.nextTargetDepth + this.draggeeDepth - 1))
				{
					if (this._.hoveringBetweenRows)
					{
						// Position the insertion as the first child of the closest target
						this.$insertion.insertBefore(this._.$nextTargetLi);
					}
					else
					{
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

			else
			{
				if (this._.hoveringBetweenRows)
				{
					// Determine which <li> to position the insertion after
					this._.draggeeX = this.mouseX - this.targetItemMouseDiffX;
					this._.$parentLis = this._.$closestTarget.parentsUntil(this.elementIndex.$elementContainer, 'li');
					this._.$closestParentLi = null;
					this._.closestParentLiXDiff = null;
					this._.closestParentDepth = null;

					for (this._.i = 0; this._.i < this._.$parentLis.length; this._.i++)
					{
						this._.$parentLi = $(this._.$parentLis[this._.i]);
						this._.parentLiXDiff = Math.abs(this._.$parentLi.offset().left - this._.draggeeX);
						this._.parentDepth = this._.$parentLi.data('depth');

						if ((!this.maxDepth || this.maxDepth >= (this._.parentDepth + this.draggeeDepth - 1)) && (
							!this._.$closestParentLi || (
								this._.parentLiXDiff < this._.closestParentLiXDiff &&
								(!this._.$nextTargetLi || this._.parentDepth >= this._.nextTargetDepth)
							)
						))
						{
							this._.$closestParentLi = this._.$parentLi;
							this._.closestParentLiXDiff = this._.parentLiXDiff;
							this._.closestParentDepth = this._.parentDepth;
						}
					}

					if (this._.$closestParentLi)
					{
						this.$insertion.insertAfter(this._.$closestParentLi);
					}
				}
				else
				{
					if (!this.maxDepth || this.maxDepth >= (this._.closestTargetDepth + this.draggeeDepth))
					{
						this._.$closestTarget.addClass('draghover');
					}
				}
			}
		}
	},

	cancelDrag: function()
	{
		this.$insertion.remove();

		if (this._.$closestTarget)
		{
			this._.$closestTarget.removeClass('draghover');
		}

		this.onMouseUp();
	},

	onDragStop: function()
	{
		// Are we repositioning the draggee?
		if (this._.$closestTarget && (this.$insertion.parent().length || this._.$closestTarget.hasClass('draghover')))
		{
			// Are we about to leave the draggee's original parents childless?
			if (!this.$draggee.siblings().length)
			{
				var $draggeeParent = this.$draggee.parent();
			}
			else
			{
				var $draggeeParent = null;
			}

			if (this.$insertion.parent().length)
			{
				// Make sure the insertion isn't right next to the draggee
				var $closestSiblings = this.$insertion.next().add(this.$insertion.prev());

				if ($.inArray(this.$draggee[0], $closestSiblings) == -1)
				{
					this.$insertion.replaceWith(this.$draggee);
					var moved = true;
				}
				else
				{
					this.$insertion.remove();
					var moved = false;
				}
			}
			else
			{
				var $ul = this._.$closestTargetLi.children('ul');

				// Make sure this is a different parent than the draggee's
				if (!$draggeeParent || !$ul.length || $ul[0] != $draggeeParent[0])
				{
					if (!$ul.length)
					{
						var $toggle = $('<div class="toggle" title="'+Craft.t('Show/hide children')+'"/>').prependTo(this._.$closestTarget);
						this.elementIndex.initToggle($toggle);

						$ul = $('<ul>').appendTo(this._.$closestTargetLi);
					}
					else if (this._.$closestTargetLi.hasClass('collapsed'))
					{
						this._.$closestTarget.children('.toggle').trigger('click');
					}

					this.$draggee.appendTo($ul);
					var moved = true;
				}
				else
				{
					var moved = false;
				}
			}

			// Remove the class either way
			this._.$closestTarget.removeClass('draghover');

			if (moved)
			{
				// Now deal with the now-childless parent
				if ($draggeeParent)
				{
					$draggeeParent.siblings('.row').children('.toggle').remove();
					$draggeeParent.remove();
				}

				// Has the depth changed?
				var newDepth = this.$draggee.parentsUntil(this.elementIndex.$elementContainer, 'li').length + 1;

				if (newDepth != this.$draggee.data('depth'))
				{
					this.setDepth(this.$draggee, newDepth);
				}

				// Make it real
				var data = {
					id:       this.$draggee.data('id'),
					prevId:   this.$draggee.prev().data('id'),
					parentId: this.$draggee.parent('ul').parent('li').data('id')
				};

				Craft.postActionRequest(this.moveAction, data, function(response, textStatus) {

					if (textStatus == 'success')
					{
						Craft.cp.displayNotice(Craft.t('New order saved.'));
					}

				});
			}
		}

		// Animate things back into place
		this.$draggee.removeClass('hidden').animate({
			height: this.draggeeHeight
		}, 'fast', $.proxy(function() {
			this.$draggee.css('height', 'auto');
		}, this));

		this.returnHelpersToDraggees();

		this.base();
	},

	setDepth: function($li, depth)
	{
		$li.data('depth', depth);

		var indent = 8 + (depth - 1) * 35;
		this.$draggee.children('.row').css({
			'margin-left':  '-'+indent+'px',
			'padding-left': indent+'px'
		});

		var $childLis = $li.children('ul').children();

		for (var i = 0; i < $childLis.length; i++)
		{
			this.setDepth($($childLis[i]), depth+1);
		}
	}

});
