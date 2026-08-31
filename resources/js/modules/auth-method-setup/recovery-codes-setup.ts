/**
 * RecoveryCodesSetup — drives the recovery-codes setup screen inside an
 * {@link AuthMethodSetupSlideout}: generates the codes on submit, shows them
 * in the success state, and offers a download. Booted by the
 * `RecoveryCodes/setup` template with the slideout's container id.
 */

import {t} from '@craftcms/ui';
import {html, LitElement, render} from 'lit';
import {property} from 'lit/decorators.js';

declare const Craft: any;

interface RecoveryCodeResponseData {
  codes: Array<string>;
  message: string;
}

export class RecoveryCodesSetup extends LitElement {
  form: HTMLFormElement | null = null;
  submitButton: HTMLElement | null = null;
  codes: string[] = [];

  @property({attribute: 'container-id'})
  containerId: string | undefined;

  override connectedCallback() {
    super.connectedCallback();

    this.form = this.querySelector('form');
    if (!this.form) {
      console.warn(
        '<craft-recovery-codes-setup/> must wrap a <form/> element.'
      );
      return;
    }

    this.form.addEventListener('submit', this.handleSubmit);
    this.submitButton = this.form.querySelector('[type="submit"]');
  }

  handleSubmit = async (e: Event) => {
    e.preventDefault();

    this.submitButton?.setAttribute('loading', 'true');
    Craft.cp.announce(t('Loading'));

    if (!(e.target instanceof HTMLFormElement)) {
      throw new Error('Recovery code setup must be submitted by a form.');
    }
    const form = e.target;

    try {
      const response = await fetch(form.getAttribute('action')!, {
        method: 'POST',
        body: JSON.stringify(new FormData(form)),
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();
      this.handleSuccess(data);
    } catch (error) {
      // Craft.cp.displayError(error?.response?.data?.message);
      console.error({error});
    } finally {
      this.submitButton?.removeAttribute('loading');
      Craft.cp.announce(Craft.t('app', 'Loading complete'));
    }
  };

  handleSuccess(data: RecoveryCodeResponseData) {
    this.form?.remove();
    render(this.successTemplate(data), this);
  }

  successTemplate = ({codes, message}: RecoveryCodeResponseData) => {
    return html`
      <div class="grid gap-6 justify-items-center flex-1">
        <div class="grid justify-items-center gap-2">
          <craft-icon
            name="circle-check"
            data-color="success"
            style="font-size: 36px"
          ></craft-icon>
          <h1 class="auth-method-setup-success-message" tabindex="-1">
            ${message}
          </h1>
        </div>
        <craft-pane class="w-3/4">
          <div class="grid gap-4">
            <ul class="text-center font-mono">
              ${codes.map((code) => html`<li>${code}</li>`)}
            </ul>

            <hr />
            <div class="flex justify-center">
              <craft-button
                type="button"
                icon="download"
                .action="${{
                  type: 'download',
                  method: 'POST',
                  url: 'auth/download-recovery-codes',
                }}"
              >
                ${t('Download Codes')}
              </craft-button>
            </div>
          </div>
        </craft-pane>
      </div>
    `;
  };

  protected override createRenderRoot() {
    return this;
  }

  // init(containerId: string): void {
  //   const slideout = Slideout.instances[containerId] as AuthMethodSetupSlideout;
  //   const button = slideout.$container.find('button.submit');
  //
  //   this.addListener(button, 'activate', () => {
  //     button.addClass('loading');
  //     Craft.cp.announce(Craft.t('app', 'Loading'));
  //
  //     Craft.sendActionRequest('post', 'auth/generate-recovery-codes')
  //       .then(({data}: any) => {
  //         slideout.showSuccess();
  //         Craft.authMethodSetup.refresh();
  //
  //         const $pane = $('<div class="pane fullwidth mt-0"/>').appendTo(
  //           slideout.$container.find('.so-body')
  //         );
  //         const $ul = $(
  //           '<ul class="auth-method-recovery-codes-list"/>'
  //         ).appendTo($pane);
  //
  //         for (const code of data.codes) {
  //           $('<li/>').text(code).appendTo($ul);
  //         }
  //
  //         $('<hr/>').appendTo($pane);
  //
  //         const $downloadContainer = $(
  //           '<div class="auth-method-recovery-codes-download"/>'
  //         ).appendTo($pane);
  //         const $downloadBtn = Craft.ui
  //           .createButton({
  //             label: Craft.t('app', 'Download codes'),
  //             spinner: true,
  //           })
  //           .attr('data-icon', 'download')
  //           .appendTo($downloadContainer);
  //
  //         this.addListener($downloadBtn, 'activate', () => {
  //           $downloadBtn.addClass('loading');
  //           Craft.cp.announce(Craft.t('app', 'Loading'));
  //
  //           const params = Craft.filterObject({
  //             [Craft.csrfTokenName]: Craft.csrfTokenValue,
  //           });
  //
  //           Craft.downloadFromUrl(
  //             'post',
  //             Craft.getActionUrl('auth/download-recovery-codes'),
  //             params
  //           )
  //             .catch((error: any) => {
  //               Craft.cp.displayError(error?.response?.data?.message);
  //             })
  //             .finally(() => {
  //               $downloadBtn.removeClass('loading');
  //               Craft.cp.announce(Craft.t('app', 'Loading complete'));
  //             });
  //         });
  //       })
  //       .finally(() => {
  //         button.removeClass('loading');
  //         Craft.cp.announce(Craft.t('app', 'Loading complete'));
  //       });
  //   });
  // }
}

if (!customElements.get('craft-recovery-codes-setup')) {
  customElements.define('craft-recovery-codes-setup', RecoveryCodesSetup);
}

export default RecoveryCodesSetup;
