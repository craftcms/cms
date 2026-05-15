import{i as e}from"./nav-item-Bfzb7bVl-kS60DrF6.js";import{mt as t}from"./_plugin-vue_export-helper-BLD82GEK.js";import{c as n}from"./lit-X8lyiNX5.js";import{r}from"./decorators-CaOws0gC.js";import{t as i}from"./decorate-BpI2plOt.js";var a=class extends t{static{this.METHOD=`totp`}get endpoint(){return`auth/verify-totp`}renderInput(){return n`
      <craft-input
        label="${e(`Verification Code`)}"
        id="totp-code"
        class="totp-code"
        name="code"
        .maxlength="${6}"
        autocomplete="one-time-code"
        inputmode="numeric"
        aria-required="true"
      ></craft-input>
    `}};i([r(`craft-input.totp-code`)],a.prototype,`_input`,void 0),t.register(`craft-totp-form`,a);var o=class extends t{static{this.METHOD=`recovery-codes`}get endpoint(){return`auth/verify-recovery-code`}renderInput(){return n`
      <craft-input
        label="${e(`Recovery Code`)}"
        id="recovery-code"
        class="recovery-code"
        name="code"
        autocomplete="off"
        aria-required="true"
      ></craft-input>
    `}};i([r(`craft-input.recovery-code`)],o.prototype,`_input`,void 0),t.register(`craft-recovery-code-form`,o);