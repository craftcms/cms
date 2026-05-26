import{c as e,i as t,o as n,s as r}from"./nav-item-Bfzb7bVl-kS60DrF6.js";import{_t as i,mt as a,yt as o}from"./_plugin-vue_export-helper-DmB1UnH4.js";import{c as s,f as c,i as l,r as u,t as d}from"./lit-X8lyiNX5.js";import{a as f,i as p,r as m}from"./decorators-CaOws0gC.js";import{n as h}from"./Queue.ts-BUzYJhyf.js";import{t as g}from"./decorate-BpI2plOt.js";function _(e){let t=new Uint8Array(e),n=``;for(let e of t)n+=String.fromCharCode(e);return btoa(n).replace(/\+/g,`-`).replace(/\//g,`_`).replace(/=/g,``)}function v(e){let t=e.replace(/-/g,`+`).replace(/_/g,`/`),n=(4-t.length%4)%4,r=t.padEnd(t.length+n,`=`),i=atob(r),a=new ArrayBuffer(i.length),o=new Uint8Array(a);for(let e=0;e<i.length;e++)o[e]=i.charCodeAt(e);return a}function y(){return b.stubThis(globalThis?.PublicKeyCredential!==void 0&&typeof globalThis.PublicKeyCredential==`function`)}var b={stubThis:e=>e};function x(e){let{id:t}=e;return{...e,id:v(t),transports:e.transports}}function S(e){return e===`localhost`||/^((xn--[a-z0-9-]+|[a-z0-9]+(-[a-z0-9]+)*)\.)+([a-z]{2,}|xn--[a-z0-9-]+)$/i.test(e)}var C=class extends Error{constructor({message:e,code:t,cause:n,name:r}){super(e,{cause:n}),Object.defineProperty(this,`code`,{enumerable:!0,configurable:!0,writable:!0,value:void 0}),this.name=r??n.name,this.code=t}},w=new class{constructor(){Object.defineProperty(this,`controller`,{enumerable:!0,configurable:!0,writable:!0,value:void 0})}createNewAbortSignal(){if(this.controller){let e=Error(`Cancelling existing WebAuthn API call for new one`);e.name=`AbortError`,this.controller.abort(e)}let e=new AbortController;return this.controller=e,e.signal}cancelCeremony(){if(this.controller){let e=Error(`Manually cancelling existing WebAuthn API call`);e.name=`AbortError`,this.controller.abort(e),this.controller=void 0}}},T=[`cross-platform`,`platform`];function E(e){if(e&&!(T.indexOf(e)<0))return e}function D(){if(!y())return O.stubThis(new Promise(e=>e(!1)));let e=globalThis.PublicKeyCredential;return e?.isConditionalMediationAvailable===void 0?O.stubThis(new Promise(e=>e(!1))):O.stubThis(e.isConditionalMediationAvailable())}var O={stubThis:e=>e};function k({error:e,options:t}){let{publicKey:n}=t;if(!n)throw Error(`options was missing required publicKey property`);if(e.name===`AbortError`){if(t.signal instanceof AbortSignal)return new C({message:`Authentication ceremony was sent an abort signal`,code:`ERROR_CEREMONY_ABORTED`,cause:e})}else if(e.name===`NotAllowedError`)return new C({message:e.message,code:`ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY`,cause:e});else if(e.name===`SecurityError`){let t=globalThis.location.hostname;if(!S(t))return new C({message:`${globalThis.location.hostname} is an invalid domain`,code:`ERROR_INVALID_DOMAIN`,cause:e});if(n.rpId!==t)return new C({message:`The RP ID "${n.rpId}" is invalid for this domain`,code:`ERROR_INVALID_RP_ID`,cause:e})}else if(e.name===`UnknownError`)return new C({message:`The authenticator was unable to process the specified options, or could not create a new assertion signature`,code:`ERROR_AUTHENTICATOR_GENERAL_ERROR`,cause:e});return e}async function A(e){!e.optionsJSON&&e.challenge&&(console.warn(`startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.`),e={optionsJSON:e});let{optionsJSON:t,useBrowserAutofill:n=!1,verifyBrowserAutofillInput:r=!0}=e;if(!y())throw Error(`WebAuthn is not supported in this browser`);let i;t.allowCredentials?.length!==0&&(i=t.allowCredentials?.map(x));let a={...t,challenge:v(t.challenge),allowCredentials:i},o={};if(n){if(!await D())throw Error(`Browser does not support WebAuthn autofill`);if(document.querySelectorAll(`input[autocomplete$='webauthn']`).length<1&&r)throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');o.mediation=`conditional`,a.allowCredentials=[]}o.publicKey=a,o.signal=w.createNewAbortSignal();let s;try{s=await navigator.credentials.get(o)}catch(e){throw k({error:e,options:o})}if(!s)throw Error(`Authentication was not completed`);let{id:c,rawId:l,response:u,type:d}=s,f;return u.userHandle&&(f=_(u.userHandle)),{id:c,rawId:_(l),response:{authenticatorData:_(u.authenticatorData),clientDataJSON:_(u.clientDataJSON),signature:_(u.signature),userHandle:f},type:d,clientExtensionResults:s.getClientExtensionResults(),authenticatorAttachment:E(s.authenticatorAttachment)}}function j(){return y()?PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable():new Promise(e=>e(!1))}var M=c`
  :host {
    display: block;
    width: 100%;
  }

  .spinner-overlay {
    display: grid;
    place-items: center;
  }

  .login-form__fields {
    display: flex;
    gap: var(--c-spacing-md);
    align-items: end;
  }

  .login-form__actions {
    margin-block-start: var(--c-spacing-lg);
  }

  .login-form__error {
    margin-block-start: var(--c-spacing-md);
  }

  .alternative-login-methods {
    margin-block-start: var(--c-spacing-lg);
  }

  hr {
    margin-block: var(--c-spacing-lg);
    border: none;
    border-block-end: 1px solid var(--c-color-border-quiet);
  }
`,N=class extends r{constructor(t){if(super(t),this.it=u,t.type!==e.CHILD)throw Error(this.constructor.directiveName+`() can only be used in child bindings`)}render(e){if(e===u||e==null)return this._t=void 0,this.it=e;if(e===l)return e;if(typeof e!=`string`)throw Error(this.constructor.directiveName+`() called with a non-string value`);if(e===this.it)return this._t;this.it=e;let t=[e];return t.raw=t,this._t={_$litType$:this.constructor.resultType,strings:t,values:[]}}};N.directiveName=`unsafeHTML`,N.resultType=1;var P=n(N),F=class extends a{static{this.METHOD=`totp`}get endpoint(){return`auth/verify-totp`}renderInput(){return s`
      <craft-input
        label="${t(`Verification Code`)}"
        id="totp-code"
        class="totp-code"
        name="code"
        .maxlength="${6}"
        autocomplete="one-time-code"
        inputmode="numeric"
        aria-required="true"
      ></craft-input>
    `}};g([m(`craft-input.totp-code`)],F.prototype,`_input`,void 0),a.register(`craft-totp-form`,F);var I=class extends a{static{this.METHOD=`recovery-codes`}get endpoint(){return`auth/verify-recovery-code`}renderInput(){return s`
      <craft-input
        label="${t(`Recovery Code`)}"
        id="recovery-code"
        class="recovery-code"
        name="code"
        autocomplete="off"
        aria-required="true"
      ></craft-input>
    `}};g([m(`craft-input.recovery-code`)],I.prototype,`_input`,void 0),a.register(`craft-recovery-code-form`,I);var L=class extends d{constructor(...e){super(...e),this._switching=!1,this.#e=!1,this.#t=h.getInstance()}static{this.styles=[M]}#e;#t;async updated(e){super.updated(e),!a.isNative(this.data?.authMethod)&&!this.#e&&!this._switching&&this._container&&(this.#e=!0,await this.#n())}async#n(){this._container&&(await Craft.appendHeadHtml(this.data.headHtml),await Craft.appendBodyHtml(this.data.bodyHtml),Craft.initUiElements(this._container),Craft.createAuthFormHandler(this.data.authMethod,this._container,()=>{this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.data.returnUrl}}))},e=>{this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:e}}))}),this._container.querySelector(`:focus-visible, input, button`)?.focus())}async#r(e){this._switching=!0,this.#e=!1;try{let t=await fetch(e.url,{headers:{Accept:`application/json`,"Content-Type":`application/json`}});if(!t.ok)throw Error(`Failed to fetch challenge data.`);this.data=await t.json()}finally{this._switching=!1}}render(){return this._switching?s`
        <craft-pane>
          <div class="spinner-overlay">
            <craft-spinner></craft-spinner>
          </div>
        </craft-pane>
      `:s`
      <craft-pane>
        <div class="auth-form-container">${P(this.data.authForm)}</div>
        ${this.data.otherMethods.length?s`
              <hr />
              <craft-action-menu>
                <craft-button slot="invoker" appearance="plain" size="zero">
                  <craft-icon slot="prefix" name="chevron-down"></craft-icon>
                  ${t(`Try another way`)}
                </craft-button>

                <div slot="content">
                  ${this.data.otherMethods.map(e=>s`
                      <craft-action-item
                        @click="${()=>this.#r(e)}"
                      >
                        ${e.name}
                      </craft-action-item>
                    `)}
                </div>
              </craft-action-menu>
            `:u}
      </craft-pane>
    `}};g([f({attribute:!1})],L.prototype,`data`,void 0),g([p()],L.prototype,`_switching`,void 0),g([m(`.auth-form-container`)],L.prototype,`_container`,void 0),customElements.get(`craft-login-challenge`)||customElements.define(`craft-login-challenge`,L);var R=class extends d{constructor(...e){super(...e),this.useEmailAsUsername=!1,this.username=``,this._busy=!1,this._error=``}static{this.styles=[M]}firstUpdated(){this._input?.focus()}#e(){return this.useEmailAsUsername?t(`Email`):t(`Username or Email`)}async#t(e){e.preventDefault(),this._error=``,this._busy=!0;try{await i.post(`users/send-password-reset-email`,{loginName:this._input.value});let e=document.createElement(`craft-dialog`);e.setAttribute(`open`,``);let n=document.createElement(`p`);n.textContent=t(`Check your email for instructions to reset your password.`),e.appendChild(n),document.body.appendChild(e)}catch(e){this._error=e?.response?.data?.message??t(`A server error occurred.`)}finally{this._busy=!1}}#n(){this.dispatchEvent(new CustomEvent(`craft:login:reset-back`,{bubbles:!0,composed:!0,detail:{username:this._input?.value??``}}))}render(){return s`
      <craft-pane>
        <form
          class="login-form login-form--reset"
          method="post"
          accept-charset="UTF-8"
          @submit="${this.#t}"
        >
          <craft-field-group>
            <craft-input
              label="${this.#e()}"
              id="reset-username"
              type="${this.useEmailAsUsername?`email`:`text`}"
              class="reset-username"
              name="username"
              .value="${this.username}"
              autocomplete="username"
              autocapitalize="off"
              required
            >
            </craft-input>
          </craft-field-group>

          <div class="login-form__actions">
            <craft-button
              type="submit"
              variant="primary"
              ?loading="${this._busy}"
            >
              ${t(`Reset password`)}
            </craft-button>
          </div>

          ${this._error?s`<craft-callout variant="danger" class="login-form__error"
                >${this._error}</craft-callout
              >`:u}
        </form>

        <hr />

        <craft-button
          type="button"
          appearance="plain"
          size="small"
          @click="${this.#n}"
        >
          <craft-icon slot="prefix" name="arrow-left"></craft-icon>
          ${t(`Back to sign in`)}
        </craft-button>
      </craft-pane>
    `}};g([f({type:Boolean,attribute:`use-email-as-username`})],R.prototype,`useEmailAsUsername`,void 0),g([f()],R.prototype,`username`,void 0),g([p()],R.prototype,`_busy`,void 0),g([p()],R.prototype,`_error`,void 0),g([m(`.reset-username`)],R.prototype,`_input`,void 0),customElements.get(`craft-login-reset-password`)||customElements.define(`craft-login-reset-password`,R);var z=class extends d{constructor(...e){super(...e),this.showPasskeyBtn=!0,this.showResetPassword=!1,this.showRememberMe=!1,this.username=``,this.staticEmail=``,this.useEmailAsUsername=!1,this.rememberMeLabel=``,this.initialError=``,this.action=``,this._view=`login`,this._error=``,this._loginBusy=!1,this._passkeyBusy=!1,this._canUsePasskey=!1,this._twoFactorData=null,this._resetUsername=``}static{this.styles=[o,M]}async connectedCallback(){super.connectedCallback(),this.initialError&&(this._error=this.initialError),this.showPasskeyBtn&&y()&&(this._canUsePasskey=await j())}#e(){return this.useEmailAsUsername?t(`Email`):t(`Username or Email`)}async#t(e){e.preventDefault(),this._error=``,this._loginBusy=!0;try{let e=await fetch(this.action,{method:`post`,headers:{Accept:`application/json`,"Content-Type":`application/json`},body:JSON.stringify({loginName:this._usernameInput.value,password:this._passwordInput.value,rememberMe:this._rememberMeInput?.checked?`1`:``})}),t=await e.json();if(!e.ok)throw Error(t.message||`A server error occurred.`);t.authMethod?(this._twoFactorData=t,this._view=`challenge`,this._loginBusy=!1):(this.#c(t.returnUrl),this._loginBusy=!1)}catch(e){this._loginBusy=!1,this.#s(e.message)}}async#n(){if(!this._passkeyBusy){this._error=``,this._passkeyBusy=!0;try{let{data:e}=await i.post(`auth/passkey-request-options`),t=await A({optionsJSON:JSON.parse(e.options)}),{data:n}=await i.post(`users/login-with-passkey`,{requestOptions:e.options,authResponse:JSON.stringify(t)});this.#c(n.returnUrl),this._passkeyBusy=!1}catch(e){this._passkeyBusy=!1;let t=e?.response?.data?.message;t?this.#s(t):console.warn(e)}}}#r(){this._error=``,this._resetUsername=this._usernameInput?.value??``,this._view=`reset-password`}#i(e){let t=e.detail?.username??``;this._view=`login`,this.updateComplete.then(()=>{t&&this._usernameInput&&(this._usernameInput.value=t),this._usernameInput?.focus()})}#a(e){this.#c(e.detail.returnUrl)}#o(e){let t=e.detail.message,n=new CustomEvent(`craft:login:error`,{bubbles:!0,composed:!0,cancelable:!0,detail:{message:t}});this.dispatchEvent(n),n.defaultPrevented||this.#s(t)}#s(e){this._error=e.trim();let t=this.shadowRoot?.querySelector(`.cp-visually-hidden[role="status"]`);t&&(t.textContent=e)}#c(e){let t=new CustomEvent(`craft:login:success`,{bubbles:!0,composed:!0,cancelable:!0,detail:{returnUrl:e}});this.dispatchEvent(t),t.defaultPrevented||(window.location.href=e)}render(){return s`
      <div>
        <span
          class="cp-visually-hidden"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        ></span>

        ${this._view===`login`?this.#l():this._view===`reset-password`?s`
                <craft-login-reset-password
                  ?use-email-as-username="${this.useEmailAsUsername}"
                  username="${this._resetUsername}"
                  @craft:login:reset-back="${this.#i}"
                ></craft-login-reset-password>
              `:s`
                <craft-login-challenge
                  .data="${this._twoFactorData}"
                  @login-verified="${this.#a}"
                  @login-failed="${this.#o}"
                ></craft-login-challenge>
              `}
      </div>
    `}#l(){let e=this._canUsePasskey||this.querySelector(`[slot="alternative-methods"]`);return s`
      <craft-pane>
        <form
          class="login-form"
          method="post"
          accept-charset="UTF-8"
          @submit="${this.#t}"
        >
          <craft-field-group>
            ${this.staticEmail?s`<input
                  type="hidden"
                  class="login-username"
                  name="username"
                  .value="${this.staticEmail}"
                />`:s`
                  <div class="field">
                    <craft-input
                      label="${this.#e()}"
                      id="login-username"
                      type="${this.useEmailAsUsername?`email`:`text`}"
                      class="login-username"
                      name="username"
                      .value="${this.username}"
                      autocomplete="username"
                      autocapitalize="off"
                      required
                    />
                  </div>
                `}

            <div>
              <craft-input-password
                label="${t(`Password`)}"
                id="login-password"
                class="login-password"
                name="password"
                autocomplete="current-password"
                required
              ></craft-input-password>

              ${this.showResetPassword?s`
                    <craft-button
                      type="button"
                      size="small"
                      appearance="plain"
                      @click="${this.#r}"
                      style="margin-block-start: var(--c-spacing-sm)"
                    >
                      ${t(`Forgot password?`)}
                    </craft-button>
                  `:u}
            </div>

            ${this.showRememberMe?s`
                  <div class="remember-me-row">
                    <craft-checkbox
                      label="${this.rememberMeLabel||t(`Stay signed in`)}"
                      type="checkbox"
                      id="login-remember-me"
                      class="login-remember-me"
                    ></craft-checkbox>
                  </div>
                `:u}
          </craft-field-group>

          <div class="login-form__actions">
            <craft-button
              type="submit"
              variant="primary"
              ?loading="${this._loginBusy}"
              style="width: 100%"
            >
              ${t(`Sign in`)}
            </craft-button>
          </div>
        </form>

        ${this._error?s`<craft-callout class="login-form__error" variant="danger"
              >${this._error}</craft-callout
            >`:u}
      </craft-pane>

      ${e?s`
            <div class="alternative-login-methods">
              ${this._canUsePasskey?s`
                    <craft-button
                      type="button"
                      appearance="filled"
                      ?loading="${this._passkeyBusy}"
                      @click="${this.#n}"
                      style="width: 100%"
                    >
                      ${t(`Sign in with a passkey`)}
                    </craft-button>
                  `:u}
              <slot name="alternative-methods"></slot>
            </div>
          `:u}
    `}};g([f({type:Boolean,attribute:`show-passkey-btn`})],z.prototype,`showPasskeyBtn`,void 0),g([f({type:Boolean,attribute:`show-reset-password`})],z.prototype,`showResetPassword`,void 0),g([f({type:Boolean,attribute:`show-remember-me`})],z.prototype,`showRememberMe`,void 0),g([f()],z.prototype,`username`,void 0),g([f({attribute:`static-email`})],z.prototype,`staticEmail`,void 0),g([f({type:Boolean,attribute:`use-email-as-username`})],z.prototype,`useEmailAsUsername`,void 0),g([f({attribute:`remember-me-label`})],z.prototype,`rememberMeLabel`,void 0),g([f({attribute:`initial-error`})],z.prototype,`initialError`,void 0),g([f()],z.prototype,`action`,void 0),g([p()],z.prototype,`_view`,void 0),g([p()],z.prototype,`_error`,void 0),g([p()],z.prototype,`_loginBusy`,void 0),g([p()],z.prototype,`_passkeyBusy`,void 0),g([p()],z.prototype,`_canUsePasskey`,void 0),g([p()],z.prototype,`_twoFactorData`,void 0),g([p()],z.prototype,`_resetUsername`,void 0),g([m(`.login-username`)],z.prototype,`_usernameInput`,void 0),g([m(`craft-input-password.login-password`)],z.prototype,`_passwordInput`,void 0),g([m(`.login-remember-me`)],z.prototype,`_rememberMeInput`,void 0),customElements.get(`craft-login-form`)||customElements.define(`craft-login-form`,z);