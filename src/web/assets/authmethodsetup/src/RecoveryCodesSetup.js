/** global: Craft */
/** global: Garnish */
Craft.RecoveryCodesSetup = Garnish.Base.extend({
  init(containerId) {
    const slideout = Craft.Slideout.instances[containerId];
    const button = slideout.$container.find('button.submit');

    button.on('activate', () => {
      button.addClass('loading');

      Craft.sendActionRequest('post', 'auth/generate-recovery-codes')
        .then(({data}) => {
          slideout.showSuccess();
          Craft.authMethodSetup.refresh();

          const $pane = $('<div class="pane fullwidth mt-0"/>').appendTo(
            slideout.$container.find('.so-body')
          );
          const $ul = $(
            '<ul class="auth-method-recovery-codes-list"/>'
          ).appendTo($pane);

          for (let code of data.codes) {
            $('<li/>').text(code).appendTo($ul);
          }

          $('<hr/>').appendTo($pane);

          const $downloadContainer = $(
            '<div class="auth-method-recovery-codes-download"/>'
          ).appendTo($pane);
          const $downloadBtn = Craft.ui
            .createButton({
              label: Craft.t('app', 'Download codes'),
              spinner: true,
            })
            .attr('data-icon', 'download')
            .appendTo($downloadContainer);

          this.addListener($downloadBtn, 'activate', () => {
            $downloadBtn.addClass('loading');

            const data = Craft.filterObject({
              [Craft.csrfTokenName]: Craft.csrfTokenValue,
            });

            Craft.downloadFromUrl(
              'post',
              Craft.getActionUrl('auth/download-recovery-codes'),
              data
            )
              .catch((error) => {
                Craft.cp.displayError(
                  error &&
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                );
              })
              .finally(() => {
                $downloadBtn.removeClass('loading');
              });
          });
        })
        .finally(() => {
          button.removeClass('loading');
        });
    });
  },
});
