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
      .catch((response) => {
        return Promise.reject({success: false, error: response.error.message});
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

  submit2faCode: function ($auth2faLoginFormContainer, inModal = false) {
    let data = {
      auth2faFields: {},
      currentMethod: null,
    };

    let auth2fa = new Craft.Auth2fa();

    data.auth2faFields = auth2fa._get2faFields($auth2faLoginFormContainer);
    data.currentMethod = auth2fa._getCurrentMethodInput(
      $auth2faLoginFormContainer
    );

    return Craft.sendActionRequest('POST', 'users/verify-2fa', {data})
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
