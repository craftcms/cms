/**
 * Asset image editor class
 */

// TODO: When rotating by 90 degrees, the cropping constraint acts like the image has not been rotated
// TODO: Maybe namespace all the attributes?
// TODO: Go over each attribute and method to make sure it's used at all.
// TODO: Rename and maybe refactor misleading names for methods. _scaleAndCenterImage(), for example. It does other stuff too.
// TODO: Condense all the var statements, where applicable in a single `var` list.

Craft.AssetImageEditor = Garnish.Modal.extend(
	{
		// jQuery objects
		$body: null,
		$footer: null,
		$tools: null,
		$buttons: null,
		$cancelBtn: null,
		$replaceBtn: null,
		$saveBtn: null,
		$editorContainer: null,
		$straighten: null,
		$croppingCanvas: null,

		// FabricJS objects
		canvas: null,
		image: null,
		viewportMask: null,
		grid: null,
		croppingCanvas: null,
		clipper: null,
		croppingRectangle: null,
		cropperHandles: null,
		croppingShade: null,

		// Image state attributes
		imageAngle: 0,
		imageStraightenAngle: 0,
		viewportRotation: 0,
		originalWidth: 0,
		originalHeight: 0,
		imageVerticeCoords: null,
		zoomRatio: 1,

		// Editor state attributes
		animationInProgress: false,
		currentView: 'rotate',
		assetId: null,
		cacheBust: null,
		draggingCropper: false,
		scalingCropper: false,
		previousMouseX: 0,
		previousMouseY: 0,
		lockAspectRatio: false,
		cropData: {},
		editorHeight: 0,
		editorWidth: 0,
		isCroppingPerformed: false,

		// Misc
		renderImage: null,
		renderCropper: null,

		init: function (assetId, settings) {
			this.cacheBust = Date.now();

			this.setSettings(settings, Craft.AssetImageEditor.defaults);

			this.assetId = assetId;

			// Build the modal
			this.$container = $('<form class="modal fitted imageeditor"></form>').appendTo(Garnish.$bod),
				this.$body = $('<div class="body"></div>').appendTo(this.$container),
				this.$footer = $('<div class="footer"/>').appendTo(this.$container);

			this.base(this.$container, this.settings);

			this.$buttons = $('<div class="buttons rightalign"/>').appendTo(this.$footer);
			this.$cancelBtn = $('<div class="btn cancel">' + Craft.t('app', 'Cancel') + '</div>').appendTo(this.$buttons);
			this.$replaceBtn = $('<div class="btn submit save replace">' + Craft.t('app', 'Replace Asset') + '</div>').appendTo(this.$buttons);

			if (this.settings.allowSavingAsNew) {
				this.$saveBtn = $('<div class="btn submit save copy">' + Craft.t('app', 'Save as New Asset') + '</div>').appendTo(this.$buttons);
			}

			this.addListener(this.$cancelBtn, 'activate', $.proxy(this, 'hide'));
			this.removeListener(this.$shade, 'click');

			this.updateRequestedImageSize();

			Craft.postActionRequest('assets/image-editor', $.proxy(this, 'loadEditor'));
		},

		updateRequestedImageSize: function () {
			var browserViewportWidth = Garnish.$doc.get(0).documentElement.clientWidth,
				browserViewportHeight = Garnish.$doc.get(0).documentElement.clientHeight;

			this.requestedImageSize = Math.min(browserViewportHeight, browserViewportWidth);
		},

		loadEditor: function (data) {
			this.$body.html(data.html);
			this.$tabs = $('.tabs li', this.$body);
			this.$viewsContainer = $('.views', this.$body);
			this.$views = $('> div', this.$viewsContainer);
			this.$imageTools = $('.image-container .image-tools', this.$body);

			this.straighteningInput = new SlideRuleInput("slide-rule", {
				onStart: function () {
					this._showGrid();
				}.bind(this),
				onChange: function (slider) {
					this.straighten(slider);
				}.bind(this),
				onEnd: function () {
					this._hideGrid();
				}.bind(this)
			});

			this.$editorContainer = $('.image-container .image', this.$body);
			this.editorHeight = this.$editorContainer.innerHeight();
			this.editorWidth = this.$editorContainer.innerWidth();

			this.updateSizeAndPosition();

			this.canvas = new fabric.StaticCanvas('image-canvas');
			this.renderImage = function () {
				Garnish.requestAnimationFrame(this.canvas.renderAll.bind(this.canvas));
			}.bind(this);

			this.canvas.enableRetinaScaling = true;

			// TODO add loading spinner
			// TODO make sure small images are not scaled up
			// TODO Make sure that retina works

			// Load the image from URL
			var imageUrl = Craft.getActionUrl('assets/edit-image', {
				assetId: this.assetId,
				size: this.requestedImageSize,
				cacheBust: this.cacheBust
			});

			fabric.Image.fromURL(imageUrl, $.proxy(function (imageObject) {

				this.image = imageObject;

				this.originalHeight = this.image.getHeight();
				this.originalWidth = this.image.getWidth();
				this.zoomRatio = 1;

				this.image.set({
					originX: 'center',
					originY: 'center'
				});

				this.canvas.add(this.image);

				// Scale the image and center it on the canvas
				this._repositionEditorElements();
				this._createViewportMask();

				// Add listeners to buttons
				this._addControlListeners();

				// Render it, finally
				this.renderImage();

				this.showView('rotate');
			}, this));
		},

		updateSizeAndPosition: function () {
			// TODO if sizing up significantly from starting size, load a higher-res image if available
			if (!this.$container) {
				return;
			}

			// Fullscreen modal

			var innerWidth = window.innerWidth;
			var innerHeight = window.innerHeight;

			this.$container.css({
				'width': innerWidth,
				'min-width': innerWidth,
				'left': 0,

				'height': innerHeight,
				'min-height': innerHeight,
				'top': 0
			});

			this.$body.css({
				'height': innerHeight - 58,
			});

			if (innerWidth < innerHeight) {
				this.$container.addClass('vertical');
			}
			else {
				this.$container.removeClass('vertical');
			}

			// If editor is loaded, update those dimensions
			if (this.$editorContainer) {
				// If image is already loaded, make sure it looks pretty.
				if (this.image) {
					this._repositionEditorElements();
				}
			}
		},

		/**
		 * Scale and center the image in the editor
		 */
		_repositionEditorElements: function () {

			if (this.currentView == 'crop') {
				var previousDimensions = this.getScaledImageDimensions();
			}

			this.editorHeight = this.$editorContainer.innerHeight();
			this.editorWidth = this.$editorContainer.innerWidth();

			this.canvas.setDimensions({
				width: this.editorWidth,
				height: this.editorHeight
			});

			if (this.currentView == 'crop') {
				this.zoomRatio = this.getZoomToFitRatio(this.getScaledImageDimensions());
				var previouslyOccupiedArea = this._getImageOccupiedArea();
				this._setImageVerticeCoordinates();
				this._repositionCropper(previouslyOccupiedArea);
			} else {
				this.zoomRatio = this.getZoomToCoverRatio(this.getScaledImageDimensions());
			}

			this._calculateViewportMask();
			this._setImagePosition();
			this._zoomImage();

			this.renderImage();
		},

		_setImagePosition: function () {
			// TODO take into account crop performed
			this.image.set({
				left: this.editorWidth / 2,
				top: this.editorHeight / 2
			});
		},

		_createViewportMask: function () {
			this.viewportMask = new fabric.Rect({
				width: this.image.width,
				height: this.image.height,
				fill: 'rgba(127,0,0,1)',
				originX: 'center',
				originY: 'center',
				globalCompositeOperation: 'destination-in',
				left: this.image.left,
				top: this.image.top
			});
			this.canvas.add(this.viewportMask);
			this.renderImage();
		},

		_calculateViewportMask: function () {
			// TODO Take into account cropping performed
			if (this.viewportMask) {
				var dimensions = {};
				if (this.currentView == 'crop') {
					dimensions = {
						width: this.editorWidth,
						height: this.editorHeight
					};
				} else {
					dimensions = this.getScaledImageDimensions();
				}

				this.viewportMask.set($.extend({}, dimensions, {
					left: this.editorWidth / 2,
					top: this.editorHeight / 2
				}));

				this.renderImage();
			}
		},

		hasOrientationChanged: function () {
			return this.viewportRotation % 180 != 0;
		},

		getScaledImageDimensions: function () {
			var imageRatio = this.originalHeight / this.originalWidth;
			var editorRatio = this.editorHeight / this.editorWidth;

			var dimensions = {};
			if (this.hasOrientationChanged()) {
				imageRatio = 1 / imageRatio;
				if (imageRatio > editorRatio) {
					dimensions.width = this.editorHeight;
					dimensions.height = Math.round(this.originalHeight * (this.editorHeight / this.originalWidth));
				} else {
					dimensions.height = this.editorWidth;
					dimensions.width = Math.round(this.originalWidth / (this.originalHeight / this.editorWidth));
				}
			} else {
				if (imageRatio > editorRatio) {
					dimensions.height = this.editorHeight;
					dimensions.width = Math.round(this.originalWidth / (this.originalHeight / this.editorHeight));
				} else {
					dimensions.width = this.editorWidth;
					dimensions.height = Math.round(this.originalHeight * (this.editorWidth / this.originalWidth));
				}
			}

			return dimensions;
		},

		/**
		 * Enforce the image's zoom ratio.
		 * @private
		 */
		_zoomImage: function () {
			var imageDimensions = this.getScaledImageDimensions();
			this.image.set({
				width: imageDimensions.width * this.zoomRatio,
				height: imageDimensions.height * this.zoomRatio,
			});
		},

		/**
		 * Add listeners to buttons
		 */
		_addControlListeners: function () {

			// Tabs
			this.addListener(this.$tabs, 'click', '_handleTabClick');

			// Controls
			this.addListener($('.rotate-left'), 'click', function (ev) {
				this.rotateImage(-90);
			}.bind(this));

			this.addListener($('.rotate-right'), 'click', function (ev) {
				this.rotateImage(90);
			}.bind(this));

			// Controls
			this.addListener($('.flip-vertical'), 'click', function (ev) {
				this.flipImage('y');
			}.bind(this));

			this.addListener($('.flip-horizontal'), 'click', function (ev) {
				this.flipImage('x');
			}.bind(this));

			this.addListener(Garnish.$doc, 'keydown', function (ev) {
				if (ev.keyCode == Garnish.SHIFT_KEY) {
					this.lockAspectRatio = true;
				}
			}.bind(this));
			this.addListener(Garnish.$doc, 'keyup', function (ev) {
				if (ev.keyCode == Garnish.SHIFT_KEY) {
					this.lockAspectRatio = false;
				}
			}.bind(this));
		},

		_handleTabClick: function (ev) {
			if (!this.animationInProgress) {
				var $tab = $(ev.currentTarget);
				var view = $tab.data('view');
				this.$tabs.removeClass('selected');
				$tab.addClass('selected');
				this.showView(view);
			}
		},

		showView: function (view) {
			this.$views.addClass('hidden');
			var $view = this.$views.filter('[data-view="' + view + '"]');
			$view.removeClass('hidden');

			if (view == 'rotate') {
				this.enableSlider();
			} else {
				this.disableSlider();
			}
			this.updateSizeAndPosition();

			if (this.currentView == 'crop' && view != 'crop') {
				this.disableCropMode();
			} else if (this.currentView != 'crop' && view == 'crop') {
				this.enableCropMode();
			}

			this.currentView = view;
		},

		/**
		 * Rotate the image along with the cropping mask.
		 *
		 * @param integer degrees
		 */
		rotateImage: function (degrees) {

			// TODO Since more than one method is using this, maybe make it a "reguestAnimationSlot" or something.
			if (!this.animationInProgress) {
				if (degrees % 90 != 0) {
					return false;
				}

				this.animationInProgress = true;
				this.viewportRotation += degrees;

				// Normalize the viewport rotation angle so it's between 0 and 359
				this.viewportRotation = parseInt((this.viewportRotation + 360) % 360, 10);

				var imageDimensions = this.getScaledImageDimensions();
				var newAngle = this.image.getAngle() + degrees;

				this.viewportMask.animate({
					angle: this.viewportRotation
				}, {
					duration: this.settings.animationDuration
				});
				// Animate the rotations
				this.image.animate({
					angle: newAngle,
					width: imageDimensions.width,
					height: imageDimensions.height
				}, {
					onChange: this.canvas.renderAll.bind(this.canvas),
					duration: this.settings.animationDuration,
					onComplete: function () {
						// Clean up angle
						var cleanAngle = parseInt((this.image.getAngle() + 360) % 360, 10);
						this.image.set({angle: cleanAngle});
						this.animationInProgress = false;

						this._repositionEditorElements();
					}.bind(this)
				});
			}
		},

		flipImage: function (scale) {
			if (!this.animationInProgress) {
				this.animationInProgress = true;

				if (this.hasOrientationChanged()) {
					scale = scale == 'y' ? 'x' : 'y';
				}

				var properties = {};

				if (scale == 'y') {
					var properties = {
						scaleY: this.image.scaleY * -1
					};
				} else {
					var properties = {
						scaleX: this.image.scaleX * -1
					};
				}

				this.image.animate(properties, {
					onChange: this.canvas.renderAll.bind(this.canvas),
					duration: this.settings.animationDuration,
					onComplete: function () {
						this.animationInProgress = false;
						this._repositionEditorElements();
					}.bind(this)
				});
			}
		},

		/**
		 * Perform the straightening by slider
		 *
		 * @param Event ev
		 */
		straighten: function (slider) {
			if (!this.animationInProgress) {
				this.animationInProgress = true;

				this.imageStraightenAngle = parseInt(slider.value, 10) % 360;

				// Straighten the image
				this.image.set({
					angle: this.viewportRotation + this.imageStraightenAngle
				});

				this.zoomRatio = this.getZoomToCoverRatio(this.getScaledImageDimensions());

				this._zoomImage();

				this.renderImage();

				this.animationInProgress = false;
			}
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

			if (this.isCroppingPerformed) {
				postData.cropData = this.cropData;
			}

			Craft.postActionRequest('assets/save-image', postData, function (data) {
				this.$buttons.find('.btn').removeClass('disabled').end().find('.spinner').remove();
				this.onSave();
				//this.hide();
			}.bind(this));
		},

		/**
		 * Return image zoom ratio depending on the straighten angle to cover a viewport by given dimensions
		 */
		getZoomToCoverRatio: function (dimensions) {
			// Convert the angle to radians
			var angleInRadians = Math.abs(this.imageStraightenAngle) * (Math.PI / 180);

			// Calculate the dimensions of the scaled image using the magic of math
			var scaledWidth = Math.sin(angleInRadians) * dimensions.height + Math.cos(angleInRadians) * dimensions.width;
			var scaledHeight = Math.sin(angleInRadians) * dimensions.width + Math.cos(angleInRadians) * dimensions.height;

			// Calculate the ratio
			return Math.max(scaledWidth / dimensions.width, scaledHeight / dimensions.height);
		},

		/**
		 * Return image zoom ratio depending on the straighten angle to fit inside a viewport by given dimensions
		 */
		getZoomToFitRatio: function (dimensions) {

			// Get the bounding box for a rotated image
			boundingBox = this._getImageBoundingBox(dimensions);

			// Scale the bounding box to fit!
			var scale = 1;
			if (boundingBox.height > this.editorHeight || boundingBox.width > this.editorWidth) {
				var vertScale = this.editorHeight / boundingBox.height,
					horiScale = this.editorWidth / boundingBox.width;
				scale = Math.min(horiScale, vertScale);
			}

			return scale;
		},

		/**
		 * Return the combined zoom ratio to fit a rectangle inside image that's been zoomed to fit.
		 */
		getCombinedZoomRatio: function (dimensions) {
			return this.getZoomToCoverRatio(dimensions) / this.getZoomToFitRatio(dimensions);
		},

		/**
		 * Draw the grid.
		 *
		 * @private
		 */
		_showGrid: function () {
			if (!this.grid) {
				var strokeOptions = {
					strokeWidth: 1,
					stroke: 'rgba(255,255,255,0.5)'
				};

				var lineCount = 8,
					gridWidth = this.viewportMask.width,
					gridHeight = this.viewportMask.height,
					xStep = gridWidth / (lineCount + 1),
					yStep = gridHeight / (lineCount + 1);

				// TODO account for cropped image
				var grid = [
					new fabric.Rect({
						strokeWidth: 2,
						stroke: 'rgba(255,255,255,1)',
						originX: 'center',
						originY: 'center',
						width: gridWidth,
						height: gridHeight,
						left: gridWidth / 2,
						top: gridHeight / 2,
						fill: 'rgba(255,255,255,0)'
					})];

				for (var i = 1; i <= lineCount; i++) {
					grid.push(new fabric.Line([i * xStep, 0, i * xStep, gridHeight], strokeOptions));
				}
				for (var i = 1; i <= lineCount; i++) {
					grid.push(new fabric.Line([0, i * yStep, gridWidth, i * yStep], strokeOptions));
				}

				this.grid = new fabric.Group(grid, {
					left: this.editorWidth / 2,
					top: this.editorHeight / 2,
					originX: 'center',
					originY: 'center',
					angle: this.viewportMask.angle
				});

				this.canvas.add(this.grid);
				this.renderImage();
			}
		},

		/**
		 * Hide the grid
		 */
		_hideGrid: function () {
			this.canvas.remove(this.grid);
			this.grid = null;
			this.renderImage();
		},

		onFadeOut: function () {
			this.destroy();
		},

		/**
		 * Apply a selected filter.
		 */
		applyFilter: function (ev) {

			// TODO
		},

		show: function () {
			this.base();

			$('html').addClass('noscroll');
		},

		hide: function () {
			this.removeAllListeners();
			this.straighteningInput.removeAllListeners();
			$('html').removeClass('noscroll');
			this.base();
		},

		onSave: function () {
			this.settings.onSave();
		},

		enableSlider: function () {
			this.$imageTools.removeClass('hidden');
		},

		disableSlider: function () {
			this.$imageTools.addClass('hidden');
		},

		enableCropMode: function () {
			this._setImageMode('crop');
		},

		disableCropMode: function () {
			this._setImageMode('regular');
		},

		_setImageMode: function (mode) {
			if (!this.animationInProgress) {
				this.animationInProgress = true;

				var imageDimensions = this.getScaledImageDimensions();
				var viewportDimensions = $.extend({}, imageDimensions);

				if (mode == 'crop') {
					this.zoomRatio = this.getZoomToFitRatio(imageDimensions);
					viewportDimensions = {
						width: this.editorWidth,
						height: this.editorHeight
					};
					var callback = function () {
						this._setImageVerticeCoordinates();
						this._showCropper();
					}.bind(this);
				} else {
					this.zoomRatio = this.getZoomToCoverRatio(imageDimensions);
					var callback = function () {
						this.updateSizeAndPosition();
						this._hideCropper();
					}.bind(this);
				}

				this.image.animate({
					width: imageDimensions.width * this.zoomRatio,
					height: imageDimensions.height * this.zoomRatio,
				}, {
					onChange: this.canvas.renderAll.bind(this.canvas),
					duration: this.settings.animationDuration,
					onComplete: function () {
						callback();
						this.animationInProgress = false;
						this.renderImage();
					}.bind(this)
				});

				this.viewportMask.animate({
					width: viewportDimensions.width,
					height: viewportDimensions.height
				}, {
					duration: this.settings.animationDuration
				});
			}
		},

		applyCrop: function () {

			var clipperWidth = this.clipper.width;
			var clipperHeight = this.clipper.height;

			// Compensate for frame stroke thickness
			var clipperCenter = {
				x: this.clipper.left + (clipperWidth / 2) + 2,
				y: this.clipper.top + (clipperHeight / 2) + 2,
			};


			var deltaX = clipperCenter.x - this.editorWidth / 2;
			var deltaY = clipperCenter.y - this.editorHeight / 2;

			// Morph the viewport to match the clipper
			this.viewportMask.animate({
				width: clipperWidth,
				height: clipperHeight
			}, {
				duration: this.settings.animationDuration
			});

			this.image.animate({
				left: this.image.left - deltaX,
				top: this.image.top - deltaY
			}, {
				onComplete: function () {
					$('.rotation-tools, .filter-tools', this.$tools).removeClass('disabled');
					$('.cropping-tools .crop-mode-enabled', this.$tools).addClass('hidden');
					$('.cropping-tools .crop-mode-disabled', this.$tools).removeClass('hidden');
				}.bind(this),
				onChange: this.canvas.renderAll.bind(this.canvas),
				duration: this.settings.animationDuration
			});

			this.hideCropper();

			var leftOffset;
			var topOffset;

			// If the image has not been straightened, then we probably have some
			// space on top/bottom or left/right edges.
			if (this.imageStraightenAngle == 0) {
				var leftOffset = Math.round((this.editorWidth - this.image.width) / 2);
				var topOffset = Math.round((this.editorHeight - this.image.height) / 2);
			} else {
				var leftOffset = 0;
				var topOffset = 0;
			}

			// When passing along the coordinates, take into account the possible excess space on edge of editor
			this.cropData = {
				width: this.clipper.width,
				height: this.clipper.height,
				cornerLeft: Math.max(Math.round(this.clipper.left - leftOffset - this.imageVerticeCoords.c.x), 0),
				cornerTop: Math.max(Math.round(this.clipper.top - topOffset - this.imageVerticeCoords.d.y), 0),
				scaledWidth: this.image.width,
				scaledHeight: this.image.height,
				zoomRatio: this.getZoomToFitRatio()
			};

			this.isCroppingPerformed = true;
		},

		_showCropper: function () {
			this._drawCropper();
			this._calculateCropperBoundaries();
			this.renderCropper();

			this.addListener(this.$croppingCanvas, 'mousemove', this._handleMouseMove.bind(this));
			this.addListener(this.$croppingCanvas, 'mousedown', this._handleMouseDown.bind(this));
			this.addListener(this.$croppingCanvas, 'mouseup', this._handleMouseUp.bind(this));
		},

		_hideCropper: function () {
			if (this.clipper) {
				this.croppingCanvas.remove(this.clipper);
				this.croppingCanvas.remove(this.croppingShade);
				this.croppingCanvas.remove(this.cropperHandles);
				this.croppingCanvas.remove(this.croppingRectangle);
				this.removeListener(this.$croppingCanvas, 'mousemove', this._handleMouseMove.bind(this));
				this.removeListener(this.$croppingCanvas, 'mousedown', this._handleMouseDown.bind(this));
				this.removeListener(this.$croppingCanvas, 'mouseup', this._handleMouseUp.bind(this));
				this.croppingCanvas = null;
				this.renderCropper = null;
			}
		},

		_drawCropper: function () {
			this.croppingCanvas = new fabric.StaticCanvas('cropping-canvas', {
				backgroundColor: 'rgba(0,0,0,0)',
				hoverCursor: 'default',
				selection: false
			});
			this.$croppingCanvas = $('#cropping-canvas', this.$editorContainer);

			this.croppingCanvas.setDimensions({
				width: this.editorWidth,
				height: this.editorHeight
			});

			this.renderCropper = function () {
				Garnish.requestAnimationFrame(this.croppingCanvas.renderAll.bind(this.croppingCanvas));
			}.bind(this);


			$('#cropping-canvas', this.$editorContainer).css({
				position: 'absolute',
				top: 0,
				left: 0
			});

			this.croppingShade = new fabric.Rect({
				left: this.editorWidth / 2,
				top: this.editorHeight / 2,
				originX: 'center',
				originY: 'center',
				width: this.editorWidth,
				height: this.editorHeight,
				fill: 'rgba(0,0,0,0.4)'
			});

			this.croppingShade.set({
				hasBorders: false,
				hasControls: false,
				selectable: false
			});

			// calculate the cropping rectangle size.
			var imageDimensions = this.getScaledImageDimensions();
			var rectangleRatio = this.imageStraightenAngle == 0 ? 1.2 : this.getCombinedZoomRatio(imageDimensions) * 1.2,
				rectWidth = imageDimensions.width / rectangleRatio,
				rectHeight = imageDimensions.height / rectangleRatio;

			// Set up the cropping viewport rectangle.
			this.clipper = new fabric.Rect({
				left: Math.floor(this.editorWidth / 2),
				top: Math.floor(this.editorHeight / 2),
				originX: 'center',
				originY: 'center',
				width: rectWidth,
				height: rectHeight,
				stroke: 'black',
				fill: 'rgba(128,0,0,1)',
				strokeWidth: 0,
				hasBorders: false,
				hasControls: false,
				selectable: false
			});

			this.clipper.globalCompositeOperation = 'destination-out';
			this.croppingCanvas.add(this.croppingShade);
			this.croppingCanvas.add(this.clipper);
		},

		_calculateCropperBoundaries: function () {
			if (this.cropperHandles) {
				this.croppingCanvas.remove(this.cropperHandles);
				this.croppingCanvas.remove(this.croppingRectangle);
			}
			var lineOptions = {
				strokeWidth: 2,
				stroke: 'rgb(255,255,255)',
				fill: false
			};

			var pathGroup = [];
			var path = new fabric.Path('M 0,10 L 0,0 L 10,0');
			path.set(lineOptions);
			pathGroup.push(path);
			var path = new fabric.Path('M ' + (this.clipper.width - 8) + ',0 L ' + (this.clipper.width + 4) + ',0 L ' + (this.clipper.width + 4) + ',10');
			path.set(lineOptions);
			pathGroup.push(path);
			var path = new fabric.Path('M ' + (this.clipper.width + 4) + ',' + (this.clipper.height - 8) + ' L' + (this.clipper.width + 4) + ',' + (this.clipper.height + 4) + ' L ' + (this.clipper.width - 8) + ',' + (this.clipper.height + 4));
			path.set(lineOptions);
			pathGroup.push(path);
			var path = new fabric.Path('M 10,' + (this.clipper.height + 4) + ' L 0,' + (this.clipper.height + 4) + ' L 0,' + (this.clipper.height - 8));
			path.set(lineOptions);
			pathGroup.push(path);

			this.cropperHandles = new fabric.Group(pathGroup, {
				left: this.clipper.left,
				top: this.clipper.top,
				originX: 'center',
				originY: 'center',
				hasBorders: false,
				hasControls: false,
				selectable: false
			});

			this.croppingRectangle = new fabric.Rect({
				left: this.clipper.left,
				top: this.clipper.top,
				width: this.clipper.width,
				height: this.clipper.height,
				fill: 'rgba(0,0,0,0)',
				stroke: 'rgba(255,255,255,0.8)',
				strokeWidth: 2,
				hasBorders: false,
				hasControls: false,
				selectable: false,
				originX: 'center',
				originY: 'center',
			});

			this.croppingCanvas.add(this.cropperHandles);
			this.croppingCanvas.add(this.croppingRectangle);
		},

		_repositionCropper: function (previouslyOccupiedArea) {
			if (!this.croppingCanvas) {
				return;
			}

			// Get the current clipper offset relative to center
			var currentOffset = {
				x: this.clipper.left - this.croppingCanvas.width / 2,
				y: this.clipper.top - this.croppingCanvas.height / 2
			};

			// Resize the cropping canvas
			this.croppingCanvas.setDimensions({
				width: this.editorWidth,
				height: this.editorHeight
			});

			// Check by what factor will the new final bounding box be different
			var currentArea = this._getImageOccupiedArea();
			var areaFactor = currentArea.width / previouslyOccupiedArea.width;

			// Adjust the cropper size to scale along with the bounding box
			this.clipper.width = Math.round(this.clipper.width * areaFactor);
			this.clipper.height = Math.round(this.clipper.height * areaFactor);

			// Adjust the coordinates: re-position clipper in relation to the new center to adjust
			// for editor size changes and then multiply by the size factor to adjust for image size changes
			this.clipper.left = this.editorWidth / 2 + (currentOffset.x * areaFactor);
			this.clipper.top = this.editorHeight / 2 + (currentOffset.y * areaFactor);

			// Resize the cropping shade
			this.croppingShade.set({
				width: this.editorWidth,
				height: this.editorHeight,
				left: this.editorWidth / 2,
				top: this.editorHeight / 2
			});

			this._calculateCropperBoundaries();

			this.renderCropper();
		},

		_getImageOccupiedArea: function () {
			var coords = this.imageVerticeCoords;
			return {
				width: Math.max(coords.a.x, coords.b.x, coords.c.x, coords.d.x) - Math.min(coords.a.x, coords.b.x, coords.c.x, coords.d.x),
				height: Math.max(coords.a.y, coords.b.y, coords.c.y, coords.d.y) - Math.min(coords.a.y, coords.b.y, coords.c.y, coords.d.y)
			}
		},

		_handleMouseDown: function (ev) {

			var handle = this._cropperHandleHitTest(ev);
			var move = this._cropperHitTest(ev);
			if (handle || move) {
				this.previousMouseX = ev.pageX;
				this.previousMouseY = ev.pageY;
				if (handle) {
					this.scalingCropper = handle;
				} else if (move) {
					this.draggingCropper = true;
				}
			}
		},

		_handleMouseMove: function (ev) {

			if (this.draggingCropper || this.scalingCropper) {
				if (this.draggingCropper) {
					this._handleCropperDrag(ev);
				} else {
					this._handleCropperScale(ev);
				}

				this.previousMouseX = ev.pageX;
				this.previousMouseY = ev.pageY;
				this._calculateCropperBoundaries();
				this.renderCropper();
			} else {
				this._setMouseCursor(ev);
			}
		},

		_handleMouseUp: function (ev) {
			this.draggingCropper = false;
			this.scalingCropper = false;
		},

		_handleCropperDrag: function (ev) {
			var deltaX = ev.pageX - this.previousMouseX;
			var deltaY = ev.pageY - this.previousMouseY;

			if (deltaX == 0 && deltaY == 0) {
				return;
			}

			var rectangle = {
				left: this.clipper.left - this.clipper.width / 2,
				top: this.clipper.top - this.clipper.height / 2,
				width: this.clipper.width,
				height: this.clipper.height
			};

			var vertices = this._getRectangleVertices(rectangle, deltaX, deltaY);

			if (!this.arePointsInsideRectangle(vertices, this.imageVerticeCoords)) {
				return;
			}

			this.clipper.set({
				left: this.clipper.left + deltaX,
				top: this.clipper.top + deltaY
			});
		},

		_handleCropperScale: function (ev) {
			var deltaX = ev.pageX - this.previousMouseX;
			var deltaY = ev.pageY - this.previousMouseY;

			if (deltaX == 0 && deltaY == 0) {
				return;
			}

			// Lock the aspect ratio
			if (this.lockAspectRatio &&
				(this.scalingCropper == 'tl' || this.scalingCropper == 'tr' ||
				this.scalingCropper == 'bl' || this.scalingCropper == 'br')
			) {
				if (Math.abs(deltaX) > Math.abs(deltaY)) {
					var ratio = this.clipper.width / this.clipper.height;
					deltaY = deltaX / ratio;
					deltaY *= (this.scalingCropper == 'tr' || this.scalingCropper == 'bl') ? -1 : 1;
				} else {
					var ratio = this.clipper.width / this.clipper.height;
					deltaX = deltaY * ratio;
					deltaX *= (this.scalingCropper == 'tr' || this.scalingCropper == 'bl') ? -1 : 1;
				}
			}

			var rectangle = {
				left: this.clipper.left - this.clipper.width / 2,
				top: this.clipper.top - this.clipper.height / 2,
				width: this.clipper.width,
				height: this.clipper.height
			};

			switch (this.scalingCropper) {
				case 't':
					rectangle.top += deltaY;
					rectangle.height -= deltaY;
					break;
				case 'b':
					rectangle.height += deltaY;
					break;
				case 'l':
					rectangle.left += deltaX;
					rectangle.width -= deltaX;
					break;
				case 'r':
					rectangle.width += deltaX;
					break;
				case 'tl':
					rectangle.top += deltaY;
					rectangle.height -= deltaY;
					rectangle.left += deltaX;
					rectangle.width -= deltaX;
					break;
				case 'tr':
					rectangle.top += deltaY;
					rectangle.height -= deltaY;
					rectangle.width += deltaX;
					break;
				case 'bl':
					rectangle.height += deltaY;
					rectangle.left += deltaX;
					rectangle.width -= deltaX;
					break;
				case 'br':
					rectangle.height += deltaY;
					rectangle.width += deltaX;
					break;
			}

			if (rectangle.height < 30 || rectangle.width < 30) {
				return;
			}

			var vertices = this._getRectangleVertices(rectangle);

			if (!this.arePointsInsideRectangle(vertices, this.imageVerticeCoords)) {
				return;
			}

			this.clipper.set({
				top: rectangle.top + rectangle.height / 2,
				left: rectangle.left + rectangle.width / 2,
				width: rectangle.width,
				height: rectangle.height
			});

			this._calculateCropperBoundaries();
		},

		_setMouseCursor: function (ev) {
			var cursor = 'default';
			var handle = this._cropperHandleHitTest(ev);

			if (handle) {
				if (handle == 't' || handle == 'b') {
					cursor = 'ns-resize';
				} else if (handle == 'l' || handle == 'r') {
					cursor = 'ew-resize';
				} else if (handle == 'tl' || handle == 'br') {
					cursor = 'nwse-resize';
				} else if (handle == 'bl' || handle == 'tr') {
					cursor = 'nesw-resize';
				}
			} else if (this._cropperHitTest(ev)) {
				cursor = 'move';
			}

			$('.body').css('cursor', cursor);
		},

		_cropperHandleHitTest: function (ev) {
			var parentOffset = this.$croppingCanvas.offset();
			var mouseX = ev.pageX - parentOffset.left;
			var mouseY = ev.pageY - parentOffset.top;

			var top = false;
			var left = false;
			var right = false;
			var bottom = false;

			// Compensate for center origin coordinate-wise
			var lb = this.clipper.left - this.clipper.width / 2;
			var rb = lb + this.clipper.width;
			var tb = this.clipper.top - this.clipper.height / 2;
			var bb = tb + this.clipper.height;

			// Left side
			if (mouseX < lb + 10 && mouseX > lb - 3) {
				if (mouseY < tb + 10 && mouseY > tb - 3) {
					return 'tl';
				} else if (mouseY < bb + 3 && mouseY > bb - 10) {
					return 'bl';
				}
			}
			// Right side
			if (mouseX > rb - 13 && mouseX < rb + 3) {
				if (mouseY < tb + 10 && mouseY > tb - 3) {
					return 'tr';
				} else if (mouseY < bb + 2 && mouseY > bb - 10) {
					return 'br';
				}
			}
			if (mouseX < lb + 3 && mouseX > lb - 3 && mouseY < bb - 10 && mouseY > tb + 10) {
				return 'l';
			}
			if (mouseX < rb + 1 && mouseX > rb - 5 && mouseY < bb - 10 && mouseY > tb + 10) {
				return 'r';
			}

			// Top
			if (mouseY < tb + 4 && mouseY > tb - 2 && mouseX > lb + 10 && mouseX < rb - 10) {
				return 't';
			}
			// Bottom
			if (mouseY < bb + 2 && mouseY > bb - 4 && mouseX > lb + 10 && mouseX < rb - 10) {
				return 'b';
			}

			return false;
		},

		_cropperHitTest: function (ev) {
			var parentOffset = this.$croppingCanvas.offset();
			var mouseX = ev.pageX - parentOffset.left;
			var mouseY = ev.pageY - parentOffset.top;

			// Compensate for center origin coordinate-wise
			var lb = this.clipper.left - this.clipper.width / 2;
			var rb = lb + this.clipper.width;
			var tb = this.clipper.top - this.clipper.height / 2;
			var bb = tb + this.clipper.height;

			if (!(mouseX >= lb && mouseX <= rb && mouseY >= tb && mouseY <= bb)) {
				return false;
			}

			return true;
		},

		_getRectangleVertices: function (rectangle, offsetX, offsetY) {
			if (typeof offsetX == typeof undefined) {
				offsetX = 0;
			}
			if (typeof offsetY == typeof undefined) {
				offsetY = 0;
			}

			var topLeft = {
				x: rectangle.left + offsetX,
				y: rectangle.top + offsetY
			};
			var topRight = {x: topLeft.x + rectangle.width, y: topLeft.y};
			var bottomRight = {x: topRight.x, y: topRight.y + rectangle.height};
			var bottomLeft = {x: topLeft.x, y: bottomRight.y};

			return [topLeft, topRight, bottomRight, bottomLeft];
		},

		_setImageVerticeCoordinates: function () {

			var angleInRadians = -1 * this.imageStraightenAngle * (Math.PI / 180);

			var imageDimensions = this.getScaledImageDimensions();
			var ratio = this.getZoomToFitRatio(imageDimensions);

			// Get the dimensions of the scaled image
			var scaledHeight = imageDimensions.height * ratio;
			var scaledWidth = imageDimensions.width * ratio;

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

			// Finally, calculate the image vertice coordinates
			this.imageVerticeCoords = {
				a: {
					x: horizontalOffset + rightHorizontalSegment,
					y: verticalOffset
				},
				b: {
					x: this.editorWidth - horizontalOffset,
					y: verticalOffset + topVerticalSegment
				},
				c: {
					x: horizontalOffset + leftHorizontalSegment,
					y: this.editorHeight - verticalOffset
				},
				d: {
					x: horizontalOffset,
					y: verticalOffset + bottomVerticalSegment
				}
			};
		},

		_destroyCropper: function () {
			this.clipper = null;
			this.cropperHandles = null;
			this.croppingRectangle = null;
			this.croppingShade = null;
			this.croppingCanvas = null;

			$('#cropping-canvas').siblings('.upper-canvas').remove();
			$('#cropping-canvas').parent('.canvas-container').before($('#cropping-canvas'));
			$('.canvas-container').remove();

		},

		_debug: function (fabricObj) {
			this.canvas.remove(this.debugger);
			this.debugger = fabricObj;
			this.canvas.add(this.debugger);
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

			// Pre-calculate the vectors and scalar products for two rectangle edges
			var ab = this._getVector(rectangle.a, rectangle.b);
			var bc = this._getVector(rectangle.b, rectangle.c);
			var scalarAbAb = this._getScalarProduct(ab, ab);
			var scalarBcBc = this._getScalarProduct(bc, bc);

			for (var i = 0; i < points.length; i++) {
				var point = points[i];

				// Calculate the vectors for two rectangle sides and for
				// the vector from vertices a and b to the point P
				var ap = this._getVector(rectangle.a, point);
				var bp = this._getVector(rectangle.b, point);

				// Calculate scalar or dot products for some vector combinations
				var scalarAbAp = this._getScalarProduct(ab, ap);
				var scalarBcBp = this._getScalarProduct(bc, bp);

				var projectsOnAB = 0 <= scalarAbAp && scalarAbAp <= scalarAbAb;
				var projectsOnBC = 0 <= scalarBcBp && scalarBcBp <= scalarBcBc;

				if (!(projectsOnAB && projectsOnBC)) {
					return false;
				}
			}

			return true;
		},

		/**
		 * Returns an object representing the vector between points a and b.
		 *
		 * @param {{x: number, y: number}} a
		 * @param {{x: number, y: number}} b
		 *
		 * @return {{x: number, y: number}}
		 */
		_getVector: function (a, b) {
			return {x: b.x - a.x, y: b.y - a.y};
		},

		/**
		 * Returns the scalar product of two vectors
		 *
		 * @param {{x: number, y: number}} a
		 * @param {{x: number, y: number}} b
		 *
		 * @return {number}
		 */
		_getScalarProduct: function (a, b) {
			return a.x * b.x + a.y * b.y;
		},

		/**
		 * Returns the magnitude of a vector.
		 *
		 * @param {{x: number, y: number}} vector
		 *
		 * @return {number}
		 */
		_getMagnitude: function (vector) {
			return Math.sqrt(vector.x * vector.x + vector.y * vector.y);
		},

		_getImageBoundingBox: function (dimensions) {

			var box = {};

			// Convert the angle to radians
			var angleInRadians = Math.abs(this.imageStraightenAngle) * (Math.PI / 180);
			var scaledHeight;
			var scaledWidth;

			var proportion = dimensions.height / dimensions.width;
			box.height = dimensions.width * (Math.sin(angleInRadians) + Math.cos(angleInRadians) * proportion);
			box.width = dimensions.width * (Math.cos(angleInRadians) + Math.sin(angleInRadians) * proportion);

			if (this.hasOrientationChanged()) {
				var temp = box.width;
				box.width = box.height;
				box.height = temp;
			}

			return box;
		}
	},
	{
		defaults: {
			animationDuration: 100,
			allowSavingAsNew: true,
			onSave: $.noop,
		}
	}
);