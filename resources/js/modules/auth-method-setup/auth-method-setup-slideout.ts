/**
 * AuthMethodSetupSlideout — a {@link Slideout} hosting an auth method's setup
 * flow (TOTP, passkeys, recovery codes). Constructed by
 * {@link AuthMethodSetup} with the `auth/method-setup-html` response data;
 * the setup templates reach it afterwards through
 * `Craft.Slideout.instances[containerId]` to call {@link showSuccess}.
 */

import {Slideout} from '@/modules/slideout';

declare const Craft: any;

export interface AuthMethodSetupSlideoutData {
  /** The auth method's display name, used in the success message. */
  methodName: string;
  /** The setup screen's HTML. */
  html: string;
  /** Container id, under which the slideout registers in `Slideout.instances`. */
  containerId: string;
}

export class AuthMethodSetupSlideout extends Slideout {
  methodName: string | null = null;

  constructor(data?: AuthMethodSetupSlideoutData) {
    super();
    if (new.target === AuthMethodSetupSlideout) {
      this.initSetup(data!);
    }
  }

  private initSetup(data: AuthMethodSetupSlideoutData): void {
    this.methodName = data.methodName;

    const contents = `
<div class="slideout__body">${data.html}</div>
<div class="slideout__footer">
  <div class="flex-grow"></div>
  <div class="flex flex-nowrap">
    <craft-button type="button" class="auth-method-close-btn">${Craft.t(
      'app',
      'Cancel'
    )}</craft-button>
  </div>
</div>
`;

    super.init(contents, {
      containerAttributes: {
        id: data.containerId,
      },
    });

    // Add alt text to the QR code image
    const $qrCodeImg = this.$container.find('[id*="qr-code-wrapper"] svg');

    if ($qrCodeImg.length) {
      $qrCodeImg.attr({
        role: 'img',
        'aria-label': Craft.t('app', 'QR Code'),
      });
    }
  }

  /**
   * Swap the body for a "{name} added successfully." confirmation and turn
   * the Cancel button into a Close button.
   */
  showSuccess(): void {
    const message = Craft.t('app', '{name} added successfully.', {
      name: this.methodName,
    });
    this.$container.find('.so-body').addClass('auth-method-setup-success')
      .html(`
        <craft-icon name="check"></craft-icon>
        <h1 class="auth-method-setup-success-message" tabindex="-1">${message}</h1>
      `);

    this.$container.find('.auth-method-setup-success-message').focus();
    this.$container
      .find('.auth-method-close-btn')
      .text(Craft.t('app', 'Close'));
  }
}

export default AuthMethodSetupSlideout;
