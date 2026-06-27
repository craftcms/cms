import{l as e}from"./nav-item-DToC7WtU-DVjPoUz7.js";import{s as t}from"./cp-Ddu3QQ2F.js";import{c as n}from"./lit-X8lyiNX5.js";import{r}from"./decorators-CaOws0gC.js";import{t as i}from"./decorate-sEzBNQWl.js";var a=class extends t{static{this.METHOD=`totp`}get endpoint(){return`auth/verify-totp`}renderInput(){return n`
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