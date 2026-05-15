import{i as e}from"./nav-item-Bfzb7bVl-kS60DrF6.js";import{gt as t}from"./_plugin-vue_export-helper-D1rWAzzS.js";import{c as n,f as r,t as i}from"./lit-X8lyiNX5.js";import{a,i as o,r as s}from"./decorators-CaOws0gC.js";import{t as c}from"./decorate-BpI2plOt.js";var l=r`
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
`,u=class extends i{constructor(...e){super(...e),this.returnUrl=``,this._state=`idle`}static{this.styles=[l]}static{this.METHOD=`CraftCms\\Cms\\Auth\\Methods\\TOTP`}firstUpdated(){this._input?.focus()}#e(e){let t=e.detail?.modelValue??``;t.replace(/\s/g,``).length===6&&this.#n(t)}async#t(e){e.preventDefault(),await this.#n(this._input?.value??``)}async#n(n){if(this._state!==`loading`){this._state=`loading`;try{await t.post(`auth/verify-totp`,{code:n}),this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.returnUrl}})),this._state=`success`,setTimeout(()=>{this._state=`idle`},2e3)}catch(t){this._state=`error`,this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:t?.response?.data?.message??e(`A server error occurred.`)}}))}}}render(){return n`
      <form
        class="login-form"
        accept-charset="UTF-8"
        @submit="${this.#t}"
      >
        <div class="login-form__fields">
          <craft-input
            label="${e(`Verification Code`)}"
            id="totp-code"
            class="totp-code"
            name="code"
            .maxlength="${6}"
            autocomplete="one-time-code"
            inputmode="numeric"
            aria-required="true"
            @model-value-changed="${this.#e}"
          >
          </craft-input>
          <craft-button
            slot="after"
            type="submit"
            variant="primary"
            ?loading="${this._state===`loading`}"
          >
            ${e(`Verify`)}
          </craft-button>
        </div>
      </form>
    `}};c([a({attribute:`return-url`})],u.prototype,`returnUrl`,void 0),c([o()],u.prototype,`_state`,void 0),c([s(`craft-input.totp-code`)],u.prototype,`_input`,void 0),customElements.get(`craft-totp-form`)||customElements.define(`craft-totp-form`,u);var d=class extends i{constructor(...e){super(...e),this.returnUrl=``,this._busy=!1}static{this.styles=[l]}static{this.METHOD=`CraftCms\\Cms\\Auth\\Methods\\RecoveryCodes`}firstUpdated(){this._input?.focus()}#e(e){let t=e.detail?.modelValue??``;t.replace(/-/g,``).length===12&&this.#n(t)}async#t(e){e.preventDefault(),this.#n(this._input?.value??``)}async#n(n){if(!this._busy){this._busy=!0;try{await t.post(`auth/verify-recovery-code`,{code:n}),this.dispatchEvent(new CustomEvent(`login-verified`,{bubbles:!0,composed:!0,detail:{returnUrl:this.returnUrl}}))}catch(t){this.dispatchEvent(new CustomEvent(`login-failed`,{bubbles:!0,composed:!0,detail:{message:t?.response?.data?.message??e(`A server error occurred.`)}}))}finally{this._busy=!1}}}render(){return n`
      <form
        class="login-form"
        accept-charset="UTF-8"
        @submit="${this.#t}"
      >
        <div class="login-form__fields">
          <craft-input
            label="${e(`Recovery Code`)}"
            id="recovery-code"
            class="recovery-code"
            name="code"
            autocomplete="off"
            aria-required="true"
            @model-value-changed="${this.#e}"
          ></craft-input>
          <craft-button
            type="submit"
            variant="primary"
            ?loading="${this._busy}"
          >
            ${e(`Verify`)}
          </craft-button>
        </div>
      </form>
    `}};c([a({attribute:`return-url`})],d.prototype,`returnUrl`,void 0),c([o()],d.prototype,`_busy`,void 0),c([s(`craft-input.recovery-code`)],d.prototype,`_input`,void 0),customElements.get(`craft-recovery-code-form`)||customElements.define(`craft-recovery-code-form`,d);export{u as n,l as r,d as t};