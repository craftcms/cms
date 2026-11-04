import {createHighlighter, type HighlightToken} from '@tanstack/highlight/core';
import {apache} from '@tanstack/highlight/languages/apache';
import {css} from '@tanstack/highlight/languages/css';
import {diff} from '@tanstack/highlight/languages/diff';
import {dockerfile} from '@tanstack/highlight/languages/dockerfile';
import {env} from '@tanstack/highlight/languages/env';
import {html} from '@tanstack/highlight/languages/html';
import {http} from '@tanstack/highlight/languages/http';
import {js} from '@tanstack/highlight/languages/js';
import {json} from '@tanstack/highlight/languages/json';
import {jsx} from '@tanstack/highlight/languages/jsx';
import {markdown} from '@tanstack/highlight/languages/markdown';
import {nginx} from '@tanstack/highlight/languages/nginx';
import {php} from '@tanstack/highlight/languages/php';
import {plaintext} from '@tanstack/highlight/languages/plaintext';
import {shell} from '@tanstack/highlight/languages/shell';
import {sql} from '@tanstack/highlight/languages/sql';
import {toml} from '@tanstack/highlight/languages/toml';
import {ts} from '@tanstack/highlight/languages/ts';
import {tsx} from '@tanstack/highlight/languages/tsx';
import {vue} from '@tanstack/highlight/languages/vue';
import {yaml} from '@tanstack/highlight/languages/yaml';
import {graphql, twig} from './highlight-languages';
import './highlight-code.css';

/**
 * The control panel's syntax highlighter, also exposed as
 * `Craft.highlighter`. Each language also answers to its aliases, such as
 * `xml` for `html`, `javascript` for `js`, or `sh` for `shell`. Anything else
 * is shown as plain text.
 */
export const highlighter = createHighlighter({
  languages: [
    apache,
    css,
    diff,
    dockerfile,
    env,
    graphql,
    html,
    http,
    js,
    json,
    jsx,
    markdown,
    nginx,
    php,
    plaintext,
    shell,
    sql,
    toml,
    ts,
    tsx,
    twig,
    vue,
    yaml,
  ],
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
 * such as server-rendered file previews, and in each Markdown code block
 * with a language, which is a `pre > code.language-*` element.
 */
export function highlightCodeBlocks(root: ParentNode): void {
  for (const code of root.querySelectorAll<HTMLElement>(
    'code[data-language], pre > code[class*="language-"]'
  )) {
    const language =
      code.dataset.language ??
      code.className.match(/(?:^|\s)language-(\S+)/)?.[1];

    if (!language) {
      continue;
    }

    code.replaceChildren(
      ...highlightTokens(code.textContent ?? '', language).map((token) => {
        if (!token.className) {
          return document.createTextNode(token.value);
        }
        const span = document.createElement('span');
        span.className = `th-${token.className}`;
        span.textContent = token.value;
        return span;
      })
    );
    code.removeAttribute('data-language');
  }
}
