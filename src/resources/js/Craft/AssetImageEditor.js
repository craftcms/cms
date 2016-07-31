/**
 * Asset image editor class
 */

// TODO: Sometimes the rotation messes up the zoom
// TODO: Rotating by 0.1 degree kills stuff for non-square images?

Craft.AssetImageEditor = Garnish.Modal.extend(
	{
		assetId: 0,

		imageUrl: "",

		// Original parameters for reference
		originalImageHeight: 0,
		originalImageWidth: 0,
		aspectRatio: 0,

		// The currently resized image dimensions
		imageHeight: 0,
		imageWidth: 0,

		canvas: null,
		canvasContext: null,
		canvasImageHeight: 0,
		canvasImageWidth: 0,

		// Image and frame rotation degrees
		rotation: 0,
		frameRotation: 0,

		// TODO: should this be limited to 50 (or some other arbitrary number)?
		// Operation stack
		doneOperations: [],
		undoneOperations: [],

		// zoom ratio for the image
		zoomRatio: 1,

		// Used when dragging the slider
		previousSliderValue: 0,
		// Used to store values when releasing the slider
		previousSavedSliderValue: 0,

		paddingSize: 24,
		imageLoaded: false,

		animationInProgress: false,
		animationFrames: 20,
		drawGridLines: false,

		$img: null,

		init: function(assetId)
		{
			this.setSettings(Craft.AssetImageEditor.defaults);

			this.assetId = assetId;
			this.imageHeight = 0;
			this.imageWidth = 0;
			this.originalImageHeight = 0;
			this.originalImageWidth = 0;
			this.imageUrl = "";
			this.aspectRatio = 0;
			this.canvasImageHeight = 0;
			this.canvasImageWidth = 0;
			this.imageLoaded = false;
			this.canvas = null;
			this.$img = null;
			this.rotation = 0;
			this.animationInProgress = false;
			this.doneOperations = [];
			this.undoneOperations = [];
			this.previousSliderValue = 0;
			this.previousSavedSliderValue = 0;


			// Build the modal
			var $container = $('<div class="modal asset-editor"></div>').appendTo(Garnish.$bod),
				$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
				$footer = $('<div class="footer"/>').appendTo($container);

			this.base($container, this.settings);

			this.$buttons = $('<div class="buttons rightalign"/>').appendTo($footer);
			this.$cancelBtn = $('<div class="btn">'+Craft.t('app', 'Cancel')+'</div>').appendTo(this.$buttons);
			this.$selectBtn = $('<div class="btn disabled submit">'+Craft.t('app', 'Replace Image')+'</div>').appendTo(this.$buttons);
			this.$selectBtn = $('<div class="btn disabled submit">'+Craft.t('app', 'Save as New Image')+'</div>').appendTo(this.$buttons);

			this.$body = $body;

			this.addListener(this.$cancelBtn, 'activate', 'cancel');
			this.removeListener(this.$shade, 'click');

			Craft.postActionRequest('assets/image-editor', {assetId: this.assetId}, $.proxy(this, 'loadEditor'));

		},

		loadEditor: function (data)
		{
			this.$body.html(data.html);
			this.canvas = this.$body.find('canvas')[0];
			this.canvasContext = this.canvas.getContext("2d");

			this.imageHeight = this.originalImageHeight = data.imageData.height;
			this.imageWidth = this.originalImageWidth = data.imageData.width;
			this.imageUrl = data.imageData.url;
			this.aspectRatio = this.imageHeight / this.imageWidth;
			this.initImage($.proxy(this, 'updateSizeAndPosition'));
			this.addListeners();
		},

		updateSizeAndPosition: function()
		{
			if (!this.imageLoaded)
			{
				this.base();
			}
			else
			{
				this.redrawEditor();
			}
		},

		cancel: function ()
		{
			this.hide();
			this.destroy();
		},

		hide: function ()
		{
			this.removeListeners();
			this.base();
		},

		initImage: function (callback)
		{
			this.$img = $('<img />');

			this.$img.attr('src', this.imageUrl).on('load', $.proxy(function ()
			{
				this.imageLoaded = true;
				callback();
			}, this));
		},

		redrawEditor: function ()
		{
			var desiredHeight = 600,
				desiredWidth = 600;

			if (this.imageLoaded)
			{
				desiredHeight = this.originalImageHeight;
				desiredWidth = this.originalImageWidth;
			}

			var availableHeight = Garnish.$win.height() - (4 * this.paddingSize) - this.$container.find('.footer').outerHeight(),
				availableWidth = Garnish.$win.width() - (5 * this.paddingSize) - this.$container.find('.image-tools').outerWidth();

			// Make the image area square, so we can rotate it comfortably.
			var imageHolderSize = Math.max(parseInt(this.$container.find('.image-tools').css('min-height'), 10), Math.min(availableHeight, availableWidth));

			// Set it all up!
			var containerWidth = imageHolderSize + this.$container.find('.image-tools').outerWidth() + (3 * this.paddingSize),
				containerHeight = imageHolderSize + this.$container.find('.footer').outerHeight() + (2 * this.paddingSize);

			this.$container.width(containerWidth).height(containerHeight)
				.find('.image-holder').width(imageHolderSize).height(imageHolderSize);

			this.canvasImageHeight = this.canvasImageWidth = imageHolderSize;

			this.$container.find('.image-tools').height(imageHolderSize + (2 * this.paddingSize));

			// Re-center.
			this.$container.css('left', Math.round((Garnish.$win.width() - containerWidth) / 2));
			this.$container.css('top', Math.round((Garnish.$win.height() - containerHeight) / 2));

			if (this.imageLoaded)
			{
				this.renderImage(true);
			}
		},

		renderImage: function (drawFrame, recalculateZoomRatio)
		{

			this.canvas.height = this.canvasImageHeight;
			this.canvas.width = this.canvasImageWidth;

			var yRatio = this.originalImageHeight / this.canvasImageHeight;
			var xRatio = this.originalImageWidth / this.canvasImageWidth;

			// Figure out the size
			if (xRatio > 1 || yRatio > 1)
			{
				if (xRatio > yRatio)
				{
					this.imageWidth = this.canvasImageWidth;
					this.imageHeight = this.imageWidth * this.aspectRatio;
				}
				else
				{
					this.imageHeight = this.canvasImageHeight;
					this.imageWidth = this.imageHeight / this.aspectRatio;
				}
			}

			// Clear canvas
			this.canvasContext.clearRect(0, 0, this.canvasImageWidth, this.canvasImageHeight);

			// Calculate the zoom ratio unless we're in the middle of an animation
			// or we're forced to (when resetting the straighten slider)
			if (!this.animationInProgress || recalculateZoomRatio)
			{
				// For non-straightened images we know the zoom is going to be 1
				if (this.rotation % 90 == 0)
				{
					this.zoomRatio = 1;
				}
				else
				{
					var rectangle = this.calculateLargestProportionalRectangle(this.rotation, this.imageWidth, this.imageHeight);
					this.zoomRatio = Math.max(this.imageWidth / rectangle.w, this.imageHeight / rectangle.h);
				}

			}

			// Remember the current context
			this.canvasContext.save();

			// Move (0,0) to center of canvas and rotate around it
			this.canvasContext.translate(Math.round(this.canvasImageWidth / 2), Math.round(this.canvasImageHeight / 2));
			this.canvasContext.rotate(this.rotation * Math.PI / 180);

			var adjustedHeight = this.imageHeight * this.zoomRatio,
				adjustedWidth = this.imageWidth * this.zoomRatio;

			// Draw the rotated image
			this.canvasContext.drawImage(this.$img[0], 0, 0, this.originalImageWidth, this.originalImageHeight,
				-(adjustedWidth / 2), -(adjustedHeight / 2), adjustedWidth, adjustedHeight);

			this.canvasContext.restore();

			if (drawFrame)
			{
				this.drawFrame();
			}

			if (this.drawGridLines)
			{
				this.drawGrid();
			}

			this.clipImage();
		},

		addListeners: function ()
		{
			this.$container.find('a.rotate.clockwise').on('click', $.proxy(function ()
			{
				if (!this.animationInProgress)
				{
					this.addOperation({imageRotation: 90});
					this.rotate(90);
				}
			}, this));
			this.$container.find('a.rotate.counter-clockwise').on('click', $.proxy(function ()
			{
				if (!this.animationInProgress)
				{
					this.addOperation({imageRotation: -90});
					this.rotate(-90);
				}
			}, this));

			var straighten = this.$container.find('.straighten')[0];

			straighten.oninput = $.proxy(this, 'straightenImage');
			straighten.onchange = $.proxy(function (event)
			{
				this.straightenImage(event, true);
			}, this);


			straighten.onmousedown = $.proxy(function ()
			{
				this.showGridLines();
				this.renderImage(true);
			}, this);

			straighten.onmouseup = $.proxy(function ()
			{
				this.hideGridLines();
				this.renderImage(true);
			}, this);

			$('.rotate.reset').on('click', $.proxy(function ()
			{
				this.$container.find('.straighten').val(0);
				this.setStraightenOffset(0, false, true, true);
			}, this));

			// TODO: remove magic numbers and move them to Garnish Constants
			this.addListener(Garnish.$doc, 'keydown', $.proxy(function (ev)
			{
				// CMD/CTRL + Y, CMD/CTRL + SHIFT + Z
				if ((ev.metaKey || ev.ctrlKey) && (ev.keyCode == 89 || (ev.keyCode == 90 && ev.shiftKey)))
				{
					this.redo();
					return false;
				}
			}, this));

			this.addListener(Garnish.$doc, 'keydown', $.proxy(function (ev)
			{
				if ((ev.metaKey || ev.ctrlKey) && !ev.shiftKey && ev.keyCode == 90)
				{
					this.undo();
					return false;
				}
			}, this));

		},

		removeListeners: function ()
		{
			this.removeListener(Garnish.$doc, 'keydown');
		},

		addOperation: function (operation)
		{
			this.doneOperations.push(operation);

			// As soon as we do something, the stack of undone operations is gone.
			this.undoneOperations = [];
		},

		undo: function ()
		{
			if (this.animationInProgress)
			{
				return;
			}

			if (this.doneOperations.length > 0)
			{
				var operation = this.doneOperations.pop();
				this.performOperation(operation, true);
				this.undoneOperations.push(operation);
			}
		},

		redo: function ()
		{
			if (this.animationInProgress)
			{
				return;
			}
			if (this.undoneOperations.length > 0)
			{
				var operation = this.undoneOperations.pop();
				this.performOperation(operation, false);
				this.doneOperations.push(operation);
			}

		},

		// TODO: This is a horrible name for this function
		performOperation: function (operation, reverse)
		{
			var modifier = reverse ? -1 : 1;

			if (typeof operation.imageRotation != "undefined")
			{
				this.rotation += modifier * operation.imageRotation;
				this.frameRotation += modifier * operation.imageRotation;
			}

			if (typeof operation.straightenOffset != "undefined")
			{
				var value = modifier * operation.straightenOffset;
				this.rotation += value;

				var $straighten = this.$container.find('.straighten');
				var newValue = parseFloat($straighten.val()) + value;

				// TODO: this is the part where we refactor the code a bit to be less confusing.
				this.previousSavedSliderValue = newValue;
				this.previousSliderValue = newValue;
				$straighten.val(newValue);
			}

			this.renderImage(true);
		},

		rotate: function (degrees, animateInstantly, preventFrameRotation)
		{
			var targetDegrees = this.rotation + degrees;

			if (!animateInstantly)
			{
				this.animationInProgress = true;
				var degreesPerFrame = Math.round(degrees / this.animationFrames * 10) / 10;

				var frameCount = 0;

				var animateCanvas = function ()
				{
					frameCount++;
					this.rotation += degreesPerFrame;

					if (!preventFrameRotation)
					{
						this.frameRotation += degreesPerFrame;
					}

					this.renderImage(true, preventFrameRotation);
					if (frameCount < this.animationFrames)
					{
						setTimeout($.proxy(animateCanvas, this), 1);
					}
					else
					{
						// Clean up the fractions and whatnot
						this.rotation = targetDegrees;
						this.cleanUpRotationDegrees();

						this.renderImage(true, preventFrameRotation);
						this.animationInProgress = false;
					}
				};

				animateCanvas.call(this);
			}
			else
			{
				this.rotation = targetDegrees;
				this.cleanUpRotationDegrees();
				this.renderImage(true);
			}
		},

		cleanUpRotationDegrees: function ()
		{
			this.rotation = this._cleanUpDegrees(this.rotation);
			this.frameRotation = this._cleanUpDegrees(this.frameRotation);
		},

		/**
		 * Ensure a degree value is within [0..360] and has at most one decimal part.
		 */
		_cleanUpDegrees: function (degrees)
		{
			if (degrees > 360)
			{
				degrees -= 360;
			}
			else if (degrees < 0)
			{
				degrees += 360;
			}

			degrees = Math.round(degrees * 10) / 10;

			return degrees;
		},

		// Trigger operation - whether we're stopping to drag the slider and should trigger a state save
		straightenImage: function (event, triggerOperation)
		{
			if (this.animationInProgress)
			{
				return;
			}
			this.setStraightenOffset($(event.currentTarget).val(), true, false, triggerOperation);
		},

		setStraightenOffset: function (degrees, animateInstantly, preventFrameRotation, triggerOperation)
		{
			var delta = degrees - this.previousSliderValue;

			this.previousSliderValue = degrees;

			if (triggerOperation)
			{
				this.addOperation({straightenOffset: degrees - this.previousSavedSliderValue});
				this.previousSavedSliderValue = degrees;
			}

			this.rotate(delta, animateInstantly, preventFrameRotation);

		},

		showGridLines: function ()
		{
			this.drawGridLines = true;
		},

		hideGridLines: function ()
		{
			this.drawGridLines = false;
		},

		/**
		 * Draw the frame around the image.
		 */
		drawFrame: function ()
		{
			// Remember the current context
			this.canvasContext.save();

			this.prepareImageFrameRectangle(this.canvasContext);

			this.canvasContext.lineWidth = 1;
			this.canvasContext.strokeStyle = 'rgba(0,0,0,0.6)';
			this.canvasContext.stroke();

			// Restore that context
			this.canvasContext.restore();

		},

		prepareImageFrameRectangle: function (canvasContext)
		{
			canvasContext.translate(Math.round(this.canvasImageWidth / 2), Math.round(this.canvasImageHeight / 2));
			canvasContext.rotate(this.frameRotation * Math.PI / 180);
			canvasContext.rect(-(this.imageWidth / 2) + 1, -(this.imageHeight / 2) + 1, this.imageWidth - 2, this.imageHeight - 2);
		},

		/**
		 * Draw the grid with guides for straightening.
		 */
		drawGrid: function ()
		{
			this.canvasContext.lineWidth = 1;

			this.canvasContext.save();

			// Rotate along the frame
			this.canvasContext.translate(Math.round(this.canvasImageWidth / 2), Math.round(this.canvasImageHeight / 2));
			this.canvasContext.rotate(this.frameRotation * Math.PI / 180);

			var xStep = (this.imageWidth - 2) / 8;
			var yStep = (this.imageHeight - 2) / 8;

			for (var step = 0; step < 9; step++)
			{
				switch (step)
				{
					case 0:
					case 8:
					case 4:
					{
						this.canvasContext.strokeStyle = 'rgba(0,0,0,0.6)';
						break;
					}
					case 2:
					case 6:
					{
						this.canvasContext.strokeStyle = 'rgba(0,0,0,0.3)';
						break;
					}
					default:
					{
						this.canvasContext.strokeStyle = 'rgba(0,0,0,0.15)';
						break;
					}

				}
				this.canvasContext.beginPath();
				this.canvasContext.moveTo(-(this.imageWidth / 2) + xStep * step + 1, -(this.imageHeight / 2));
				this.canvasContext.lineTo(-(this.imageWidth / 2) + xStep * step + 1, (this.imageHeight / 2));
				this.canvasContext.closePath();
				this.canvasContext.stroke();
			}

			for (step = 0; step < 9; step++)
			{
				switch (step)
				{
					case 0:
					case 8:
					case 4:
					{
						this.canvasContext.strokeStyle = 'rgba(0,0,0,0.6)';
						break;
					}
					case 2:
					case 6:
					{
						this.canvasContext.strokeStyle = 'rgba(0,0,0,0.3)';
						break;
					}
					default:
					{
						this.canvasContext.strokeStyle = 'rgba(0,0,0,0.15)';
						break;
					}

				}
				this.canvasContext.beginPath();
				this.canvasContext.moveTo(-(this.imageWidth / 2), -(this.imageHeight / 2) + yStep * step + 1);
				this.canvasContext.lineTo((this.imageWidth / 2), -(this.imageHeight / 2) + yStep * step + 1);
				this.canvasContext.closePath();
				this.canvasContext.stroke();
			}

			this.canvasContext.restore();
		},

		/**
		 * Add a new clipping canvas on top of the existing canvas.
		 */
		clipImage: function ()
		{
			var mask = Garnish.$doc[0].createElement('canvas');
			mask.width = this.canvas.width;
			mask.height = this.canvas.height;

			var context = mask.getContext('2d');
			context.fillStyle = 'white';
			context.fillRect(0, 0, mask.width, mask.height);
			context.globalCompositeOperation = 'xor';
			this.prepareImageFrameRectangle(context);
			context.fill();

			this.canvasContext.drawImage(mask, 0, 0);

		},

		/**
		 * Calculate the largest possible rectangle within a rotated rectangle.
		 * Adapted from http://stackoverflow.com/a/18402507/2040791
		 */
		calculateLargestProportionalRectangle: function(angle, origWidth, origHeight)
		{

			var w0, h0;

			if (origWidth <= origHeight)
			{
				w0 = origWidth;
				h0 = origHeight;
			}
			else
			{
				w0 = origHeight;
				h0 = origWidth;
			}

			// Angle normalization in range [-PI..PI)
			if (angle > 180)
			{
				angle = 180 - angle;
			}
			if (angle < 0)
			{
				angle = angle + 180;
			}
			var ang = angle * (Math.PI / 180);

			if (ang > Math.PI / 2)
			{
				ang = Math.PI - ang;
			}

			var c = w0 / (h0 * Math.sin(ang) + w0 * Math.cos(ang)),
				w, h;

			if (origWidth <= origHeight)
			{
				w = w0 * c;
				h = h0 * c;
			}
			else
			{
				w = h0 * c;
				h = w0 * c;
			}

			return { w: w, h: h};
		}

	},
	{
		defaults: {
			resizable: false,
			shadeClass: "assetEditor"
		}
	}
);
