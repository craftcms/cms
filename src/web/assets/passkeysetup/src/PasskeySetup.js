import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
} from '@simplewebauthn/browser';
import {startRegistration} from '@simplewebauthn/browser';

/** global: Craft */
/** global: Garnish */
Craft.PasskeySetup = Garnish.Base.extend({
  $passkeysTable: null,
  $addPasskeyBtn: null,
  $errors: null,

  async init() {
    this.$passkeysTable = $('#passkeys');
    this.$addPasskeyBtn = $('#add-passkey-btn');
    this.$errors = $('#passkey-errors');

    if (
      !browserSupportsWebAuthn() ||
      !(await platformAuthenticatorIsAvailable())
    ) {
      const $container = $('<div class="readable"/>');
      $('<blockquote/>', {
        class: 'note warning',
        text: Craft.t('app', 'This browser doesn’t support passkeys.'),
      }).appendTo($container);
      this.$addPasskeyBtn.replaceWith($container);
      return;
    }

    this.initTable();
    this.addListener(this.$addPasskeyBtn, 'activate', 'createPasskey');
  },

  initTable() {
    this.addListener(this.$passkeysTable.find('.delete'), 'activate', (ev) => {
      ev.preventDefault();
      const $button = $(ev.currentTarget);
      this.deletePasskey($button.data('uid'), $button.data('name'));
    });
  },

  updateTable(html) {
    this.$passkeysTable.html(html);
    this.initTable();
  },

  async createPasskey() {
    if (this.$addPasskeyBtn.hasClass('loading')) {
      return;
    }

    this.$addPasskeyBtn.addClass('loading');
    Craft.cp.announce(Craft.t('app', 'Loading'));

    try {
      await (() =>
        new Promise((resolve, reject) => {
          try {
            Craft.elevatedSessionManager.requireElevatedSession(
              async () => {
                await this.startRegistration();
                resolve();
              },
              () => {
                resolve();
              },
              Math.min(Craft.elevatedSessionDuration, 300)
            );
          } catch (e) {
            reject(e);
          }
        }))();
    } finally {
      this.$addPasskeyBtn.removeClass('loading');
    }
  },

  async startRegistration() {
    // GET registration options from the endpoint that calls
    let data;
    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'auth/passkey-creation-options'
      );
      data = response.data;
    } catch (e) {
      Craft.cp.displayError(e?.response?.data?.message);
      return;
    }

    let defaultName = this.browserName() + ' on ' + this.platformName();
    const credentialName = prompt(
      Craft.t('app', 'Enter a name for the passkey.'),
      defaultName
    );

    if (credentialName === null) {
      return;
    }

    let regResponse;

    try {
      regResponse = await startRegistration(data.options);
    } catch (e) {
      Craft.cp.displayError(e?.message);
      return;
    }

    this.verifyRegistration(regResponse, credentialName);
  },

  async verifyRegistration(startRegistrationResponse, credentialName) {
    let data;

    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'auth/verify-passkey-creation',
        {
          data: {
            credentials: JSON.stringify(startRegistrationResponse),
            credentialName: credentialName,
          },
        }
      );
      data = response.data;
    } catch (e) {
      Craft.cp.displayError(e?.response?.data?.message);
      return;
    }

    Craft.cp.displaySuccess(data.message);
    this.updateTable(data.tableHtml);
  },

  async deletePasskey(uid, name) {
    if (
      !confirm(
        Craft.t(
          'app',
          'Are you sure you want to delete the “{name}” passkey?',
          {name}
        )
      )
    ) {
      return;
    }

    let data;

    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'auth/delete-passkey',
        {
          data: {uid},
        }
      );
      data = response.data;
    } catch (e) {
      Craft.cp.displayError(e?.response?.data?.message);
      return;
    }

    Craft.cp.displaySuccess(data.message);
    this.updateTable(data.tableHtml);
  },

  platformName() {
    const platform = navigator.platform;

    if (platform.indexOf('Mac') !== -1) {
      return 'Mac';
    }
    if (platform.indexOf('iPhone') !== -1 || platform.indexOf('Pike')) {
      return 'iPhone';
    }
    if (platform.indexOf('iPad') !== -1) {
      return 'iPad';
    }
    if (platform.indexOf('iPod') !== -1) {
      return 'iPod';
    }
    if (platform.indexOf('FreeBSD') !== -1) {
      return 'FreeBSD';
    }
    if (platform.indexOf('Linux') !== -1) {
      return 'Linux';
    }
    if (platform.indexOf('Win') !== -1) {
      return 'Windows';
    }
    if (platform.indexOf('Nintendo') !== -1) {
      return 'Nintendo';
    }
    if (platform.indexOf('SunOS') !== -1) {
      return 'Solaris';
    }
    // in other cases - just use the full name returned by navigator.platform
    return platform;
  },

  browserName() {
    const userAgent = navigator.userAgent;

    if (userAgent.match(/chrome|chromium|crios/i)) {
      return 'Chrome';
    }
    if (userAgent.match(/firefox|fxios/i)) {
      return 'Firefox';
    }
    if (userAgent.match(/safari/i)) {
      return 'Safari';
    }
    if (userAgent.match(/opr\//i)) {
      return 'Opera';
    }
    if (userAgent.match(/edg/i)) {
      return 'Edge';
    }
    if (userAgent.match(/trident/i)) {
      return 'IE';
    }
    return 'Browser';
  },
});
