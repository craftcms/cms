import {css} from 'lit';
export default css`
  :host {
    cursor: pointer;
    font: inherit;
    display: inline-flex;
    justify-content: center;
    gap: var(--c-spacing-sm);
    align-items: center;
    border-radius: var(--c-button-radius, var(--c-form-control-radius));
    padding-inline: var(
      --c-button-spacing-inline,
      var(--c-form-control-spacing-inline)
    );
    padding-block: 0;
    width: auto;
    min-height: var(--c-button-height, var(--c-size-control-md));
    min-width: var(--c-button-width, var(--c-size-control-md));
    white-space: nowrap;

    /* Colorable styles */
    color: var(--c-color-on-loud, var(--c-color-neutral-on-loud));
    border-width: var(--c-button-border-width, 1px);
    border-style: var(--c-button-border-style, solid);
    border-color: var(
      --c-color-border-loud,
      var(--c-color-neutral-border-loud)
    );
    background-color: var(
      --c-color-fill-loud,
      var(--c-color-neutral-fill-loud)
    );
  }

  @media (hover: hover) {
    :host(:hover) {
      background-color: color-mix(
        in oklab,
        var(--c-color-fill-loud, var(--c-button-default-fill)),
        var(--c-color-on-loud) 10%
      );
      color: var(--c-color-on-loud);
    }
  }

  :host(:not(:disabled):not(.loading):active) {
    color: var(--c-color-on-loud);
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-loud, var(--c-color-neutral-fill-normal)),
      var(--c-color-mix-active)
    );
  }

  /*
  Sizes
   */
  :host([size~='zero']) {
    min-width: 0;
    min-height: 0;
    padding-inline: 0;
  }

  :host([size~='small']) {
    padding-inline: var(--c-spacing-sm);
    min-width: var(--c-size-control-sm);
    min-height: var(--c-size-control-sm);
    font-size: 0.9em;
  }

  :host([size~='large']) {
    padding-inline: var(--c-spacing-lg);
    min-height: var(--c-size-control-lg);
    min-width: var(--c-size-control-lg);
  }

  :host([loading]) {
    position: relative;

    .prefix,
    .label,
    .suffix {
      visibility: hidden;
    }

    craft-spinner {
      --size: 1.25em;
      position: absolute;
      inset-block-start: calc(50% - var(--size) / 2);
      inset-inline-start: calc(50% - var(--size) / 2);
    }
  }

  /*
  Icon
   */
  :host([icon]) {
    aspect-ratio: 1;
    padding-inline: 0;
    padding-block: 0;
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    line-height: 1;
  }

  :host([icon][size~='small']) {
    font-size: 0.8em;
  }

  /*
  Appearances 
   */

  /* Plain */
  :host([appearance~='plain']) {
    background-color: transparent;
    border-color: transparent;
    color: inherit;
  }

  :host([appearance~='plain']:hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-button-default-fill)),
      var(--c-color-on-quiet) 10%
    );
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='plain']:active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)),
      var(--c-color-on-quiet) 20%
    );
  }

  /* Filled */
  :host([appearance~='filled']) {
    background-color: var(
      --c-color-fill-normal,
      var(--c-color-neutral-fill-normal)
    );
    border-color: transparent;
    color: var(--c-color-on-normal, var(--c-color-neutral-on-normal));
  }

  :host([appearance~='filled']:hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-normal, var(--c-color-neutral-fill-normal)),
      var(--c-color-on-normal) 10%
    );
    color: var(--c-color-on-normal, var(--c-color-neutral-on-normal));
  }

  :host([appearance~='filled']:active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)),
      var(--c-color-on-normal) 20%
    );
  }

  /* Dashed */
  :host([appearance~='dashed']) {
    background-color: transparent;
    border-color: var(--c-color-border-normal);
    border-style: dashed;
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='dashed']:hover) {
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-button-default-fill)),
      var(--c-color-on-quiet) 10%
    );
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='dashed']:active) {
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-quiet, var(--c-color-neutral-fill-quiet)),
      var(--c-color-on-quiet) 20%
    );
  }

  /*
  Variants (aka fill colors) 
   */
  :host([variant~='primary']) {
    --c-color-fill-loud: var(--c-color-brand-fill-loud);
    --c-color-fill-normal: var(--c-color-brand-fill-normal);
    --c-color-fill-quiet: var(--c-color-brand-fill-quiet);
    --c-color-border-loud: var(--c-color-brand-border-loud);
    --c-color-border-normal: var(--c-color-brand-border-normal);
    --c-color-border-quiet: var(--c-color-brand-border-quiet);
    --c-color-on-loud: var(--c-color-brand-on-loud);
    --c-color-on-normal: var(--c-color-brand-on-normal);
    --c-color-on-quiet: var(--c-color-brand-on-quiet);
  }

  :host([variant='default']) {
    --c-color-fill-loud: var(--c-color-neutral-fill-loud);
    --c-color-fill-normal: var(--c-color-neutral-fill-normal);
    --c-color-fill-quiet: var(--c-color-neutral-fill-quiet);
    --c-color-border-loud: var(--c-color-neutral-border-loud);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-quiet: var(--c-color-neutral-border-quiet);
    --c-color-on-loud: var(--c-color-neutral-on-loud);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-quiet: var(--c-color-neutral-on-quiet);
  }

  :host([variant~='danger']) {
    --c-color-fill-loud: var(--c-color-danger-fill-loud);
    --c-color-fill-normal: var(--c-color-danger-fill-normal);
    --c-color-fill-quiet: var(--c-color-danger-fill-quiet);
    --c-color-border-loud: var(--c-color-danger-border-loud);
    --c-color-border-normal: var(--c-color-danger-border-normal);
    --c-color-border-quiet: var(--c-color-danger-border-quiet);
    --c-color-on-loud: var(--c-color-danger-on-loud);
    --c-color-on-normal: var(--c-color-danger-on-normal);
    --c-color-on-quiet: var(--c-color-danger-on-quiet);
  }

  .button-content {
    display: flex;
    align-items: center;
    gap: 0.25em;
    width: 100%;
  }

  .prefix,
  .suffix {
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }

  .button-content--start {
    justify-content: start;
  }

  .button-content--end {
    justify-content: end;
  }

  craft-button-group craft-button {
    border-radius: 0;
  }

  craft-button-reset,
  craft-button-submit {
    /* Temporarily make it very obvious when these are used */
    outline: 10px solid red;
  }
`;
