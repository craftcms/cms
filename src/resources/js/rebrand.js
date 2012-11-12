(function($) {

    var $modalContainerDiv = $('<div class="modal logo-modal"></div>').appendTo(Blocks.$body);

    /**
     * File Manager
     */
    var LogoHandler = Blocks.Base.extend({

        modal: null,

        init: function() {
            var element = $('.logo-controls .upload-logo');
            var options = {
                element:    element[0],
                action:     Blocks.actionUrl + '/rebrand/uploadLogo',
                multiple:   false,
                onComplete: function (fileId, fileName, response) {
                    if (response.html) {
                        $modalContainerDiv.empty().append(response.html);
                        if (!this.modal) {
                            this.modal = new LogoModal();
                            this.modal.setContainer($modalContainerDiv);
                        }

                        var modal = this.modal;

                        modal.bindButtons();
                        modal.addListener(modal.$saveBtn, 'click', 'saveImage');
                        modal.addListener(modal.$cancelBtn, 'click', 'cancel');

                        modal.show();
                        modal.removeListener(Blocks.ui.Modal.$shade, 'click');

                        $modalContainerDiv.find('img').load(function () {
                            var logoTool = new LogoImageAreaTool($modalContainerDiv);
                            logoTool.showArea(modal);
                        });
                    }
                },
                allowedExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                template: '<div class="QqUploader-uploader"><div class="QqUploader-upload-drop-area" style="display: none; "><span>Drop files here to upload</span></div><div class="QqUploader-upload-button" style="position: relative; overflow: hidden; direction: ltr; ">' +
                             element.text() +
                            '<input type="file" name="file" style="position: absolute; right: 0px; top: 0px; font-family: Arial; font-size: 118px; margin: 0px; padding: 0px; cursor: pointer; opacity: 0; "></div><ul class="QqUploader-upload-list"></ul></div>'

            };

            options.sizeLimit = Blocks.maxUploadSize;

            this.uploader = new qqUploader.FileUploader(options);

            $('.logo-controls .delete-logo').click(function () {
                if (confirm(Blocks.t('Are you sure you want to delete the logo?'))) {
                    $(this).parent().append('<div class="blocking-modal"></div>');
                    Blocks.postActionRequest('rebrand/deleteLogo', {}, $.proxy(function (response){
                        location.reload();
                    }, this));

                }
            });
        }
    }),

    LogoModal = Blocks.ui.Modal.extend({

        $container: null,
        $saveBtn: null,
        $cancelBtn: null,

        areaSelect: null,
        factor: null,
        source: null,

        init: function() {
            this.base();
        },

        bindButtons: function () {
            this.$saveBtn = this.$container.find('.submit:first');
            this.$cancelBtn = this.$container.find('.cancel:first');
        },

        cancel: function () {
            this.hide();
            this.areaSelect.setOptions({remove: true, hide: true, disable: true});
            this.$container.empty();
        },

        saveImage: function () {

            var selection = this.areaSelect.getSelection();
            var params = {
                x1: Math.round(selection.x1 / this.factor),
                x2: Math.round(selection.x2 / this.factor),
                y1: Math.round(selection.y1 / this.factor),
                y2: Math.round(selection.y2 / this.factor),
                source: this.source
            };

            Blocks.postActionRequest('rebrand/cropLogo', params, $.proxy(function (response) {

                if (response.error)
                {
                    alert(response.error);
                }
                else
                {
                    location.reload();
                }

                this.hide();
                this.$container.empty();
                this.areaSelect.setOptions({remove: true, hide: true, disable: true});


            }, this));

            this.areaSelect.setOptions({disable: true});
            this.removeListener(this.$saveBtn, 'click');
            this.removeListener(this.$cancelBtn, 'click');

            this.$container.find('.crop-logo').fadeTo(50, 0.5);
        }

    }),

    LogoImageAreaTool = Blocks.Base.extend({

        $container: null,

        init: function($container) {
            this.$container = $container;
        },

        showArea: function(referenceObject) {
            var $target = this.$container.find('img');

            var areaOptions = {
                aspectRatio: "3:1",
                maxHeight: $target.height(),
                maxWidth: $target.width(),
                instance: true,
                resizable: true,
                show: true,
                persistent: true,
                handles: true

            };

            var areaSelect = $target.imgAreaSelect(areaOptions);
            areaSelect.setSelection(0, 0, 300, 100);
            areaSelect.update();

            referenceObject.areaSelect = areaSelect;
            referenceObject.factor = $target.attr('data-factor');
            referenceObject.source = $target.attr('src').split('/').pop();
        }
    });

    new LogoHandler();
})(jQuery);
