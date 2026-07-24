/**
 * AuthMethodSetup — manages the `#auth-method-setup` method listing (the
 * user security screen and the 2FA setup screen): wires each method's Set up
 * button, opens the setup slideout behind an elevated-session check, and
 * refreshes the listing after a method is added or removed. See the module
 * README for usage.
 */

import {browserSupportsWebAuthn} from '@simplewebauthn/browser';
import {Base, type GarnishBaseSettings} from '@craftcms/garnish';
import {AuthMethodSetupSlideout} from './auth-method-setup-slideout';

declare const Craft: any;
declare const $: any;

export interface AuthMethodSetupSettings extends GarnishBaseSettings {
  /** Called after {@link AuthMethodSetup.refresh} re-renders the listing. */
  onRefresh: () => void;
}

/**
 * Boots the auth-method listing at `#auth-method-setup`. Templates construct
 * it and keep the instance at `Craft.authMethodSetup` so the setup screens
 * can call {@link refresh} after completing a flow.
 *
 * @fires refresh - After the listing has been re-fetched and re-rendered.
 */
export class AuthMethodSetup extends Base<AuthMethodSetupSettings> {
  static defaults: AuthMethodSetupSettings = {
    onRefresh: () => {},
  };

  /** The setup slideout class (`Craft.AuthMethodSetup.Slideout`). */
  static Slideout = AuthMethodSetupSlideout;

  /** Method listing containers, keyed by their `data-method` attribute. */
  methodListings: Record<string, HTMLElement> | null = null;

  showingSlideout = false;

  constructor(settings?: Partial<AuthMethodSetupSettings>) {
    super();
    if (new.target === AuthMethodSetup) {
      this.init(settings);
    }
  }

  init(settings?: Partial<AuthMethodSetupSettings>): void {
    this.setSettings(settings, AuthMethodSetup.defaults);
    this.initUi();
  }

  initUi(): void {
    const listings: Record<string, HTMLElement> = Object.fromEntries(
      Array.from(
        document.querySelectorAll<HTMLElement>(
          '#auth-method-setup .auth-method'
        )
      ).map((container) => [container.getAttribute('data-method')!, container])
    );
    this.methodListings = listings;

    for (const container of Object.values(listings)) {
      this.initListing(container);
    }
  }

  initListing(container: HTMLElement): void {
    const setupBtn = container.querySelector('.auth-method-setup-btn');
    this.addListener(setupBtn, 'activate', () => {
      const method = container.getAttribute('data-method')!;
      this.showSetupSlideout(method);
    });
  }

  focusMethodButton(method: string): void {
    this.methodListings![method]!.querySelector('button')?.focus();
  }

  /**
   * Fetch the method's setup screen and open it in an
   * {@link AuthMethodSetupSlideout}, behind an elevated-session requirement.
   */
  showSetupSlideout(method: string): void {
    if (this.showingSlideout) {
      return;
    }
    const button = this.methodListings![method]!.querySelector(
      '.auth-method-setup-btn'
    )!;
    if (button.classList.contains('loading')) {
      return;
    }

    button.classList.add('loading');
    Craft.cp.announce(Craft.t('app', 'Loading'));

    Craft.elevatedSessionManager.requireElevatedSession(
      () => {
        Craft.sendActionRequest('POST', 'auth/method-setup-html', {
          data: {method},
        })
          .then(async ({data}: any) => {
            this.showingSlideout = true;
            const slideout = new AuthMethodSetupSlideout(data);
            slideout.on('close', () => {
              this.showingSlideout = false;
            });
            await Craft.appendHeadHtml(data.headHtml);
            await Craft.appendBodyHtml(data.bodyHtml);
            this.addListener(
              slideout.$container.find('.auth-method-close-btn'),
              'activate',
              () => {
                slideout.close();
                this.focusMethodButton(method);
              }
            );

            if (
              data.selectedMethod === 'craft\\auth\\passkeys\\type\\WebAuthn' &&
              browserSupportsWebAuthn()
            ) {
              new Craft.WebAuthnSetup(slideout, this.settings);
            }
          })
          .catch(({response}: any) => {
            Craft.cp.displayError(response.data.message);
          })
          .finally(() => {
            button.classList.remove('loading');
          });
      },
      () => {
        button.classList.remove('loading');
      },
      // Re-request an elevated session even if the user just logged in, so
      // they have the full elevated-session duration (capped at 5 minutes)
      // to complete the setup.
      Math.min(Craft.elevatedSessionDuration, 300)
    );
  }

  /** Re-fetch and re-render the method listing, then re-wire it. */
  refresh(): void {
    Craft.sendActionRequest('POST', 'auth/method-listing-html').then(
      async ({data}: any) => {
        // Release the outgoing listing's button listeners before replacing
        // the markup below.
        for (const container of Object.values(this.methodListings ?? {})) {
          this.removeAllListeners(
            container.querySelector('.auth-method-setup-btn')
          );
        }

        const $container = $('#auth-method-setup').html(
          $(data.html).children()
        );
        Craft.initUiElements($container);
        await Craft.appendHeadHtml(data.headHtml);
        await Craft.appendBodyHtml(data.bodyHtml);
        this.initUi();
        this.settings!.onRefresh();
        this.trigger('refresh');
      }
    );
  }
}

export default AuthMethodSetup;
