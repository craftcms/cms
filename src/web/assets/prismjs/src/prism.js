/**
 * Prism compatibility shim, backed by TanStack Highlight (`Craft.highlighter`).
 *
 * Supports the commonly-used Prism APIs (`highlight()`, `highlightElement()`, `highlightAll()`,
 * `highlightAllUnder()`, `languages`, and `util.encode()`). Grammar customization, hooks,
 * and plugins aren’t supported.
 */
import {
  escapeHtml,
  renderNodesToHtml,
  renderTokens,
} from '@tanstack/highlight/core';

// Prism language names that TanStack Highlight doesn’t have aliases for
const languageMap = {
  atom: 'html',
  markup: 'html',
  mathml: 'html',
  rss: 'html',
  ssml: 'html',
  svg: 'html',
  webmanifest: 'json',
};

const languageRegex = /(?:^|\s)lang(?:uage)?-([\w-]+)(?=\s|$)/i;
const selector =
  'code[class*="language-"], [class*="language-"] code, code[class*="lang-"], [class*="lang-"] code';

const normalizeLanguage = (language) =>
  languageMap[language?.toLowerCase()] ?? language;

const getLanguage = (element) => {
  for (let el = element; el; el = el.parentElement) {
    const match = el.className?.match?.(languageRegex);
    if (match) {
      return match[1].toLowerCase();
    }
  }
  return 'none';
};

// Stand-in grammar objects, so `Prism.highlight(code, Prism.languages.json, 'json')` works
const grammars = {};
const languageMethods = {
  extend: (id) => Prism.languages[id],
  insertBefore: (inside) => Prism.languages[inside],
  DFS: () => {},
};

const Prism = {
  manual: window.Prism?.manual ?? false,
  disableWorkerMessageHandler: true,
  hooks: {all: {}, add: () => {}, run: () => {}},
  plugins: {},
  util: {
    encode: (text) => escapeHtml(text),
  },
  languages: new Proxy(languageMethods, {
    get(target, name) {
      if (typeof name !== 'string' || name in target) {
        return target[name];
      }
      return (grammars[name] ??= {name});
    },
    has(target, name) {
      return (
        name in target ||
        Craft.highlighter.normalizeLanguage(normalizeLanguage(name)) !==
          'plaintext'
      );
    },
  }),

  highlight(text, grammar, language) {
    const {tokens} = Craft.highlighter.tokenize(text, {
      lang: normalizeLanguage(language ?? grammar?.name),
    });
    return renderNodesToHtml(renderTokens(tokens));
  },

  highlightElement(element, async, callback) {
    const language = getLanguage(element);
    element.innerHTML = Prism.highlight(
      element.textContent,
      Prism.languages[language],
      language
    );
    callback?.call(element);
  },

  highlightAllUnder(container, async, callback) {
    for (const element of container.querySelectorAll(selector)) {
      Prism.highlightElement(element, async, callback);
    }
  },

  highlightAll(async, callback) {
    Prism.highlightAllUnder(document, async, callback);
  },
};

window.Prism = Prism;

// Like Prism, automatically highlight code blocks on page load unless `Prism.manual` is set
if (!Prism.manual) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Prism.highlightAll());
  } else {
    setTimeout(() => Prism.highlightAll());
  }
}
