import {createApp, reactive, type App} from 'vue';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import type {FormDefinitionData, FormErrors, FormValues} from './types';

export interface FormDefinitionHostContext {
  definition: FormDefinitionData;
  bindingScope: string;
  values: FormValues;
  errors: FormErrors;
  readOnly: boolean;
}

export class FormDefinitionHost extends HTMLElement {
  #app?: App;
  #context?: FormDefinitionHostContext;

  set context(context: FormDefinitionHostContext) {
    this.#context = {
      ...context,
      values: reactive(context.values),
      errors: reactive(context.errors),
    };
    if (this.isConnected) {
      this.#mount();
    }
  }

  get context(): FormDefinitionHostContext | undefined {
    return this.#context;
  }

  set errors(errors: FormErrors) {
    if (!this.#context) {
      return;
    }

    for (const key of Object.keys(this.#context.errors)) {
      delete this.#context.errors[key];
    }

    Object.assign(this.#context.errors, errors);
  }

  connectedCallback(): void {
    this.#mount();
  }

  disconnectedCallback(): void {
    this.#app?.unmount();
    this.#app = undefined;
  }

  #mount(): void {
    this.#app?.unmount();

    if (!this.#context) {
      return;
    }

    this.#app = createApp(FormDefinitionRenderer, {
      definition: this.#context.definition,
      bindingScope: this.#context.bindingScope,
      values: this.#context.values,
      errors: this.#context.errors,
      readOnly: this.#context.readOnly,
    });
    window.Cp.$components.install(this.#app);
    this.#app.mount(this);
  }
}

if (!customElements.get('craft-form-definition')) {
  customElements.define('craft-form-definition', FormDefinitionHost);
}
