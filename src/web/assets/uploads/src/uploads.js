import {FileUpload, UploadError, defaults} from './FileUpload.js';

window.Craft = window.Craft || {};
window.Craft.Uploads = {FileUpload, UploadError, defaults};

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
const selector = 'input[type=file][data-upload-session]';
const hiddenSelector = 'input[type=hidden][data-upload-session-for]';

/** Each file input’s uploads. A new selection or a reset bumps `selection`, so older uploads stop affecting it. */
const inputStates = new WeakMap();
/** Each form’s pending uploads. */
const pending = new WeakMap();
/** Forms waiting for their uploads before they submit. */
const waiting = new WeakSet();

document.addEventListener('change', (event) => {
  if (
    event.target instanceof HTMLInputElement &&
    event.target.matches(selector)
  ) {
    uploadInput(event.target);
  }
});

document.addEventListener(
  'submit',
  (event) => {
    const form = event.target;
    if (!pending.get(form)?.size) {
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
      event.submitter?.form === form ? event.submitter : undefined;

    settle(form).then(() => {
      // Resetting the form cancels the submission
      if (!waiting.delete(form)) {
        return;
      }
      form.requestSubmit(submitter?.isConnected ? submitter : undefined);
    });
  },
  true
);

document.addEventListener('reset', (event) => {
  waiting.delete(event.target);
  // `elements` includes controls outside the form that name it with a `form` attribute
  for (const element of [...event.target.elements]) {
    if (element.matches(hiddenSelector)) {
      element.remove();
    } else if (element.matches(selector)) {
      resetInput(element);
    }
  }
});

function uploadInput(input) {
  const files = [...input.files];
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

  let parameters = {};
  try {
    parameters = JSON.parse(input.dataset.uploadSession || '{}');
  } catch {
    console.warn('data-upload-session must be a JSON object.', input);
  }

  // Read the form's own token at upload time, since the page's may be cached
  const csrfToken =
    [...(input.form?.elements ?? [])].find(
      (element) => element.name === defaults.csrfTokenName
    )?.value || defaults.csrfToken;

  // Add the hidden inputs up front, so asset IDs keep the order files were chosen in
  let last = input;
  let queue = Promise.resolve();

  for (const file of files) {
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.dataset.uploadSessionFor = '';
    if (input.hasAttribute('form')) {
      hidden.setAttribute('form', input.getAttribute('form'));
    }
    last.after(hidden);
    last = hidden;

    const upload = new FileUpload(file, {
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
            hidden.name = name;
            hidden.value = result.assetId;
            dispatch(input, 'uploadcomplete', {file, result});
          }
        },
        (error) => {
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

function resetInput(input) {
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
function updateValidity(input) {
  input.setCustomValidity(inputState(input).error);
  if (input.dataset.uploadSessionRequired !== undefined) {
    input.required = !hiddenInputs(input).length;
  }
}

function inputState(input) {
  if (!inputStates.has(input)) {
    inputStates.set(input, {selection: 0, uploads: new Set(), error: ''});
  }
  return inputStates.get(input);
}

function discardUploads(state) {
  state.selection++;
  state.error = '';
  for (const upload of state.uploads) {
    // Uploads that are already saving can't be canceled; their assets are left unused
    upload.cancel().catch(() => {});
  }
  state.uploads.clear();
}

function hiddenInputs(input) {
  const inputs = [];
  let next = input.nextElementSibling;
  while (next?.matches(hiddenSelector)) {
    inputs.push(next);
    next = next.nextElementSibling;
  }
  return inputs;
}

function track(form, promise) {
  if (!form) {
    return;
  }

  if (!pending.has(form)) {
    pending.set(form, new Set());
  }

  const uploads = pending.get(form);
  uploads.add(promise);
  promise.finally(() => uploads.delete(promise));
}

/** Waits for the form’s uploads, including any started while waiting. */
async function settle(form) {
  const uploads = pending.get(form);
  while (uploads?.size) {
    await Promise.allSettled([...uploads]);
  }
}

function dispatch(input, name, detail) {
  input.dispatchEvent(new CustomEvent(name, {bubbles: true, detail}));
}
