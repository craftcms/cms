import {expect, it} from 'vite-plus/test';
import {highlightCodeBlocks} from './highlight-code';

it('highlights code blocks, keeping markup in them as text', () => {
  const root = document.createElement('div');
  root.innerHTML =
    '<pre><code data-language="json">{"a": "&lt;b&gt;"}</code></pre><pre><code>plain</code></pre>';

  highlightCodeBlocks(root);

  const [code, plain] = root.querySelectorAll('code');
  expect(code!.querySelector('b')).toBeNull();
  expect(code!.textContent).toBe('{"a": "<b>"}');
  expect(code!.querySelector('.th-property')!.textContent).toBe('"a"');
  expect(code!.querySelector('.th-string')!.textContent).toBe('"<b>"');
  expect(code!.hasAttribute('data-language')).toBe(false);
  expect(plain!.innerHTML).toBe('plain');
});
