import{l as e}from"./nav-item-ixoxjtrg-DTBVd69z.js";import{s as t}from"./cp-BKCRQ1VT.js";import{c as n}from"./lit-BpPOIUnZ.js";import{r}from"./decorators-BOwDFZC2.js";import{t as i}from"./decorate-CpzDR30L.js";var a=class extends t{static{this.METHOD=`totp`}get endpoint(){return`auth/verify-totp`}renderInput(){return n`
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
    `}};i([r(`craft-input.totp-code`)],a.prototype,`_input`,void 0),t.register(`craft-totp-form`,a);export{a as t};