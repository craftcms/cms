import {createHighlighter} from '@tanstack/highlight/core';
import {diff} from '@tanstack/highlight/languages/diff';
import {html} from '@tanstack/highlight/languages/html';
import {js} from '@tanstack/highlight/languages/js';
import {json} from '@tanstack/highlight/languages/json';
import {php} from '@tanstack/highlight/languages/php';
import {plaintext} from '@tanstack/highlight/languages/plaintext';
import {yaml} from '@tanstack/highlight/languages/yaml';

Craft.highlighter = createHighlighter({
  languages: [diff, html, js, json, php, plaintext, yaml],
});
