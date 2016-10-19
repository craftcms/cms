/**
 * Asset image editor class
 */

// TODO: For portrait images the zoomToFitRatio is off
// TODO: When rotating by 90 degrees, the cropping constraint acts like the image has not been rotated
// TODO: Smooth out the cropping constraints
// TODO: UI
// TODO: Handle resize

Craft.AssetImageEditor = Garnish.Modal.extend(
	{
		// jQuery objects
		$body: null,
		$tools: null,
		$buttons: null,
		$cancelBtn: null,
		$replaceBtn: null,
		$saveBtn: null,
		$editorContainer: null,
		$straighten: null,

		// References and parameters
		canvas: null,
		image: null,
		viewport: null,
		viewportMask: null,
		assetId: null,
		cacheBust: null,
		zoomRatio: 1,

		// Cropping references
		croppingCanvas: null,
		clipper: null,
		cropper: null,
		croppingShade: null,
		tiltedImageVerticeCoords: null,
		lastValidCroppingCoordinates: null,
		lastValidCroppingScales: null,
		previousScreenX: 0,
		previousScreenY: 0,
		isCroppingPerformed: false,

		// Filters
		appliedFilter: null,
		appliedFilterOptions: {},

		// Editor parameters
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

			this.canvas = new fabric.StaticCanvas('image-canvas', {backgroundColor: this.backgroundColor, hoverCursor: 'default'});
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

				this.zoomRatio = this.getZoomToCoverRatio();
				this._renewImageZoomRatio();

				// Create the viewport mask on the edges so straightening the image looks nice
				this.viewportMask = this._createViewportMask({
					width: this.viewportWidth,
					height: this.viewportHeight,
					top: this.image.top - 1,
					left: this.image.left - 1
				});

				// Set up a cropping viewport
				this.viewport = new fabric.Group([this.image, this.viewportMask], {
					originX: 'center',
					originY: 'center',
					selectable: false
				});
				this.canvas.add(this.viewport);

				// Add listeners to buttons and draw the grid
				this._addListeners();
				this._drawGrid();

				this._prepareImageForRotation();

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

				this.viewportWidth = this.image.width;
			} else {
				this.viewportWidth = this.editorWidth;
				this.image.width = this.viewportWidth;

				// Never scale to parts of a pixel
				this.image.height = Math.round(this.originalHeight * (this.image.width / this.originalWidth));

				this.viewportHeight = this.image.height;
			}

			this.image.set({
				originX: 'left',
				originY: 'top',
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
		_createViewportMask: function (dimensions) {
			var mask = new fabric.Rect({
				width: dimensions.width,
				height: dimensions.height,
				fill: '#000',
				left: dimensions.left + (dimensions.width / 2),
				top: dimensions.top + (dimensions.height / 2),
				originX: 'center',
				originY: 'center'
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
				this.cancelCropMode(ev);
			}));
			this.addListener($('.apply-crop', this.$tools), 'click', _callIfControlActive(function (ev) {
				this.applyCrop(ev);
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
				this.discardCrop();
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

						this.getZoomToCoverRatio();
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
				this.discardCrop();
				this.animationInProgress = true;

				if (ev) {
					if (ev.type == 'change' || ev.type == 'click') {
						this.hideGrid();
					} else {
						this.showGrid();
					}
				}

				var newAngle = parseInt(this.$straighten.val(), 10);

				this._prepareImageForRotation();

				// Straighten the image
				this.image.set({
					angle: parseInt((newAngle + 360) % 360, 10),
				});

				this.zoomRatio = this.getZoomToCoverRatio();
				this._renewImageZoomRatio();
				this._destroyCropper();

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
		 * Return image zoom ratio depending on the straighten angle to cover the viewport fully
		 */
		getZoomToCoverRatio: function () {
			this.imageStraightenAngle = parseFloat(this.$straighten.val());

			// Convert the angle to radians
			var angleInRadians = Math.abs(this.imageStraightenAngle) * (Math.PI / 180);

			// Calculate the dimensions of the scaled image using the magic of math
			var scaledWidth = Math.sin(angleInRadians) * this.viewportHeight + Math.cos(angleInRadians) * this.viewportWidth;
			var scaledHeight = Math.sin(angleInRadians) * this.viewportWidth + Math.cos(angleInRadians) * this.viewportHeight;

			// Calculate the ratio
			return Math.max(scaledWidth /  this.viewportWidth, scaledHeight / this.viewportHeight);
		},

		/**
		 * Return image zoom ratio depending on the straighten angle to fit inside the viewport
		 */
		getZoomToFitRatio: function () {
			this.imageStraightenAngle = parseFloat(this.$straighten.val());

			// Convert the angle to radians
			var angleInRadians = Math.abs(this.imageStraightenAngle) * (Math.PI / 180);

			// Use straight triangles and substitution to get an expression that equates to scaled height
			var proportion = this.originalWidth / this.originalHeight;
			var scaledHeight = this.viewportWidth / (Math.cos(angleInRadians) * proportion + Math.sin(angleInRadians));
			var scaledWidth = scaledHeight * proportion;

			// Calculate the ratio
			return Math.max(scaledHeight / this.viewportHeight, scaledWidth/ this.viewportWidth);
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
				this._prepareImageForRotation();
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

			this.zoomRatio = this.getZoomToFitRatio();

			var callback = function () {
				$('.cropping-tools .crop-mode-enabled', this.$tools).removeClass('hidden');
				$('.cropping-tools .crop-mode-disabled', this.$tools).addClass('hidden');
				this.canvas.renderAll();
			}.bind(this);

			this.discardCrop(true);
			this.showCropper();
			this._switchEditingMode({mode: 'crop', onFinish: callback});

			this.viewportMask.animate({
				width: this.editorWidth,
				height: this.editorHeight
			}, {
				duration: this.settings.animationDuration
			});

			this.canvas.renderAll();
		},

		cancelCropMode: function () {
			this.zoomRatio = this.getZoomToCoverRatio();

			var callback = function () {
				$('.rotation-tools, .filter-tools', this.$tools).removeClass('disabled');
				$('.cropping-tools .crop-mode-enabled', this.$tools).addClass('hidden');
				$('.cropping-tools .crop-mode-disabled', this.$tools).removeClass('hidden');

				this.canvas.renderAll();
			}.bind(this);

			this.hideCropper()
			this._switchEditingMode({mode: 'edit', onFinish: callback});

			this.viewportMask.animate({
				width: this.viewportWidth,
				height: this.viewportHeight
			}, {
				duration: this.settings.animationDuration
			});

			this.canvas.renderAll();
		},

		discardCrop: function (skipAnimation) {
			if (this.isCroppingPerformed) {

				// Reset image position
				this._scaleAndCenterImage();

				// Reset rotation origin
				this._prepareImageForRotation();

				// Re-set the viewport mask size
				var properties = {
					width: this.viewportWidth,
					height: this.viewportHeight,
					top: this.image.top - 1,
					left: this.image.left - 1
				};

				// A special case is when discarding crop info and going straight
				// to crop mode. The switching to crop mode animates the viewport,
				// so we don't so we do not interfere.
				if (skipAnimation) {
					this.viewportMask.set(properties);
				} else {
					this.viewportMask.animate(properties,{
						duration: this.settings.animationDuration,
						onChange: this.canvas.renderAll.bind(this.canvas)
					});
				}

				this.canvas.renderAll();

				this.isCroppingPerformed = false;
			}
		},

		applyCrop: function () {

			var cropperWidth = this.clipper.width * this.lastValidCroppingScales.x;
			var cropperHeight = this.clipper.height * this.lastValidCroppingScales.y;

			// Compensate for frame stroke thickness
			var cropperCenter = {
				x: this.lastValidCroppingCoordinates.left + (cropperWidth / 2) + 2,
				y: this.lastValidCroppingCoordinates.top + (cropperHeight / 2) + 2,
			}


			var deltaX = this.editorWidth / 2 - cropperCenter.x;
			var deltaY = this.editorHeight / 2 - cropperCenter.y;

			var viewportWidth = this.clipper.width;
			var viewportHeight = this.clipper.height;

			// Morph the viewport to match the clipper
			this.viewportMask.animate({
				width: cropperWidth,
				height: cropperHeight
			}, {
				duration: this.settings.animationDuration
			});

			this.image.animate({
				left: this.image.left + deltaX,
				top: this.image.top + deltaY
			},{
				onComplete: function () {
					$('.rotation-tools, .filter-tools', this.$tools).removeClass('disabled');
					$('.cropping-tools .crop-mode-enabled', this.$tools).addClass('hidden');
					$('.cropping-tools .crop-mode-disabled', this.$tools).removeClass('hidden');
				}.bind(this),
				onChange: this.canvas.renderAll.bind(this.canvas),
				duration: this.settings.animationDuration});

			this.hideCropper();

			this.isCroppingPerformed = true;
		},

		_switchEditingMode: function (settings) {
			$('.rotation-tools, .filter-tools, .cropping-tools', this.$tools).addClass('disabled').find('select').prop('disabled', true);

			if (settings.mode == 'crop') {
				var selector = '.cropping-tools';
			} else {
				var selector = '.cropping-tools, .rotation-tools, .filter-tools';
			}

			this.image.animate({
				scaleX: this.zoomRatio,
				scaleY: this.zoomRatio
			}, {
				onChange: this.canvas.renderAll.bind(this.canvas),
				duration: this.settings.animationDuration,
				onComplete: $.proxy(function () {
					$(selector, this.$tools).removeClass('disabled').find('select').prop('disabled', false);
					settings.onFinish();
				}, this)
			});
		},

		showCropper: function () {
			if (!this.cropper) {
				this._createCropper();
			}

			// Shade MUST be added first for masking purposes.
			this.croppingCanvas.add(this.croppingShade);
			this.croppingCanvas.add(this.cropper);
			this.croppingCanvas.setActiveObject(this.cropper);
		},

		hideCropper: function () {
			if (this.cropper) {
				this.croppingCanvas.remove(this.cropper);
				this.croppingCanvas.remove(this.croppingShade);
			}
		},

		_createCropper: function () {

			this.croppingCanvas = new fabric.Canvas('cropping-canvas', {backgroundColor: 'rgba(0,0,0,0)', hoverCursor: 'default', selection: false});

			this.croppingCanvas.setDimensions({
				width: this.editorWidth,
				height: this.editorHeight
			});

			$('.canvas-container', this.$editorContainer).css({position: 'absolute', top: 0, left: 0});

			this.croppingShade = new fabric.Rect({
				width: this.editorWidth,
				height: this.editorHeight,
				fill: 'rgba(255,255,255,0.4)',
				left: 0,
				top: 0
			});

			this.croppingShade.set({
				hasBorders: false,
				hasControls: false,
				selectable: false,
				lockRotation: true
			});

			// calculate the cropping rectangle size.
			combinedRatio = this.imageStraightenAngle == 0 ? 1.2 : (this.getZoomToCoverRatio() / this.getZoomToFitRatio()) * 1.2;
			var rectWidth = this.viewportWidth / combinedRatio,
				rectHeight = this.viewportHeight / combinedRatio;

			// Set up the cropping viewport rectangle.
			var croppingGroup = [];
			this.clipper = new fabric.Rect({
				left: 0,
				top: 0,
				width: rectWidth,
				height: rectHeight,
				stroke: 'black',
				fill: 'rgba(128,0,0,1)',
				strokeWidth: 0
			});

			this.clipper.globalCompositeOperation = 'destination-out';
			croppingGroup.push(this.clipper);

			// Draw the cropping rectangle.
			var rectangle = new fabric.Rect({
				left: 0,
				top: 0,
				width: rectWidth - 1,
				height: rectHeight - 1,
				fill: 'rgba(0,0,0,0)',
				stroke: 'rgba(255,255,255,0.8)',
				strokeWidth: 2
			});
			croppingGroup.push(rectangle);

			this.cropper = new fabric.Group(croppingGroup,
				{
					left: this.editorWidth / 2 - rectWidth / 2 - 2,
					top: this.editorHeight / 2 - rectHeight / 2 - 2,
					hoverCursor: 'move'
				}
			);

			this.cropper.set({
				hasBorders: false,
				lockRotation: true,
				hasRotatingPoint: false,
				borderOpacityWhenMoving: 1,
				cornerColor: 'rgba(255,255,255,0.8)',
				transparentCorners: false,
				cornerSize: 10,
				cornerStyle: 'circle'
			});

			this.lastValidCroppingCoordinates = {
				left: this.cropper.left,
				top: this.cropper.top
			};

			this.lastValidCroppingScales = {
				x: 1,
				y: 1
			}

			this._setTiltedVerticeCoordinates();

			var onCropperChange = function (eventType, options) {
				var cropper = options.target;

				var rectWidth = Math.floor(cropper.width * cropper.scaleX);
				var rectHeight = Math.floor(cropper.height * cropper.scaleY);

				// Cropping rectangle border is 2px thick, so compensate for that.
				var topLeft = {x: Math.ceil(cropper.left) + 2, y: Math.ceil(cropper.top) + 2};
				var topRight = {x: topLeft.x + rectWidth - 4, y: topLeft.y};
				var bottomRight = {x: topRight.x, y: topRight.y + rectHeight - 4};
				var bottomLeft = {x: topLeft.x, y: bottomRight.y};


				var fitsTheImage = this.arePointsInsideRectangle([topLeft, topRight, bottomRight, bottomLeft], this.tiltedImageVerticeCoords);

				var bigEnough = rectWidth >= this.settings.minimumCropperSize.width && rectHeight >= this.settings.minimumCropperSize.height;

				if (!(fitsTheImage && bigEnough)) {
					var adjustedPosition = false;
					if (bigEnough && eventType == "move") {
						var deltaX = options.e.screenX - this.previousScreenX;
						var deltaY = options.e.screenY - this.previousScreenY;
					}

					cropper.set({
						scaleX: this.lastValidCroppingScales.x,
						scaleY: this.lastValidCroppingScales.y});

					// No adjustment, just hard set to last valid coords.
					if (!adjustedPosition) {
						cropper.set({
							top: this.lastValidCroppingCoordinates.top,
							left: this.lastValidCroppingCoordinates.left
						});
					}
				}

				this.lastValidCroppingScales = {x: cropper.scaleX, y: cropper.scaleY};
				this.lastValidCroppingCoordinates = {top: cropper.top, left: cropper.left};
				this.previousScreenX = options.e.screenX;
				this.previousScreenY = options.e.screenY;

			}.bind(this);

			this.croppingCanvas.on({
				'object:moving': function (options) { onCropperChange.call(this, "move", options);}.bind(this),
				'object:scaling': function (options) { onCropperChange.call(this, "scale", options);}.bind(this)
			});
		},

		_setTiltedVerticeCoordinates: function () {

			var angleInRadians = -1 * this.imageStraightenAngle * (Math.PI / 180);

			// Get the dimensions of the scaled image
			var scaledHeight = this.image.height  * this.getZoomToFitRatio();
			var scaledWidth = this.image.width  * this.getZoomToFitRatio();

			// Calculate the segments of the containing box for the image.
			// When referring to top/bottom or right/left segments, these are on the
			// right-side and bottom projection of the containing box for the zoomed out image.
			var topVerticalSegment = Math.cos(angleInRadians) * scaledHeight;
			var bottomVerticalSegment = Math.sin(angleInRadians) * scaledWidth;
			var rightHorizontalSegment = Math.cos(angleInRadians) * scaledWidth;
			var leftHorizontalSegment = Math.sin(angleInRadians) * scaledHeight;

			// Calculate the offsets from editor box for the image-containing box
			var verticalOffset = (this.editorHeight - (topVerticalSegment + bottomVerticalSegment)) / 2;
			var horizontalOffset = (this.editorWidth - (leftHorizontalSegment + rightHorizontalSegment)) / 2;

			// Finally, calculate the tilted image vertice coordinates
			this.tiltedImageVerticeCoords = {
				a: {x: horizontalOffset + rightHorizontalSegment, y: verticalOffset},
				b: {x: this.editorWidth - horizontalOffset, y: verticalOffset + topVerticalSegment},
				c: {x: horizontalOffset + leftHorizontalSegment, y: this.editorHeight - verticalOffset},
				d: {x: horizontalOffset, y: verticalOffset + bottomVerticalSegment}
			};
		},

		_destroyCropper: function () {
			this.clipper = null;
			this.cropper = null;
			this.croppingShade = null;
			this.croppingCanvas = null;

			$('#cropping-canvas').siblings('.upper-canvas').remove();
			$('#cropping-canvas').parent('.canvas-container').before($('#cropping-canvas'));
			$('.canvas-container').remove();

		},

		/**
		 * Given an array of points in the form of {x: int, y:int} and a rectangle in the form of
		 * {a:{x:int, y:int}, b:{x:int, y:int}, c:{x:int, y:int}} (the fourth vertice is unnecessary)
		 * return true if the point is in the rectangle.
		 *
		 * Adapted from: http://stackoverflow.com/a/2763387/2040791
		 *
		 * @param point
		 * @param rectangle
		 */
		arePointsInsideRectangle: function (points, rectangle) {

			// Return the vector between two points.
			var getVector = function (a, b) {
				return {x: b.x - a.x, y: b.y - a.y};
			};

			// Return the dot product for two vectors
			var scalarProduct = function (a, b) {
				return a.x * b.x + a.y * b.y;
			}

			// Pre-calculate the vectors and scalar products for two rectangle edges
			var ab = getVector(rectangle.a, rectangle.b);
			var bc = getVector(rectangle.b, rectangle.c);
			var scalarAbAb = scalarProduct(ab, ab);
			var scalarBcBc = scalarProduct(bc, bc);

			for (var i = 0; i < points.length; i++) {
				var point = points[i];

				// Calculate the vectors for two rectangle sides and for
				// the vector from vertices a and b to the point P
				var ap = getVector(rectangle.a, point);
				var bp = getVector(rectangle.b, point);

				// Calculate scalar or dot products for some vector combinations
				var scalarAbAp = scalarProduct(ab, ap);
				var scalarBcBp = scalarProduct(bc, bp);

				// The dot product of two vectors is negative for acute angles and 0 for straight angles, so:
				// 1) The comparison to 0 makes sure that point projection on the
				//    rectangle edge is towards the edge vector's endpoint.
				// 2) That scalar product has to be smaller than the edge vector
				//    squared. Otherwise the dot's projection on the edge is beyond
				//    the vector's endpoint which means it's outside of rectangle.
				var projectsOnAB = 0 <= scalarAbAp && scalarAbAp <= scalarAbAb;
				var projectsOnBC = 0 <= scalarBcBp && scalarBcBp <= scalarBcBc;

				if (!(projectsOnAB && projectsOnBC)) {
					return false;
				}
			}

			return true;
		},

		_prepareImageForRotation: function () {
			this.image.set({
				originX: 'center',
				originY: 'center',
				left: 0,
				top: 0
			});
			this.canvas.renderAll();
		}
	},
	{
		defaults: {
			gridLineThickness: 1,
			gridLineColor: '#000000',
			gridLinePrecision: 2,
			animationDuration: 100,
			assetSize: 400,
			allowSavingAsNew: true,
			minimumCropperSize : {
				width: 50,
				height: 50
			},

			onSave: $.noop,
		}
	}
);