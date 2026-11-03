/**
 * AuthMethodSetupSlideout — a {@link Slideout} hosting an auth method's setup
 * flow (TOTP, passkeys, recovery codes). Constructed by
 * {@link AuthMethodSetup} with the `auth/method-setup-html` response data;
 * the setup templates reach it afterwards through
 * `Craft.Slideout.instances[containerId]` to call {@link showSuccess}.
 */

import {Slideout} from '@/modules/slideout';
import {t} from '@craftcms/ui/utilities/translate';

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
    <craft-button type="button" class="auth-method-close-btn">${t('Cancel')}</craft-button>
  </div>
</div>
`;

    super.init(contents, {
      containerAttributes: {
        id: data.containerId,
      },
    });

    // Add alt text to the QR code image
    for (const qrCode of this.container!.querySelectorAll(
      '[id*="qr-code-wrapper"] svg'
    )) {
      qrCode.setAttribute('role', 'img');
      qrCode.setAttribute('aria-label', t('QR Code'));
    }
  }

  /**
   * Swap the body for a "{name} added successfully." confirmation and turn
   * the Cancel button into a Close button.
   */
  showSuccess(): void {
    const message = t('{name} added successfully.', {
      name: this.methodName,
    });
    const container = this.container!;
    for (const body of container.querySelectorAll('.so-body')) {
      body.classList.add('auth-method-setup-success');
      body.innerHTML = `
        <craft-icon name="check"></craft-icon>
        <h1 class="auth-method-setup-success-message" tabindex="-1">${message}</h1>
      `;
    }

    container
      .querySelector<HTMLElement>('.auth-method-setup-success-message')
      ?.focus();
    for (const button of container.querySelectorAll('.auth-method-close-btn')) {
      button.textContent = t('Close');
    }
  }
}

export default AuthMethodSetupSlideout;
