import stylelint from 'stylelint';

const {
  createPlugin,
  utils: {report, ruleMessages},
} = stylelint;

const ruleName = 'craft/has-subject-only';

const messages = ruleMessages(ruleName, {
  rejected: (combinator) =>
    `Avoid :has() before a ${combinator} combinator: Chromium then restyles the :has() element's subtree, into every shadow root in it, whenever anything is added or removed inside it. Put :has() on the element being styled, as in .tabs:has(~ .panel) rather than :has(> .panel) > .tabs, or toggle a class or attribute from JS.`,
});

/** Pseudo-classes whose arguments are selector lists that share the compound they sit in. */
const SELECTOR_LIST_CALL = /:(?:is|where|not|matches|-webkit-any)$/i;

/** Sibling combinators only restyle the siblings, so they're left alone. */
const COMBINATOR_NAMES = {
  ' ': 'descendant',
  '>': 'child',
};

/**
 * Finds the first descendant or child combinator that follows a `:has()` in
 * the same complex selector, including a `:has()` inside `:is()`, `:where()`,
 * or `:not()`. Skips strings, escapes, and attribute selectors, and ignores
 * anything inside a `:has()` argument.
 *
 * @param {string} selector
 * @returns {string | null} The combinator's name.
 */
export function findCombinatorAfterHas(selector) {
  const root = {
    selectors: true,
    inHas: false,
    pending: false,
    anyPending: false,
  };
  const stack = [root];
  let quote = null;
  let bracket = false;

  for (let i = 0; i < selector.length; i++) {
    const char = selector[i];
    const frame = stack.at(-1);

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

    if (bracket) {
      bracket = char !== ']';
      continue;
    }

    if (char === '"' || char === "'") {
      quote = char;
    } else if (char === '[') {
      bracket = true;
    } else if (char === '(') {
      const before = selector.slice(0, i);
      const isHas = /:has$/i.test(before);
      stack.push({
        isHas,
        inHas: frame.inHas || isHas,
        selectors: SELECTOR_LIST_CALL.test(before),
        pending: false,
        anyPending: false,
      });
    } else if (char === ')') {
      const closed = stack.pop();
      const parent = stack.at(-1);
      if (
        parent.selectors &&
        !parent.inHas &&
        (closed.isHas ||
          (closed.selectors && (closed.anyPending || closed.pending)))
      ) {
        parent.pending = true;
      }
    } else if (!frame.selectors || frame.inHas) {
      continue;
    } else if (char === ',') {
      frame.anyPending ||= frame.pending;
      frame.pending = false;
    } else if (/\s/.test(char) || '>+~'.includes(char)) {
      let combinator = char;

      if (/\s/.test(char)) {
        const prev = selector.slice(0, i).trimEnd().at(-1);
        const next = selector.slice(i).trimStart()[0];
        if (
          prev === undefined ||
          next === undefined ||
          '(,>+~'.includes(prev) ||
          '),>+~'.includes(next)
        ) {
          continue;
        }
        combinator = ' ';
      }

      if (frame.pending && combinator in COMBINATOR_NAMES) {
        return COMBINATOR_NAMES[combinator];
      }
    }
  }

  return null;
}

/**
 * The rule's selector with its nesting resolved, so a `:has()` in a parent
 * rule is checked against the combinators its nested rules add.
 *
 * @param {import('postcss').Rule} rule
 * @returns {string}
 */
function resolvedSelector(rule) {
  let parent = rule.parent;

  while (parent && parent.type !== 'rule') {
    parent = parent.parent;
  }

  if (!parent) {
    return rule.selector;
  }

  const outer = `:is(${resolvedSelector(parent)})`;

  return rule.selectors
    .map((selector) =>
      selector.includes('&')
        ? selector.replaceAll('&', outer)
        : `${outer} ${selector}`
    )
    .join(', ');
}

export default createPlugin(ruleName, (enabled) => (root, result) => {
  // Lit styles match within one shadow root, which bounds what they restyle.
  if (!enabled || /\.[cm]?[jt]sx?$/.test(root.source?.input.file ?? '')) {
    return;
  }

  root.walkRules((rule) => {
    // Keyframe selectors such as `from` and `50%` aren't element selectors.
    if (
      rule.parent?.type === 'atrule' &&
      /keyframes$/i.test(rule.parent.name)
    ) {
      return;
    }

    const combinator = findCombinatorAfterHas(resolvedSelector(rule));

    if (combinator) {
      report({
        ruleName,
        result,
        node: rule,
        message: messages.rejected(combinator),
        word: rule.selector,
      });
    }
  });
});
