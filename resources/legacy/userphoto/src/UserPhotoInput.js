import './user-photo-input.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.UserPhotoInput = Garnish.Base.extend(
    {
      userId: null,
      imageUpload: null,

      init(userId, containerSelector, settings) {
        this.userId = userId;
        this.setSettings(settings, Craft.UserPhotoInput.defaults);

        let uploadSettings = {
          postParameters: {
            userId: this.userId,
          },
          containerSelector: containerSelector,
          uploadAction: 'users/upload-user-photo',
          deleteAction: 'users/delete-user-photo',
          uploadButtonSelector: '.btn.upload-photo',
          deleteButtonSelector: '.btn.delete-photo',
          fileInputSelector: 'input[type=file]',
          uploadParamName: 'photo',

          onAfterRefreshImage: (response) => {
            if (typeof response.html !== 'undefined') {
              Craft.refreshElementInstances(this.userId);
              if (response.headerPhotoHtml) {
                const $headerPhotos = $('.header-photo');
                for (let i = 0; i < $headerPhotos.length; i++) {
                  const $headerPhoto = $(response.headerPhotoHtml);
                  $headerPhotos.eq(i).replaceWith($headerPhoto);
                  Craft.cp.elementThumbLoader.load($headerPhoto);
                }
              }
              this.initImageEditor();
            }
          },
        };

        this.imageUpload = new Craft.ImageUpload(uploadSettings);
        this.initImageEditor();
      },

      initImageEditor() {
        this.addListener(
          this.imageUpload.$container.find('.edit-photo'),
          'click',
          (ev) => {
            new Craft.AssetImageEditor($(ev.currentTarget).data('photoid'), {
              allowSavingAsNew: false,
              onSave: () => {
                Craft.sendActionRequest('POST', 'users/render-photo-input', {
                  data: {
                    userId: this.userId,
                  },
                }).then(({data}) => {
                  this.imageUpload.refreshImage(data);
                });
              },
            });
          }
        );
      },
    },
    {
      defaults: {
        isCurrentUser: false,
      },
    }
  );
})(jQuery);
