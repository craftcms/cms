/**
 * Asset image editor class
 */

Craft.AssetImageEditor = Garnish.Modal.extend(
	{
		// References
		canvas: null,
		image: null,
		viewport: null,
		$editorContainer: null,
		$straighten: null,
		url: "",

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

		init: function (url, settings) {
			this.setSettings(settings, Craft.AssetImageEditor.defaults);

			// Build the modal
			var $container = $('<div class="modal asset-editor"></div>').appendTo(Garnish.$bod),
				$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
				$footer = $('<div class="footer"/>').appendTo($container);

			this.base($container, this.settings);

			this.$buttons = $('<div class="buttons rightalign"/>').appendTo($footer);
			this.$cancelBtn = $('<div class="btn">' + Craft.t('app', 'Cancel') + '</div>').appendTo(this.$buttons);
			this.$replaceBtn = $('<div class="btn disabled submit">' + Craft.t('app', 'Replace Asset') + '</div>').appendTo(this.$buttons);
			this.$saveBtn = $('<div class="btn disabled submit">' + Craft.t('app', 'Save as New Asset') + '</div>').appendTo(this.$buttons);
			this.$ExportBtn = $('<div class="btn submit export">' + Craft.t('app', 'Export') + '</div>').appendTo(this.$buttons);

			this.$body = $body;

			this.addListener(this.$cancelBtn, 'activate', $.proxy(this, 'hide'));
			this.removeListener(this.$shade, 'click');

			this.url = url;

			Craft.postActionRequest('assets/image-editor', $.proxy(this, 'loadEditor'));
		},

		loadEditor: function (data) {
			this.$body.html(data.html);

			this.canvas = new fabric.StaticCanvas('image-manipulator', {backgroundColor: this.backgroundColor});
			this.canvas.enableRetinaScaling = true;

			this.$editorContainer = $('#image-holder');
			this.$straighten = $('.rotate.straighten');

			this.editorHeight = this.$editorContainer.innerHeight();
			this.editorWidth = this.$editorContainer.innerWidth();

			// Load the image from URL
			fabric.Image.fromURL(this.url, $.proxy(function (imageObject) {
				this.image = imageObject;

				// Store for later reference
				this.originalHeight = this.image.getHeight();
				this.originalWidth = this.image.getWidth();

				// Scale the image and center it on the canvas
				this._scaleAndCenterImage();

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

			this._setImageZoomRatio();
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
			$('.rotate.counter-clockwise').on('click', $.proxy(function () {
				this.rotateViewport(-90);
			}, this));

			$('.rotate.clockwise').on('click', $.proxy(function () {
				this.rotateViewport(90);
			}, this));

			$('.rotate.reset').on('click', $.proxy(function (ev) {
				this.resetStraighten(ev);
			}, this));

			$('.rotate.straighten').on('input change', $.proxy(function (ev) {
				this.straighten(ev);
			}, this));
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

						this._setImageZoomRatio();
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

				this.imageAngle = this.image.getAngle();
				this._setImageZoomRatio();

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
		 * Set image zoom ratio depending on the straighten angle
		 */
		_setImageZoomRatio: function () {
			this.imageStraightenAngle = parseFloat(this.$straighten.val());

			// If we're rotated by 90 or 270 degrees, flip the width with height
			if (this.viewportRotation % 180 == 0) {
				viewportWidth = this.viewportWidth;
				viewportHeight = this.viewportHeight;
				imageHeight = this.image.height;
				imageWidth = this.image.width;
			} else {
				viewportWidth = this.viewportHeight;
				viewportHeight = this.viewportWidth;
				imageHeight = this.image.width;
				imageWidth = this.image.height;
			}

			// Get the size of the largest proportional rectangle
			var proportionalRectangle = this._calculateLargestProportionalRectangle(this.imageStraightenAngle, imageWidth, imageHeight);

			// Scale it
			var zoomRatio = Math.max(viewportWidth / proportionalRectangle.w, viewportHeight / proportionalRectangle.h);

			this.image.scale(zoomRatio);
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
				new fabric.Line([0, 0, imageWidth, 0], strokeOptions),
				new fabric.Line([0, imageHeight, 0, 0], strokeOptions),
				new fabric.Line([imageWidth, 0, imageWidth, imageHeight], strokeOptions),
				new fabric.Line([imageWidth, imageHeight, 0, imageHeight], strokeOptions)
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

		/**
		 * Calculate the largest possible rectangle within a rotated rectangle.
		 * Adapted from http://stackoverflow.com/a/18402507/2040791
		 */
		_calculateLargestProportionalRectangle: function (angle, origWidth, origHeight) {

			var w0, h0;

			if (origWidth <= origHeight) {
				w0 = origWidth;
				h0 = origHeight;
			}
			else {
				w0 = origHeight;
				h0 = origWidth;
			}

			// Angle normalization in range [-PI..PI)
			if (angle > 180) {
				angle = 180 - angle;
			}
			if (angle < 0) {
				angle = angle + 180;
			}
			var ang = angle * (Math.PI / 180);

			if (ang > Math.PI / 2) {
				ang = Math.PI - ang;
			}

			var c = w0 / (h0 * Math.sin(ang) + w0 * Math.cos(ang)),
				w, h;

			if (origWidth <= origHeight) {
				w = w0 * c;
				h = h0 * c;
			}
			else {
				w = h0 * c;
				h = w0 * c;
			}

			return {w: w, h: h};
		},

		onFadeOut: function () {
			this.destroy();
		}
	},
	{
		defaults: {
			gridLineThickness: 1,
			gridLineColor: '#000000',
			gridLinePrecision: 2,
			animationDuration: 150,

			onSave: $.noop,
		}
	}
);
