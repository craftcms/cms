import{c as e,i as t,o as n,s as r}from"./nav-item-Bfzb7bVl-kS60DrF6.js";import{B as i,E as a,J as o,L as s,S as c,b as l,gt as u,l as d,lt as f,t as ee,vt as te,x as ne,y as p}from"./_plugin-vue_export-helper-D1rWAzzS.js";import{c as m,i as h,r as g,t as _}from"./lit-X8lyiNX5.js";import{a as v,i as y,r as b}from"./decorators-CaOws0gC.js";import{n as x}from"./Queue.ts-BUzYJhyf.js";import{i as S}from"./wayfinder-Bdp6r9Ay.js";import{t as C}from"./decorate-BpI2plOt.js";import{n as w,r as T,t as E}from"./recovery-code-form-DfnG3S1t.js";import{n as D}from"./LoginController-DQ2PO5vg.js";var O={class:`cp-login`},k={class:`grid gap-3 justify-items-center`},A={key:0,class:`flex justify-center`},j=[`src`,`alt`],M={class:`w-sm`},N=ee(a({__name:`AuthBase`,setup(e){let{general:t,system:n}=S();return(e,r)=>(s(),c(`div`,O,[p(`div`,k,[f(t).cpLogoUrl?(s(),c(`h1`,A,[p(`img`,{src:f(t).cpLogoUrl,alt:f(n).name,width:`288px`,height:`auto`,class:`inline-block`},null,8,j)])):ne(``,!0),p(`div`,M,[i(e.$slots,`default`,{},void 0,!0)])])]))}}),[[`__scopeId`,`data-v-3da43497`]]);function P(e){let t=new Uint8Array(e),n=``;for(let e of t)n+=String.fromCharCode(e);return btoa(n).replace(/\+/g,`-`).replace(/\//g,`_`).replace(/=/g,``)}function F(e){let t=e.replace(/-/g,`+`).replace(/_/g,`/`),n=(4-t.length%4)%4,r=t.padEnd(t.length+n,`=`),i=atob(r),a=new ArrayBuffer(i.length),o=new Uint8Array(a);for(let e=0;e<i.length;e++)o[e]=i.charCodeAt(e);return a}function I(){return L.stubThis(globalThis?.PublicKeyCredential!==void 0&&typeof globalThis.PublicKeyCredential==`function`)}var L={stubThis:e=>e};function R(e){let{id:t}=e;return{...e,id:F(t),transports:e.transports}}function z(e){return e===`localhost`||/^((xn--[a-z0-9-]+|[a-z0-9]+(-[a-z0-9]+)*)\.)+([a-z]{2,}|xn--[a-z0-9-]+)$/i.test(e)}var B=class extends Error{constructor({message:e,code:t,cause:n,name:r}){super(e,{cause:n}),Object.defineProperty(this,`code`,{enumerable:!0,configurable:!0,writable:!0,value:void 0}),this.name=r??n.name,this.code=t}},V=new class{constructor(){Object.defineProperty(this,`controller`,{enumerable:!0,configurable:!0,writable:!0,value:void 0})}createNewAbortSignal(){if(this.controller){let e=Error(`Cancelling existing WebAuthn API call for new one`);e.name=`AbortError`,this.controller.abort(e)}let e=new AbortController;return this.controller=e,e.signal}cancelCeremony(){if(this.controller){let e=Error(`Manually cancelling existing WebAuthn API call`);e.name=`AbortError`,this.controller.abort(e),this.controller=void 0}}},H=[`cross-platform`,`platform`];function U(e){if(e&&!(H.indexOf(e)<0))return e}function W(){if(!I())return G.stubThis(new Promise(e=>e(!1)));let e=globalThis.PublicKeyCredential;return e?.isConditionalMediationAvailable===void 0?G.stubThis(new Promise(e=>e(!1))):G.stubThis(e.isConditionalMediationAvailable())}var G={stubThis:e=>e};function K({error:e,options:t}){let{publicKey:n}=t;if(!n)throw Error(`options was missing required publicKey property`);if(e.name===`AbortError`){if(t.signal instanceof AbortSignal)return new B({message:`Authentication ceremony was sent an abort signal`,code:`ERROR_CEREMONY_ABORTED`,cause:e})}else if(e.name===`NotAllowedError`)return new B({message:e.message,code:`ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY`,cause:e});else if(e.name===`SecurityError`){let t=globalThis.location.hostname;if(!z(t))return new B({message:`${globalThis.location.hostname} is an invalid domain`,code:`ERROR_INVALID_DOMAIN`,cause:e});if(n.rpId!==t)return new B({message:`The RP ID "${n.rpId}" is invalid for this domain`,code:`ERROR_INVALID_RP_ID`,cause:e})}else if(e.name===`UnknownError`)return new B({message:`The authenticator was unable to process the specified options, or could not create a new assertion signature`,code:`ERROR_AUTHENTICATOR_GENERAL_ERROR`,cause:e});return e}async function q(e){!e.optionsJSON&&e.challenge&&(console.warn(`startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.`),e={optionsJSON:e});let{optionsJSON:t,useBrowserAutofill:n=!1,verifyBrowserAutofillInput:r=!0}=e;if(!I())throw Error(`WebAuthn is not supported in this browser`);let i;t.allowCredentials?.length!==0&&(i=t.allowCredentials?.map(R));let a={...t,challenge:F(t.challenge),allowCredentials:i},o={};if(n){if(!await W())throw Error(`Browser does not support WebAuthn autofill`);if(document.querySelectorAll(`input[autocomplete$='webauthn']`).length<1&&r)throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');o.mediation=`conditional`,a.allowCredentials=[]}o.publicKey=a,o.signal=V.createNewAbortSignal();let s;try{s=await navigator.credentials.get(o)}catch(e){throw K({error:e,options:o})}if(!s)throw Error(`Authentication was not completed`);let{id:c,rawId:l,response:u,type:d}=s,f;return u.userHandle&&(f=P(u.userHandle)),{id:c,rawId:P(l),response:{authenticatorData:P(u.authenticatorData),clientDataJSON:P(u.clientDataJSON),signature:P(u.signature),userHandle:f},type:d,clientExtensionResults:s.getClientExtensionResults(),authenticatorAttachment:U(s.authenticatorAttachment)}}function J(){return I()?PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable():new Promise(e=>e(!1))}var Y=class extends r{constructor(t){if(super(t),this.it=g,t.type!==e.CHILD)throw Error(this.constructor.directiveName+`() can only be used in child bindings`)}render(e){if(e===g||e==null)return this._t=void 0,this.it=e;if(e===h)return e;if(typeof e!=`string`)throw Error(this.constructor.directiveName+`() called with a non-string value`);if(e===this.it)return this._t;this.it=e;let t=[e];return t.raw=t,this._t={_$litType$:this.constructor.resultType,strings:t,values:[]}}};Y.directiveName=`unsafeHTML`,Y.resultType=1;var X=n(Y),re=new Set([w.METHOD,E.METHOD]),Z=class extends _{constructor(...e){super(...e),this._switching=!1,this.#e=!1,this.#t=x.getInstance()}static{this.styles=[T]}#e;#t;async updated(e){super.updated(e),!re.has(this.data?.authMethod)&&!this.#e&&!this._switching&&this._container&&(this.#e=!0,await this.#n())}async#n(){this._container&&(await Craft.appendHeadHtml(this.data.headHtml),await Craft.appendBodyHtml(this.data.bodyHtml),Craft.initUiElements(this._container),Craft.createAuthFormHandler(this.data.authMethod,this._container,()=>{this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.data.returnUrl}}))},e=>{this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:e}}))}),this._container.querySelector(`:focus-visible, input, button`)?.focus())}async#r(e){this._switching=!0,this.#e=!1;try{let t=await fetch(e.url,{headers:{Accept:`application/json`,"Content-Type":`application/json`}});if(!t.ok)throw Error(`Failed to fetch challenge data.`);this.data=await t.json()}finally{this._switching=!1}}render(){return this._switching?m`
        <craft-pane>
          <div class="spinner-overlay">
            <craft-spinner></craft-spinner>
          </div>
        </craft-pane>
      `:m`
      <craft-pane>
        <div class="auth-form-container">${X(this.data.authForm)}</div>
        ${this.data.otherMethods.length?m`
              <hr />
              <craft-action-menu>
                <craft-button slot="invoker" appearance="plain" size="zero">
                  <craft-icon slot="prefix" name="chevron-down"></craft-icon>
                  ${t(`Try another way`)}
                </craft-button>

                <div slot="content">
                  ${this.data.otherMethods.map(e=>m`
                      <craft-action-item
                        @click="${()=>this.#r(e)}"
                      >
                        ${e.name}
                      </craft-action-item>
                    `)}
                </div>
              </craft-action-menu>
            `:g}
      </craft-pane>
    `}};C([v({attribute:!1})],Z.prototype,`data`,void 0),C([y()],Z.prototype,`_switching`,void 0),C([b(`.auth-form-container`)],Z.prototype,`_container`,void 0),customElements.get(`craft-login-challenge`)||customElements.define(`craft-login-challenge`,Z);var Q=class extends _{constructor(...e){super(...e),this.useEmailAsUsername=!1,this.username=``,this._busy=!1,this._error=``,this._validateOnInput=!1}static{this.styles=[T]}firstUpdated(){this._input?.focus()}#e(){return this.useEmailAsUsername?t(`Email`):t(`Username or Email`)}#t(){let e=this._input?.value??``;return e.length===0?this.useEmailAsUsername?t(`Invalid email.`):t(`Invalid username or email.`):this.useEmailAsUsername&&!e.match(/.+@.+\..+/)?t(`Invalid email.`):!0}#n(){this._validateOnInput&&this.#t()===!0&&(this._error=``)}async#r(e){e.preventDefault();let n=this.#t();if(n!==!0){this._error=n,this._validateOnInput=!0;return}this._error=``,this._busy=!0;try{await u.post(`users/send-password-reset-email`,{loginName:this._input.value});let e=document.createElement(`craft-dialog`);e.setAttribute(`open`,``);let n=document.createElement(`p`);n.textContent=t(`Check your email for instructions to reset your password.`),e.appendChild(n),document.body.appendChild(e)}catch(e){this._error=e?.response?.data?.message??t(`A server error occurred.`)}finally{this._busy=!1}}#i(){this.dispatchEvent(new CustomEvent(`craft:login:reset-back`,{bubbles:!0,composed:!0,detail:{username:this._input?.value??``}}))}render(){return m`
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

          ${this._error?m`<craft-callout variant="danger" class="login-form__error"
                >${this._error}</craft-callout
              >`:g}
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
    `}};C([v({type:Boolean,attribute:`use-email-as-username`})],Q.prototype,`useEmailAsUsername`,void 0),C([v()],Q.prototype,`username`,void 0),C([y()],Q.prototype,`_busy`,void 0),C([y()],Q.prototype,`_error`,void 0),C([y()],Q.prototype,`_validateOnInput`,void 0),C([b(`.reset-username`)],Q.prototype,`_input`,void 0),customElements.get(`craft-login-reset-password`)||customElements.define(`craft-login-reset-password`,Q);var $=class extends _{constructor(...e){super(...e),this.showPasskeyBtn=!0,this.showResetPassword=!1,this.showRememberMe=!1,this.username=``,this.staticEmail=``,this.useEmailAsUsername=!1,this.minPasswordLength=6,this.maxPasswordLength=160,this.rememberMeLabel=``,this.initialError=``,this.action=``,this._view=`login`,this._error=``,this._loginBusy=!1,this._passkeyBusy=!1,this._canUsePasskey=!1,this._validateOnInput=!1,this._twoFactorData=null,this._resetUsername=``}static{this.styles=[te,T]}async connectedCallback(){super.connectedCallback(),this.initialError&&(this._error=this.initialError),this.showPasskeyBtn&&I()&&(this._canUsePasskey=await J())}#e(){return this.useEmailAsUsername?t(`Email`):t(`Username or Email`)}#t(){let e=this._usernameInput?.value??``;if(e.length===0)return this.useEmailAsUsername?t(`Invalid email.`):t(`Invalid username or email.`);if(this.useEmailAsUsername&&!e.match(/.+@.+\..+/))return t(`Invalid email.`);let n=this._passwordInput?.value.length??0;return n<this.minPasswordLength?t(`{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.`,{attribute:t(`Password`),min:this.minPasswordLength},`yii`):n>this.maxPasswordLength?t(`{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.`,{attribute:t(`Password`),max:this.maxPasswordLength},`yii`):!0}#n(){this._validateOnInput&&this.#t()===!0&&(this._error=``)}async#r(e){e.preventDefault();let n=this.#t();if(n!==!0){this.#l(n),this._validateOnInput=!0;return}this._error=``,this._loginBusy=!0;try{let e=await fetch(this.action,{method:`post`,headers:{Accept:`application/json`,"Content-Type":`application/json`},body:JSON.stringify({loginName:this._usernameInput.value,password:this._passwordInput.value,rememberMe:this._rememberMeInput?.checked?`1`:``})});if(!e.ok)throw Error(`Something went wrong.`);let t=await e.json();t.authMethod?(this._twoFactorData=t,this._view=`challenge`,this._loginBusy=!1):(this.#u(t.returnUrl),this._loginBusy=!1)}catch(e){console.log(e),this._loginBusy=!1,this.#l(e?.response?.data?.message??t(`A server error occurred.`))}}async#i(){if(!this._passkeyBusy){this._error=``,this._passkeyBusy=!0;try{let{data:e}=await u.post(`auth/passkey-request-options`),t=await q({optionsJSON:JSON.parse(e.options)}),{data:n}=await u.post(`users/login-with-passkey`,{requestOptions:e.options,authResponse:JSON.stringify(t)});this.#u(n.returnUrl),this._passkeyBusy=!1}catch(e){this._passkeyBusy=!1;let t=e?.response?.data?.message;t?this.#l(t):console.warn(e)}}}#a(){this._error=``,this._resetUsername=this._usernameInput?.value??``,this._view=`reset-password`}#o(e){let t=e.detail?.username??``;this._view=`login`,this.updateComplete.then(()=>{t&&this._usernameInput&&(this._usernameInput.value=t),this._usernameInput?.focus()})}#s(e){this.#u(e.detail.returnUrl)}#c(e){let t=e.detail.message,n=new CustomEvent(`craft:login:error`,{bubbles:!0,composed:!0,cancelable:!0,detail:{message:t}});this.dispatchEvent(n),n.defaultPrevented||this.#l(t)}#l(e){this._error=e.trim();let t=this.shadowRoot?.querySelector(`.cp-visually-hidden[role="status"]`);t&&(t.textContent=e)}#u(e){let t=new CustomEvent(`craft:login:success`,{bubbles:!0,composed:!0,cancelable:!0,detail:{returnUrl:e}});this.dispatchEvent(t),t.defaultPrevented||(window.location.href=e)}render(){return m`
      <div>
        <span
          class="cp-visually-hidden"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        ></span>

        ${this._view===`login`?this.#d():this._view===`reset-password`?m`
                <craft-login-reset-password
                  ?use-email-as-username="${this.useEmailAsUsername}"
                  username="${this._resetUsername}"
                  @craft:login:reset-back="${this.#o}"
                ></craft-login-reset-password>
              `:m`
                <craft-login-challenge
                  .data="${this._twoFactorData}"
                  @login-verified="${this.#s}"
                  @login-failed="${this.#c}"
                ></craft-login-challenge>
              `}
      </div>
    `}#d(){let e=this._canUsePasskey||this.querySelector(`[slot="alternative-methods"]`);return m`
      <craft-pane>
        <form
          class="login-form"
          method="post"
          accept-charset="UTF-8"
          @submit="${this.#r}"
        >
          <craft-field-group>
            ${this.staticEmail?m`<input
                  type="hidden"
                  class="login-username"
                  name="username"
                  .value="${this.staticEmail}"
                />`:m`
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

              ${this.showResetPassword?m`
                    <craft-button
                      type="button"
                      size="small"
                      appearance="plain"
                      @click="${this.#a}"
                      style="margin-block-start: var(--c-spacing-sm)"
                    >
                      ${t(`Forgot password?`)}
                    </craft-button>
                  `:g}
            </div>

            ${this.showRememberMe?m`
                  <div class="remember-me-row">
                    <craft-checkbox
                      label="${this.rememberMeLabel||t(`Stay signed in`)}"
                      type="checkbox"
                      id="login-remember-me"
                      class="login-remember-me"
                    ></craft-checkbox>
                  </div>
                `:g}
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

        ${this._error?m`<craft-callout class="login-form__error" variant="danger"
              >${this._error}</craft-callout
            >`:g}
      </craft-pane>

      ${e?m`
            <div class="alternative-login-methods">
              ${this._canUsePasskey?m`
                    <craft-button
                      type="button"
                      appearance="filled"
                      ?loading="${this._passkeyBusy}"
                      @click="${this.#i}"
                      style="width: 100%"
                    >
                      ${t(`Sign in with a passkey`)}
                    </craft-button>
                  `:g}
              <slot name="alternative-methods"></slot>
            </div>
          `:g}
    `}};C([v({type:Boolean,attribute:`show-passkey-btn`})],$.prototype,`showPasskeyBtn`,void 0),C([v({type:Boolean,attribute:`show-reset-password`})],$.prototype,`showResetPassword`,void 0),C([v({type:Boolean,attribute:`show-remember-me`})],$.prototype,`showRememberMe`,void 0),C([v()],$.prototype,`username`,void 0),C([v({attribute:`static-email`})],$.prototype,`staticEmail`,void 0),C([v({type:Boolean,attribute:`use-email-as-username`})],$.prototype,`useEmailAsUsername`,void 0),C([v({type:Number,attribute:`min-password-length`})],$.prototype,`minPasswordLength`,void 0),C([v({type:Number,attribute:`max-password-length`})],$.prototype,`maxPasswordLength`,void 0),C([v({attribute:`remember-me-label`})],$.prototype,`rememberMeLabel`,void 0),C([v({attribute:`initial-error`})],$.prototype,`initialError`,void 0),C([v()],$.prototype,`action`,void 0),C([y()],$.prototype,`_view`,void 0),C([y()],$.prototype,`_error`,void 0),C([y()],$.prototype,`_loginBusy`,void 0),C([y()],$.prototype,`_passkeyBusy`,void 0),C([y()],$.prototype,`_canUsePasskey`,void 0),C([y()],$.prototype,`_validateOnInput`,void 0),C([y()],$.prototype,`_twoFactorData`,void 0),C([y()],$.prototype,`_resetUsername`,void 0),C([b(`.login-username`)],$.prototype,`_usernameInput`,void 0),C([b(`craft-input-password.login-password`)],$.prototype,`_passwordInput`,void 0),C([b(`.login-remember-me`)],$.prototype,`_rememberMeInput`,void 0),customElements.get(`craft-login-form`)||customElements.define(`craft-login-form`,$);var ie=[`action`,`username`,`use-email-as-username`],ae=a({__name:`LoginPage`,props:{logo:{},errors:{},authFormData:{}},setup(e){let t=d(),{general:n}=S();return(e,r)=>(s(),l(N,null,{default:o(()=>[p(`craft-login-form`,{action:f(D)().url,"show-reset-password":``,"show-remember-me":``,username:f(t).props.username,"use-email-as-username":f(n).useEmailAsUsername},null,8,ie)]),_:1}))}});export{ae as default};