import {createApp, reactive, type App} from 'vue';
import FormRenderer from './FormRenderer.vue';
import type {FormPayload, FormErrors, FormValues} from './types';

export interface FormHostContext {
  form: FormPayload;
  bindingScope: string;
  values: FormValues;
  errors: FormErrors;
  readOnly: boolean;
}

export class FormHost extends HTMLElement {
  #app?: App;
  #context?: FormHostContext;

  set context(context: FormHostContext) {
    this.#context = {
      ...context,
      values: reactive(context.values),
      errors: reactive(context.errors),
    };
    if (this.isConnected) {
      this.#mount();
    }
  }

  get context(): FormHostContext | undefined {
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

    this.#app = createApp(FormRenderer, {
      form: this.#context.form,
      bindingScope: this.#context.bindingScope,
      values: this.#context.values,
      errors: this.#context.errors,
      readOnly: this.#context.readOnly,
    });
    window.Cp.$components.install(this.#app);
    this.#app.mount(this);
  }
}

if (!customElements.get('craft-form')) {
  customElements.define('craft-form', FormHost);
}
