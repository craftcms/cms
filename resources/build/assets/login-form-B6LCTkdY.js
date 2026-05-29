import{a as e,i as t,l as n,o as r}from"./nav-item-CDlDuMpT-DENemU4x.js";import{d as i,s as a,u as o}from"./cp-NSlSyu0V.js";import{c as s,f as c,i as l,r as u,t as d}from"./lit-BpPOIUnZ.js";import{a as f,i as p,r as m}from"./decorators-BOwDFZC2.js";import{t as h}from"./decorate-BM_SnROF.js";function g(e){let t=new Uint8Array(e),n=``;for(let e of t)n+=String.fromCharCode(e);return btoa(n).replace(/\+/g,`-`).replace(/\//g,`_`).replace(/=/g,``)}function _(e){let t=e.replace(/-/g,`+`).replace(/_/g,`/`),n=(4-t.length%4)%4,r=t.padEnd(t.length+n,`=`),i=atob(r),a=new ArrayBuffer(i.length),o=new Uint8Array(a);for(let e=0;e<i.length;e++)o[e]=i.charCodeAt(e);return a}function v(){return y.stubThis(globalThis?.PublicKeyCredential!==void 0&&typeof globalThis.PublicKeyCredential==`function`)}var y={stubThis:e=>e};function b(e){let{id:t}=e;return{...e,id:_(t),transports:e.transports}}function x(e){return e===`localhost`||/^((xn--[a-z0-9-]+|[a-z0-9]+(-[a-z0-9]+)*)\.)+([a-z]{2,}|xn--[a-z0-9-]+)$/i.test(e)}var S=class extends Error{constructor({message:e,code:t,cause:n,name:r}){super(e,{cause:n}),Object.defineProperty(this,`code`,{enumerable:!0,configurable:!0,writable:!0,value:void 0}),this.name=r??n.name,this.code=t}},C=new class{constructor(){Object.defineProperty(this,`controller`,{enumerable:!0,configurable:!0,writable:!0,value:void 0})}createNewAbortSignal(){if(this.controller){let e=Error(`Cancelling existing WebAuthn API call for new one`);e.name=`AbortError`,this.controller.abort(e)}let e=new AbortController;return this.controller=e,e.signal}cancelCeremony(){if(this.controller){let e=Error(`Manually cancelling existing WebAuthn API call`);e.name=`AbortError`,this.controller.abort(e),this.controller=void 0}}},w=[`cross-platform`,`platform`];function T(e){if(e&&!(w.indexOf(e)<0))return e}function E(){if(!v())return D.stubThis(new Promise(e=>e(!1)));let e=globalThis.PublicKeyCredential;return e?.isConditionalMediationAvailable===void 0?D.stubThis(new Promise(e=>e(!1))):D.stubThis(e.isConditionalMediationAvailable())}var D={stubThis:e=>e};function O({error:e,options:t}){let{publicKey:n}=t;if(!n)throw Error(`options was missing required publicKey property`);if(e.name===`AbortError`){if(t.signal instanceof AbortSignal)return new S({message:`Authentication ceremony was sent an abort signal`,code:`ERROR_CEREMONY_ABORTED`,cause:e})}else if(e.name===`NotAllowedError`)return new S({message:e.message,code:`ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY`,cause:e});else if(e.name===`SecurityError`){let t=globalThis.location.hostname;if(!x(t))return new S({message:`${globalThis.location.hostname} is an invalid domain`,code:`ERROR_INVALID_DOMAIN`,cause:e});if(n.rpId!==t)return new S({message:`The RP ID "${n.rpId}" is invalid for this domain`,code:`ERROR_INVALID_RP_ID`,cause:e})}else if(e.name===`UnknownError`)return new S({message:`The authenticator was unable to process the specified options, or could not create a new assertion signature`,code:`ERROR_AUTHENTICATOR_GENERAL_ERROR`,cause:e});return e}async function k(e){!e.optionsJSON&&e.challenge&&(console.warn(`startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.`),e={optionsJSON:e});let{optionsJSON:t,useBrowserAutofill:n=!1,verifyBrowserAutofillInput:r=!0}=e;if(!v())throw Error(`WebAuthn is not supported in this browser`);let i;t.allowCredentials?.length!==0&&(i=t.allowCredentials?.map(b));let a={...t,challenge:_(t.challenge),allowCredentials:i},o={};if(n){if(!await E())throw Error(`Browser does not support WebAuthn autofill`);if(document.querySelectorAll(`input[autocomplete$='webauthn']`).length<1&&r)throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');o.mediation=`conditional`,a.allowCredentials=[]}o.publicKey=a,o.signal=C.createNewAbortSignal();let s;try{s=await navigator.credentials.get(o)}catch(e){throw O({error:e,options:o})}if(!s)throw Error(`Authentication was not completed`);let{id:c,rawId:l,response:u,type:d}=s,f;return u.userHandle&&(f=g(u.userHandle)),{id:c,rawId:g(l),response:{authenticatorData:g(u.authenticatorData),clientDataJSON:g(u.clientDataJSON),signature:g(u.signature),userHandle:f},type:d,clientExtensionResults:s.getClientExtensionResults(),authenticatorAttachment:T(s.authenticatorAttachment)}}function A(){return v()?PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable():new Promise(e=>e(!1))}var j=c`
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
`,M=class extends e{constructor(e){if(super(e),this.it=u,e.type!==r.CHILD)throw Error(this.constructor.directiveName+`() can only be used in child bindings`)}render(e){if(e===u||e==null)return this._t=void 0,this.it=e;if(e===l)return e;if(typeof e!=`string`)throw Error(this.constructor.directiveName+`() called with a non-string value`);if(e===this.it)return this._t;this.it=e;let t=[e];return t.raw=t,this._t={_$litType$:this.constructor.resultType,strings:t,values:[]}}};M.directiveName=`unsafeHTML`,M.resultType=1;var N=t(M),P=class extends a{static{this.METHOD=`totp`}get endpoint(){return`auth/verify-totp`}renderInput(){return s`
      <craft-input
        label="${n(`Verification Code`)}"
        id="totp-code"
        class="totp-code"
        name="code"
        .maxlength="${6}"
        autocomplete="one-time-code"
        inputmode="numeric"
        aria-required="true"
      ></craft-input>
    `}};h([m(`craft-input.totp-code`)],P.prototype,`_input`,void 0),a.register(`craft-totp-form`,P);var F=class extends a{static{this.METHOD=`recovery-codes`}get endpoint(){return`auth/verify-recovery-code`}renderInput(){return s`
      <craft-input
        label="${n(`Recovery Code`)}"
        id="recovery-code"
        class="recovery-code"
        name="code"
        autocomplete="off"
        aria-required="true"
      ></craft-input>
    `}};h([m(`craft-input.recovery-code`)],F.prototype,`_input`,void 0),a.register(`craft-recovery-code-form`,F);var I=class extends d{constructor(...e){super(...e),this._switching=!1,this.#e=!1}static{this.styles=[j]}#e;async updated(e){super.updated(e),!a.isNative(this.data?.authMethod)&&!this.#e&&!this._switching&&this._container&&(this.#e=!0,await this.#t())}async#t(){this._container&&(await Craft.appendHeadHtml(this.data.headHtml),await Craft.appendBodyHtml(this.data.bodyHtml),Craft.initUiElements(this._container),Craft.createAuthFormHandler(this.data.authMethod,this._container,()=>{this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.data.returnUrl}}))},e=>{this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:e}}))}),this._container.querySelector(`:focus-visible, input, button`)?.focus())}async#n(e){this._switching=!0,this.#e=!1;try{let t=await fetch(e.url,{headers:{Accept:`application/json`,"Content-Type":`application/json`}});if(!t.ok)throw Error(`Failed to fetch challenge data.`);this.data=await t.json()}finally{this._switching=!1}}render(){return this._switching?s`
        <craft-pane>
          <div class="spinner-overlay">
            <craft-spinner></craft-spinner>
          </div>
        </craft-pane>
      `:s`
      <craft-pane>
        <div class="auth-form-container">${N(this.data.authForm)}</div>
        ${this.data.otherMethods.length?s`
              <hr />
              <craft-action-menu>
                <craft-button slot="invoker" appearance="plain" size="zero">
                  <craft-icon slot="prefix" name="chevron-down"></craft-icon>
                  ${n(`Try another way`)}
                </craft-button>

                <div slot="content">
                  ${this.data.otherMethods.map(e=>s`
                      <craft-action-item
                        @click="${()=>this.#n(e)}"
                      >
                        ${e.name}
                      </craft-action-item>
                    `)}
                </div>
              </craft-action-menu>
            `:u}
      </craft-pane>
    `}};h([f({attribute:!1})],I.prototype,`data`,void 0),h([p()],I.prototype,`_switching`,void 0),h([m(`.auth-form-container`)],I.prototype,`_container`,void 0),customElements.get(`craft-login-challenge`)||customElements.define(`craft-login-challenge`,I);var L=class extends d{constructor(...e){super(...e),this.useEmailAsUsername=!1,this.username=``,this._busy=!1,this._error=``}static{this.styles=[j]}firstUpdated(){this._input?.focus()}#e(){return this.useEmailAsUsername?n(`Email`):n(`Username or Email`)}async#t(e){e.preventDefault(),this._error=``,this._busy=!0;try{await o.post(`users/send-password-reset-email`,{loginName:this._input.value});let e=document.createElement(`craft-dialog`);e.setAttribute(`open`,``);let t=document.createElement(`p`);t.textContent=n(`Check your email for instructions to reset your password.`),e.appendChild(t),document.body.appendChild(e)}catch(e){this._error=e?.response?.data?.message??n(`A server error occurred.`)}finally{this._busy=!1}}#n(){this.dispatchEvent(new CustomEvent(`craft:login:reset-back`,{bubbles:!0,composed:!0,detail:{username:this._input?.value??``}}))}render(){return s`
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
              ${n(`Reset password`)}
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
          ${n(`Back to sign in`)}
        </craft-button>
      </craft-pane>
    `}};h([f({type:Boolean,attribute:`use-email-as-username`})],L.prototype,`useEmailAsUsername`,void 0),h([f()],L.prototype,`username`,void 0),h([p()],L.prototype,`_busy`,void 0),h([p()],L.prototype,`_error`,void 0),h([m(`.reset-username`)],L.prototype,`_input`,void 0),customElements.get(`craft-login-reset-password`)||customElements.define(`craft-login-reset-password`,L);var R=class extends d{constructor(...e){super(...e),this.showPasskeyBtn=!0,this.showResetPassword=!1,this.showRememberMe=!1,this.username=``,this.staticEmail=``,this.useEmailAsUsername=!1,this.rememberMeLabel=``,this.initialError=``,this.action=``,this._view=`login`,this._error=``,this._loginBusy=!1,this._passkeyBusy=!1,this._canUsePasskey=!1,this._twoFactorData=null,this._resetUsername=``}static{this.styles=[i,j]}async connectedCallback(){super.connectedCallback(),this.initialError&&(this._error=this.initialError),this.showPasskeyBtn&&v()&&(this._canUsePasskey=await A())}#e(){return this.useEmailAsUsername?n(`Email`):n(`Username or Email`)}async#t(e){e.preventDefault(),this._error=``,this._loginBusy=!0;try{let e=await fetch(this.action,{method:`post`,headers:{Accept:`application/json`,"Content-Type":`application/json`},body:JSON.stringify({loginName:this._usernameInput.value,password:this._passwordInput.value,rememberMe:this._rememberMeInput?.checked?`1`:``})}),t=await e.json();if(!e.ok)throw Error(t.message||`A server error occurred.`);t.authMethod?(this._twoFactorData=t,this._view=`challenge`,this._loginBusy=!1):(this.#c(t.returnUrl),this._loginBusy=!1)}catch(e){this._loginBusy=!1,this.#s(e.message)}}async#n(){if(!this._passkeyBusy){this._error=``,this._passkeyBusy=!0;try{let{data:e}=await o.post(`auth/passkey-request-options`),t=await k({optionsJSON:JSON.parse(e.options)}),{data:n}=await o.post(`users/login-with-passkey`,{requestOptions:e.options,authResponse:JSON.stringify(t)});this.#c(n.returnUrl),this._passkeyBusy=!1}catch(e){this._passkeyBusy=!1;let t=e?.response?.data?.message;t?this.#s(t):console.warn(e)}}}#r(){this._error=``,this._resetUsername=this._usernameInput?.value??``,this._view=`reset-password`}#i(e){let t=e.detail?.username??``;this._view=`login`,this.updateComplete.then(()=>{t&&this._usernameInput&&(this._usernameInput.value=t),this._usernameInput?.focus()})}#a(e){this.#c(e.detail.returnUrl)}#o(e){let t=e.detail.message,n=new CustomEvent(`craft:login:error`,{bubbles:!0,composed:!0,cancelable:!0,detail:{message:t}});this.dispatchEvent(n),n.defaultPrevented||this.#s(t)}#s(e){this._error=e.trim();let t=this.shadowRoot?.querySelector(`.cp-visually-hidden[role="status"]`);t&&(t.textContent=e)}#c(e){let t=new CustomEvent(`craft:login:success`,{bubbles:!0,composed:!0,cancelable:!0,detail:{returnUrl:e}});this.dispatchEvent(t),t.defaultPrevented||(window.location.href=e)}render(){return s`
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
                label="${n(`Password`)}"
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
                      ${n(`Forgot password?`)}
                    </craft-button>
                  `:u}
            </div>

            ${this.showRememberMe?s`
                  <div class="remember-me-row">
                    <craft-checkbox
                      label="${this.rememberMeLabel||n(`Stay signed in`)}"
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
              ${n(`Sign in`)}
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
                      ${n(`Sign in with a passkey`)}
                    </craft-button>
                  `:u}
              <slot name="alternative-methods"></slot>
            </div>
          `:u}
    `}};h([f({type:Boolean,attribute:`show-passkey-btn`})],R.prototype,`showPasskeyBtn`,void 0),h([f({type:Boolean,attribute:`show-reset-password`})],R.prototype,`showResetPassword`,void 0),h([f({type:Boolean,attribute:`show-remember-me`})],R.prototype,`showRememberMe`,void 0),h([f()],R.prototype,`username`,void 0),h([f({attribute:`static-email`})],R.prototype,`staticEmail`,void 0),h([f({type:Boolean,attribute:`use-email-as-username`})],R.prototype,`useEmailAsUsername`,void 0),h([f({attribute:`remember-me-label`})],R.prototype,`rememberMeLabel`,void 0),h([f({attribute:`initial-error`})],R.prototype,`initialError`,void 0),h([f()],R.prototype,`action`,void 0),h([p()],R.prototype,`_view`,void 0),h([p()],R.prototype,`_error`,void 0),h([p()],R.prototype,`_loginBusy`,void 0),h([p()],R.prototype,`_passkeyBusy`,void 0),h([p()],R.prototype,`_canUsePasskey`,void 0),h([p()],R.prototype,`_twoFactorData`,void 0),h([p()],R.prototype,`_resetUsername`,void 0),h([m(`.login-username`)],R.prototype,`_usernameInput`,void 0),h([m(`craft-input-password.login-password`)],R.prototype,`_passwordInput`,void 0),h([m(`.login-remember-me`)],R.prototype,`_rememberMeInput`,void 0),customElements.get(`craft-login-form`)||customElements.define(`craft-login-form`,R);