import{c as e,i as t,o as n,s as r}from"./nav-item-Bfzb7bVl-kS60DrF6.js";import{B as i,E as a,J as o,L as s,S as c,_t as l,b as u,l as d,lt as f,mt as p,t as ee,x as te,y as m,yt as h}from"./_plugin-vue_export-helper-BLD82GEK.js";import{c as g,f as _,i as v,r as y,t as b}from"./lit-X8lyiNX5.js";import{a as x,i as S,r as C}from"./decorators-CaOws0gC.js";import{n as w}from"./Queue.ts-BUzYJhyf.js";import{i as T}from"./wayfinder-DmhY2auL.js";import{t as E}from"./decorate-BpI2plOt.js";import"./recovery-code-form-jfN4bCig.js";import{n as D}from"./LoginController-BV5gkC9o.js";var O={class:`cp-login`},k={class:`grid gap-3 justify-items-center`},A={key:0,class:`flex justify-center`},ne=[`src`,`alt`],j={class:`w-sm`},M=ee(a({__name:`AuthBase`,setup(e){let{general:t,system:n}=T();return(e,r)=>(s(),c(`div`,O,[m(`div`,k,[f(t).cpLogoUrl?(s(),c(`h1`,A,[m(`img`,{src:f(t).cpLogoUrl,alt:f(n).name,width:`288px`,height:`auto`,class:`inline-block`},null,8,ne)])):te(``,!0),m(`div`,j,[i(e.$slots,`default`,{},void 0,!0)])])]))}}),[[`__scopeId`,`data-v-3da43497`]]);function N(e){let t=new Uint8Array(e),n=``;for(let e of t)n+=String.fromCharCode(e);return btoa(n).replace(/\+/g,`-`).replace(/\//g,`_`).replace(/=/g,``)}function P(e){let t=e.replace(/-/g,`+`).replace(/_/g,`/`),n=(4-t.length%4)%4,r=t.padEnd(t.length+n,`=`),i=atob(r),a=new ArrayBuffer(i.length),o=new Uint8Array(a);for(let e=0;e<i.length;e++)o[e]=i.charCodeAt(e);return a}function F(){return I.stubThis(globalThis?.PublicKeyCredential!==void 0&&typeof globalThis.PublicKeyCredential==`function`)}var I={stubThis:e=>e};function L(e){let{id:t}=e;return{...e,id:P(t),transports:e.transports}}function R(e){return e===`localhost`||/^((xn--[a-z0-9-]+|[a-z0-9]+(-[a-z0-9]+)*)\.)+([a-z]{2,}|xn--[a-z0-9-]+)$/i.test(e)}var z=class extends Error{constructor({message:e,code:t,cause:n,name:r}){super(e,{cause:n}),Object.defineProperty(this,`code`,{enumerable:!0,configurable:!0,writable:!0,value:void 0}),this.name=r??n.name,this.code=t}},B=new class{constructor(){Object.defineProperty(this,`controller`,{enumerable:!0,configurable:!0,writable:!0,value:void 0})}createNewAbortSignal(){if(this.controller){let e=Error(`Cancelling existing WebAuthn API call for new one`);e.name=`AbortError`,this.controller.abort(e)}let e=new AbortController;return this.controller=e,e.signal}cancelCeremony(){if(this.controller){let e=Error(`Manually cancelling existing WebAuthn API call`);e.name=`AbortError`,this.controller.abort(e),this.controller=void 0}}},V=[`cross-platform`,`platform`];function H(e){if(e&&!(V.indexOf(e)<0))return e}function U(){if(!F())return W.stubThis(new Promise(e=>e(!1)));let e=globalThis.PublicKeyCredential;return e?.isConditionalMediationAvailable===void 0?W.stubThis(new Promise(e=>e(!1))):W.stubThis(e.isConditionalMediationAvailable())}var W={stubThis:e=>e};function G({error:e,options:t}){let{publicKey:n}=t;if(!n)throw Error(`options was missing required publicKey property`);if(e.name===`AbortError`){if(t.signal instanceof AbortSignal)return new z({message:`Authentication ceremony was sent an abort signal`,code:`ERROR_CEREMONY_ABORTED`,cause:e})}else if(e.name===`NotAllowedError`)return new z({message:e.message,code:`ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY`,cause:e});else if(e.name===`SecurityError`){let t=globalThis.location.hostname;if(!R(t))return new z({message:`${globalThis.location.hostname} is an invalid domain`,code:`ERROR_INVALID_DOMAIN`,cause:e});if(n.rpId!==t)return new z({message:`The RP ID "${n.rpId}" is invalid for this domain`,code:`ERROR_INVALID_RP_ID`,cause:e})}else if(e.name===`UnknownError`)return new z({message:`The authenticator was unable to process the specified options, or could not create a new assertion signature`,code:`ERROR_AUTHENTICATOR_GENERAL_ERROR`,cause:e});return e}async function K(e){!e.optionsJSON&&e.challenge&&(console.warn(`startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.`),e={optionsJSON:e});let{optionsJSON:t,useBrowserAutofill:n=!1,verifyBrowserAutofillInput:r=!0}=e;if(!F())throw Error(`WebAuthn is not supported in this browser`);let i;t.allowCredentials?.length!==0&&(i=t.allowCredentials?.map(L));let a={...t,challenge:P(t.challenge),allowCredentials:i},o={};if(n){if(!await U())throw Error(`Browser does not support WebAuthn autofill`);if(document.querySelectorAll(`input[autocomplete$='webauthn']`).length<1&&r)throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');o.mediation=`conditional`,a.allowCredentials=[]}o.publicKey=a,o.signal=B.createNewAbortSignal();let s;try{s=await navigator.credentials.get(o)}catch(e){throw G({error:e,options:o})}if(!s)throw Error(`Authentication was not completed`);let{id:c,rawId:l,response:u,type:d}=s,f;return u.userHandle&&(f=N(u.userHandle)),{id:c,rawId:N(l),response:{authenticatorData:N(u.authenticatorData),clientDataJSON:N(u.clientDataJSON),signature:N(u.signature),userHandle:f},type:d,clientExtensionResults:s.getClientExtensionResults(),authenticatorAttachment:H(s.authenticatorAttachment)}}function q(){return F()?PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable():new Promise(e=>e(!1))}var J=_`
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
`,Y=class extends r{constructor(t){if(super(t),this.it=y,t.type!==e.CHILD)throw Error(this.constructor.directiveName+`() can only be used in child bindings`)}render(e){if(e===y||e==null)return this._t=void 0,this.it=e;if(e===v)return e;if(typeof e!=`string`)throw Error(this.constructor.directiveName+`() called with a non-string value`);if(e===this.it)return this._t;this.it=e;let t=[e];return t.raw=t,this._t={_$litType$:this.constructor.resultType,strings:t,values:[]}}};Y.directiveName=`unsafeHTML`,Y.resultType=1;var X=n(Y),Z=class extends b{constructor(...e){super(...e),this._switching=!1,this.#e=!1,this.#t=w.getInstance()}static{this.styles=[J]}#e;#t;async updated(e){super.updated(e),!p.isNative(this.data?.authMethod)&&!this.#e&&!this._switching&&this._container&&(this.#e=!0,await this.#n())}async#n(){this._container&&(await Craft.appendHeadHtml(this.data.headHtml),await Craft.appendBodyHtml(this.data.bodyHtml),Craft.initUiElements(this._container),Craft.createAuthFormHandler(this.data.authMethod,this._container,()=>{this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.data.returnUrl}}))},e=>{this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:e}}))}),this._container.querySelector(`:focus-visible, input, button`)?.focus())}async#r(e){this._switching=!0,this.#e=!1;try{let t=await fetch(e.url,{headers:{Accept:`application/json`,"Content-Type":`application/json`}});if(!t.ok)throw Error(`Failed to fetch challenge data.`);this.data=await t.json()}finally{this._switching=!1}}render(){return this._switching?g`
        <craft-pane>
          <div class="spinner-overlay">
            <craft-spinner></craft-spinner>
          </div>
        </craft-pane>
      `:g`
      <craft-pane>
        <div class="auth-form-container">${X(this.data.authForm)}</div>
        ${this.data.otherMethods.length?g`
              <hr />
              <craft-action-menu>
                <craft-button slot="invoker" appearance="plain" size="zero">
                  <craft-icon slot="prefix" name="chevron-down"></craft-icon>
                  ${t(`Try another way`)}
                </craft-button>

                <div slot="content">
                  ${this.data.otherMethods.map(e=>g`
                      <craft-action-item
                        @click="${()=>this.#r(e)}"
                      >
                        ${e.name}
                      </craft-action-item>
                    `)}
                </div>
              </craft-action-menu>
            `:y}
      </craft-pane>
    `}};E([x({attribute:!1})],Z.prototype,`data`,void 0),E([S()],Z.prototype,`_switching`,void 0),E([C(`.auth-form-container`)],Z.prototype,`_container`,void 0),customElements.get(`craft-login-challenge`)||customElements.define(`craft-login-challenge`,Z);var Q=class extends b{constructor(...e){super(...e),this.useEmailAsUsername=!1,this.username=``,this._busy=!1,this._error=``,this._validateOnInput=!1}static{this.styles=[J]}firstUpdated(){this._input?.focus()}#e(){return this.useEmailAsUsername?t(`Email`):t(`Username or Email`)}#t(){let e=this._input?.value??``;return e.length===0?this.useEmailAsUsername?t(`Invalid email.`):t(`Invalid username or email.`):this.useEmailAsUsername&&!e.match(/.+@.+\..+/)?t(`Invalid email.`):!0}#n(){this._validateOnInput&&this.#t()===!0&&(this._error=``)}async#r(e){e.preventDefault();let n=this.#t();if(n!==!0){this._error=n,this._validateOnInput=!0;return}this._error=``,this._busy=!0;try{await l.post(`users/send-password-reset-email`,{loginName:this._input.value});let e=document.createElement(`craft-dialog`);e.setAttribute(`open`,``);let n=document.createElement(`p`);n.textContent=t(`Check your email for instructions to reset your password.`),e.appendChild(n),document.body.appendChild(e)}catch(e){this._error=e?.response?.data?.message??t(`A server error occurred.`)}finally{this._busy=!1}}#i(){this.dispatchEvent(new CustomEvent(`craft:login:reset-back`,{bubbles:!0,composed:!0,detail:{username:this._input?.value??``}}))}render(){return g`
      <craft-pane>
        <form
          class="login-form login-form--reset"
          method="post"
          accept-charset="UTF-8"
          @submit="${this.#r}"
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
              aria-required="true"
              @input="${this.#n}"
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

          ${this._error?g`<craft-callout variant="danger" class="login-form__error"
                >${this._error}</craft-callout
              >`:y}
        </form>

        <hr />

        <craft-button
          type="button"
          appearance="plain"
          size="small"
          @click="${this.#i}"
        >
          <craft-icon slot="prefix" name="arrow-left"></craft-icon>
          ${t(`Back to sign in`)}
        </craft-button>
      </craft-pane>
    `}};E([x({type:Boolean,attribute:`use-email-as-username`})],Q.prototype,`useEmailAsUsername`,void 0),E([x()],Q.prototype,`username`,void 0),E([S()],Q.prototype,`_busy`,void 0),E([S()],Q.prototype,`_error`,void 0),E([S()],Q.prototype,`_validateOnInput`,void 0),E([C(`.reset-username`)],Q.prototype,`_input`,void 0),customElements.get(`craft-login-reset-password`)||customElements.define(`craft-login-reset-password`,Q);var $=class extends b{constructor(...e){super(...e),this.showPasskeyBtn=!0,this.showResetPassword=!1,this.showRememberMe=!1,this.username=``,this.staticEmail=``,this.useEmailAsUsername=!1,this.minPasswordLength=6,this.maxPasswordLength=160,this.rememberMeLabel=``,this.initialError=``,this.action=``,this._view=`login`,this._error=``,this._loginBusy=!1,this._passkeyBusy=!1,this._canUsePasskey=!1,this._validateOnInput=!1,this._twoFactorData=null,this._resetUsername=``}static{this.styles=[h,J]}async connectedCallback(){super.connectedCallback(),this.initialError&&(this._error=this.initialError),this.showPasskeyBtn&&F()&&(this._canUsePasskey=await q())}#e(){return this.useEmailAsUsername?t(`Email`):t(`Username or Email`)}#t(){let e=this._usernameInput?.value??``;if(e.length===0)return this.useEmailAsUsername?t(`Invalid email.`):t(`Invalid username or email.`);if(this.useEmailAsUsername&&!e.match(/.+@.+\..+/))return t(`Invalid email.`);let n=this._passwordInput?.value.length??0;return n<this.minPasswordLength?t(`{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.`,{attribute:t(`Password`),min:this.minPasswordLength},`yii`):n>this.maxPasswordLength?t(`{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.`,{attribute:t(`Password`),max:this.maxPasswordLength},`yii`):!0}#n(){this._validateOnInput&&this.#t()===!0&&(this._error=``)}async#r(e){e.preventDefault();let n=this.#t();if(n!==!0){this.#l(n),this._validateOnInput=!0;return}this._error=``,this._loginBusy=!0;try{let e=await fetch(this.action,{method:`post`,headers:{Accept:`application/json`,"Content-Type":`application/json`},body:JSON.stringify({loginName:this._usernameInput.value,password:this._passwordInput.value,rememberMe:this._rememberMeInput?.checked?`1`:``})});if(!e.ok)throw Error(`Something went wrong.`);let t=await e.json();t.authMethod?(this._twoFactorData=t,this._view=`challenge`,this._loginBusy=!1):(this.#u(t.returnUrl),this._loginBusy=!1)}catch(e){console.log(e),this._loginBusy=!1,this.#l(e?.response?.data?.message??t(`A server error occurred.`))}}async#i(){if(!this._passkeyBusy){this._error=``,this._passkeyBusy=!0;try{let{data:e}=await l.post(`auth/passkey-request-options`),t=await K({optionsJSON:JSON.parse(e.options)}),{data:n}=await l.post(`users/login-with-passkey`,{requestOptions:e.options,authResponse:JSON.stringify(t)});this.#u(n.returnUrl),this._passkeyBusy=!1}catch(e){this._passkeyBusy=!1;let t=e?.response?.data?.message;t?this.#l(t):console.warn(e)}}}#a(){this._error=``,this._resetUsername=this._usernameInput?.value??``,this._view=`reset-password`}#o(e){let t=e.detail?.username??``;this._view=`login`,this.updateComplete.then(()=>{t&&this._usernameInput&&(this._usernameInput.value=t),this._usernameInput?.focus()})}#s(e){this.#u(e.detail.returnUrl)}#c(e){let t=e.detail.message,n=new CustomEvent(`craft:login:error`,{bubbles:!0,composed:!0,cancelable:!0,detail:{message:t}});this.dispatchEvent(n),n.defaultPrevented||this.#l(t)}#l(e){this._error=e.trim();let t=this.shadowRoot?.querySelector(`.cp-visually-hidden[role="status"]`);t&&(t.textContent=e)}#u(e){let t=new CustomEvent(`craft:login:success`,{bubbles:!0,composed:!0,cancelable:!0,detail:{returnUrl:e}});this.dispatchEvent(t),t.defaultPrevented||(window.location.href=e)}render(){return g`
      <div>
        <span
          class="cp-visually-hidden"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        ></span>

        ${this._view===`login`?this.#d():this._view===`reset-password`?g`
                <craft-login-reset-password
                  ?use-email-as-username="${this.useEmailAsUsername}"
                  username="${this._resetUsername}"
                  @craft:login:reset-back="${this.#o}"
                ></craft-login-reset-password>
              `:g`
                <craft-login-challenge
                  .data="${this._twoFactorData}"
                  @login-verified="${this.#s}"
                  @login-failed="${this.#c}"
                ></craft-login-challenge>
              `}
      </div>
    `}#d(){let e=this._canUsePasskey||this.querySelector(`[slot="alternative-methods"]`);return g`
      <craft-pane>
        <form
          class="login-form"
          method="post"
          accept-charset="UTF-8"
          @submit="${this.#r}"
        >
          <craft-field-group>
            ${this.staticEmail?g`<input
                  type="hidden"
                  class="login-username"
                  name="username"
                  .value="${this.staticEmail}"
                />`:g`
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
                      @user-input-changed="${this.#n}"
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
                @user-input-changed="${this.#n}"
              ></craft-input-password>

              ${this.showResetPassword?g`
                    <craft-button
                      type="button"
                      size="small"
                      appearance="plain"
                      @click="${this.#a}"
                      style="margin-block-start: var(--c-spacing-sm)"
                    >
                      ${t(`Forgot password?`)}
                    </craft-button>
                  `:y}
            </div>

            ${this.showRememberMe?g`
                  <div class="remember-me-row">
                    <craft-checkbox
                      label="${this.rememberMeLabel||t(`Stay signed in`)}"
                      type="checkbox"
                      id="login-remember-me"
                      class="login-remember-me"
                    ></craft-checkbox>
                  </div>
                `:y}
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

        ${this._error?g`<craft-callout class="login-form__error" variant="danger"
              >${this._error}</craft-callout
            >`:y}
      </craft-pane>

      ${e?g`
            <div class="alternative-login-methods">
              ${this._canUsePasskey?g`
                    <craft-button
                      type="button"
                      appearance="filled"
                      ?loading="${this._passkeyBusy}"
                      @click="${this.#i}"
                      style="width: 100%"
                    >
                      ${t(`Sign in with a passkey`)}
                    </craft-button>
                  `:y}
              <slot name="alternative-methods"></slot>
            </div>
          `:y}
    `}};E([x({type:Boolean,attribute:`show-passkey-btn`})],$.prototype,`showPasskeyBtn`,void 0),E([x({type:Boolean,attribute:`show-reset-password`})],$.prototype,`showResetPassword`,void 0),E([x({type:Boolean,attribute:`show-remember-me`})],$.prototype,`showRememberMe`,void 0),E([x()],$.prototype,`username`,void 0),E([x({attribute:`static-email`})],$.prototype,`staticEmail`,void 0),E([x({type:Boolean,attribute:`use-email-as-username`})],$.prototype,`useEmailAsUsername`,void 0),E([x({type:Number,attribute:`min-password-length`})],$.prototype,`minPasswordLength`,void 0),E([x({type:Number,attribute:`max-password-length`})],$.prototype,`maxPasswordLength`,void 0),E([x({attribute:`remember-me-label`})],$.prototype,`rememberMeLabel`,void 0),E([x({attribute:`initial-error`})],$.prototype,`initialError`,void 0),E([x()],$.prototype,`action`,void 0),E([S()],$.prototype,`_view`,void 0),E([S()],$.prototype,`_error`,void 0),E([S()],$.prototype,`_loginBusy`,void 0),E([S()],$.prototype,`_passkeyBusy`,void 0),E([S()],$.prototype,`_canUsePasskey`,void 0),E([S()],$.prototype,`_validateOnInput`,void 0),E([S()],$.prototype,`_twoFactorData`,void 0),E([S()],$.prototype,`_resetUsername`,void 0),E([C(`.login-username`)],$.prototype,`_usernameInput`,void 0),E([C(`craft-input-password.login-password`)],$.prototype,`_passwordInput`,void 0),E([C(`.login-remember-me`)],$.prototype,`_rememberMeInput`,void 0),customElements.get(`craft-login-form`)||customElements.define(`craft-login-form`,$);var re=[`action`,`username`,`use-email-as-username`],ie=a({__name:`LoginPage`,props:{logo:{},errors:{},authFormData:{}},setup(e){let t=d(),{general:n}=T();return(e,r)=>(s(),u(M,null,{default:o(()=>[m(`craft-login-form`,{action:f(D)().url,"show-reset-password":``,"show-remember-me":``,username:f(t).props.username,"use-email-as-username":f(n).useEmailAsUsername},null,8,re)]),_:1}))}});export{ie as default};