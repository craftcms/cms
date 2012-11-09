(function($) {

    var $modalContainerDiv = $('<div class="modal profile-image-modal"></div>').appendTo(Blocks.$body);

    /**
     * File Manager
     */
    var ProfileImageHandler = Blocks.Base.extend({

        userId: null,
        modal: null,

        init: function() {
            var element = $('.user-photo-controls .upload-photo');
            var options = {
                element:    element[0],
                action:     Blocks.getActionUrl('users/uploadUserPhoto'),
                params:     {userId: $('.user-photo').attr('data-user')},
                multiple:   false,
                onComplete: function (fileId, fileName, response) {
                    if (response.html) {
                        $modalContainerDiv.empty().append(response.html);
                        if (!this.modal) {
                            this.modal = new ProfileImageModal();
                            this.modal.userId = $('.user-photo').attr('data-user');
                            this.modal.setContainer($modalContainerDiv);
                        }

                        var modal = this.modal;


                        modal.bindButtons();
                        modal.addListener(modal.$saveBtn, 'click', 'saveImage');
                        modal.addListener(modal.$cancelBtn, 'click', 'cancel');

                        modal.show();
                        modal.removeListener(Blocks.ui.Modal.$shade, 'click');


                        $modalContainerDiv.find('img').load(function () {
                            var profileTool = new ProfileImageAreaTool($modalContainerDiv);
                            profileTool.showArea(modal);
                        });
                    }
                },
                allowedExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                template: '<div class="QqUploader-uploader"><div class="QqUploader-upload-drop-area" style="display: none; "><span>Drop files here to upload</span></div><div class="QqUploader-upload-button" style="position: relative; overflow: hidden; direction: ltr; ">' +
                             element.text() +
                            '<input type="file" name="file" style="position: absolute; right: 0px; top: 0px; font-family: Arial; font-size: 118px; margin: 0px; padding: 0px; cursor: pointer; opacity: 0; "></div><ul class="QqUploader-upload-list"></ul></div>'

            };

            if (typeof maxUploadSize != "undefined") {
                options.sizeLimit = maxUploadSize;
            }
            this.uploader = new qqUploader.FileUploader(options);

            $('.user-photo-controls .delete-photo').click(function () {
                if (confirm(Blocks.t('Are you sure you want to delete this photo?'))) {
                    $(this).parent().append('<div class="blocking-modal"></div>');
                    Blocks.postActionRequest('users/deleteUserPhoto', {userId: $('.user-photo').attr('data-user')}, $.proxy(function (response){
                        location.reload();
                    }, this));

                }
            });
        }
    }),

    ProfileImageModal = Blocks.ui.Modal.extend({

        $container: null,
        $saveBtn: null,
        $cancelBtn: null,

        areaSelect: null,
        factor: null,
        source: null,
        userId: null,


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
                source: this.source,
                userId: this.userId
            };

            Blocks.postActionRequest('users/cropUserPhoto', params, $.proxy(function (response) {

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

            this.$container.find('.crop-profile-image').fadeTo(50, 0.5);
        }

    }),

    ProfileImageAreaTool = Blocks.Base.extend({

        $container: null,

        init: function($container) {
            this.$container = $container;
        },

        showArea: function(referenceObject) {
            var $target = this.$container.find('img');

            var squareSize = Math.min($target.width(), $target.height());

            var areaOptions = {
                aspectRatio: "1:1",
                maxHeight: squareSize,
                maxWidth: squareSize,
                instance: true,
                resizable: true,
                show: true,
                persistent: true,
                handles: true

            };

            var areaSelect = $target.imgAreaSelect(areaOptions);
            areaSelect.setSelection(
                Math.round(($target.width() - squareSize) / 2),
                Math.round(($target.height() - squareSize) / 2),
                Math.round(($target.width() - squareSize) / 2) + squareSize,
                Math.round(($target.height() - squareSize) / 2) + squareSize);
            areaSelect.update();

            referenceObject.areaSelect = areaSelect;
            referenceObject.factor = $target.attr('data-factor');
            referenceObject.source = $target.attr('src').split('/').pop();
        }
    });

    new ProfileImageHandler();
})(jQuery);
