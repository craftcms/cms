import{s as e}from"./cp-DBw9TANf.js";import{l as t}from"./nav-item-CDlDuMpT-DENemU4x.js";import{c as n}from"./lit-BpPOIUnZ.js";import{r}from"./decorators-BOwDFZC2.js";import{t as i}from"./decorate-CpzDR30L.js";var a=class extends e{static{this.METHOD=`totp`}get endpoint(){return`auth/verify-totp`}renderInput(){return n`
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
    `}};i([r(`craft-input.totp-code`)],a.prototype,`_input`,void 0),e.register(`craft-totp-form`,a);export{a as t};