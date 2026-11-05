import {expect, it} from 'vite-plus/test';
import {highlightCodeBlocks, highlightTokens} from './highlight-code';

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

function classed(code: string, language: string): string[] {
  return highlightTokens(code, language)
    .filter((token) => token.className)
    .map((token) => `${token.className}:${token.value}`);
}

it('highlights Twig within HTML, including in attribute values', () => {
  expect(
    classed(
      '{# hi #}<a href="{{ entry.url }}">{% if a < b %}{{ "<i>"|upper }}{% endif %}</a>',
      'twig'
    )
  ).toEqual([
    'comment:{# hi #}',
    'tag:a',
    'attr:href',
    'string:"',
    'tag:{{',
    'property:url',
    'tag:}}',
    'string:"',
    'tag:{%',
    'keyword:if',
    'tag:%}',
    'tag:{{',
    'string:"<i>"',
    'function:upper',
    'tag:}}',
    'tag:{%',
    'keyword:endif',
    'tag:%}',
    'tag:a',
  ]);
});

it('highlights GraphQL', () => {
  expect(
    classed(
      'query Posts($limit: Int!) {\n  entries(search: "#1", limit: $limit) { title } # all\n}',
      'graphql'
    )
  ).toEqual([
    'keyword:query',
    'type:Posts',
    'variable:$limit',
    'type:Int',
    'operator:!',
    'function:entries',
    'property:search',
    'string:"#1"',
    'property:limit',
    'variable:$limit',
    'comment:# all',
  ]);
});

it('highlights Markdown code blocks by their language class', () => {
  const root = document.createElement('div');
  root.innerHTML =
    '<p><code class="language-php">$a</code></p><pre><code class="language-php">$a = 1;</code></pre><pre><code class="language-">b</code></pre>';

  highlightCodeBlocks(root);

  const [inline, block, empty] = root.querySelectorAll('code');
  expect(inline!.innerHTML).toBe('$a');
  expect(block!.textContent).toBe('$a = 1;');
  expect(block!.querySelector('.th-variable')!.textContent).toBe('$a');
  expect(block!.querySelector('.th-number')!.textContent).toBe('1');
  expect(empty!.innerHTML).toBe('b');
});
