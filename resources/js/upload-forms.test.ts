import {afterEach, beforeAll, beforeEach, expect, it, vi} from 'vitest';
import type {UploadOptions} from './upload-client';

const uploads: MockUpload[] = [];

class MockUpload {
  resolve!: (result: {assetId: number}) => void;
  reject!: (error: Error) => void;
  cancel = vi.fn(async () => {});
  started = false;
  private result = new Promise<{assetId: number}>((resolve, reject) => {
    this.resolve = resolve;
    this.reject = reject;
  });

  constructor(
    public file: File,
    public options: UploadOptions
  ) {
    uploads.push(this);
  }

  upload() {
    this.started = true;
    return this.result;
  }
}

vi.mock('./upload-client', () => ({FileUpload: MockUpload}));

let defaults: typeof import('./upload-forms').defaults;

beforeAll(async () => {
  const forms = await import('./upload-forms');
  defaults = forms.defaults;
  forms.initUploadForms();
});

beforeEach(() => {
  Object.assign(defaults, {
    url: '/actions/assets/uploads',
    csrfTokenName: '_token',
    csrfToken: 'page-token',
  });
  document.body.innerHTML = `
    <form>
      <input type="hidden" name="_token" value="form-token">
      <input type="file" name="fields[photos][]" multiple required data-upload-session='{"fieldId": 12}'>
    </form>
  `;
});

afterEach(() => {
  uploads.length = 0;
});

function choose(input: HTMLInputElement, names: string[]) {
  Object.defineProperty(input, 'files', {
    configurable: true,
    value: names.map((name) => new File(['abc'], name)),
  });
  input.dispatchEvent(new Event('change', {bubbles: true}));
}

const upload = (index: number) => uploads[index]!;

const flush = () => new Promise((resolve) => setTimeout(resolve));

function fileInput() {
  return document.querySelector<HTMLInputElement>('input[type=file]')!;
}

function hiddenInputs() {
  return [
    ...document.querySelectorAll<HTMLInputElement>(
      'input[data-upload-session-for]'
    ),
  ];
}

it('uploads chosen files one at a time and posts their asset IDs in order', async () => {
  const input = fileInput();
  choose(input, ['a.jpg', 'b.jpg']);
  await flush();

  expect(input.hasAttribute('name')).toBe(false);
  expect(uploads.map(({started}) => started)).toEqual([true, false]);
  expect(upload(0).options).toMatchObject({
    url: '/actions/assets/uploads',
    parameters: {fieldId: 12},
    csrfToken: 'form-token',
  });

  upload(0).resolve({assetId: 1});
  await flush();
  expect(upload(1).started).toBe(true);
  upload(1).resolve({assetId: 2});
  await flush();

  expect(hiddenInputs().map((hidden) => [hidden.name, hidden.value])).toEqual([
    ['fields[photos][]', '1'],
    ['fields[photos][]', '2'],
  ]);
  expect(input.required).toBe(false);
});

it('waits for uploads before submitting the form', async () => {
  const form = document.querySelector('form')!;
  const submit = vi.fn((event: SubmitEvent) => event.preventDefault());
  form.addEventListener('submit', submit);
  const requestSubmit = vi
    .spyOn(form, 'requestSubmit')
    .mockImplementation(() => {});

  choose(fileInput(), ['a.jpg']);
  form.dispatchEvent(new SubmitEvent('submit', {cancelable: true}));
  form.dispatchEvent(new SubmitEvent('submit', {cancelable: true}));

  expect(submit).not.toHaveBeenCalled();
  expect(requestSubmit).not.toHaveBeenCalled();

  upload(0).resolve({assetId: 1});
  await flush();

  expect(requestSubmit).toHaveBeenCalledTimes(1);
});

it('reports failed uploads on the input and cancels them', async () => {
  const input = fileInput();
  const errors: string[] = [];
  input.addEventListener('uploaderror', (event) =>
    errors.push((event as CustomEvent).detail.error.message)
  );

  choose(input, ['a.jpg']);
  upload(0).reject(new Error('Too large'));
  await flush();

  expect(errors).toEqual(['Too large']);
  expect(input.validationMessage).toBe('Too large');
  expect(upload(0).cancel).toHaveBeenCalled();
  expect(hiddenInputs()).toHaveLength(0);
  expect(input.required).toBe(true);
});

it('discards uploads when the form is reset', async () => {
  const form = document.querySelector('form')!;
  const input = fileInput();
  choose(input, ['a.jpg']);
  upload(0).resolve({assetId: 1});
  await flush();
  choose(input, ['b.jpg']);

  form.reset();
  await flush();

  expect(upload(1).cancel).toHaveBeenCalled();
  expect(hiddenInputs()).toHaveLength(0);
  expect(input.required).toBe(true);
});

it('falls back to the page’s CSRF token', () => {
  document.querySelector('input[name=_token]')!.remove();
  choose(fileInput(), ['a.jpg']);

  expect(upload(0).options.csrfToken).toBe('page-token');
});
