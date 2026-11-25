import {FileUpload} from './upload-client';

export interface UploadDefaults {
  /** The URL that starts asset uploads. */
  url?: string;
  /** The name of forms’ CSRF token inputs. */
  csrfTokenName?: string;
  /** The page’s CSRF token, used when a form has no token input. */
  csrfToken?: string;
}

interface UploadResult {
  assetId: number;
}

interface InputState {
  selection: number;
  uploads: Set<FileUpload<UploadResult>>;
  error: string;
}

/** Defaults for `data-upload-session` inputs, set by the page that loads them. */
export const defaults: UploadDefaults = {};

const selector = 'input[type=file][data-upload-session]';
const hiddenSelector = 'input[type=hidden][data-upload-session-for]';

/** Each file input’s uploads. A new selection or a reset bumps `selection`, so older uploads stop affecting it. */
const inputStates = new WeakMap<HTMLInputElement, InputState>();
/** Each form’s pending uploads. */
const pending = new WeakMap<HTMLFormElement, Set<Promise<void>>>();
/** Forms waiting for their uploads before they submit. */
const waiting = new WeakSet<HTMLFormElement>();

let initialized = false;

/**
 * Uploads files chosen in `<input type="file" data-upload-session>` as soon as
 * they’re chosen, and replaces them with hidden inputs holding the new asset
 * IDs, under the file input’s name (such as `fields[photos][]`).
 *
 * The attribute’s value is a JSON object of upload parameters, such as
 * `{"fieldId": 12}` or `{"folderId": 3}`. Files upload one at a time, and a new
 * selection replaces the previous one, as it would for a plain file input. The
 * form waits for uploads to finish before it submits (scripts that call
 * `form.submit()` skip that wait), and resetting the form discards them.
 *
 * The input dispatches `uploadprogress`, `uploadcomplete` and `uploaderror`
 * events, with details in `event.detail`.
 */
export function initUploadForms(): void {
  if (initialized) {
    return;
  }

  initialized = true;
  document.addEventListener('change', onChange);
  document.addEventListener('submit', onSubmit, true);
  document.addEventListener('reset', onReset);
}

function onChange(event: Event): void {
  if (
    event.target instanceof HTMLInputElement &&
    event.target.matches(selector)
  ) {
    uploadInput(event.target);
  }
}

function onSubmit(event: SubmitEvent): void {
  const form = event.target;

  if (!(form instanceof HTMLFormElement) || !pending.get(form)?.size) {
    return;
  }

  // Submit once the uploads are done; each one reports its own errors
  event.preventDefault();
  event.stopImmediatePropagation();

  // Repeated clicks while waiting don't queue more submissions
  if (waiting.has(form)) {
    return;
  }

  waiting.add(form);
  const submitter =
    event.submitter instanceof HTMLElement &&
    (event.submitter as HTMLButtonElement).form === form
      ? event.submitter
      : undefined;

  void settle(form).then(() => {
    // Resetting the form cancels the submission
    if (!waiting.delete(form)) {
      return;
    }

    form.requestSubmit(submitter?.isConnected ? submitter : undefined);
  });
}

function onReset(event: Event): void {
  const form = event.target;

  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  waiting.delete(form);

  // `elements` includes controls outside the form that name it with a `form` attribute.
  // It's live, so copy it before removing any.
  for (const element of Array.from(form.elements)) {
    if (element.matches(hiddenSelector)) {
      element.remove();
    } else if (
      element instanceof HTMLInputElement &&
      element.matches(selector)
    ) {
      resetInput(element);
    }
  }
}

