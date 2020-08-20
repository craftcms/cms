/** global: Craft */
/** global: Garnish */

/**
 * Asset image editor class
 */

Craft.AssetImageEditor = Garnish.Modal.extend(
    {
        // jQuery objects
        $body: null,
        $footer: null,
        $imageTools: null,
        $buttons: null,
        $cancelBtn: null,
        $replaceBtn: null,
        $saveBtn: null,
        $editorContainer: null,
        $straighten: null,
        $croppingCanvas: null,
        $spinnerCanvas: null,

        // FabricJS objects
        canvas: null,
        image: null,
        viewport: null,
        focalPoint: null,
        grid: null,
        croppingCanvas: null,
        clipper: null,
        croppingRectangle: null,
        cropperHandles: null,
        cropperGrid: null,
        croppingShade: null,
        croppingAreaText: null,

        // Image state attributes
        imageStraightenAngle: 0,
        viewportRotation: 0,
        originalWidth: 0,
        originalHeight: 0,
        imageVerticeCoords: null,
        zoomRatio: 1,

        // Editor state attributes
        animationInProgress: false,
        currentView: '',
        assetId: null,
        cacheBust: null,
        draggingCropper: false,
        scalingCropper: false,
        draggingFocal: false,
        previousMouseX: 0,
        previousMouseY: 0,
        shiftKeyHeld: false,
        editorHeight: 0,
        editorWidth: 0,
        cropperState: false,
        scaleFactor: 1,
        flipData: {},
        focalPointState: false,
        spinnerInterval: null,
        maxImageSize: null,
        lastLoadedDimensions: null,
        imageIsLoading: false,
        mouseMoveEvent: null,
        croppingConstraint: false,
        constraintOrientation: 'landscape',
        showingCustomConstraint: false,

        // Rendering proxy functions
        renderImage: null,
        renderCropper: null,

        init: function(assetId, settings) {
            this.cacheBust = Date.now();

            this.setSettings(settings, Craft.AssetImageEditor.defaults);

            this.assetId = assetId;
            this.flipData = {x: 0, y: 0};

            // Build the modal
            this.$container = $('<form class="modal fitted imageeditor"></form>').appendTo(Garnish.$bod);
            this.$body = $('<div class="body"></div>').appendTo(this.$container);
            this.$footer = $('<div class="footer"/>').appendTo(this.$container);

            this.base(this.$container, this.settings);

            this.$buttons = $('<div class="buttons right"/>').appendTo(this.$footer);
            this.$cancelBtn = $('<button/>', {
                type: 'button',
                class: 'btn cancel',
                text: Craft.t('app', 'Cancel'),
            }).appendTo(this.$buttons);
            this.$replaceBtn = $('<button/>', {
                type: 'button',
                class: 'btn submit save replace',
                text: Craft.t('app', 'Save'),
            }).appendTo(this.$buttons);

            if (this.settings.allowSavingAsNew) {
                this.$saveBtn = $('<button/>', {
                    type: 'button',
                    class: 'btn submit save copy',
                    text: Craft.t('app', 'Save as a new asset'),
                }).appendTo(this.$buttons);
                this.addListener(this.$saveBtn, 'activate', this.saveImage);
            }

            this.addListener(this.$replaceBtn, 'activate', this.saveImage);
            this.addListener(this.$cancelBtn, 'activate', this.hide);
            this.removeListener(this.$shade, 'click');

            this.maxImageSize = this.getMaxImageSize();

            Craft.postActionRequest('assets/image-editor', {assetId: assetId}, $.proxy(this, 'loadEditor'));
        },

        /**
         * Get the max image size that is viewable in the editor currently
         */
        getMaxImageSize: function() {
            var browserViewportWidth = Garnish.$doc.get(0).documentElement.clientWidth;
            var browserViewportHeight = Garnish.$doc.get(0).documentElement.clientHeight;

            return  Math.max(browserViewportHeight, browserViewportWidth) * (window.devicePixelRatio > 1 ? 2 : 1);
        },

        /**
         * Load the editor markup and start loading components and the image.
         *
         * @param data
         */
        loadEditor: function(data) {
            if (!data.html) {
                alert(Craft.t('app', 'Could not load the image editor.'));
            }

            this.$body.html(data.html);
            this.$tabs = $('.tabs li', this.$body);
            this.$viewsContainer = $('.views', this.$body);
            this.$views = $('> div', this.$viewsContainer);
            this.$imageTools = $('.image-container .image-tools', this.$body);
            this.$editorContainer = $('.image-container .image', this.$body);
            this.editorHeight = this.$editorContainer.innerHeight();
            this.editorWidth = this.$editorContainer.innerWidth();

            this._showSpinner();

            this.updateSizeAndPosition();

            // Load the canvas on which we'll host our image and set up the proxy render function
            this.canvas = new fabric.StaticCanvas('image-canvas');

            // Set up the cropping canvas jquery element for tracking all the nice events
            this.$croppingCanvas = $('#cropping-canvas', this.$editorContainer);
            this.$croppingCanvas.width(this.editorWidth);
            this.$croppingCanvas.height(this.editorHeight);

            this.canvas.enableRetinaScaling = true;
            this.renderImage = function() {
                Garnish.requestAnimationFrame(this.canvas.renderAll.bind(this.canvas));
            }.bind(this);

            // Load the image from URL
            var imageUrl = Craft.getActionUrl('assets/edit-image', {
                assetId: this.assetId,
                size: this.maxImageSize,
                cacheBust: this.cacheBust
            });

            // Load image and set up the initial properties
            fabric.Image.fromURL(imageUrl, $.proxy(function(imageObject) {
                this.image = imageObject;
                this.image.set({
                    originX: 'center',
                    originY: 'center',
                    left: this.editorWidth / 2,
                    top: this.editorHeight / 2
                });
                this.canvas.add(this.image);

                this.originalHeight = this.image.getHeight();
                this.originalWidth = this.image.getWidth();
                this.zoomRatio = 1;

                this.lastLoadedDimensions = this.getScaledImageDimensions();

                // Set up the image bounding box, viewport and position everything
                this._setFittedImageVerticeCoordinates();
                this._repositionEditorElements();

                // Set up the focal point
                var focalState = {
                    imageDimensions: this.getScaledImageDimensions(),
                    offsetX: 0,
                    offsetY: 0
                };

                var focal = false;
                if (data.focalPoint) {
                    // Transform the focal point coordinates from relative to absolute
                    var focalData = data.focalPoint;

                    // Resolve for the current image dimensions.
                    var adjustedX = focalState.imageDimensions.width * focalData.x;
                    var adjustedY = focalState.imageDimensions.height * focalData.y;

                    focalState.offsetX = adjustedX - focalState.imageDimensions.width / 2;
                    focalState.offsetY = adjustedY - focalState.imageDimensions.height / 2;

                    focal = true;
                }

                this.storeFocalPointState(focalState);

                if (focal) {
                    this._createFocalPoint();
                }

                this._createViewport();
                this.storeCropperState();

                // Add listeners to buttons
                this._addControlListeners();

                // Add mouse event listeners
                this.addListener(this.$croppingCanvas, 'mousemove,touchmove', this._handleMouseMove);
                this.addListener(this.$croppingCanvas, 'mousedown,touchstart', this._handleMouseDown);
                this.addListener(this.$croppingCanvas, 'mouseup,touchend', this._handleMouseUp);
                this.addListener(this.$croppingCanvas, 'mouseout,touchcancel', this._handleMouseOut);

                this._hideSpinner();

                // Render it, finally
                this.renderImage();

                // Make sure verything gets fired for the first tab
                this.$tabs.first().trigger('click');
            }, this));
        },

        /**
         * Reload the image to better fit the current available image editor viewport.
         */
        _reloadImage: function () {
            if (this.imageIsLoading) {
                return;
            }

            this.imageIsLoading = true;
            this.maxImageSize = this.getMaxImageSize();

            // Load the image from URL
            var imageUrl = Craft.getActionUrl('assets/edit-image', {
                assetId: this.assetId,
                size: this.maxImageSize,
                cacheBust: this.cacheBust
            });

            this.image.setSrc(imageUrl, function(imageObject) {
                this.originalHeight = imageObject.getHeight();
                this.originalWidth = imageObject.getWidth();
                this.lastLoadedDimensions = {width: this.originalHeight, height: this.originalWidth};
                this.updateSizeAndPosition();
                this.renderImage();
                this.imageIsLoading = false;
            }.bind(this));
        },

        /**
         * Update the modal size and position on browser resize
         */
        updateSizeAndPosition: function() {
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
                'height': innerHeight - 62
            });

            if (innerWidth < innerHeight) {
                this.$container.addClass('vertical');
            }
            else {
                this.$container.removeClass('vertical');
            }

            if (this.$spinnerCanvas) {
                this.$spinnerCanvas.css({
                    left: ((this.$spinnerCanvas.parent().width()/2)-(this.$spinnerCanvas.width()/2))+'px',
                    top: ((this.$spinnerCanvas.parent().height()/2)-(this.$spinnerCanvas.height()/2))+'px'
                });
            }

            // If image is already loaded, make sure it looks pretty.
            if (this.$editorContainer && this.image) {
                this._repositionEditorElements();
            }
        },

        /**
         * Reposition the editor elements to accurately reflect the editor state with current dimensions
         */
        _repositionEditorElements: function() {
            // Remember what the dimensions were before the resize took place
            var previousEditorDimensions = {
                width: this.editorWidth,
                height: this.editorHeight
            };

            this.editorHeight = this.$editorContainer.innerHeight();
            this.editorWidth = this.$editorContainer.innerWidth();

            this.canvas.setDimensions({
                width: this.editorWidth,
                height: this.editorHeight
            });

            var currentScaledDimensions = this.getScaledImageDimensions();

            // If we're cropping now, we have to reposition the cropper correctly in case
            // the area for image changes, forcing the image size to change as well.
            if (this.currentView === 'crop') {
                this.zoomRatio = this.getZoomToFitRatio(this.getScaledImageDimensions());
                var previouslyOccupiedArea = this._getBoundingRectangle(this.imageVerticeCoords);
                this._setFittedImageVerticeCoordinates();
                this._repositionCropper(previouslyOccupiedArea);
            } else {
                // Otherwise just recalculate the image zoom ratio
                this.zoomRatio = this.getZoomToCoverRatio(this.getScaledImageDimensions()) * this.scaleFactor;
            }

            // Reposition the image relatively to the previous editor dimensions.
            this._repositionImage(previousEditorDimensions);
            this._repositionViewport();
            this._repositionFocalPoint(previousEditorDimensions);
            this._zoomImage();

            this.renderImage();

            if (currentScaledDimensions.width / this.lastLoadedDimensions.width > 1.5 || currentScaledDimensions.height / this.lastLoadedDimensions.height > 1.5) {
                this._reloadImage();
            }
        },

        /**
         * Reposition image based on how the editor dimensions have changed.
         * This ensures keeping the image center offset, if there is any.
         *
         * @param previousEditorDimensions
         */
        _repositionImage: function(previousEditorDimensions) {
            this.image.set({
                left: this.image.left - (previousEditorDimensions.width - this.editorWidth) / 2,
                top: this.image.top - (previousEditorDimensions.height - this.editorHeight) / 2
            });
        },

        /**
         * Create the viewport for image editor.
         */
        _createViewport: function() {
            this.viewport = new fabric.Rect({
                width: this.image.width,
                height: this.image.height,
                fill: 'rgba(127,0,0,1)',
                originX: 'center',
                originY: 'center',
                globalCompositeOperation: 'destination-in', // This clips everything outside of the viewport
                left: this.image.left,
                top: this.image.top
            });
            this.canvas.add(this.viewport);
            this.renderImage();
        },

        /**
         * Create the focal point.
         */
        _createFocalPoint: function() {
            var focalPointState = this.focalPointState;
            var sizeFactor = this.getScaledImageDimensions().width / focalPointState.imageDimensions.width;

            var focalX = focalPointState.offsetX * sizeFactor * this.zoomRatio * this.scaleFactor;
            var focalY = focalPointState.offsetY * sizeFactor * this.zoomRatio * this.scaleFactor;

            // Adjust by image margins
            focalX += this.image.left;
            focalY += this.image.top;

            var deltaX = 0;
            var deltaY = 0;

            // When creating a fresh focal point, drop it dead in the center of the viewport, not the image.
            if (this.viewport && focalPointState.offsetX === 0 && focalPointState.offsetY === 0) {
                if (this.currentView !== 'crop') {
                    deltaX = this.viewport.left - this.image.left;
                    deltaY = this.viewport.top - this.image.top;
                } else {
                    // Unless we have a cropper showing, in which case drop it in the middle of the cropper
                    deltaX = this.clipper.left - this.image.left;
                    deltaY = this.clipper.top - this.image.top;
                }

                // Bump focal to middle of viewport
                focalX += deltaX;
                focalY += deltaY;

                // Reflect changes in saved state
                focalPointState.offsetX += deltaX / (sizeFactor * this.zoomRatio * this.scaleFactor);
                focalPointState.offsetY += deltaY / (sizeFactor * this.zoomRatio * this.scaleFactor);
            }

            this.focalPoint = new fabric.Group([
                new fabric.Circle({radius: 8, fill: 'rgba(0,0,0,0.5)', strokeWidth: 2, stroke: 'rgba(255,255,255,0.8)', left: 0, top: 0, originX: 'center', originY: 'center'}),
                new fabric.Circle({radius: 1, fill: 'rgba(255,255,255,0)', strokeWidth: 2, stroke: 'rgba(255,255,255,0.8)', left: 0, top: 0, originX: 'center', originY: 'center'})
            ], {
                originX: 'center',
                originY: 'center',
                left: focalX,
                top: focalY
            });

            this.storeFocalPointState(focalPointState);
            this.canvas.add(this.focalPoint);
        },

        /**
         * Toggle focal point
         */
        toggleFocalPoint: function() {
            if (!this.focalPoint) {
                this._createFocalPoint();
            } else {
                this.canvas.remove(this.focalPoint);
                this.focalPoint = null;
            }

            this.renderImage();
        },

        /**
         * Reposition the viewport to handle editor resizing.
         */
        _repositionViewport: function() {
            if (this.viewport) {
                var dimensions = {
                    left: this.editorWidth / 2,
                    top: this.editorHeight / 2
                };

                // If we're cropping, nothing exciting happens for the viewport
                if (this.currentView === 'crop') {
                    dimensions.width = this.editorWidth;
                    dimensions.height = this.editorHeight;
                } else {
                    // If this is the first initial reposition, no cropper state yet
                    if (this.cropperState) {
                        // Recall the state
                        var state = this.cropperState;

                        var scaledImageDimensions = this.getScaledImageDimensions();
                        // Make sure we have the correct current image size
                        var sizeFactor = scaledImageDimensions.width / state.imageDimensions.width;

                        // Set the viewport dimensions
                        dimensions.width = state.width * sizeFactor * this.zoomRatio;
                        dimensions.height = state.height * sizeFactor * this.zoomRatio;

                        // Adjust the image position to show the correct part of the image in the viewport
                        this.image.set({
                            left: (this.editorWidth / 2) - (state.offsetX * sizeFactor),
                            top: (this.editorHeight / 2) - (state.offsetY * sizeFactor)
                        });
                    } else {
                        $.extend(dimensions, this.getScaledImageDimensions());
                    }
                }
                this.viewport.set(dimensions);
            }
        },

        _repositionFocalPoint: function(previousEditorDimensions) {
            if (this.focalPoint) {
                var offsetX = this.focalPoint.left - this.editorWidth / 2;
                var offsetY = this.focalPoint.top - this.editorHeight / 2;

                var currentWidth = this.image.width;
                var newWidth = this.getScaledImageDimensions().width * this.zoomRatio;
                var ratio = newWidth / currentWidth / this.scaleFactor;

                offsetX -= (previousEditorDimensions.width - this.editorWidth) / 2;
                offsetY -= (previousEditorDimensions.height - this.editorHeight) / 2;

                offsetX *= ratio;
                offsetY *= ratio;

                this.focalPoint.set({
                    left: this.editorWidth / 2 + offsetX,
                    top: this.editorHeight / 2 + offsetY
                });
            }
        },

        /**
         * Return true if the image orientation has changed
         */
        hasOrientationChanged: function() {
            return this.viewportRotation % 180 !== 0;
        },

        /**
         * Return the current image dimensions that would be used in the current image area with no straightening or rotation applied.
         */
        getScaledImageDimensions: function() {
            if (typeof this.getScaledImageDimensions._ === 'undefined') {
                this.getScaledImageDimensions._ = {};
            }

            this.getScaledImageDimensions._.imageRatio = this.originalHeight / this.originalWidth;
            this.getScaledImageDimensions._.editorRatio = this.editorHeight / this.editorWidth;

            this.getScaledImageDimensions._.dimensions = {};
            if (this.getScaledImageDimensions._.imageRatio > this.getScaledImageDimensions._.editorRatio) {
                this.getScaledImageDimensions._.dimensions.height = Math.min(this.editorHeight, this.originalHeight);
                this.getScaledImageDimensions._.dimensions.width = Math.round(this.originalWidth / (this.originalHeight / this.getScaledImageDimensions._.dimensions.height));
            } else {
                this.getScaledImageDimensions._.dimensions.width = Math.min(this.editorWidth, this.originalWidth);
                this.getScaledImageDimensions._.dimensions.height = Math.round(this.originalHeight * (this.getScaledImageDimensions._.dimensions.width / this.originalWidth));
            }

            return this.getScaledImageDimensions._.dimensions;
        },

        /**
         * Set the image dimensions to reflect the current zoom ratio.
         */
        _zoomImage: function() {
            if (typeof this._zoomImage._ === 'undefined') {
                this._zoomImage._ = {};
            }

            this._zoomImage._.imageDimensions = this.getScaledImageDimensions();
            this.image.set({
                width: this._zoomImage._.imageDimensions.width * this.zoomRatio,
                height: this._zoomImage._.imageDimensions.height * this.zoomRatio
            });
        },

        /**
         * Set up listeners for the controls.
         */
        _addControlListeners: function() {
            // Tabs
            this.addListener(this.$tabs, 'click', this._handleTabClick);

            // Focal point
            this.addListener($('.focal-point'), 'click', this.toggleFocalPoint);

            // Rotate controls
            this.addListener($('.rotate-left'), 'click', function() {
                this.rotateImage(-90);
            });
            this.addListener($('.rotate-right'), 'click', function() {
                this.rotateImage(90);
            });
            this.addListener($('.flip-vertical'), 'click', function() {
                this.flipImage('y');
            });
            this.addListener($('.flip-horizontal'), 'click', function() {
                this.flipImage('x');
            });

            // Straighten slider
            this.straighteningInput = new Craft.SlideRuleInput("slide-rule", {
                onStart: function() {
                    this._showGrid();
                }.bind(this),
                onChange: function(slider) {
                    this.straighten(slider);
                }.bind(this),
                onEnd: function() {
                    this._hideGrid();
                    this._cleanupFocalPointAfterStraighten();
                }.bind(this)
            });

            // Cropper scale modifier key
            this.addListener(Garnish.$doc, 'keydown', function(ev) {
                if (ev.keyCode === Garnish.SHIFT_KEY) {
                    this.shiftKeyHeld = true;
                }
            });
            this.addListener(Garnish.$doc, 'keyup', function(ev) {
                if (ev.keyCode === Garnish.SHIFT_KEY) {
                    this.shiftKeyHeld = false;
                }
            });

            this.addListener($('.constraint-buttons .constraint', this.$container), 'click', this._handleConstraintClick);
            this.addListener($('.orientation input', this.$container), 'click', this._handleOrientationClick);
            this.addListener($('.constraint-buttons .custom-input input', this.$container), 'keyup', this._applyCustomConstraint);
        },

        /**
         * Handle a constraint button click.
         *
         * @param ev
         */
        _handleConstraintClick: function (ev) {
            var constraint = $(ev.currentTarget).data('constraint');
            var $target = $(ev.currentTarget);
            $target.siblings().removeClass('active');
            $target.addClass('active');

            if (constraint == 'custom') {
                this._showCustomConstraint();
                this._applyCustomConstraint();
                return;
            }

            this._hideCustomConstraint();

            this.setCroppingConstraint(constraint);
            this.enforceCroppingConstraint();
        },

        /**
         * Handle an orientation switch click.
         *
         * @param ev
         */
        _handleOrientationClick: function (ev) {
            if (ev.currentTarget.value === this.constraintOrientation) {
                return;
            }
            this.constraintOrientation = ev.currentTarget.value;

            var $constraints = $('.constraint.flip', this.$container);

            for (var i = 0; i < $constraints.length; i++) {
                var $constraint = $($constraints[i]);
                $constraint.data('constraint', 1 / $constraint.data('constraint'));
                $constraint.html($constraint.html().split(':').reverse().join(':'));
            }

            $constraints.filter('.active').click();
        },

        /**
         * Apply the custom ratio set in the inputs
         */
        _applyCustomConstraint: function () {
            var constraint = this._getCustomConstraint();

            if (constraint.w > 0 && constraint.h > 0) {
                this.setCroppingConstraint(constraint.w / constraint.h);
                this.enforceCroppingConstraint();
            }
        },

        /**
         * Get the custom constraint.
         *
         * @returns {{w: *, h: *}}
         */
        _getCustomConstraint: function () {
            var w = parseFloat($('.custom-constraint-w').val());
            var h = parseFloat($('.custom-constraint-h').val());
            return {
                w: isNaN(w) ? 0 : w,
                h: isNaN(h) ? 0 : h,
            }
        },

        /**
         * Set the custom constraint.
         *
         * @param w
         * @param h
         */
        _setCustomConstraint: function (w, h) {
            $('.custom-constraint-w').val(parseFloat(w));
            $('.custom-constraint-h').val(parseFloat(h));
        },

        /**
         * Hide the custom constraint inputs.
         */
        _hideCustomConstraint: function () {
            this.showingCustomConstraint = false;
            $('.constraint.custom .custom-input', this.$container).addClass('hidden');
            $('.constraint.custom .custom-label', this.$container).removeClass('hidden');
            $('.orientation', this.$container).removeClass('hidden');
        },

        /**
         * Show the custom constraint inputs.
         */
        _showCustomConstraint: function () {
            if (this.showingCustomConstraint) {
                return;
            }

            this.showingCustomConstraint = true;
            $('.constraint.custom .custom-input', this.$container).removeClass('hidden');
            $('.constraint.custom .custom-label', this.$container).addClass('hidden');
            $('.orientation', this.$container).addClass('hidden');
        },

        /**
         * Handle tab click.
         *
         * @param ev
         */
        _handleTabClick: function(ev) {
            if (!this.animationInProgress) {
                var $tab = $(ev.currentTarget);
                var view = $tab.data('view');
                this.$tabs.removeClass('selected');
                $tab.addClass('selected');
                this.showView(view);
            }
        },

        /**
         * Show a view.
         *
         * @param view
         */
        showView: function(view) {
            if (this.currentView === view) {
                return;
            }

            this.$views.addClass('hidden');
            var $view = this.$views.filter('[data-view="' + view + '"]');
            $view.removeClass('hidden');

            if (view === 'rotate') {
                this.enableSlider();
            } else {
                this.disableSlider();
            }


            // Now that most likely our editor dimensions have changed, time to reposition stuff
            this.updateSizeAndPosition();

            // See if we have to enable or disable crop mode as we transition between tabs
            if (this.currentView === 'crop' && view !== 'crop') {
                this.disableCropMode();
            } else if (this.currentView !== 'crop' && view === 'crop') {
                this.enableCropMode();
            }

            // Mark the current view
            this.currentView = view;
        },

        /**
         * Store the current cropper state.
         *
         * Cropper state is always assumed to be saved at a zoom ratio of 1 to be used
         * as the basis for recalculating the cropper position and dimensions.
         *
         * @param [state]
         */
        storeCropperState: function(state) {
            if (typeof this.storeCropperState._ === 'undefined') {
                this.storeCropperState._ = {};
            }

            // If we're asked to store a specific state.
            if (state) {
                this.cropperState = state;
            } else if (this.clipper) {
                this.storeCropperState._.zoomFactor = 1 / this.zoomRatio;

                this.cropperState = {
                    offsetX: (this.clipper.left - this.image.left) * this.storeCropperState._.zoomFactor,
                    offsetY: (this.clipper.top - this.image.top) * this.storeCropperState._.zoomFactor,
                    height: this.clipper.height * this.storeCropperState._.zoomFactor,
                    width: this.clipper.width * this.storeCropperState._.zoomFactor,
                    imageDimensions: this.getScaledImageDimensions()
                };
            } else {
                this.storeCropperState._.dimensions = this.getScaledImageDimensions();
                this.cropperState = {
                    offsetX: 0,
                    offsetY: 0,
                    height: this.storeCropperState._.dimensions.height,
                    width: this.storeCropperState._.dimensions.width,
                    imageDimensions: this.storeCropperState._.dimensions
                };
            }
        },

        /**
         * Store focal point coordinates in a manner that is not tied to zoom ratio and rotation.
         */
        storeFocalPointState: function(state) {
            if (typeof this.storeFocalPointState._ === 'undefined') {
                this.storeFocalPointState._ = {};
            }

            // If we're asked to store a specific state.
            if (state) {
                this.focalPointState = state;
            } else if (this.focalPoint) {
                this.storeFocalPointState._.zoomFactor = 1 / this.zoomRatio;
                this.focalPointState = {
                    offsetX: (this.focalPoint.left - this.image.left) * this.storeFocalPointState._.zoomFactor / this.scaleFactor,
                    offsetY: (this.focalPoint.top - this.image.top) * this.storeFocalPointState._.zoomFactor / this.scaleFactor,
                    imageDimensions: this.getScaledImageDimensions()
                };
            }
        },

        /**
         * Rotate the image along with the viewport.
         *
         * @param degrees
         */
        rotateImage: function(degrees) {
            if (!this.animationInProgress) {
                // We're not that kind of an establishment, sir.
                if (degrees !== 90 && degrees !== -90) {
                    return false;
                }

                this.animationInProgress = true;
                this.viewportRotation += degrees;

                // Normalize the viewport rotation angle so it's between 0 and 359
                this.viewportRotation = parseInt((this.viewportRotation + 360) % 360, 10);

                var newAngle = this.image.angle + degrees;
                var scaledImageDimensions = this.getScaledImageDimensions();
                var imageZoomRatio;

                if (this.hasOrientationChanged()) {
                    imageZoomRatio = this.getZoomToCoverRatio({height: scaledImageDimensions.width, width: scaledImageDimensions.height});
                } else {
                    imageZoomRatio = this.getZoomToCoverRatio(scaledImageDimensions);
                }

                // In cases when for some reason we've already zoomed in on the image,
                // use existing zoom.
                if (this.zoomRatio > imageZoomRatio) {
                    imageZoomRatio = this.zoomRatio;
                }

                var viewportProperties = {
                    angle: degrees === 90 ? '+=90' : '-=90'
                };

                var imageProperties = {
                    angle: newAngle,
                    width: scaledImageDimensions.width * imageZoomRatio,
                    height: scaledImageDimensions.height * imageZoomRatio
                };

                var scaleFactor = 1;
                if (this.scaleFactor < 1) {
                    scaleFactor = 1 / this.scaleFactor;
                    this.scaleFactor = 1;
                } else {
                    if (this.viewport.width > this.editorHeight) {
                        scaleFactor = this.editorHeight / this.viewport.width;
                    } else if (this.viewport.height > this.editorWidth) {
                        scaleFactor = this.editorWidth / this.viewport.height;
                    }
                    this.scaleFactor = scaleFactor;
                }

                if (scaleFactor < 1) {
                    imageProperties.width *= scaleFactor;
                    imageProperties.height *= scaleFactor;
                }

                var state = this.cropperState;

                // Make sure we reposition the image as well to focus on the same image area
                var deltaX = state.offsetX;
                var deltaY = state.offsetY;
                var angleInRadians = degrees * (Math.PI / 180);

                // Calculate how the cropper would need to move in a circle to maintain
                // the focus on the same region if the image was rotated with zoom intact.
                var newDeltaX = deltaX * Math.cos(angleInRadians) - deltaY * Math.sin(angleInRadians);
                var newDeltaY = deltaX * Math.sin(angleInRadians) + deltaY * Math.cos(angleInRadians);

                var sizeFactor = scaledImageDimensions.width / state.imageDimensions.width;

                var modifiedDeltaX = newDeltaX * sizeFactor * this.zoomRatio * this.scaleFactor;
                var modifiedDeltaY = newDeltaY * sizeFactor * this.zoomRatio * this.scaleFactor;

                imageProperties.left = this.editorWidth / 2 - modifiedDeltaX;
                imageProperties.top = this.editorHeight / 2 - modifiedDeltaY;

                state.offsetX = newDeltaX;
                state.offsetY = newDeltaY;

                var temp = state.width;
                state.width = state.height;
                state.height = temp;

                this.storeCropperState(state);

                if (this.focalPoint) {
                    this.canvas.remove(this.focalPoint);
                }

                this.viewport.animate(viewportProperties, {
                    duration: this.settings.animationDuration,
                    onComplete: function() {
                        // If we're zooming the image in or out, better do the same to viewport
                        var temp = this.viewport.height * scaleFactor;
                        this.viewport.height = this.viewport.width * scaleFactor;
                        this.viewport.width = temp;
                        this.viewport.set({angle: 0});
                    }.bind(this)
                });

                // Animate the rotation and dimension change
                this.image.animate(imageProperties, {
                    onChange: this.canvas.renderAll.bind(this.canvas),
                    duration: this.settings.animationDuration,
                    onComplete: function() {
                        var cleanAngle = parseFloat((this.image.angle + 360) % 360);
                        this.image.set({angle: cleanAngle});
                        this.animationInProgress = false;
                        if (this.focalPoint) {
                            this._adjustFocalPointByAngle(degrees);
                            this.straighten(this.straighteningInput);
                            this.canvas.add(this.focalPoint);
                        } else {
                            this._resetFocalPointPosition();
                        }
                    }.bind(this)
                });
            }
        },

        /**
         * Flip an image along an axis.
         *
         * @param axis
         */
        flipImage: function(axis) {
            if (!this.animationInProgress) {
                this.animationInProgress = true;

                if (this.hasOrientationChanged()) {
                    axis = axis === 'y' ? 'x' : 'y';
                }

                if (this.focalPoint) {
                    this.canvas.remove(this.focalPoint);
                } else {
                    this._resetFocalPointPosition();
                }

                var editorCenter = {x: this.editorWidth / 2, y: this.editorHeight / 2};
                this.straighteningInput.setValue(-this.imageStraightenAngle);
                this.imageStraightenAngle = -this.imageStraightenAngle;
                var properties = {
                    angle: this.viewportRotation + this.imageStraightenAngle
                };

                var deltaY, deltaX;
                var cropperState = this.cropperState;
                var focalPointState = this.focalPointState;

                // Reposition the image, viewport, and stored cropper and focal point states.
                if ((axis === 'y' && this.hasOrientationChanged()) || (axis !== 'y' && !this.hasOrientationChanged())) {
                    cropperState.offsetX = -cropperState.offsetX;
                    focalPointState.offsetX = -focalPointState.offsetX;
                    deltaX = this.image.left - editorCenter.x;
                    properties.left = editorCenter.x - deltaX;
                } else {
                    cropperState.offsetY = -cropperState.offsetY;
                    focalPointState.offsetY = -focalPointState.offsetY;
                    deltaY = this.image.top - editorCenter.y;
                    properties.top = editorCenter.y - deltaY;
                }

                if (axis === 'y') {
                    properties.scaleY = this.image.scaleY * -1;
                    this.flipData.y = 1 - this.flipData.y;
                } else {
                    properties.scaleX = this.image.scaleX * -1;
                    this.flipData.x = 1 - this.flipData.x;
                }

                this.storeCropperState(cropperState);
                this.storeFocalPointState(focalPointState);

                this.image.animate(properties, {
                    onChange: this.canvas.renderAll.bind(this.canvas),
                    duration: this.settings.animationDuration,
                    onComplete: function() {
                        this.animationInProgress = false;
                        if (this.focalPoint) {
                            // Well this is handy
                            this._adjustFocalPointByAngle(0);
                            this.canvas.add(this.focalPoint);
                        }
                    }.bind(this)
                });
            }
        },

        /**
         * Perform the straightening with input slider.
         *
         * @param {Craft.SlideRuleInput} slider
         */
        straighten: function(slider) {
            if (!this.animationInProgress) {
                this.animationInProgress = true;

                var previousAngle = this.image.angle;

                this.imageStraightenAngle = (this.settings.allowDegreeFractions ? parseFloat(slider.value) : Math.round(parseFloat(slider.value))) % 360;

                // Straighten the image
                this.image.set({
                    angle: this.viewportRotation + this.imageStraightenAngle
                });

                // Set the new zoom ratio
                this.zoomRatio = this.getZoomToCoverRatio(this.getScaledImageDimensions()) * this.scaleFactor;
                this._zoomImage();

                if (this.cropperState) {
                    this._adjustEditorElementsOnStraighten(previousAngle);
                }

                this.renderImage();

                this.animationInProgress = false;
            }
        },

        /**
         * Adjust the cropped viewport when straightening the image to correct for
         * bumping into edges, keeping focus on the cropped area center and to
         * maintain the illusion that the image is being straightened relative to the viewport center.
         *
         * @param {integer} previousAngle integer the previous image angle before straightening
         */
        _adjustEditorElementsOnStraighten: function(previousAngle) {
            var scaledImageDimensions = this.getScaledImageDimensions();
            var angleDelta = this.image.angle - previousAngle;
            var state = this.cropperState;

            var currentZoomRatio = this.zoomRatio;
            var adjustmentRatio = 1;

            var deltaX, deltaY, newCenterX, newCenterY, sizeFactor;

            do {
                // Get the cropper center coordinates
                var cropperCenterX = state.offsetX;
                var cropperCenterY = state.offsetY;
                var angleInRadians = angleDelta * (Math.PI / 180);

                // Calculate how the cropper would need to move in a circle to maintain
                // the focus on the same region if the image was rotated with zoom intact.
                newCenterX = cropperCenterX * Math.cos(angleInRadians) - cropperCenterY * Math.sin(angleInRadians);
                newCenterY = cropperCenterX * Math.sin(angleInRadians) + cropperCenterY * Math.cos(angleInRadians);

                sizeFactor = scaledImageDimensions.width / state.imageDimensions.width;

                // Figure out the final image offset to keep the viewport focused where we need it
                deltaX = newCenterX * currentZoomRatio * sizeFactor;
                deltaY = newCenterY * currentZoomRatio * sizeFactor;

                // If the image would creep in the viewport, figure out how to math around it.
                var imageVertices = this.getImageVerticeCoords(currentZoomRatio);
                var rectangle = {
                    width: this.viewport.width,
                    height: this.viewport.height,
                    left: this.editorWidth / 2 - this.viewport.width / 2 + deltaX,
                    top: this.editorHeight / 2 - this.viewport.height / 2 + deltaY
                };
                adjustmentRatio = this._getZoomRatioToFitRectangle(rectangle, imageVertices);
                currentZoomRatio = currentZoomRatio * adjustmentRatio;

                // If we had to make adjustments, do the calculations again
            } while (adjustmentRatio !== 1);

            // Reposition the image correctly
            this.image.set({
                left: this.editorWidth / 2 - deltaX,
                top: this.editorHeight / 2 - deltaY
            });

            // Finally, store the new cropper state to reflect the rotation change.
            state.offsetX = newCenterX;
            state.offsetY = newCenterY;
            state.width = this.viewport.width / currentZoomRatio / sizeFactor;
            state.height = this.viewport.height / currentZoomRatio / sizeFactor;

            this.storeCropperState(state);

            // Zoom the image in and we're done.
            this.zoomRatio = currentZoomRatio;

            if (this.focalPoint) {
                this._adjustFocalPointByAngle(angleDelta);

                if (!this._isCenterInside(this.focalPoint, this.viewport)) {
                    this.focalPoint.set({opacity: 0});
                } else {
                    this.focalPoint.set({opacity: 1});
                }
            } else if (angleDelta !== 0) {
                this._resetFocalPointPosition();
            }

            this._zoomImage();
        },

        /**
         * If focal point is active and outside of viewport after straightening, reset it.
         */
        _cleanupFocalPointAfterStraighten: function() {
            if (this.focalPoint && !this._isCenterInside(this.focalPoint, this.viewport)) {
                this.focalPoint.set({opacity: 1});
                var state = this.focalPointState;
                state.offsetX = 0;
                state.offsetY = 0;
                this.storeFocalPointState(state);
                this.toggleFocalPoint();
            }
        },

        /**
         * Reset focal point to the middle of image.
         */
        _resetFocalPointPosition: function () {
            var state = this.focalPointState;
            state.offsetX = 0;
            state.offsetY = 0;
            this.storeFocalPointState(state);
        },

        /**
         * Returns true if a center of an object is inside another rectangle shaped object that is not rotated.
         *
         * @param object
         * @param containingObject
         *
         * @returns {boolean}
         */
        _isCenterInside: function(object, containingObject) {
            return (object.left > containingObject.left - containingObject.width / 2
                && object.top > containingObject.top - containingObject.height / 2
                && object.left < containingObject.left + containingObject.width / 2
                && object.top < containingObject.top + containingObject.height / 2
            );
        },

        /**
         * Adjust the focal point by an angle in degrees.
         * @param angle
         */
        _adjustFocalPointByAngle: function(angle) {
            var angleInRadians = angle * (Math.PI / 180);
            var state = this.focalPointState;

            var focalX = state.offsetX;
            var focalY = state.offsetY;

            // Calculate how the focal point would need to move in a circle to keep on the same spot
            // on the image if it was rotated with zoom intact.
            var newFocalX = focalX * Math.cos(angleInRadians) - focalY * Math.sin(angleInRadians);
            var newFocalY = focalX * Math.sin(angleInRadians) + focalY * Math.cos(angleInRadians);
            var sizeFactor = this.getScaledImageDimensions().width / state.imageDimensions.width;

            var adjustedFocalX = newFocalX * sizeFactor * this.zoomRatio;
            var adjustedFocalY = newFocalY * sizeFactor * this.zoomRatio;

            this.focalPoint.left = this.image.left + adjustedFocalX;
            this.focalPoint.top = this.image.top + adjustedFocalY;

            state.offsetX = newFocalX;
            state.offsetY = newFocalY;
            this.storeFocalPointState(state);
        },

        /**
         * Get the zoom ratio required to fit a rectangle within another rectangle, that is defined by vertices.
         * If the rectangle fits, 1 will be returned.
         *
         * @param rectangle
         * @param containingVertices
         */
        _getZoomRatioToFitRectangle: function(rectangle, containingVertices) {
            var rectangleVertices = this._getRectangleVertices(rectangle);
            var vertex;

            // Check if any of the viewport vertices end up out of bounds
            for (var verticeIndex = 0; verticeIndex < rectangleVertices.length; verticeIndex++) {
                vertex = rectangleVertices[verticeIndex];

                if (!this.arePointsInsideRectangle([vertex], containingVertices)) {
                    break;
                }

                vertex = false;
            }

            // If there's no vertex set after loop, it means that all of them are inside the image rectangle
            var adjustmentRatio;

            if (!vertex) {
                adjustmentRatio = 1;
            } else {
                // Find out which edge got crossed by the vertex
                var edge = this._getEdgeCrossed(containingVertices, vertex);

                var rectangleCenter = {
                    x: rectangle.left + rectangle.width / 2,
                    y: rectangle.top + rectangle.height / 2
                };

                // Calculate how much further that edge needs to be.
                // https://en.wikipedia.org/wiki/Distance_from_a_point_to_a_line#Line_defined_by_two_points
                var distanceFromVertexToEdge = Math.abs((edge[1].y - edge[0].y) * vertex.x - (edge[1].x - edge[0].x) * vertex.y + edge[1].x * edge[0].y - edge[1].y * edge[0].x) / Math.sqrt(Math.pow(edge[1].y - edge[0].y, 2) + Math.pow(edge[1].x - edge[0].x, 2));
                var distanceFromCenterToEdge = Math.abs((edge[1].y - edge[0].y) * rectangleCenter.x - (edge[1].x - edge[0].x) * rectangleCenter.y + edge[1].x * edge[0].y - edge[1].y * edge[0].x) / Math.sqrt(Math.pow(edge[1].y - edge[0].y, 2) + Math.pow(edge[1].x - edge[0].x, 2));

                // Adjust the zoom ratio
                adjustmentRatio = ((distanceFromVertexToEdge + distanceFromCenterToEdge) / distanceFromCenterToEdge);
            }

            return adjustmentRatio;
        },

        /**
         * Save the image.
         *
         * @param ev
         */
        saveImage: function(ev) {
            var $button = $(ev.currentTarget);
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

            if (this.cropperState) {
                var cropData = {};

                cropData.height = this.cropperState.height;
                cropData.width = this.cropperState.width;
                cropData.offsetX = this.cropperState.offsetX;
                cropData.offsetY = this.cropperState.offsetY;

                postData.imageDimensions = this.cropperState.imageDimensions;

                postData.cropData = cropData;
            } else {
                postData.imageDimensions = this.getScaledImageDimensions();
            }

            if (this.focalPoint) {
                postData.focalPoint = this.focalPointState;
            }

            postData.flipData = this.flipData;
            postData.zoom = this.zoomRatio;

            Craft.postActionRequest('assets/save-image', postData, function(data) {
                this.$buttons.find('.btn').removeClass('disabled').end().find('.spinner').remove();

                if (data.error) {
                    alert(data.error);
                    return;
                }

                this.onSave();
                this.hide();
                Craft.cp.runQueue();
            }.bind(this));
        },

        /**
         * Return image zoom ratio depending on the straighten angle to cover a viewport by given dimensions.
         *
         * @param dimensions
         */
        getZoomToCoverRatio: function(dimensions) {
            // Convert the angle to radians
            var angleInRadians = Math.abs(this.imageStraightenAngle) * (Math.PI / 180);

            // Calculate the dimensions of the scaled image using the magic of math
            var scaledWidth = Math.sin(angleInRadians) * dimensions.height + Math.cos(angleInRadians) * dimensions.width;
            var scaledHeight = Math.sin(angleInRadians) * dimensions.width + Math.cos(angleInRadians) * dimensions.height;

            // Calculate the ratio
            return Math.max(scaledWidth / dimensions.width, scaledHeight / dimensions.height);
        },

        /**
         * Return image zoom ratio depending on the straighten angle to fit inside a viewport by given dimensions.
         *
         * @param dimensions
         */
        getZoomToFitRatio: function(dimensions) {
            // Get the bounding box for a rotated image
            var boundingBox = this._getImageBoundingBox(dimensions);

            // Scale the bounding box to fit
            var scale = 1;
            if (boundingBox.height > this.editorHeight || boundingBox.width > this.editorWidth) {
                var vertScale = this.editorHeight / boundingBox.height;
                var horiScale = this.editorWidth / boundingBox.width;
                scale = Math.min(horiScale, vertScale);
            }

            return scale;
        },

        /**
         * Return the combined zoom ratio to fit a rectangle inside image that's been zoomed to fit.
         */
        getCombinedZoomRatio: function(dimensions) {
            return this.getZoomToCoverRatio(dimensions) / this.getZoomToFitRatio(dimensions);
        },

        /**
         * Draw the grid.
         *
         * @private
         */
        _showGrid: function() {
            if (!this.grid) {
                var strokeOptions = {
                    strokeWidth: 1,
                    stroke: 'rgba(255,255,255,0.5)'
                };

                var lineCount = 8;
                var gridWidth = this.viewport.width;
                var gridHeight = this.viewport.height;
                var xStep = gridWidth / (lineCount + 1);
                var yStep = gridHeight / (lineCount + 1);

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
                    })
                ];

                var i;
                for (i = 1; i <= lineCount; i++) {
                    grid.push(new fabric.Line([i * xStep, 0, i * xStep, gridHeight], strokeOptions));
                }
                for (i = 1; i <= lineCount; i++) {
                    grid.push(new fabric.Line([0, i * yStep, gridWidth, i * yStep], strokeOptions));
                }

                this.grid = new fabric.Group(grid, {
                    left: this.editorWidth / 2,
                    top: this.editorHeight / 2,
                    originX: 'center',
                    originY: 'center',
                    angle: this.viewport.angle
                });

                this.canvas.add(this.grid);
                this.renderImage();
            }
        },

        /**
         * Hide the grid
         */
        _hideGrid: function() {
            this.canvas.remove(this.grid);
            this.grid = null;
            this.renderImage();
        },

        /**
         * Remove all the events when hiding the editor.
         */
        onFadeOut: function() {
            this.destroy();
        },

        /**
         * Make sure underlying content is not scrolled by accident.
         */
        show: function() {
            this.base();

            $('html').addClass('noscroll');
        },

        /**
         * Allow the content to scroll.
         */
        hide: function() {
            this.removeAllListeners();
            this.straighteningInput.removeAllListeners();
            $('html').removeClass('noscroll');
            this.base();
        },

        /**
         * onSave callback.
         */
        onSave: function() {
            this.settings.onSave();
            this.trigger('save');
        },

        /**
         * Enable the rotation slider.
         */
        enableSlider: function() {
            this.$imageTools.removeClass('hidden');
        },

        /**
         * Disable the rotation slider.
         */
        disableSlider: function() {
            this.$imageTools.addClass('hidden');
        },

        /**
         * Switch to crop mode.
         */
        enableCropMode: function() {
            var imageDimensions = this.getScaledImageDimensions();
            this.zoomRatio = this.getZoomToFitRatio(imageDimensions);

            var viewportProperties = {
                width: this.editorWidth,
                height: this.editorHeight
            };

            var imageProperties = {
                width: imageDimensions.width * this.zoomRatio,
                height: imageDimensions.height * this.zoomRatio,
                left: this.editorWidth / 2,
                top: this.editorHeight / 2
            };

            var callback = function() {
                this._setFittedImageVerticeCoordinates();

                // Restore cropper
                var state = this.cropperState;
                var scaledImageDimensions = this.getScaledImageDimensions();
                var sizeFactor = scaledImageDimensions.width / state.imageDimensions.width;

                // Restore based on the stored information
                var cropperData = {
                    left: this.image.left + (state.offsetX * sizeFactor * this.zoomRatio),
                    top: this.image.top + (state.offsetY * sizeFactor * this.zoomRatio),
                    width: state.width * sizeFactor * this.zoomRatio,
                    height: state.height * sizeFactor * this.zoomRatio
                };

                this._showCropper(cropperData);

                if (this.focalPoint) {
                    sizeFactor = scaledImageDimensions.width / this.focalPointState.imageDimensions.width;
                    this.focalPoint.left = this.image.left + (this.focalPointState.offsetX * sizeFactor * this.zoomRatio);
                    this.focalPoint.top = this.image.top + (this.focalPointState.offsetY * sizeFactor * this.zoomRatio);
                    this.canvas.add(this.focalPoint);
                }
            }.bind(this);

            this._editorModeTransition(callback, imageProperties, viewportProperties);
        },

        /**
         * Switch out of crop mode.
         */
        disableCropMode: function() {
            var viewportProperties = {};

            this._hideCropper();
            var imageDimensions = this.getScaledImageDimensions();
            var targetZoom = this.getZoomToCoverRatio(imageDimensions) * this.scaleFactor;
            var inverseZoomFactor = targetZoom / this.zoomRatio;
            this.zoomRatio = targetZoom;

            var imageProperties = {
                width: imageDimensions.width * this.zoomRatio,
                height: imageDimensions.height * this.zoomRatio,
                left: this.editorWidth / 2,
                top: this.editorHeight / 2
            };

            var offsetX = this.clipper.left - this.image.left;
            var offsetY = this.clipper.top - this.image.top;

            var imageOffsetX = offsetX * inverseZoomFactor;
            var imageOffsetY = offsetY * inverseZoomFactor;
            imageProperties.left = (this.editorWidth / 2) - imageOffsetX;
            imageProperties.top = (this.editorHeight / 2) - imageOffsetY;

            // Calculate the cropper dimensions after all the zooming
            viewportProperties.height = this.clipper.height * inverseZoomFactor;
            viewportProperties.width = this.clipper.width * inverseZoomFactor;

            if (!this.focalPoint || (this.focalPoint && !this._isCenterInside(this.focalPoint, this.clipper))) {
                if (this.focalPoint) {
                    this.toggleFocalPoint();
                }

                this._resetFocalPointPosition();
            }

            var callback = function() {
                // Reposition focal point correctly
                if (this.focalPoint) {
                    var sizeFactor = this.getScaledImageDimensions().width / this.focalPointState.imageDimensions.width;
                    this.focalPoint.left = this.image.left + (this.focalPointState.offsetX * sizeFactor * this.zoomRatio);
                    this.focalPoint.top = this.image.top + (this.focalPointState.offsetY * sizeFactor * this.zoomRatio);
                    this.canvas.add(this.focalPoint);
                }
            }.bind(this);

            this._editorModeTransition(callback, imageProperties, viewportProperties);
        },

        /**
         * Transition between cropping end editor modes
         *
         * @param callback
         * @param imageProperties
         * @param viewportProperties
         * @private
         */
        _editorModeTransition: function (callback, imageProperties, viewportProperties) {
            if (!this.animationInProgress) {
                this.animationInProgress = true;

                // Without this it looks semi-broken during animation
                if (this.focalPoint) {
                    this.canvas.remove(this.focalPoint);
                    this.renderImage();
                }

                this.image.animate(imageProperties, {
                    onChange: this.canvas.renderAll.bind(this.canvas),
                    duration: this.settings.animationDuration,
                    onComplete: function() {
                        callback();
                        this.animationInProgress = false;
                        this.renderImage();
                    }.bind(this)
                });

                this.viewport.animate(viewportProperties, {
                    duration: this.settings.animationDuration
                });
            }
        },

        _showSpinner: function() {
            this.$spinnerCanvas = $('<canvas id="spinner-canvas"></canvas>').appendTo($('.image', this.$container));
            var canvas = document.getElementById('spinner-canvas');
            var context = canvas.getContext('2d');
            var start = new Date();
            var lines = 16,
                cW = context.canvas.width,
                cH = context.canvas.height;

            var draw = function() {
                var rotation = parseInt(((new Date() - start) / 1000) * lines) / lines;
                context.save();
                context.clearRect(0, 0, cW, cH);
                context.translate(cW / 2, cH / 2);
                context.rotate(Math.PI * 2 * rotation);
                for (var i = 0; i < lines; i++) {
                    context.beginPath();
                    context.rotate(Math.PI * 2 / lines);
                    context.moveTo(cW / 10, 0);
                    context.lineTo(cW / 4, 0);
                    context.lineWidth = cW / 30;
                    context.strokeStyle = "rgba(255,255,255," + i / lines + ")";
                    context.stroke();
                }
                context.restore();
            };
            this.spinnerInterval = window.setInterval(draw, 1000 / 30);
        },

        _hideSpinner: function () {
            window.clearInterval(this.spinnerInterval);
            this.$spinnerCanvas.remove();
            this.$spinnerCanvas = null;
        },

        /**
         * Show the cropper.
         *
         * @param clipperData
         */
        _showCropper: function(clipperData) {
            this._setupCropperLayer(clipperData);
            this._redrawCropperElements();
            this.renderCropper();
        },

        /**
         * Hide the cropper.
         */
        _hideCropper: function() {
            if (this.clipper) {
                this.croppingCanvas.remove(this.clipper);
                this.croppingCanvas.remove(this.croppingShade);
                this.croppingCanvas.remove(this.cropperHandles);
                this.croppingCanvas.remove(this.cropperGrid);
                this.croppingCanvas.remove(this.croppingRectangle);
                this.croppingCanvas.remove(this.croppingAreaText);

                this.croppingCanvas = null;
                this.renderCropper = null;
            }
        },

        /**
         * Draw the cropper.
         *
         * @param clipperData
         */
        _setupCropperLayer: function(clipperData) {
            // Set up the canvas for cropper
            this.croppingCanvas = new fabric.StaticCanvas('cropping-canvas', {
                backgroundColor: 'rgba(0,0,0,0)',
                hoverCursor: 'default',
                selection: false
            });

            this.croppingCanvas.setDimensions({
                width: this.editorWidth,
                height: this.editorHeight
            });

            this.renderCropper = function() {
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
                fill: 'rgba(0,0,0,0.7)'
            });

            // Calculate the cropping rectangle size
            var imageDimensions = this.getScaledImageDimensions();
            var rectangleRatio = this.imageStraightenAngle === 0 ? 1 : this.getCombinedZoomRatio(imageDimensions) * 1.2;
            var rectWidth = imageDimensions.width / rectangleRatio;
            var rectHeight = imageDimensions.height / rectangleRatio;

            if (this.hasOrientationChanged()) {
                var temp = rectHeight;
                rectHeight = rectWidth;
                rectWidth = temp;
            }

            // Set up the cropping viewport rectangle
            this.clipper = new fabric.Rect({
                left: this.editorWidth / 2,
                top: this.editorHeight / 2,
                originX: 'center',
                originY: 'center',
                width: rectWidth,
                height: rectHeight,
                stroke: 'black',
                fill: 'rgba(128,0,0,1)',
                strokeWidth: 0
            });

            // Set from clipper data
            if (clipperData) {
                this.clipper.set(clipperData);
            }

            this.clipper.globalCompositeOperation = 'destination-out';
            this.croppingCanvas.add(this.croppingShade);
            this.croppingCanvas.add(this.clipper);
        },

        /**
         * Redraw the cropper boundaries
         */
        _redrawCropperElements: function() {
            if (typeof this._redrawCropperElements._ === 'undefined') {
                this._redrawCropperElements._ = {};
            }

            if (this.cropperHandles) {
                this.croppingCanvas.remove(this.cropperHandles);
                this.croppingCanvas.remove(this.cropperGrid);
                this.croppingCanvas.remove(this.croppingRectangle);
                this.croppingCanvas.remove(this.croppingAreaText);
            }
            this._redrawCropperElements._.lineOptions = {
                strokeWidth: 4,
                stroke: 'rgb(255,255,255)',
                fill: false
            };

            this._redrawCropperElements._.gridOptions = {
                strokeWidth: 2,
                stroke: 'rgba(255,255,255,0.5)'
            };

            // Draw the handles
            this._redrawCropperElements._.pathGroup = [
                new fabric.Path('M 0,10 L 0,0 L 10,0', this._redrawCropperElements._.lineOptions),
                new fabric.Path('M ' + (this.clipper.width - 8) + ',0 L ' + (this.clipper.width + 4) + ',0 L ' + (this.clipper.width + 4) + ',10', this._redrawCropperElements._.lineOptions),
                new fabric.Path('M ' + (this.clipper.width + 4) + ',' + (this.clipper.height - 8) + ' L' + (this.clipper.width + 4) + ',' + (this.clipper.height + 4) + ' L ' + (this.clipper.width - 8) + ',' + (this.clipper.height + 4), this._redrawCropperElements._.lineOptions),
                new fabric.Path('M 10,' + (this.clipper.height + 4) + ' L 0,' + (this.clipper.height + 4) + ' L 0,' + (this.clipper.height - 8), this._redrawCropperElements._.lineOptions)
            ];

            this.cropperHandles = new fabric.Group(this._redrawCropperElements._.pathGroup, {
                left: this.clipper.left,
                top: this.clipper.top,
                originX: 'center',
                originY: 'center'
            });

            // Don't forget the rectangle
            this.croppingRectangle = new fabric.Rect({
                left: this.clipper.left,
                top: this.clipper.top,
                width: this.clipper.width,
                height: this.clipper.height,
                fill: 'rgba(0,0,0,0)',
                stroke: 'rgba(255,255,255,0.8)',
                strokeWidth: 2,
                originX: 'center',
                originY: 'center'
            });

            this.cropperGrid = new fabric.Group(
                [
                    new fabric.Line([this.clipper.width * 0.33, 0, this.clipper.width * 0.33, this.clipper.height], this._redrawCropperElements._.gridOptions),
                    new fabric.Line([this.clipper.width * 0.66, 0, this.clipper.width * 0.66, this.clipper.height], this._redrawCropperElements._.gridOptions),
                    new fabric.Line([0, this.clipper.height * 0.33, this.clipper.width, this.clipper.height * 0.33], this._redrawCropperElements._.gridOptions),
                    new fabric.Line([0, this.clipper.height * 0.66, this.clipper.width, this.clipper.height * 0.66], this._redrawCropperElements._.gridOptions)
                ], {
                    left: this.clipper.left,
                    top: this.clipper.top,
                    originX: 'center',
                    originY: 'center'
                }
            );

            this._redrawCropperElements._.cropTextTop = this.croppingRectangle.top + (this.clipper.height / 2) + 12;
            this._redrawCropperElements._.cropTextBackgroundColor = 'rgba(0,0,0,0)';

            if (this._redrawCropperElements._.cropTextTop + 12 > this.editorHeight - 2) {
                this._redrawCropperElements._.cropTextTop -= 24;
                this._redrawCropperElements._.cropTextBackgroundColor = 'rgba(0,0,0,0.5)';
            }

            this.croppingAreaText = new fabric.Textbox(Math.round(this.clipper.width) + ' x ' + Math.round(this.clipper.height), {
                left: this.croppingRectangle.left,
                top: this._redrawCropperElements._.cropTextTop,
                fontSize: 13,
                fill: 'rgb(200,200,200)',
                backgroundColor: this._redrawCropperElements._.cropTextBackgroundColor,
                font: 'Craft',
                width: 70,
                height: 15,
                originX: 'center',
                originY: 'center',
                textAlign: 'center'
            });

            this.croppingCanvas.add(this.cropperHandles);
            this.croppingCanvas.add(this.cropperGrid);
            this.croppingCanvas.add(this.croppingRectangle);
            this.croppingCanvas.add(this.croppingAreaText);
        },

        /**
         * Reposition the cropper when the image editor dimensions change.
         *
         * @param previousImageArea
         */
        _repositionCropper: function(previousImageArea) {
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
            var currentArea = this._getBoundingRectangle(this.imageVerticeCoords);
            var areaFactor = currentArea.width / previousImageArea.width;

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

            this._redrawCropperElements();
            this.renderCropper();
        },

        /**
         * Get the dimensions of a bounding rectangle by a set of four coordinates.
         *
         * @param coordinateSet
         */
        _getBoundingRectangle: function(coordinateSet) {
            return {
                width: Math.max(coordinateSet.a.x, coordinateSet.b.x, coordinateSet.c.x, coordinateSet.d.x) - Math.min(coordinateSet.a.x, coordinateSet.b.x, coordinateSet.c.x, coordinateSet.d.x),
                height: Math.max(coordinateSet.a.y, coordinateSet.b.y, coordinateSet.c.y, coordinateSet.d.y) - Math.min(coordinateSet.a.y, coordinateSet.b.y, coordinateSet.c.y, coordinateSet.d.y)
            };
        },

        /**
         * Handle the mouse being clicked.
         *
         * @param ev
         */
        _handleMouseDown: function(ev) {
            // Focal before resize before dragging
            var focal = this.focalPoint && this._isMouseOver(ev, this.focalPoint);
            var move = this.croppingCanvas && this._isMouseOver(ev, this.clipper);
            var handle = this.croppingCanvas && this._cropperHandleHitTest(ev);

            if (handle || move || focal) {
                this.previousMouseX = ev.pageX;
                this.previousMouseY = ev.pageY;

                if (focal) {
                    this.draggingFocal = true;
                } else if (handle) {
                    this.scalingCropper = handle;
                } else if (move) {
                    this.draggingCropper = true;
                }
            }
        },

        /**
         * Handle the mouse being moved.
         *
         * @param ev
         */
        _handleMouseMove: function(ev) {
            if (this.mouseMoveEvent !== null) {
                Garnish.requestAnimationFrame(this._handleMouseMoveInternal.bind(this));
            }
            this.mouseMoveEvent = ev;
        },

        _handleMouseMoveInternal: function() {
            if (this.mouseMoveEvent === null) {
                return;
            }

            if (this.focalPoint && this.draggingFocal) {
                this._handleFocalDrag(this.mouseMoveEvent);
                this.storeFocalPointState();
                this.renderImage();
            } else if (this.draggingCropper || this.scalingCropper) {
                if (this.draggingCropper) {
                    this._handleCropperDrag(this.mouseMoveEvent);
                } else {
                    this._handleCropperResize(this.mouseMoveEvent);
                }

                this._redrawCropperElements();

                this.storeCropperState();
                this.renderCropper();
            } else {
                this._setMouseCursor(this.mouseMoveEvent);
            }

            this.previousMouseX = this.mouseMoveEvent.pageX;
            this.previousMouseY = this.mouseMoveEvent.pageY;

            this.mouseMoveEvent = null;
        },

        /**
         * Handle mouse being released.
         *
         * @param ev
         */
        _handleMouseUp: function(ev) {
            this.draggingCropper = false;
            this.scalingCropper = false;
            this.draggingFocal = false;
        },

        /**
         * Handle mouse out
         *
         * @param ev
         */
        _handleMouseOut: function(ev) {
            this._handleMouseUp(ev);
            this.mouseMoveEvent = ev;
            this._handleMouseMoveInternal();
        },

        /**
         * Handle cropper being dragged.
         *
         * @param ev
         */
        _handleCropperDrag: function(ev) {
            if (typeof this._handleCropperDrag._ === 'undefined') {
                this._handleCropperDrag._ = {};
            }

            this._handleCropperDrag._.deltaX = ev.pageX - this.previousMouseX;
            this._handleCropperDrag._.deltaY = ev.pageY - this.previousMouseY;

            if (this._handleCropperDrag._.deltaX === 0 && this._handleCropperDrag._.deltaY === 0) {
                return false;
            }

            this._handleCropperDrag._.rectangle = {
                left: this.clipper.left - this.clipper.width / 2,
                top: this.clipper.top - this.clipper.height / 2,
                width: this.clipper.width,
                height: this.clipper.height
            };

            this._handleCropperDrag._.vertices = this._getRectangleVertices(this._handleCropperDrag._.rectangle, this._handleCropperDrag._.deltaX, this._handleCropperDrag._.deltaY);

            // If this would drag it outside of the image
            if (!this.arePointsInsideRectangle(this._handleCropperDrag._.vertices, this.imageVerticeCoords)) {
                // Try to find the furthest point in the same general direction where we can drag it

                // Delta iterator setup
                this._handleCropperDrag._.dxi = 0;
                this._handleCropperDrag._.dyi = 0;
                this._handleCropperDrag._.xStep = this._handleCropperDrag._.deltaX > 0 ? -1 : 1;
                this._handleCropperDrag._.yStep = this._handleCropperDrag._.deltaY > 0 ? -1 : 1;

                // The furthest we can move
                this._handleCropperDrag._.furthest = 0;
                this._handleCropperDrag._.furthestDeltas = {};

                // Loop through every combination of dragging it not so far
                for (this._handleCropperDrag._.dxi = Math.min(Math.abs(this._handleCropperDrag._.deltaX), 10); this._handleCropperDrag._.dxi >= 0; this._handleCropperDrag._.dxi--) {
                    for (this._handleCropperDrag._.dyi = Math.min(Math.abs(this._handleCropperDrag._.deltaY), 10); this._handleCropperDrag._.dyi >= 0; this._handleCropperDrag._.dyi--) {
                        this._handleCropperDrag._.vertices = this._getRectangleVertices(this._handleCropperDrag._.rectangle, this._handleCropperDrag._.dxi * (this._handleCropperDrag._.deltaX > 0 ? 1 : -1), this._handleCropperDrag._.dyi * (this._handleCropperDrag._.deltaY > 0 ? 1 : -1));

                        if (this.arePointsInsideRectangle(this._handleCropperDrag._.vertices, this.imageVerticeCoords)) {
                            if (this._handleCropperDrag._.dxi + this._handleCropperDrag._.dyi > this._handleCropperDrag._.furthest) {
                                this._handleCropperDrag._.furthest = this._handleCropperDrag._.dxi + this._handleCropperDrag._.dyi;
                                this._handleCropperDrag._.furthestDeltas = {
                                    x: this._handleCropperDrag._.dxi * (this._handleCropperDrag._.deltaX > 0 ? 1 : -1),
                                    y: this._handleCropperDrag._.dyi * (this._handleCropperDrag._.deltaY > 0 ? 1 : -1)
                                }
                            }
                        }
                    }
                }

                // REALLY can't drag along the cursor movement
                if (this._handleCropperDrag._.furthest == 0) {
                    return;
                } else {
                    this._handleCropperDrag._.deltaX = this._handleCropperDrag._.furthestDeltas.x;
                    this._handleCropperDrag._.deltaY = this._handleCropperDrag._.furthestDeltas.y;
                }
            }

            this.clipper.set({
                left: this.clipper.left + this._handleCropperDrag._.deltaX,
                top: this.clipper.top + this._handleCropperDrag._.deltaY
            });
        },

        /**
         * Handle focal point being dragged.
         *
         * @param ev
         */
        _handleFocalDrag: function(ev) {
            if (typeof this._handleFocalDrag._ === 'undefined') {
                this._handleFocalDrag._ = {};
            }

            if (this.focalPoint) {
                this._handleFocalDrag._.deltaX = ev.pageX - this.previousMouseX;
                this._handleFocalDrag._.deltaY = ev.pageY - this.previousMouseY;

                if (this._handleFocalDrag._.deltaX === 0 && this._handleFocalDrag._.deltaY === 0) {
                    return;
                }

                this._handleFocalDrag._.newX = this.focalPoint.left + this._handleFocalDrag._.deltaX;
                this._handleFocalDrag._.newY = this.focalPoint.top + this._handleFocalDrag._.deltaY;

                // Just make sure that the focal point stays inside the image
                if (this.currentView === 'crop') {
                    if (!this.arePointsInsideRectangle([{x: this._handleFocalDrag._.newX, y: this._handleFocalDrag._.newY}], this.imageVerticeCoords)) {
                        return;
                    }
                } else {
                    if (!(this.viewport.left - this.viewport.width / 2 - this._handleFocalDrag._.newX < 0 && this.viewport.left + this.viewport.width / 2 - this._handleFocalDrag._.newX > 0
                        && this.viewport.top - this.viewport.height / 2 - this._handleFocalDrag._.newY < 0 && this.viewport.top + this.viewport.height / 2 - this._handleFocalDrag._.newY > 0)) {
                        return;
                    }
                }

                this.focalPoint.set({
                    left: this.focalPoint.left + this._handleFocalDrag._.deltaX,
                    top: this.focalPoint.top + this._handleFocalDrag._.deltaY
                });
            }
        },

        /**
         * Set the cropping constraint
         * @param constraint
         */
        setCroppingConstraint: function(constraint) {
            // In case this caused the sidebar width to change.
            this.updateSizeAndPosition();

            switch (constraint) {
                case 'none':
                    this.croppingConstraint = false;
                    break;

                case 'original':
                    this.croppingConstraint = this.originalWidth / this.originalHeight;
                    break;

                case 'current':
                    this.croppingConstraint = this.clipper.width / this.clipper.height;
                    break;

                case 'custom':

                    break;
                default:
                    this.croppingConstraint = parseFloat(constraint);

                    break;
            }
        },

        /**
         * Enforce the cropping constraint
         */
        enforceCroppingConstraint: function () {
            if (typeof this.enforceCroppingConstraint._ === 'undefined') {
                this.enforceCroppingConstraint._ = {};
            }

            if (this.animationInProgress || !this.croppingConstraint) {
                return;
            }

            this.animationInProgress = true;

            // Mock the clipping rectangle for collision tests
            this.enforceCroppingConstraint._.rectangle = {
                left: this.clipper.left - this.clipper.width / 2,
                top: this.clipper.top - this.clipper.height / 2,
                width: this.clipper.width,
                height: this.clipper.height
            };

            // If wider than it should be
            if (this.clipper.width > this.clipper.height * this.croppingConstraint)
            {
                this.enforceCroppingConstraint._.previousHeight = this.enforceCroppingConstraint._.rectangle.height;

                // Make it taller!
                this.enforceCroppingConstraint._.rectangle.height = this.clipper.width / this.croppingConstraint;

                // Getting really awkward having to convert between 0;0 being center or top-left corner.
                this.enforceCroppingConstraint._.rectangle.top -= (this.enforceCroppingConstraint._.rectangle.height - this.enforceCroppingConstraint._.previousHeight) / 2;

                // If the clipper would end up out of bounds, make it narrower instead.
                if (!this.arePointsInsideRectangle(this._getRectangleVertices(this.enforceCroppingConstraint._.rectangle), this.imageVerticeCoords)) {
                    this.enforceCroppingConstraint._.rectangle.width = this.clipper.height * this.croppingConstraint;
                    this.enforceCroppingConstraint._.rectangle.height = this.enforceCroppingConstraint._.rectangle.width / this.croppingConstraint;
                }
            } else {
                // Follow the same pattern, if taller than it should be.
                this.enforceCroppingConstraint._.previousWidth = this.enforceCroppingConstraint._.rectangle.width;
                this.enforceCroppingConstraint._.rectangle.width = this.clipper.height * this.croppingConstraint;
                this.enforceCroppingConstraint._.rectangle.left -= (this.enforceCroppingConstraint._.rectangle.width - this.enforceCroppingConstraint._.previousWidth) / 2;

                if (!this.arePointsInsideRectangle(this._getRectangleVertices(this.enforceCroppingConstraint._.rectangle), this.imageVerticeCoords)) {
                    this.enforceCroppingConstraint._.rectangle.height = this.clipper.width / this.croppingConstraint;
                    this.enforceCroppingConstraint._.rectangle.width = this.enforceCroppingConstraint._.rectangle.height * this.croppingConstraint;
                }
            }

            this.enforceCroppingConstraint._.properties = {
                height: this.enforceCroppingConstraint._.rectangle.height,
                width: this.enforceCroppingConstraint._.rectangle.width
            };

            // Make sure to redraw cropper handles and gridlines when resizing
            this.clipper.animate(this.enforceCroppingConstraint._.properties, {
                onChange: function() {
                    this._redrawCropperElements();
                    this.croppingCanvas.renderAll();
                }.bind(this),
                duration: this.settings.animationDuration,
                onComplete: function() {
                    this._redrawCropperElements();
                    this.animationInProgress = false;
                    this.renderCropper();
                    this.storeCropperState();
                }.bind(this)
            });
        },

        /**
         * Handle cropper being resized.
         *
         * @param ev
         */
        _handleCropperResize: function(ev) {
            if (typeof this._handleCropperResize._ === 'undefined') {
                this._handleCropperResize._ = {};
            }

            // Size deltas
            this._handleCropperResize._.deltaX = ev.pageX - this.previousMouseX;
            this._handleCropperResize._.deltaY = ev.pageY - this.previousMouseY;

            if (this.scalingCropper === 'b' || this.scalingCropper === 't') {
                this._handleCropperResize._.deltaX = 0;
            }

            if (this.scalingCropper === 'l' || this.scalingCropper === 'r') {
                this._handleCropperResize._.deltaY = 0;
            }

            if (this._handleCropperResize._.deltaX === 0 && this._handleCropperResize._.deltaY === 0) {
                return;
            }

            // Translate from center-center origin to absolute coords
            this._handleCropperResize._.startingRectangle = {
                left: this.clipper.left - this.clipper.width / 2,
                top: this.clipper.top - this.clipper.height / 2,
                width: this.clipper.width,
                height: this.clipper.height
            }

            this._handleCropperResize._.rectangle = this._calculateNewCropperSizeByDeltas(this._handleCropperResize._.startingRectangle, this._handleCropperResize._.deltaX, this._handleCropperResize._.deltaY, this.scalingCropper);

            if (this._handleCropperResize._.rectangle.height < 30 || this._handleCropperResize._.rectangle.width < 30) {
                return;
            }

            if (!this.arePointsInsideRectangle(this._getRectangleVertices(this._handleCropperResize._.rectangle), this.imageVerticeCoords)) {
                return;
            }

            // Translate back to center-center origin.
            this.clipper.set({
                top: this._handleCropperResize._.rectangle.top + this._handleCropperResize._.rectangle.height / 2,
                left: this._handleCropperResize._.rectangle.left + this._handleCropperResize._.rectangle.width / 2,
                width: this._handleCropperResize._.rectangle.width,
                height: this._handleCropperResize._.rectangle.height
            });

            this._redrawCropperElements();
        },

        _calculateNewCropperSizeByDeltas: function (startingRectangle, deltaX, deltaY, cropperDirection) {
            if (typeof this._calculateNewCropperSizeByDeltas._ === 'undefined') {
                this._calculateNewCropperSizeByDeltas._ = {};
            }

            // Center deltas
            this._calculateNewCropperSizeByDeltas._.topDelta = 0;
            this._calculateNewCropperSizeByDeltas._.leftDelta = 0;

            this._calculateNewCropperSizeByDeltas._.rectangle = startingRectangle;
            this._calculateNewCropperSizeByDeltas._.deltaX = deltaX;
            this._calculateNewCropperSizeByDeltas._.deltaY = deltaY;

            // Lock the aspect ratio if needed
            if (this.croppingConstraint) {
                this._calculateNewCropperSizeByDeltas._.change = 0;

                // Take into account the mouse direction and figure out the "real" change in cropper size
                switch (cropperDirection) {
                    case 't':
                        this._calculateNewCropperSizeByDeltas._.change = -this._calculateNewCropperSizeByDeltas._.deltaY;
                        break;
                    case 'b':
                        this._calculateNewCropperSizeByDeltas._.change = this._calculateNewCropperSizeByDeltas._.deltaY;
                        break;
                    case 'r':
                        this._calculateNewCropperSizeByDeltas._.change = this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                    case 'l':
                        this._calculateNewCropperSizeByDeltas._.change = -this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                    case 'tr':
                        this._calculateNewCropperSizeByDeltas._.change = Math.abs(this._calculateNewCropperSizeByDeltas._.deltaY) > Math.abs(this._calculateNewCropperSizeByDeltas._.deltaX) ? -this._calculateNewCropperSizeByDeltas._.deltaY : this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                    case 'tl':
                        this._calculateNewCropperSizeByDeltas._.change = Math.abs(this._calculateNewCropperSizeByDeltas._.deltaY) > Math.abs(this._calculateNewCropperSizeByDeltas._.deltaX) ? -this._calculateNewCropperSizeByDeltas._.deltaY : -this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                    case 'br':
                        this._calculateNewCropperSizeByDeltas._.change = Math.abs(this._calculateNewCropperSizeByDeltas._.deltaY) > Math.abs(this._calculateNewCropperSizeByDeltas._.deltaX) ? this._calculateNewCropperSizeByDeltas._.deltaY : this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                    case 'bl':
                        this._calculateNewCropperSizeByDeltas._.change = Math.abs(this._calculateNewCropperSizeByDeltas._.deltaY) > Math.abs(this._calculateNewCropperSizeByDeltas._.deltaX) ? this._calculateNewCropperSizeByDeltas._.deltaY : -this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                }

                if (this.croppingConstraint > 1) {
                    this._calculateNewCropperSizeByDeltas._.deltaX = this._calculateNewCropperSizeByDeltas._.change;
                    this._calculateNewCropperSizeByDeltas._.deltaY = this._calculateNewCropperSizeByDeltas._.deltaX / this.croppingConstraint;
                } else {
                    this._calculateNewCropperSizeByDeltas._.deltaY = this._calculateNewCropperSizeByDeltas._.change;
                    this._calculateNewCropperSizeByDeltas._.deltaX = this._calculateNewCropperSizeByDeltas._.deltaY * this.croppingConstraint;
                }

                this._calculateNewCropperSizeByDeltas._.rectangle.height += this._calculateNewCropperSizeByDeltas._.deltaY;
                this._calculateNewCropperSizeByDeltas._.rectangle.width += this._calculateNewCropperSizeByDeltas._.deltaX;

                // Make the cropper compress/expand relative to the correct edge to make it feel "right"
                switch (cropperDirection) {
                    case 't':
                        this._calculateNewCropperSizeByDeltas._.rectangle.top -= this._calculateNewCropperSizeByDeltas._.deltaY;
                        this._calculateNewCropperSizeByDeltas._.rectangle.left -= this._calculateNewCropperSizeByDeltas._.deltaX / 2;
                        break;
                    case 'b':
                        this._calculateNewCropperSizeByDeltas._.rectangle.left += -this._calculateNewCropperSizeByDeltas._.deltaX / 2;
                        break;
                    case 'r':
                        this._calculateNewCropperSizeByDeltas._.rectangle.top += -this._calculateNewCropperSizeByDeltas._.deltaY / 2;
                        break;
                    case 'l':
                        this._calculateNewCropperSizeByDeltas._.rectangle.top -= this._calculateNewCropperSizeByDeltas._.deltaY / 2;
                        this._calculateNewCropperSizeByDeltas._.rectangle.left -= this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                    case 'tr':
                        this._calculateNewCropperSizeByDeltas._.rectangle.top -= this._calculateNewCropperSizeByDeltas._.deltaY;
                        break;
                    case 'tl':
                        this._calculateNewCropperSizeByDeltas._.rectangle.top -= this._calculateNewCropperSizeByDeltas._.deltaY;
                        this._calculateNewCropperSizeByDeltas._.rectangle.left -= this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                    case 'bl':
                        this._calculateNewCropperSizeByDeltas._.rectangle.left -= this._calculateNewCropperSizeByDeltas._.deltaX;
                        break;
                }
            } else {
                // Lock the aspect ratio
                if (this.shiftKeyHeld &&
                    (cropperDirection === 'tl' || cropperDirection === 'tr' ||
                        cropperDirection === 'bl' || cropperDirection === 'br')
                ) {
                    this._calculateNewCropperSizeByDeltas._.ratio;
                    if (Math.abs(deltaX) > Math.abs(deltaY)) {
                        this._calculateNewCropperSizeByDeltas._.ratio = startingRectangle.width / startingRectangle.height;
                        this._calculateNewCropperSizeByDeltas._.deltaY = this._calculateNewCropperSizeByDeltas._.deltaX / this._calculateNewCropperSizeByDeltas._.ratio;
                        this._calculateNewCropperSizeByDeltas._.deltaY *= (cropperDirection === 'tr' || cropperDirection === 'bl') ? -1 : 1;
                    } else {
                        this._calculateNewCropperSizeByDeltas._.ratio = startingRectangle.width / startingRectangle.height;
                        this._calculateNewCropperSizeByDeltas._.deltaX = this._calculateNewCropperSizeByDeltas._.deltaY * this._calculateNewCropperSizeByDeltas._.ratio;
                        this._calculateNewCropperSizeByDeltas._.deltaX *= (cropperDirection === 'tr' || cropperDirection === 'bl') ? -1 : 1;
                    }
                }

                if (cropperDirection.match(/t/)) {
                    this._calculateNewCropperSizeByDeltas._.rectangle.top += this._calculateNewCropperSizeByDeltas._.deltaY;
                    this._calculateNewCropperSizeByDeltas._.rectangle.height -= this._calculateNewCropperSizeByDeltas._.deltaY;
                }
                if (cropperDirection.match(/b/)) {
                    this._calculateNewCropperSizeByDeltas._.rectangle.height += this._calculateNewCropperSizeByDeltas._.deltaY;
                }
                if (cropperDirection.match(/r/)) {
                    this._calculateNewCropperSizeByDeltas._.rectangle.width += this._calculateNewCropperSizeByDeltas._.deltaX;
                }
                if (cropperDirection.match(/l/)) {
                    this._calculateNewCropperSizeByDeltas._.rectangle.left += this._calculateNewCropperSizeByDeltas._.deltaX;
                    this._calculateNewCropperSizeByDeltas._.rectangle.width -= this._calculateNewCropperSizeByDeltas._.deltaX;
                }
            }

            this._calculateNewCropperSizeByDeltas._.rectangle.top = this._calculateNewCropperSizeByDeltas._.rectangle.top;
            this._calculateNewCropperSizeByDeltas._.rectangle.left = this._calculateNewCropperSizeByDeltas._.rectangle.left;
            this._calculateNewCropperSizeByDeltas._.rectangle.width = this._calculateNewCropperSizeByDeltas._.rectangle.width;
            this._calculateNewCropperSizeByDeltas._.rectangle.height = this._calculateNewCropperSizeByDeltas._.rectangle.height;

            return this._calculateNewCropperSizeByDeltas._.rectangle;
        },

        /**
         * Set mouse cursor by it's position over cropper.
         *
         * @param ev
         */
        _setMouseCursor: function(ev) {
            if (typeof this._setMouseCursor._ === 'undefined') {
                this._setMouseCursor._ = {};
            }

            if (Garnish.isMobileBrowser(true)) {
                return;
            }
            this._setMouseCursor._.cursor = 'default';
            this._setMouseCursor._.handle = this.croppingCanvas && this._cropperHandleHitTest(ev);
            if (this.focalPoint && this._isMouseOver(ev, this.focalPoint)) {
                this._setMouseCursor._.cursor = 'pointer';
            } else if (this._setMouseCursor._.handle) {
                if (this._setMouseCursor._.handle === 't' || this._setMouseCursor._.handle === 'b') {
                    this._setMouseCursor._.cursor = 'ns-resize';
                } else if (this._setMouseCursor._.handle === 'l' || this._setMouseCursor._.handle === 'r') {
                    this._setMouseCursor._.cursor = 'ew-resize';
                } else if (this._setMouseCursor._.handle === 'tl' || this._setMouseCursor._.handle === 'br') {
                    this._setMouseCursor._.cursor = 'nwse-resize';
                } else if (this._setMouseCursor._.handle === 'bl' || this._setMouseCursor._.handle === 'tr') {
                    this._setMouseCursor._.cursor = 'nesw-resize';
                }
            } else if (this.croppingCanvas && this._isMouseOver(ev, this.clipper)) {
                this._setMouseCursor._.cursor = 'move';
            }

            $('.body').css('cursor', this._setMouseCursor._.cursor);
        },

        /**
         * Test whether the mouse cursor is on any cropper handles.
         *
         * @param ev
         */
        _cropperHandleHitTest: function(ev) {
            if (typeof this._cropperHandleHitTest._ === 'undefined') {
                this._cropperHandleHitTest._ = {};
            }

            this._cropperHandleHitTest._.parentOffset = this.$croppingCanvas.offset();
            this._cropperHandleHitTest._.mouseX = ev.pageX - this._cropperHandleHitTest._.parentOffset.left;
            this._cropperHandleHitTest._.mouseY = ev.pageY - this._cropperHandleHitTest._.parentOffset.top;

            // Compensate for center origin coordinate-wise
            this._cropperHandleHitTest._.lb = this.clipper.left - this.clipper.width / 2;
            this._cropperHandleHitTest._.rb = this._cropperHandleHitTest._.lb + this.clipper.width;
            this._cropperHandleHitTest._.tb = this.clipper.top - this.clipper.height / 2;
            this._cropperHandleHitTest._.bb = this._cropperHandleHitTest._.tb + this.clipper.height;

            // Left side top/bottom
            if (this._cropperHandleHitTest._.mouseX < this._cropperHandleHitTest._.lb + 10 && this._cropperHandleHitTest._.mouseX > this._cropperHandleHitTest._.lb - 3) {
                if (this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.tb + 10 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.tb - 3) {
                    return 'tl';
                } else if (this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.bb + 3 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.bb - 10) {
                    return 'bl';
                }
            }
            // Right side top/bottom
            if (this._cropperHandleHitTest._.mouseX > this._cropperHandleHitTest._.rb - 13 && this._cropperHandleHitTest._.mouseX < this._cropperHandleHitTest._.rb + 3) {
                if (this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.tb + 10 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.tb - 3) {
                    return 'tr';
                } else if (this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.bb + 2 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.bb - 10) {
                    return 'br';
                }
            }

            // Left or right
            if (this._cropperHandleHitTest._.mouseX < this._cropperHandleHitTest._.lb + 3 && this._cropperHandleHitTest._.mouseX > this._cropperHandleHitTest._.lb - 3 && this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.bb - 10 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.tb + 10) {
                return 'l';
            }
            if (this._cropperHandleHitTest._.mouseX < this._cropperHandleHitTest._.rb + 1 && this._cropperHandleHitTest._.mouseX > this._cropperHandleHitTest._.rb - 5 && this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.bb - 10 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.tb + 10) {
                return 'r';
            }

            // Top or bottom
            if (this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.tb + 4 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.tb - 2 && this._cropperHandleHitTest._.mouseX > this._cropperHandleHitTest._.lb + 10 && this._cropperHandleHitTest._.mouseX < this._cropperHandleHitTest._.rb - 10) {
                return 't';
            }
            if (this._cropperHandleHitTest._.mouseY < this._cropperHandleHitTest._.bb + 2 && this._cropperHandleHitTest._.mouseY > this._cropperHandleHitTest._.bb - 4 && this._cropperHandleHitTest._.mouseX > this._cropperHandleHitTest._.lb + 10 && this._cropperHandleHitTest._.mouseX < this._cropperHandleHitTest._.rb - 10) {
                return 'b';
            }

            return false;
        },

        /**
         * Test whether the mouse cursor is on a fabricJS object.
         *
         * @param object
         * @param event
         *
         * @return boolean
         */

        _isMouseOver: function(event, object) {
            if (typeof this._isMouseOver._ === 'undefined') {
                this._isMouseOver._ = {};
            }

            this._isMouseOver._.parentOffset = this.$croppingCanvas.offset();
            this._isMouseOver._.mouseX = event.pageX - this._isMouseOver._.parentOffset.left;
            this._isMouseOver._.mouseY = event.pageY - this._isMouseOver._.parentOffset.top;

            // Compensate for center origin coordinate-wise
            this._isMouseOver._.lb = object.left - object.width / 2;
            this._isMouseOver._.rb = this._isMouseOver._.lb + object.width;
            this._isMouseOver._.tb = object.top - object.height / 2;
            this._isMouseOver._.bb = this._isMouseOver._.tb + object.height;

            return (
                this._isMouseOver._.mouseX >= this._isMouseOver._.lb &&
                this._isMouseOver._.mouseX <= this._isMouseOver._.rb &&
                this._isMouseOver._.mouseY >= this._isMouseOver._.tb &&
                this._isMouseOver._.mouseY <= this._isMouseOver._.bb
            );
        },

        /**
         * Get vertices of a rectangle defined by left,top,height and width properties.
         * Optionally it's possible to provide offsetX and offsetY values.
         * Left and top properties of rectangle reference the top-left corner.
         *
         * @param rectangle
         * @param [offsetX]
         * @param [offsetY]
         */
        _getRectangleVertices: function(rectangle, offsetX, offsetY) {
            if (typeof this._getRectangleVertices._ === 'undefined') {
                this._getRectangleVertices._ = {};
            }

            if (typeof offsetX === 'undefined') {
                offsetX = 0;
            }
            if (typeof offsetY === 'undefined') {
                offsetY = 0;
            }

            this._getRectangleVertices._.topLeft = {
                x: rectangle.left + offsetX,
                y: rectangle.top + offsetY
            };

            this._getRectangleVertices._.topRight = {x: this._getRectangleVertices._.topLeft.x + rectangle.width, y: this._getRectangleVertices._.topLeft.y};
            this._getRectangleVertices._.bottomRight = {x: this._getRectangleVertices._.topRight.x, y: this._getRectangleVertices._.topRight.y + rectangle.height};
            this._getRectangleVertices._.bottomLeft = {x: this._getRectangleVertices._.topLeft.x, y: this._getRectangleVertices._.bottomRight.y};

            return [this._getRectangleVertices._.topLeft, this._getRectangleVertices._.topRight, this._getRectangleVertices._.bottomRight, this._getRectangleVertices._.bottomLeft];
        },

        /**
         * Set image vertice coordinates for an image that's been zoomed to fit.
         */
        _setFittedImageVerticeCoordinates: function() {
            this.imageVerticeCoords = this.getImageVerticeCoords('fit');
        },

        /**
         * Get image vertice coords by a zoom mode and taking into account the straightening angle.
         * The zoomMode can be either "cover", "fit" or a discrete float value.
         *
         * @param zoomMode
         */
        getImageVerticeCoords: function(zoomMode) {
            var angleInRadians = -1 * ((this.hasOrientationChanged() ? 90 : 0) + this.imageStraightenAngle) * (Math.PI / 180);

            var imageDimensions = this.getScaledImageDimensions();

            var ratio;

            if (typeof zoomMode === "number") {
                ratio = zoomMode;
            } else if (zoomMode === "cover") {
                ratio = this.getZoomToCoverRatio(imageDimensions);
            } else {
                ratio = this.getZoomToFitRatio(imageDimensions);
            }

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
            return {
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

        /**
         * Debug stuff by continuously rendering a fabric object on canvas.
         *
         * @param fabricObj
         */
        _debug: function(fabricObj) {
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
         * @param points
         * @param rectangle
         */
        arePointsInsideRectangle: function(points, rectangle) {
            if (typeof this.arePointsInsideRectangle._ === 'undefined') {
                this.arePointsInsideRectangle._ = {};
            }

            // Pre-calculate the vectors and scalar products for two rectangle edges
            this.arePointsInsideRectangle._.ab = this._getVector(rectangle.a, rectangle.b);
            this.arePointsInsideRectangle._.bc = this._getVector(rectangle.b, rectangle.c);
            this.arePointsInsideRectangle._.scalarAbAb = this._getScalarProduct(this.arePointsInsideRectangle._.ab, this.arePointsInsideRectangle._.ab);
            this.arePointsInsideRectangle._.scalarBcBc = this._getScalarProduct(this.arePointsInsideRectangle._.bc, this.arePointsInsideRectangle._.bc);

            for (this.arePointsInsideRectangle._.i = 0; this.arePointsInsideRectangle._.i < points.length; this.arePointsInsideRectangle._.i++) {
                this.arePointsInsideRectangle._.point = points[this.arePointsInsideRectangle._.i];

                // Calculate the vectors for two rectangle sides and for
                // the vector from vertices a and b to the point P
                this.arePointsInsideRectangle._.ap = this._getVector(rectangle.a, this.arePointsInsideRectangle._.point);
                this.arePointsInsideRectangle._.bp = this._getVector(rectangle.b, this.arePointsInsideRectangle._.point);

                // Calculate scalar or dot products for some vector combinations
                this.arePointsInsideRectangle._.scalarAbAp = this._getScalarProduct(this.arePointsInsideRectangle._.ab, this.arePointsInsideRectangle._.ap);
                this.arePointsInsideRectangle._.scalarBcBp = this._getScalarProduct(this.arePointsInsideRectangle._.bc, this.arePointsInsideRectangle._.bp);

                this.arePointsInsideRectangle._.projectsOnAB = 0 <= this.arePointsInsideRectangle._.scalarAbAp && this.arePointsInsideRectangle._.scalarAbAp <= this.arePointsInsideRectangle._.scalarAbAb;
                this.arePointsInsideRectangle._.projectsOnBC = 0 <= this.arePointsInsideRectangle._.scalarBcBp && this.arePointsInsideRectangle._.scalarBcBp <= this.arePointsInsideRectangle._.scalarBcBc;

                if (!(this.arePointsInsideRectangle._.projectsOnAB && this.arePointsInsideRectangle._.projectsOnBC)) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Returns an object representing the vector between points a and b.
         *
         * @param a
         * @param b
         */
        _getVector: function(a, b) {
            return {x: b.x - a.x, y: b.y - a.y};
        },

        /**
         * Returns the scalar product of two vectors
         *
         * @param a
         * @param b
         */
        _getScalarProduct: function(a, b) {
            return a.x * b.x + a.y * b.y;
        },

        /**
         * Returns the magnitude of a vector_redrawCropperElements
         * .
         *
         * @param vector
         */
        _getVectorMagnitude: function(vector) {
            return Math.sqrt(vector.x * vector.x + vector.y * vector.y);
        },

        /**
         * Returns the angle between two vectors in degrees with two decimal points
         *
         * @param a
         * @param b
         */
        _getAngleBetweenVectors: function(a, b) {
            return Math.round(Math.acos(Math.min(1, this._getScalarProduct(a, b) / (this._getVectorMagnitude(a) * this._getVectorMagnitude(b)))) * 180 / Math.PI * 100) / 100;
        },

        /**
         * Return the rectangle edge crossed by an imaginary line drawn from editor center to a vertex
         *
         * @param rectangle
         * @param vertex
         *
         * @returns {*}
         */
        _getEdgeCrossed: function(rectangle, vertex) {
            // Determine over which edge the vertex is
            var edgePoints = [
                [rectangle.a, rectangle.b],
                [rectangle.b, rectangle.c],
                [rectangle.c, rectangle.d],
                [rectangle.d, rectangle.a]
            ];

            var centerPoint = {x: this.editorWidth / 2, y: this.editorHeight / 2};
            var smallestDiff = 180;
            var edgeCrossed = null;

            // Test each edge
            for (var edgeIndex = 0; edgeIndex < edgePoints.length; edgeIndex++) {
                var edge = edgePoints[edgeIndex];
                var toCenter = this._getVector(edge[0], centerPoint);
                var edgeVector = this._getVector(edge[0], edge[1]);
                var toVertex = this._getVector(edge[0], vertex);

                // If the angle between toCenter/toVertex is the sum of
                // angles between edgeVector/toCenter and edgeVector/toVertex, it means that
                // the edgeVector is between the other two meaning that this is the offending vertex.
                // To avoid the rounding errors, we'll take the closest match
                var diff = Math.abs(this._getAngleBetweenVectors(toCenter, toVertex) - (this._getAngleBetweenVectors(toCenter, edgeVector) + this._getAngleBetweenVectors(edgeVector, toVertex)));

                if (diff < smallestDiff) {
                    smallestDiff = diff;
                    edgeCrossed = edge;
                }
            }

            return edgeCrossed;
        },

        /**
         * Get the image bounding box by image scaled dimensions, taking ingo account the straightening angle.
         *
         * @param dimensions
         */
        _getImageBoundingBox: function(dimensions) {
            var box = {};

            var angleInRadians = Math.abs(this.imageStraightenAngle) * (Math.PI / 180);

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
            allowDegreeFractions: true
        }
    }
);
