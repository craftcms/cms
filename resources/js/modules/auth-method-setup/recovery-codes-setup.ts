/**
 * RecoveryCodesSetup — drives the recovery-codes setup screen inside an
 * {@link AuthMethodSetupSlideout}: generates the codes on submit, shows them
 * in the success state, and offers a download. Booted by the
 * `RecoveryCodes/setup` template with the slideout's container id.
 */

import {Base} from '@craftcms/garnish';
import {Slideout} from '@/modules/slideout';
import type {AuthMethodSetupSlideout} from './auth-method-setup-slideout';

declare const Craft: any;
declare const $: any;

export class RecoveryCodesSetup extends Base {
  constructor(containerId?: string) {
    super();
    if (new.target === RecoveryCodesSetup) {
      this.init(containerId!);
    }
  }

  init(containerId: string): void {
    const slideout = Slideout.instances[containerId] as AuthMethodSetupSlideout;
    const button = slideout.$container.find('button.submit');

    this.addListener(button, 'activate', () => {
      button.addClass('loading');
      Craft.cp.announce(Craft.t('app', 'Loading'));

      Craft.sendActionRequest('post', 'auth/generate-recovery-codes')
        .then(({data}: any) => {
          slideout.showSuccess();
          Craft.authMethodSetup.refresh();

          const $pane = $('<div class="pane fullwidth mt-0"/>').appendTo(
            slideout.$container.find('.so-body')
          );
          const $ul = $(
            '<ul class="auth-method-recovery-codes-list"/>'
          ).appendTo($pane);

          for (const code of data.codes) {
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
            Craft.cp.announce(Craft.t('app', 'Loading'));

            const params = Craft.filterObject({
              [Craft.csrfTokenName]: Craft.csrfTokenValue,
            });

            Craft.downloadFromUrl(
              'post',
              Craft.getActionUrl('auth/download-recovery-codes'),
              params
            )
              .catch((error: any) => {
                Craft.cp.displayError(error?.response?.data?.message);
              })
              .finally(() => {
                $downloadBtn.removeClass('loading');
                Craft.cp.announce(Craft.t('app', 'Loading complete'));
              });
          });
        })
        .finally(() => {
          button.removeClass('loading');
          Craft.cp.announce(Craft.t('app', 'Loading complete'));
        });
    });
  }
}

export default RecoveryCodesSetup;
