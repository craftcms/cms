/** global: Craft */
/** global: Garnish */
import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
} from '@simplewebauthn/browser';

/**
 * Elevated Session Manager
 */
Craft.ElevatedSessionManager = Garnish.Base.extend(
  {
    fetchingTimeout: false,

    loginModal: null,
    showingLoginModal: false,

    onSuccess: null,
    onCancel: null,
    success: false,

    /**
     * @callback requireElevatedSessionCallback
     */
    /**
     * Requires that the user has an elevated session.
     *
     * @param {requireElevatedSessionCallback} onSuccess The callback function that should be called once the user has an elevated session
     * @param {requireElevatedSessionCallback} [onCancel] The callback function that should be called if establishing an elevated session is cancelled
     * @param {number} [minSafeElevatedSessionTimeout] The minimum amount of time that must be remaining on an existing elevated session
     * (in seconds), for it to be considered safe. (Defaults to 5.)
     */
    async requireElevatedSession(
      onSuccess,
      onCancel,
      minSafeElevatedSessionTimeout
    ) {
      this.onSuccess = onSuccess;
      this.onCancel = onCancel;

      // Check the time remaining on the user’s elevated session (if any)
      this.fetchingTimeout = true;

      let data;

      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'users/get-elevated-session-timeout'
        );
        data = response.data;
      } finally {
        this.fetchingTimeout = false;
      }

      if (
        data.timeout === false ||
        data.timeout >=
          (minSafeElevatedSessionTimeout ||
            Craft.ElevatedSessionManager.minSafeElevatedSessionTimeout)
      ) {
        this.onSuccess();
      } else {
        // Show the login modal
        this.showLoginModal();
      }
    },

    /**
     * Shows the login modal.
     */
    async showLoginModal() {
      if (this.showingLoginModal) {
        return;
      }

      this.showingLoginModal = true;

      if (this.loginModal) {
        this.loginModal.destroy();
      }

      const {data} = await Craft.sendActionRequest(
        'POST',
        'users/login-modal',
        {
          data: {
            email: Craft.userEmail,
            forElevatedSession: true,
          },
        }
      );
      const $container = $(data.html);

      this.loginModal = new Garnish.Modal($container, {
        closeOtherModals: false,
        shadeClass: 'modal-shade dark login-modal-shade',
        onFadeIn: async () => {
          Craft.initUiElements($container);
          new Craft.LoginForm($container.find('.login-container'), {
            showPasskeyBtn: Craft.userHasPasskeys,
            onLogin: () => {
              this.success = true;
              this.loginModal.hide();
            },
          });
          await Craft.appendHeadHtml(data.headHtml);
          await Craft.appendBodyHtml(data.bodyHtml);
        },
        onFadeOut: () => {
          this.loginModal.destroy();
          this.loginModal = null;
        },
        onHide: () => {
          this.showingLoginModal = false;
          if (this.success) {
            this.onSuccess();
          } else if (this.onCancel) {
            this.onCancel();
          }
        },
      });
    },
  },
  {
    minSafeElevatedSessionTimeout: 5,
  }
);

// Instantiate it
Craft.elevatedSessionManager = new Craft.ElevatedSessionManager();
