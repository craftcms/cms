import {FileUpload, UploadError, defaults} from './FileUpload.js';

window.Craft = window.Craft || {};
window.Craft.Uploads = {FileUpload, UploadError, defaults};

/**
 * Uploads files chosen in `<input type="file" data-upload-session>` as soon as
 * they’re chosen, and replaces them with hidden inputs holding the new asset
 * IDs, under the file input’s name (such as `fields[photos][]`).
 *
 * The attribute’s value is a JSON object of upload parameters, such as
 * `{"fieldId": 12}` or `{"folderId": 3}`. The form waits for uploads to finish
 * before it submits.
 *
 * The input dispatches `uploadprogress`, `uploadcomplete` and `uploaderror`
 * events, with details in `event.detail`.
 */
const selector = 'input[type=file][data-upload-session]';
const hiddenSelector = 'input[type=hidden][data-upload-session-for]';
const pending = new WeakMap();

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
    const uploads = pending.get(event.target);
    if (!uploads?.size) {
      return;
    }

    // Submit once the uploads are done; each one reports its own errors
    event.preventDefault();
    event.stopImmediatePropagation();
    Promise.allSettled([...uploads]).then(() => {
      event.target.requestSubmit(event.submitter);
    });
  },
  true
);

function uploadInput(input) {
  const files = [...input.files];
  if (!files.length) {
    return;
  }

  // The file input keeps its name, so its files are never posted with the form
  input.dataset.uploadSessionName ??= input.name;
  input.removeAttribute('name');
  const name = input.dataset.uploadSessionName;

  if (!input.multiple) {
    removeHiddenInputs(input);
  }

  let parameters = {};
  try {
    parameters = JSON.parse(input.dataset.uploadSession || '{}');
  } catch {
    console.warn('data-upload-session must be a JSON object.', input);
  }

  input.setCustomValidity('');

  // Add the hidden inputs up front, so asset IDs keep the order files were chosen in
  let last = input;
  while (last.nextElementSibling?.matches(hiddenSelector)) {
    last = last.nextElementSibling;
  }

  for (const file of files) {
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.dataset.uploadSessionFor = '';
    last.after(hidden);
    last = hidden;

    const upload = new FileUpload(file, {
      parameters,
      onProgress: (loaded, total) => {
        dispatch(input, 'uploadprogress', {file, loaded, total});
      },
    });

    const promise = upload
      .upload()
      .then((result) => {
        hidden.name = name;
        hidden.value = result.assetId;
        dispatch(input, 'uploadcomplete', {file, result});
      })
      .catch((error) => {
        hidden.remove();
        input.setCustomValidity(error.message);
        dispatch(input, 'uploaderror', {file, error});
        throw error;
      });

    track(input.form, promise);
  }

  input.value = '';
}

function removeHiddenInputs(input) {
  let next = input.nextElementSibling;
  while (next?.matches(hiddenSelector)) {
    const hidden = next;
    next = next.nextElementSibling;
    hidden.remove();
  }
}

function track(form, promise) {
  if (!form) {
    return;
  }

  if (!pending.has(form)) {
    pending.set(form, new Set());
  }

  const uploads = pending.get(form);
  const settled = promise.catch(() => {});
  uploads.add(settled);
  settled.then(() => uploads.delete(settled));
}

function dispatch(input, name, detail) {
  input.dispatchEvent(new CustomEvent(name, {bubbles: true, detail}));
}
