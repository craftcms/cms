import{l as e}from"./nav-item-DToC7WtU-DVjPoUz7.js";import{s as t}from"./cp-Ddu3QQ2F.js";import{c as n}from"./lit-X8lyiNX5.js";import{r}from"./decorators-CaOws0gC.js";import{t as i}from"./decorate-sEzBNQWl.js";var a=class extends t{static{this.METHOD=`recovery-codes`}get endpoint(){return`auth/verify-recovery-code`}renderInput(){return n`
      <craft-input
        label="${e(`Recovery Code`)}"
        id="recovery-code"
        class="recovery-code"
        name="code"
        autocomplete="off"
        aria-required="true"
      ></craft-input>
    `}};i([r(`craft-input.recovery-code`)],a.prototype,`_input`,void 0),t.register(`craft-recovery-code-form`,a);export{a as t};