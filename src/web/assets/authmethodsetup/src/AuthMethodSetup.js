import {browserSupportsWebAuthn} from '@simplewebauthn/browser';

/** global: Craft */
/** global: Garnish */
Craft.AuthMethodSetup = Garnish.Base.extend(
  {
    methodListings: null,

    init(settings) {
      this.setSettings(settings, Craft.AuthMethodSetup.defaults);
      this.initUi();
    },

    initUi() {
      this.methodListings = Craft.index(
        document.querySelectorAll('#auth-method-setup .auth-method'),
        (container) => container.getAttribute('data-method')
      );

      for (let container of Object.values(this.methodListings)) {
        this.initListing(container);
      }
    },

    initListing(container) {
      const setupBtn = container.querySelector('.auth-method-setup-btn');
      this.addListener(setupBtn, 'activate', (ev) => {
        const method = container.getAttribute('data-method');
        this.showSetupSlideout(method);
      });
    },

    focusMethodButton(method) {
      this.methodListings[method].querySelector('button').focus();
    },

    showSetupSlideout(method) {
      const button = this.methodListings[method].querySelector(
        '.auth-method-setup-btn'
      );
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
            .then(async ({data}) => {
              const slideout = new Craft.AuthMethodSetup.Slideout(data);
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

              // todo
              if (
                data.selectedMethod ===
                  'craft\\auth\\passkeys\\type\\WebAuthn' &&
                browserSupportsWebAuthn()
              ) {
                new Craft.WebAuthnSetup(slideout, this.settings);
              }
            })
            .catch(({response}) => {
              // Add the error message
              Craft.cp.displayError(response.data.message);
            })
            .finally(() => {
              button.classList.remove('loading');
            });
        },
        () => {
          button.classList.remove('loading');
        },
        // give them 5 minutes to complete setup
        Math.min(Craft.elevatedSessionDuration, 300)
      );
    },

    refresh() {
      Craft.sendActionRequest('POST', 'auth/method-listing-html').then(
        async ({data}) => {
          const $container = $('#auth-method-setup').html(
            $(data.html).children()
          );
          Craft.initUiElements($container);
          await Craft.appendHeadHtml(data.headHtml);
          await Craft.appendBodyHtml(data.bodyHtml);
          this.removeAllListeners();
          this.initUi();
          this.settings.onRefresh();
          this.trigger('refresh');
        }
      );
    },
  },
  {
    defaults: {
      onRefresh: () => {},
    },
  }
);

Craft.AuthMethodSetup.Slideout = Craft.Slideout.extend({
  methodName: null,

  init(data) {
    this.methodName = data.methodName;

    const contents = `
<div class="so-body">${data.html}</div>
<div class="so-footer">
  <div class="flex-grow"></div>
  <div class="flex flex-nowrap">
    <button type="button" class="btn auth-method-close-btn">${Craft.t(
      'app',
      'Cancel'
    )}</button>
  </div>
</div>
`;

    this.base(contents, {
      containerAttributes: {
        id: data.containerId,
      },
    });
  },

  showSuccess() {
    const message = Craft.t('app', '{name} added successfully.', {
      name: this.methodName,
    });
    this.$container.find('.so-body').addClass('auth-method-setup-success')
      .html(`
        <div class="auth-method-setup-success-graphic" data-icon="check" aria-hidden="true"></div>
        <h1 class="auth-method-setup-success-message" tabindex="-1">${message}</h1>
      `);

    this.$container.find('.auth-method-setup-success-message').focus();
    this.$container
      .find('.auth-method-close-btn')
      .text(Craft.t('app', 'Close'));
  },
});
