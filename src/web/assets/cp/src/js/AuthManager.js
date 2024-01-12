/** global: Craft */
/** global: Garnish */
/**
 * AuthManager class
 */
Craft.AuthManager = Garnish.Base.extend(
  {
    remainingSessionTime: null,
    checkRemainingSessionTimer: null,
    showLoginModalTimer: null,
    decrementLogoutWarningInterval: null,

    showingLogoutWarningModal: false,
    showingLoginModal: false,

    logoutWarningModal: null,
    loginModal: null,

    $logoutWarningPara: null,
    $passwordInput: null,
    $loginBtn: null,
    loginBtn: null,

    /**
     * Init
     */
    init: function () {
      if (Craft.username) {
        this.updateRemainingSessionTime(Craft.remainingSessionTime, false);
      }
    },

    /**
     * Sets a timer for the next time to check the auth timeout.
     */
    setCheckRemainingSessionTimer: function (seconds) {
      if (this.checkRemainingSessionTimer) {
        clearTimeout(this.checkRemainingSessionTimer);
      }

      this.checkRemainingSessionTimer = setTimeout(
        this.checkRemainingSessionTime.bind(this),
        seconds * 1000
      );
    },

    /**
     * Pings the server to see how many seconds are left on the current user session, and handles the response.
     */
    async checkRemainingSessionTime(extendSession) {
      const url = Craft.getActionUrl(
        'users/session-info',
        !extendSession ? 'dontExtendSession=1' : null
      );
      try {
        const {data} = await Craft.sendActionRequest('GET', url);
        if (typeof Craft.csrfTokenValue !== 'undefined') {
          Craft.csrfTokenValue = data.csrfTokenValue;
        }
        this.updateRemainingSessionTime(data.timeout, data.isGuest);
      } catch (e) {
        this.updateRemainingSessionTime(-1, false);
      }
    },

    /**
     * Updates our record of the auth timeout, and handles it.
     */
    updateRemainingSessionTime: function (remainingSessionTime, isGuest) {
      this.remainingSessionTime = parseInt(remainingSessionTime);

      // Are we within the warning window?
      if (
        this.remainingSessionTime !== -1 &&
        this.remainingSessionTime < Craft.AuthManager.minSafeSessionTime
      ) {
        // Is there still time to renew the session?
        if (!isGuest || this.remainingSessionTime) {
          if (!this.showingLogoutWarningModal) {
            // Show the warning modal
            this.showLogoutWarningModal();
          }

          const seconds = Math.min(
            Craft.AuthManager.checkInterval,
            this.remainingSessionTime
          );
          this.setCheckRemainingSessionTimer(Math.max(1, seconds));
        } else {
          // Show the login modal
          if (!this.showingLoginModal) {
            this.hideLogoutWarningModal();
            this.showLoginModal();
          }
          this.setCheckRemainingSessionTimer(Craft.AuthManager.checkInterval);
        }
      } else {
        // Everything's good!
        this.hideLogoutWarningModal();
        this.hideLoginModal();

        // Will be be within the minSafeSessionTime before the next update?
        if (
          this.remainingSessionTime !== -1 &&
          this.remainingSessionTime <
            Craft.AuthManager.minSafeSessionTime +
              Craft.AuthManager.checkInterval
        ) {
          this.setCheckRemainingSessionTimer(
            this.remainingSessionTime - Craft.AuthManager.minSafeSessionTime + 1
          );
        } else {
          this.setCheckRemainingSessionTimer(Craft.AuthManager.checkInterval);
        }
      }
    },

    /**
     * Shows the logout warning modal.
     */
    showLogoutWarningModal: function () {
      var quickShow;

      if (this.showingLoginModal) {
        this.hideLoginModal(true);
        quickShow = true;
      } else {
        quickShow = false;
      }

      this.showingLogoutWarningModal = true;

      if (!this.logoutWarningModal) {
        let $form = $(
          '<form id="logoutwarningmodal" class="modal alert fitted"/>'
        );
        let $body = $('<div class="body"/>').appendTo($form);
        let $buttons = $('<div class="buttons right"/>').appendTo($body);
        let $logoutBtn = $('<button/>', {
          type: 'button',
          class: 'btn',
          text: Craft.t('app', 'Sign out now'),
        }).appendTo($buttons);
        let $renewSessionBtn = $('<button/>', {
          type: 'submit',
          class: 'btn submit',
          text: Craft.t('app', 'Keep me signed in'),
        }).appendTo($buttons);

        this.$logoutWarningPara = $('<p/>').prependTo($body);

        this.logoutWarningModal = new Garnish.Modal($form, {
          autoShow: false,
          closeOtherModals: false,
          hideOnEsc: false,
          hideOnShadeClick: false,
          shadeClass: 'modal-shade dark logoutwarningmodalshade',
          onFadeIn: function () {
            if (!Garnish.isMobileBrowser(true)) {
              // Auto-focus the renew button
              setTimeout(function () {
                $renewSessionBtn.trigger('focus');
              }, 100);
            }
          },
        });

        this.addListener($logoutBtn, 'activate', 'logout');
        this.addListener($form, 'submit', 'renewSession');
      }

      if (quickShow) {
        this.logoutWarningModal.quickShow();
      } else {
        this.logoutWarningModal.show();
      }

      this.updateLogoutWarningMessage();

      this.decrementLogoutWarningInterval = setInterval(
        this.decrementLogoutWarning.bind(this),
        1000
      );
    },

    /**
     * Updates the logout warning message indicating that the session is about to expire.
     */
    updateLogoutWarningMessage: function () {
      this.$logoutWarningPara.text(
        Craft.t('app', 'Your session will expire in {time}.', {
          time: Craft.secondsToHumanTimeDuration(this.remainingSessionTime),
        })
      );

      this.logoutWarningModal.updateSizeAndPosition();
    },

    decrementLogoutWarning: function () {
      if (this.remainingSessionTime > 0) {
        this.remainingSessionTime--;
        this.updateLogoutWarningMessage();
      }

      if (this.remainingSessionTime === 0) {
        clearInterval(this.decrementLogoutWarningInterval);
      }
    },

    /**
     * Hides the logout warning modal.
     */
    hideLogoutWarningModal: function (quick) {
      this.showingLogoutWarningModal = false;

      if (this.logoutWarningModal) {
        if (quick) {
          this.logoutWarningModal.quickHide();
        } else {
          this.logoutWarningModal.hide();
        }

        if (this.decrementLogoutWarningInterval) {
          clearInterval(this.decrementLogoutWarningInterval);
        }
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
          },
        }
      );
      const $container = $(data.html);

      this.loginModal = new Garnish.Modal($container, {
        closeOtherModals: false,
        hideOnEsc: false,
        hideOnShadeClick: false,
        shadeClass: 'modal-shade dark blurred login-modal-shade',
        onFadeIn: () => {
          Craft.initUiElements($container);
          new Craft.LoginForm($container.find('.login-container'), {
            showPasskeyBtn: Craft.userHasPasskeys,
            onLogin: () => {
              this.loginModal.hide();
            },
          });
          Craft.appendHeadHtml(data.headHtml);
          Craft.appendBodyHtml(data.bodyHtml);
        },
        onFadeOut: () => {
          this.loginModal.destroy();
          this.loginModal = null;
        },
        onHide: () => {
          this.showingLoginModal = false;
        },
      });
    },

    /**
     * Hides the login modal.
     */
    hideLoginModal: function (quick) {
      if (this.loginModal) {
        if (quick) {
          this.loginModal.quickHide();
        } else {
          this.loginModal.hide();
        }
        // reset the modal
        this.loginModal.destroy();
        this.loginModal = null;
      }
    },

    logout: function () {
      $.get({
        url: Craft.getActionUrl('users/logout'),
        dataType: 'json',
        success: () => {
          Craft.redirectTo('');
        },
      });
    },

    renewSession: function (ev) {
      if (ev) {
        ev.preventDefault();
      }

      this.hideLogoutWarningModal();
      this.checkRemainingSessionTime(true);
    },

    closeModal: function () {
      this.loginBtn.successEvent();
      this.hideLoginModal();
      this.checkRemainingSessionTime();
    },
  },
  {
    checkInterval: 60,
    minSafeSessionTime: 120,
  }
);
