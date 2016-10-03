/**
 * Asset image editor class
 */

Craft.AssetImageEditor = Garnish.Modal.extend(
	{
		// jQuery objects
		$body: null,
		$tools: null,
		$buttons: null,
		$cancelBtn: null,
		$replaceBtn: null,
		$saveBtn: null,

		// References and parameters
		canvas: null,
		image: null,
		viewport: null,
		$editorContainer: null,
		$straighten: null,
		assetId: null,
		cacheBust: null,

		// Filters
		appliedFilter: null,
		appliedFilterOptions: {},

		// Editor paramters
		editorHeight: 0,
		editorWidth: 0,
		viewportWidth: 0,
		viewportHeight: 0,

		// Image attributes
		imageAngle: 0,
		imageStraightenAngle: 0,
		viewportRotation: 0,
		originalWidth: 0,
		originalHeight: 0,

		// Animation
		animationInProgress: false,

		init: function (assetId, settings) {
			this.cacheBust = Date.now();

			this.setSettings(settings, Craft.AssetImageEditor.defaults);

			this.assetId = assetId;

			// Build the modal
			var $container = $('<div class="modal asset-editor"></div>').appendTo(Garnish.$bod),
				$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
				$footer = $('<div class="footer"/>').appendTo($container);

			this.base($container, this.settings);

			this.$buttons = $('<div class="buttons rightalign"/>').appendTo($footer);
			this.$cancelBtn = $('<div class="btn cancel">' + Craft.t('app', 'Cancel') + '</div>').appendTo(this.$buttons);
			this.$replaceBtn = $('<div class="btn submit save replace">' + Craft.t('app', 'Replace Asset') + '</div>').appendTo(this.$buttons);

			if (this.settings.allowSavingAsNew) {
				this.$saveBtn = $('<div class="btn submit save copy">' + Craft.t('app', 'Save as New Asset') + '</div>').appendTo(this.$buttons);
			}

			this.$body = $body;

			this.addListener(this.$cancelBtn, 'activate', $.proxy(this, 'hide'));
			this.removeListener(this.$shade, 'click');

			Craft.postActionRequest('assets/image-editor', $.proxy(this, 'loadEditor'));
		},

		loadEditor: function (data) {
			this.$body.html(data.html);
			this.$tools = $('.image-tools', this.$body);

			this.canvas = new fabric.StaticCanvas('image-manipulator', {backgroundColor: this.backgroundColor});
			this.canvas.enableRetinaScaling = true;

			this.$editorContainer = $('#image-holder');
			this.$straighten = $('.rotate.straighten');

			this.editorHeight = this.$editorContainer.innerHeight();
			this.editorWidth = this.$editorContainer.innerWidth();

			// Load the image from URL
			var imageUrl = Craft.getActionUrl('assets/edit-image', {assetId: this.assetId, size: this.settings.assetSize, cacheBust: this.cacheBust});
			fabric.Image.fromURL(imageUrl, $.proxy(function (imageObject) {
				this.image = imageObject;

				// Store for later reference
				this.originalHeight = this.image.getHeight();
				this.originalWidth = this.image.getWidth();

				// Scale the image and center it on the canvas
				this._scaleAndCenterImage();

				this._setImageZoomRatioToCover();
				this._renewImageZoomRatio();

				// Create the cropping mask on the edges so straightening the image looks nice
				var mask = this._createCroppingMask();

				// Set up a cropping viewport
				this.viewport = new fabric.Group([this.image, mask], {
					originX: 'center',
					originY: 'center'
				});
				this.canvas.add(this.viewport);

				// Add listeners to buttons and draw the grid
				this._addListeners();
				this._drawGrid();

				// Render it, finally
				this.canvas.renderAll();
			}, this));
		},

		/**
		 * Scale and center the image in the editor
		 */
		_scaleAndCenterImage: function () {

			// The width/height correction by a pixel might seem paranoid, but we really want
			// to get rid of 0.5 pixels and also make sure that the image is within
			// the editor or the final image might have a 1px sliver of background
			if (this.image.height > this.image.width) {
				this.viewportHeight = this.editorHeight;
				this.image.height = this.viewportHeight;

				// Never scale to parts of a pixel
				this.image.width = Math.round(this.originalWidth * (this.image.height / this.originalHeight));

				// Correct for neat divisions
				if (this.image.width % 2 == 1) {
					this.image.width += (this.image.width < this.editorWidth ? 1 : -1);
				}

				this.viewportWidth = this.image.width;
			} else {
				this.viewportWidth = this.editorWidth;
				this.image.width = this.viewportWidth;

				// Never scale to parts of a pixel
				this.image.height = Math.round(this.originalHeight * (this.image.width / this.originalWidth));

				// Correct for neat divisions
				if (this.image.height % 2 == 1) {
					this.image.height += (this.image.height < this.editorHeight ? 1 : -1);
				}

				this.viewportHeight = this.image.height;
			}

			this.image.set({
				left: (this.editorWidth - this.image.width) / 2,
				top: (this.editorHeight - this.image.height) / 2
			});

			this.canvas.setDimensions({
				width: this.editorWidth,
				height: this.editorHeight
			});
		},

		/**
		 * Renew the image's zoom ratio.
		 * @private
		 */
		_renewImageZoomRatio: function () {
			this.image.scale(this.zoomRatio);
		},

		/**
		 * Create the cropping mask so that the image is cropped to viewport when rotating
		 *
		 * @returns fabric.Rect
		 */
		_createCroppingMask: function () {
			var mask = new fabric.Rect({
				width: this.viewportWidth,
				height: this.viewportHeight,
				fill: '#fff',
				left: this.image.left,
				top: this.image.top
			});
			mask.globalCompositeOperation = 'destination-in';
			return mask;
		},

		/**
		 * Add listeners to buttons
		 */
		_addListeners: function () {

			// Generate a callback function that checks if the control is active beforehand
			var _callIfControlActive = function (callback) {
				return function (ev) {
					if (this.isActiveControl($(ev.currentTarget))) {
						callback.call(this, ev);
					} else {
						ev.preventDefault();
						ev.stopPropagation();
					}
				}.bind(this);
			}.bind(this);

			this.addListener($('.rotate.counter-clockwise'), 'click', _callIfControlActive(function (ev) {
				this.rotateViewport(-90);
			}));
			this.addListener($('.rotate.clockwise'),'click', _callIfControlActive(function (ev) {
				this.rotateViewport(90);
			}));

			this.addListener($('.rotate.reset'), 'click', _callIfControlActive(function (ev) {
				this.resetStraighten(ev);
			}));
			this.addListener($('.rotate.straighten'), 'input change mouseup mousedown click', _callIfControlActive(function (ev) {
				this.straighten(ev);
			}));

			this.addListener($('.filter-select select', this.$tools), 'change', _callIfControlActive(function (ev) {
				$option = $(ev.currentTarget).find('option:selected');
				$('.filter-fields').addClass('hidden');
				if ($option.val()) {
					$('.filter-fields[filter=' + $option.val() + ']').removeClass('hidden');
				}
			}));
			this.addListener($('.filter-tools .btn.apply-filter', this.$tools), 'click', _callIfControlActive(function (ev) {
				this.applyFilter(ev);
			}));

			this.addListener($('.cropping-tool', this.$tools), 'click', _callIfControlActive(function (ev) {
				this.enableCropMode(ev);
			}));
			this.addListener($('.reset-crop', this.$tools), 'click', _callIfControlActive(function (ev) {
				this.disableCropMode(ev);
			}));

			this.addListener($('.btn.cancel', this.$buttons), 'click', $.proxy(this, 'hide'));
			this.addListener($('.btn.save', this.$buttons), 'click', $.proxy(this, 'saveImage'));
		},

		/**
		 * Rotate the image along with the cropping mask.
		 *
		 * @param integer degrees
		 */
		rotateViewport: function (degrees) {
			if (!this.animationInProgress) {
				this.animationInProgress = true;

				this.viewportRotation += degrees;

				// Normalize the viewport rotation angle so it's between 0 and 359
				this.viewportRotation = parseInt((this.viewportRotation + 360) % 360, 10);

				var newAngle = this.viewport.getAngle() + degrees;

				// Animate the rotations
				this.viewport.animate('angle', newAngle, {
					onChange: this.canvas.renderAll.bind(this.canvas),
					duration: this.settings.animationDuration,
					onComplete: $.proxy(function () {
						// Clean up angle
						var cleanAngle = parseInt((this.viewport.getAngle() + 360) % 360, 10);
						this.viewport.set({angle: cleanAngle});
						this.animationInProgress = false;

						this._setImageZoomRatioToCover();
					}, this)
				});
			}
		},

		/**
		 * Perform the straightening by slider
		 *
		 * @param Event ev
		 */
		straighten: function (ev) {
			if (!this.animationInProgress) {
				this.animationInProgress = true;

				if (ev) {
					if (ev.type == 'change') {
						this.hideGrid();
					} else {
						this.showGrid();
					}
				}

				var newAngle = parseInt(this.$straighten.val(), 10);

				// Straighten the image
				this.image.set({
					angle: parseInt((newAngle + 360) % 360, 10),
					originX: 'center',
					originY: 'center',
					left: 0,
					top: 0
				});

				this._setImageZoomRatioToCover();
				this._renewImageZoomRatio();

				this.canvas.renderAll();

				this.animationInProgress = false;
			}
		},

		/**
		 * Reset the straighten degrees
		 *
		 * @param Event ev
		 */
		resetStraighten: function (ev) {
			if (this.animationInProgress) {
				return;
			}

			this.$straighten.val(0);
			this.straighten();
		},

		/**
		 * Save the image.
		 *
		 * @param Event ev
		 */
		saveImage: function (ev) {

			$button = $(ev.currentTarget);
			if ($button.hasClass('disabled')) {
				return false;
			}

			$('.btn', this.$buttons).addClass('disabled');
			this.$buttons.append('<div class="spinner"></div>');

			var postData = {
				assetId: this.assetId,
				viewportRotation: this.viewportRotation,
				imageRotation: this.imageStraightenAngle,
				replace: $button.hasClass('replace') ? 1 : 0
			};

			var filterHandle = this.appliedFilter;

			if (filterHandle) {
				postData.filter = filterHandle;
				var filterOptions = this.appliedFilterOptions;

				for (var option in filterOptions) {
					postData['filterOptions[' + option + ']'] = encodeURIComponent(filterOptions[option]);
				}
			}

			Craft.postActionRequest('assets/save-image', postData, $.proxy(function (data) {
				this.$buttons.find('.btn').removeClass('disabled').end().find('.spinner').remove();
				this.onSave();
				this.hide();
			}, this));
		},

		/**
		 * Set image zoom ratio depending on the straighten angle
		 */
		_setImageZoomRatioToCover: function () {
			this.imageStraightenAngle = parseFloat(this.$straighten.val());

			// Convert the angle to radians
			var angleInRadians = Math.abs(this.imageStraightenAngle) * (Math.PI / 180);

			// Calculate the dimensions of the scaled image using the magic of math
			var scaledWidth = Math.sin(angleInRadians) * this.viewportHeight + Math.cos(angleInRadians) * this.viewportWidth;
			var scaledHeight = Math.sin(angleInRadians) * this.viewportWidth + Math.cos(angleInRadians) * this.viewportHeight;

			// Calculate the ratio
			this.zoomRatio = Math.max(scaledWidth /  this.viewportWidth, scaledHeight / this.viewportHeight);
		},

		/**
		 * Draw the grid.
		 *
		 * @private
		 */
		_drawGrid: function () {
			var strokeOptions = {
				strokeWidth: this.settings.gridLineThickness,
				opacity: 1,
				stroke: this.settings.gridLineColor
			};

			var imageWidth = this.viewportWidth,
				imageHeight = this.viewportHeight;

			// draw Frame;
			var gridLines = [
				new fabric.Line([0, 0, imageWidth - 1, 0], strokeOptions),
				new fabric.Line([0, imageHeight - 1, 0, 0], strokeOptions),
				new fabric.Line([imageWidth - 1, 0, imageWidth - 1, imageHeight - 1], strokeOptions),
				new fabric.Line([imageWidth, imageHeight - 1, 0, imageHeight - 1], strokeOptions)
			];

			/**
			 * This function takes a length of a dimension, divides it in two, draws a line and recursively calls
			 * itself on both of the new segments.
			 */
			var divideAndDraw = $.proxy(function (divisionLevel, dimensionToDivide, offset, lineLength, axis) {

				var divisionPoint = Math.ceil(dimensionToDivide / 2 - this.settings.gridLineThickness / 2 + offset);

				// Set the start/end points depending on the axis we're drawing along
				if (axis == 'x') {
					pointOptions = [0, divisionPoint, lineLength, divisionPoint];
				} else {
					pointOptions = [divisionPoint, 0, divisionPoint, lineLength];
				}

				// Ensure the opacity gradually decreases
				strokeOptions.opacity = 1 - ((divisionLevel - 1) * (1 / this.settings.gridLinePrecision));

				gridLines.push(new fabric.Line(pointOptions, strokeOptions));

				// If we're not done yet, divide and conquer both new segments
				if (divisionLevel < this.settings.gridLinePrecision) {
					divideAndDraw(divisionLevel + 1, dimensionToDivide / 2, offset, lineLength, axis);
					divideAndDraw(divisionLevel + 1, dimensionToDivide / 2, offset + dimensionToDivide / 2, lineLength, axis);
				}
			}, this);

			divideAndDraw(1, imageWidth, 0, imageHeight, 'y');
			divideAndDraw(1, imageHeight, 0, imageWidth, 'x');

			this.grid = new fabric.Group(gridLines, {
				left: this.image.left,
				top: this.image.top,
				opacity: 0
			});

			this.viewport.add(this.grid);
		},

		/**
		 * Show the grid
		 */
		showGrid: function () {
			this.grid.set({opacity: 1});
		},

		/**
		 * Hide the grid
		 */
		hideGrid: function () {
			this.grid.set({opacity: 0});
		},

		onFadeOut: function () {
			this.destroy();
		},

		/**
		 * Apply a selected filter.
		 */
		applyFilter: function (ev) {

			$button = $(ev.currentTarget);
			if ($button.hasClass('disabled')) {
				return false;
			}

			$button.addClass('disabled');

			$spinner = $('<div class="spinner filter-spinner"></div>').insertAfter($button);

			var getParams = {
				assetId: this.assetId,
				size: this.settings.assetSize
			};

			var filterHandle = this.getSelectedFilter();

			if (filterHandle) {
				var filterOptions = this.getFilterOptions(filterHandle);

				// No use in requesting same image again.
				if (filterHandle == this.appliedFilter && JSON.stringify(this.appliedFilterOptions) == JSON.stringify(filterOptions)) {
					$spinner.remove();
					$button.removeClass('disabled');
					return;
				}

				this.appliedFilter = filterHandle;
				this.appliedFilterOptions = filterOptions;

				getParams.filter = filterHandle;

				for (var option in filterOptions) {
					getParams['filterOptions[' + option + ']'] = encodeURIComponent(filterOptions[option]);
				}

			} else {
				// No use in requesting same image again.
				if (this.appliedFilter == null) {
					$spinner.remove();
					$button.removeClass('disabled');
					return;
				}

				this.appliedFilterOptions = {};
				this.appliedFilter = null;
			}

			imageUrl = Craft.getActionUrl('assets/edit-image', getParams);

			this.image.setSrc(imageUrl, $.proxy(function (imageObject) {
				this._scaleAndCenterImage();
				this.straighten();
				$spinner.remove();
				$button.removeClass('disabled');
			}, this));
		},

		/**
		 * Get the currently selected filter's handle
		 */
		getSelectedFilter: function () {
			return $('.filter-select', this.$tools).find('option:selected').val();
		},

		/**
		 * Get the filter options by a filter handle
		 * @param filterHandle
		 */
		getFilterOptions: function (filterHandle) {
			var filterParams = {};
			$filterFields = $('.filter-fields[filter=' + filterHandle + ']').find('input, select, textarea');
			$filterFields.each(function () {
				$input = $(this);
				filterParams[$input.prop('name')] = encodeURIComponent($input.val());
			});

			return filterParams;
		},

		onSave: function () {
			this.settings.onSave();
		},

		isActiveControl: function ($element) {
			return $element.parents('.disabled').length == 0;
		},

		enableCropMode: function () {
			$('.rotation-tools, .filter-tools', this.$tools).addClass('disabled');
			$('.cropping-tools .crop-mode-enabled', this.$tools).removeClass('hidden');
			$('.cropping-tools .crop-mode-disabled', this.$tools).addClass('hidden');
		},

		disableCropMode: function () {
			$('.rotation-tools, .filter-tools', this.$tools).removeClass('disabled');
			$('.cropping-tools .crop-mode-enabled', this.$tools).addClass('hidden');
			$('.cropping-tools .crop-mode-disabled', this.$tools).removeClass('hidden');
		}
	},
	{
		defaults: {
			gridLineThickness: 1,
			gridLineColor: '#000000',
			gridLinePrecision: 2,
			animationDuration: 150,
			assetSize: 400,
			allowSavingAsNew: true,

			onSave: $.noop,
		}
	}
);