function uploadInput(input: HTMLInputElement): void {
  const files = [...(input.files ?? [])];

  if (!files.length) {
    return;
  }

  // The file input loses its name, so its files are never posted with the form
  input.dataset.uploadSessionName ??= input.name;
  input.removeAttribute('name');
  const name = input.dataset.uploadSessionName;

  // The input is cleared below, so `required` is checked against the uploads instead
  if (input.required) {
    input.dataset.uploadSessionRequired = '';
  }

  const state = inputState(input);

  // A new selection replaces the previous one, uploaded or not
  discardUploads(state);
  hiddenInputs(input).forEach((hidden) => hidden.remove());

  const selection = state.selection;
  const isCurrent = () => state.selection === selection;
  state.error = '';

  let parameters: Record<string, unknown> = {};

  try {
    parameters = JSON.parse(input.dataset.uploadSession || '{}');
  } catch {
    console.warn('data-upload-session must be a JSON object.', input);
  }

  // Read the form's own token at upload time, since the page's may be cached
  const formToken = [...(input.form?.elements ?? [])].find(
    (element): element is HTMLInputElement =>
      element instanceof HTMLInputElement &&
      !!defaults.csrfTokenName &&
      element.name === defaults.csrfTokenName
  )?.value;
  const csrfToken = formToken || defaults.csrfToken;

  // Add the hidden inputs up front, so asset IDs keep the order files were chosen in
  let last: Element = input;
  let queue: Promise<unknown> = Promise.resolve();

  for (const file of files) {
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.dataset.uploadSessionFor = '';

    const formAttribute = input.getAttribute('form');
    if (formAttribute !== null) {
      hidden.setAttribute('form', formAttribute);
    }

    last.after(hidden);
    last = hidden;

    const upload = new FileUpload<UploadResult>(file, {
      url: defaults.url ?? '',
      parameters,
      csrfToken,
      onProgress: (loaded, total) => {
        if (isCurrent()) {
          dispatch(input, 'uploadprogress', {file, loaded, total});
        }
      },
    });
    state.uploads.add(upload);

    // One at a time, as in the control panel, so guests stay within the rate limit
    const run = queue.then(() => upload.upload());
    queue = run.catch(() => {});
    const promise = run
      .then(
        (result) => {
          if (isCurrent()) {
            hidden.name = name ?? '';
            hidden.value = String(result.assetId);
            dispatch(input, 'uploadcomplete', {file, result});
          }
        },
        (error: Error) => {
          hidden.remove();
          // Nothing retries it, so don't leave its file staged until it expires
          upload.cancel().catch(() => {});
          if (isCurrent()) {
            state.error = error.message;
            dispatch(input, 'uploaderror', {file, error});
          }
        }
      )
      .finally(() => {
        state.uploads.delete(upload);
        if (isCurrent()) {
          updateValidity(input);
        }
      });

    track(input.form, promise);
  }

  input.value = '';
  updateValidity(input);
}

function resetInput(input: HTMLInputElement): void {
  discardUploads(inputState(input));
  input.setCustomValidity('');

  if (input.dataset.uploadSessionRequired !== undefined) {
    input.required = true;
  }
}

/**
 * Shows the latest upload error, and requires a file only while there are no
 * uploads, finished or not.
 */
function updateValidity(input: HTMLInputElement): void {
  input.setCustomValidity(inputState(input).error);

  if (input.dataset.uploadSessionRequired !== undefined) {
    input.required = !hiddenInputs(input).length;
  }
}

function inputState(input: HTMLInputElement): InputState {
  let state = inputStates.get(input);

  if (!state) {
    state = {selection: 0, uploads: new Set(), error: ''};
    inputStates.set(input, state);
  }

  return state;
}

function discardUploads(state: InputState): void {
  state.selection++;
  state.error = '';

  for (const upload of state.uploads) {
    // Uploads that are already saving can't be canceled; their assets are left unused
    upload.cancel().catch(() => {});
  }

  state.uploads.clear();
}

function hiddenInputs(input: HTMLInputElement): Element[] {
  const inputs: Element[] = [];
  let next = input.nextElementSibling;

  while (next?.matches(hiddenSelector)) {
    inputs.push(next);
    next = next.nextElementSibling;
  }

  return inputs;
}

function track(form: HTMLFormElement | null, promise: Promise<void>): void {
  if (!form) {
    return;
  }

  let uploads = pending.get(form);

  if (!uploads) {
    uploads = new Set();
    pending.set(form, uploads);
  }

  uploads.add(promise);
  const tracked = uploads;
  void promise.finally(() => tracked.delete(promise));
}

/** Waits for the form’s uploads, including any started while waiting. */
async function settle(form: HTMLFormElement): Promise<void> {
  const uploads = pending.get(form);

  while (uploads?.size) {
    await Promise.allSettled(uploads);
  }
}

function dispatch(
  input: HTMLInputElement,
  name: string,
  detail: Record<string, unknown>
): void {
  input.dispatchEvent(new CustomEvent(name, {bubbles: true, detail}));
}
