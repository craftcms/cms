import stylelint from 'stylelint';

const {
  createPlugin,
  utils: {report, ruleMessages},
} = stylelint;

const ruleName = 'craft/has-argument-positive';

const messages = ruleMessages(ruleName, {
  rejected: (found) =>
    `Avoid ${found} inside :has(): Chromium then restyles the whole document whenever any element is added or removed. Match positively inside :has() and negate outside it, as in :has(a):not(:has(a.b)).`,
});

/**
 * Finds the first `:not()` or `*` inside a `:has()` argument, at any nesting
 * depth. Skips strings, escapes, and attribute selectors, so `[a*='b']`,
 * `[a=':not(']`, and `.\*` are fine.
 *
 * @param {string} selector
 * @returns {string | null}
 */
export function findInHasArgument(selector) {
  // One entry per open parenthesis: whether it's inside a :has() argument.
  const stack = [];
  const inHas = () => stack.at(-1) === true;
  let quote = null;
  let bracket = false;

  for (let i = 0; i < selector.length; i++) {
    const char = selector[i];

    if (char === '\\') {
      i++;
      continue;
    }

    if (quote) {
      if (char === quote) {
        quote = null;
      }
      continue;
    }

    if (char === '"' || char === "'") {
      quote = char;
    } else if (bracket) {
      bracket = char !== ']';
    } else if (char === '[') {
      bracket = true;
    } else if (char === '(') {
      const has = /:has$/i.test(selector.slice(0, i));
      stack.push(has || inHas());
    } else if (char === ')') {
      stack.pop();
    } else if (inHas()) {
      if (char === '*') {
        return '*';
      }
      if (/^:not\(/i.test(selector.slice(i, i + 5))) {
        return ':not()';
      }
    }
  }

  return null;
}

export default createPlugin(ruleName, (enabled) => (root, result) => {
  if (!enabled) {
    return;
  }

  root.walkRules((rule) => {
    const found = findInHasArgument(rule.selector);

    if (found) {
      report({
        ruleName,
        result,
        node: rule,
        message: messages.rejected(found),
        word: rule.selector,
      });
    }
  });
});
