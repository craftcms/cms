import {startAuthentication} from '@simplewebauthn/browser';

/** global: Craft */
/** global: Garnish */
Craft.Auth2faLogin = {
  loginWithPassword: false,
  loginWithSecurityKey: false,

  startWebauthnLogin: function (data, inModal = false) {
    return Craft.sendActionRequest('POST', 'users/start-webauthn-login', {data})
      .then((response) => {
        const authenticationOptions = response.data.authenticationOptions;
        const userId = response.data.userId;
        const duration = response.data.duration;

        try {
          return startAuthentication(authenticationOptions)
            .then((authResponse) => {
              return Promise.resolve(
                Craft.Auth2faLogin.verifyWebAuthnLogin(
                  authenticationOptions,
                  authResponse,
                  userId,
                  duration,
                  inModal
                )
              );
            })
            .catch((authResponseError) => {
              return Promise.reject({success: false, error: authResponseError});
            });
        } catch (error) {
          return Promise.reject({success: false, error: error});
        }
      })
      .catch(({response}) => {
        return Promise.reject({success: false, error: response.data.message});
      });
  },

  verifyWebAuthnLogin: function (
    authenticationOptions,
    authResponse,
    userId,
    duration,
    inModal
  ) {
    let data = {
      userId: userId,
      authenticationOptions: JSON.stringify(authenticationOptions),
      authResponse: JSON.stringify(authResponse),
      duration: duration,
    };

    return Craft.sendActionRequest('POST', 'users/webauthn-login', {data})
      .then((response) => {
        if (inModal) {
          return Promise.resolve({success: true});
        } else {
          return Promise.resolve({
            success: true,
            returnUrl: response.data.returnUrl,
          });
        }
      })
      .catch(({response}) => {
        return Promise.reject({success: false, error: response.data.message});
      });
  },

  submitMfaCode: function ($mfaLoginFormContainer, inModal = false) {
    let data = {
      auth2faFields: {},
      currentMethod: null,
    };

    let mfa = new Craft.Auth2fa();

    data.auth2faFields = mfa._getMfaFields($mfaLoginFormContainer);
    data.currentMethod = mfa._getCurrentMethodInput($mfaLoginFormContainer);

    return Craft.sendActionRequest('POST', 'users/verify-mfa', {data})
      .then((response) => {
        if (inModal) {
          return Promise.resolve({success: true});
        } else {
          return Promise.resolve({
            success: true,
            returnUrl: response.data.returnUrl,
          });
        }
      })
      .catch(({response}) => {
        return Promise.reject({success: false, error: response.data.message});
      });
  },
};
