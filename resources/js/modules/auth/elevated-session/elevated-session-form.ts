import {elevatedSessionManager} from './manager';
import {jq} from '@/common/utils/jquery';

type FormTarget = HTMLFormElement | string | ArrayLike<HTMLFormElement>;
type InputTarget = Element | string | ArrayLike<Element>;
type InputValue = string | string[] | null;

function firstElement<T extends Element>(target: T | string | ArrayLike<T>): T {
  const element =
    target instanceof Element
      ? target
      : target instanceof Object
        ? target[0]
        : document.querySelector<T>(String(target));

  if (!element) {
    throw new Error('Unable to find the elevated-session form.');
  }

  return element;
}

function inputValue(input: Element): InputValue {
  const currentInput = passwordInput(input);

  if (currentInput instanceof HTMLInputElement) {
    if (
      ['checkbox', 'radio'].includes(currentInput.type) &&
      !currentInput.checked
    ) {
      return null;
    }

    return currentInput.value;
  }

  if (currentInput instanceof HTMLTextAreaElement) {
    return currentInput.value;
  }

  if (currentInput instanceof HTMLSelectElement) {
    const values = Array.from(currentInput.selectedOptions, ({value}) => value);

    if (!currentInput.multiple) {
      return values.at(-1) ?? '';
    }

    return currentInput.name.endsWith('[]') ? values : (values.at(-1) ?? null);
  }

  return currentInput.getAttribute('value');
}

function passwordInput(input: Element): Element {
  const jquery = jq();
  const currentInput =
    jquery?.(input).data?.('passwordInput')?.$currentInput?.[0];

  return currentInput ?? input;
}

function valuesMatch(first: InputValue, second: InputValue): boolean {
  return Array.isArray(first) || Array.isArray(second)
    ? JSON.stringify(first) === JSON.stringify(second)
    : first === second;
}

export class ElevatedSessionForm {
  readonly form: HTMLFormElement;

  private readonly inputSelectors: string[] = [];
  private readonly inputs = new Map<Element, InputValue>();
  private enabled = true;
  private resuming = false;

  constructor(
    form: FormTarget,
    inputTargets?: InputTarget | InputTarget[],
    private readonly manager: Pick<
      typeof elevatedSessionManager,
      'require'
    > = elevatedSessionManager
  ) {
    this.form = firstElement(form);

    if (!(this.form instanceof HTMLFormElement)) {
      throw new TypeError('ElevatedSessionForm requires a form element.');
    }

    if (inputTargets !== undefined) {
      const targets = Array.isArray(inputTargets)
        ? inputTargets
        : [inputTargets];
      targets.forEach((target) => this.track(target));
    }

    this.form.addEventListener('submit', this.handleSubmit);
  }

  inputsChanged(): boolean {
    if (this.inputSelectors.length === 0 && this.inputs.size === 0) {
      return true;
    }

    if (
      this.inputSelectors.some((selector) =>
        Array.from(this.form.querySelectorAll(selector)).some(
          (input) => !this.inputs.has(input)
        )
      )
    ) {
      return true;
    }

    return Array.from(this.inputs).some(
      ([input, value]) => !valuesMatch(inputValue(input), value)
    );
  }

  disable(): void {
    this.enabled = false;
  }

  enable(): void {
    this.enabled = true;
  }

  destroy(): void {
    this.form.removeEventListener('submit', this.handleSubmit);
  }

  private readonly handleSubmit = (event: SubmitEvent): void => {
    if (!this.enabled || this.resuming) {
      this.resuming = false;
      return;
    }

    // The guard only gates native form submissions. If an earlier listener
    // already canceled the submit (e.g. an Inertia page that intercepts the
    // form and confirms the session itself), there is nothing to resume.
    if (event.defaultPrevented) {
      return;
    }

    if (!this.inputsChanged()) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    void this.manager.require().then((confirmed) => {
      if (!confirmed) {
        return;
      }

      this.resuming = true;
      if (event.submitter instanceof HTMLElement) {
        this.form.requestSubmit(event.submitter);
      } else {
        this.form.requestSubmit();
      }
    });
  };

  private track(target: InputTarget): void {
    if (!(target instanceof Object)) {
      const selector = String(target);
      this.inputSelectors.push(selector);
      this.form
        .querySelectorAll(selector)
        .forEach((input) => this.inputs.set(input, inputValue(input)));
      return;
    }

    const inputs = target instanceof Element ? [target] : Array.from(target);
    inputs.forEach((input) => this.inputs.set(input, inputValue(input)));
  }
}
