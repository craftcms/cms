import{s as e}from"./cp-BGj8ev7d.js";import{l as t}from"./nav-item-DFZbQ-Jw-CtSAdykQ.js";import{c as n}from"./lit-BpPOIUnZ.js";import{r}from"./decorators-BOwDFZC2.js";import{t as i}from"./decorate-CpzDR30L.js";var a=class extends e{static{this.METHOD=`recovery-codes`}get endpoint(){return`auth/verify-recovery-code`}renderInput(){return n`
      <craft-input
        label="${t(`Recovery Code`)}"
        id="recovery-code"
        class="recovery-code"
        name="code"
        autocomplete="off"
        aria-required="true"
      ></craft-input>
    `}};i([r(`craft-input.recovery-code`)],a.prototype,`_input`,void 0),e.register(`craft-recovery-code-form`,a);export{a as t};