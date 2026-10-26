import {createHighlighter, type HighlightToken} from '@tanstack/highlight/core';
import {diff} from '@tanstack/highlight/languages/diff';
import {html} from '@tanstack/highlight/languages/html';
import {js} from '@tanstack/highlight/languages/js';
import {json} from '@tanstack/highlight/languages/json';
import {markdown} from '@tanstack/highlight/languages/markdown';
import {php} from '@tanstack/highlight/languages/php';
import {plaintext} from '@tanstack/highlight/languages/plaintext';
import {yaml} from '@tanstack/highlight/languages/yaml';
import './highlight-code.css';

/**
 * The control panel's syntax highlighter, also exposed as
 * `Craft.highlighter`. Each language also answers to its aliases, such as
 * `xml` for `html` or `javascript` for `js`. Anything else is shown as plain
 * text.
 */
export const highlighter = createHighlighter({
  languages: [diff, html, js, json, markdown, php, plaintext, yaml],
});

/** Splits code into tokens, classed by what they are. */
export function highlightTokens(
  code: string,
  language: string
): HighlightToken[] {
  return highlighter.tokenize(code, {lang: language}).tokens;
}

/**
 * Highlights the code in each `code[data-language]` element within `root`,
 * such as server-rendered file previews.
 */
export function highlightCodeBlocks(root: ParentNode): void {
  for (const code of root.querySelectorAll<HTMLElement>(
    'code[data-language]'
  )) {
    code.replaceChildren(
      ...highlightTokens(code.textContent ?? '', code.dataset.language!).map(
        (token) => {
          if (!token.className) {
            return document.createTextNode(token.value);
          }
          const span = document.createElement('span');
          span.className = `th-${token.className}`;
          span.textContent = token.value;
          return span;
        }
      )
    );
    code.removeAttribute('data-language');
  }
}
