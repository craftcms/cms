import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {submitScreenForm} from './submitScreenForm';
import {firstMessages} from './errors';

const post = vi.fn();
const client = {post};

/**
 * A screen rendered through `cp/Screen`: real inputs, namespaced per panel,
 * including the hidden action input the server appends.
 */
function renderScreenForm(namespace = 'abc123'): HTMLFormElement {
  const form = document.createElement('form');
  form.innerHTML = `
    <input name="${namespace}[action]" value="elements/save">
    <input name="${namespace}[title]" value="Hello">
    <input name="${namespace}[elementId]" value="5">
  `;
  document.body.appendChild(form);

  return form;
}

beforeEach(() => {
  post.mockReset();
  post.mockImplementation(() => Promise.resolve({data: {}}));
});
afterEach(() => {
  document.body.innerHTML = '';
});

describe('firstMessages', () => {
  it('flattens the server error bag to one message per field', () => {
    // `withErrors()` gives an Inertia page one message per field, so the
    // slideout path has to match or errors render differently.
    expect(
      firstMessages({title: ['Required.', 'Too short.'], slug: 'Taken.'})
    ).toEqual({title: 'Required.', slug: 'Taken.'});
  });

  it('survives a null or empty message list', () => {
    expect(firstMessages({a: [], b: null})).toEqual({a: '', b: ''});
  });
});

describe('submitScreenForm', () => {
  it('posts the namespaced inputs to the action', async () => {
    const form = renderScreenForm();

    const result = await submitScreenForm(
      form,
      {
        action: 'elements/save',
        namespace: 'abc123',
        containerId: 'slideout-1',
      },
      client
    );

    expect(result.ok).toBe(true);

    const call = post.mock.calls[0];
    if (!call) throw new Error('Expected the screen form request.');
    const [action, body, config] = call;
    expect(action).toBe('elements/save');
    expect(body).toBeInstanceOf(FormData);
    // The inputs stay namespaced on the wire; the server un-prefixes them.
    if (!(body instanceof FormData))
      throw new Error('Expected a FormData request body.');
    expect(body.get('abc123[title]')).toBe('Hello');
    expect(config.headers['X-Craft-Namespace']).toBe('abc123');
    expect(config.headers['X-Craft-Container-Id']).toBe('slideout-1');
  });

  it('omits the namespace header when there is no namespace', async () => {
    await submitScreenForm(
      renderScreenForm(),
      {action: 'elements/save'},
      client
    );

    const call = post.mock.calls[0];
    if (!call) throw new Error('Expected the screen form request.');
    expect(call[2].headers).not.toHaveProperty('X-Craft-Namespace');
  });

  it('returns flattened field errors on a 400', async () => {
    // `asJsonFailure()` answers 400, not Laravel's usual 422.
    post.mockImplementation(() =>
      Promise.reject({
        response: {status: 400, data: {errors: {title: ['Required.']}}},
      })
    );

    const result = await submitScreenForm(
      renderScreenForm(),
      {action: 'elements/save'},
      client
    );

    expect(result).toEqual({ok: false, errors: {title: 'Required.'}});
  });

  it('falls back to the message when there are no field errors', async () => {
    post.mockImplementation(() =>
      Promise.reject({response: {status: 400, data: {message: 'Nope.'}}})
    );

    const result = await submitScreenForm(
      renderScreenForm(),
      {action: 'elements/save'},
      client
    );

    expect(result).toEqual({ok: false, message: 'Nope.'});
  });

  it('reports anything else as fatal rather than swallowing it', async () => {
    const boom = new Error('network down');
    post.mockImplementation(() => Promise.reject(boom));

    const result = await submitScreenForm(
      renderScreenForm(),
      {action: 'elements/save'},
      client
    );

    expect(result.ok).toBe(false);
    expect(result.fatal).toBe(boom);
  });
});
