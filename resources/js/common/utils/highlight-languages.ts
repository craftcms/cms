import {
  defineLanguage,
  type HighlightTokenClass,
  type TokenRange,
} from '@tanstack/highlight/core';

/**
 * A kind of token, by a pattern for it. A `className` callback can return
 * `undefined` to leave a match unclassed.
 */
type Pattern = {
  className:
    | HighlightTokenClass
    | ((match: string) => HighlightTokenClass | undefined);
  regex: RegExp;
};

/**
 * Ranges for each pattern's matches in `code`, shifted by `offset`. Earlier
 * patterns win: a match that overlaps one already found is skipped.
 */
function matchRanges(
  code: string,
  patterns: Pattern[],
  offset = 0
): TokenRange[] {
  const taken = new Uint8Array(code.length);
  const ranges: TokenRange[] = [];

  for (const {className, regex} of patterns) {
    for (const match of code.matchAll(regex)) {
      const start = match.index;
      const end = start + match[0].length;
      const tokenClass =
        typeof className === 'function' ? className(match[0]) : className;

      if (
        start === end ||
        !tokenClass ||
        taken.subarray(start, end).includes(1)
      ) {
        continue;
      }

      taken.fill(1, start, end);
      ranges.push({
        className: tokenClass,
        start: start + offset,
        end: end + offset,
      });
    }
  }

  return ranges;
}

/** Twig's tags, prints, and comments, unclosed ones running to the end. */
const twigRegion =
  /\{#[\s\S]*?(?:#\}|$)|\{\{[\s\S]*?(?:\}\}|$)|\{%[\s\S]*?(?:%\}|$)/g;

const twigPatterns: Pattern[] = [
  {className: 'string', regex: /"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'/g},
  {className: 'keyword', regex: /(?<=^\{%[-~]?\s*)[A-Za-z_]\w*/g},
  {className: 'tag', regex: /^\{[{%][-~]?|[-~]?[}%]\}$/g},
  {className: 'function', regex: /(?<=\|\s*)[A-Za-z_]\w*/g},
  {className: 'function', regex: /\b[A-Za-z_]\w*(?=\s*\()/g},
  {className: 'property', regex: /(?<=\.)[A-Za-z_]\w*/g},
  {className: 'literal', regex: /\b(?:true|false|null|none)\b/gi},
  {
    className: 'keyword',
    regex:
      /\b(?:and|as|b-and|b-or|b-xor|else|ends with|if|in|is|matches|not|only|or|starts with|with|xor)\b/g,
  },
  {className: 'number', regex: /\b\d+(?:\.\d+)?\b/g},
];

/**
 * Twig templates: the markup around Twig's tags is highlighted as HTML, and
 * the tags, prints, and comments within it, even in attribute values.
 */
export const twig = defineLanguage({
  name: 'twig',
  tokenize(code, context) {
    const regions = [...code.matchAll(twigRegion)].map((match) => ({
      start: match.index,
      end: match.index + match[0].length,
      code: match[0],
    }));
    const ranges = regions.flatMap((region) =>
      region.code.startsWith('{#')
        ? [
            {
              className: 'comment' as const,
              start: region.start,
              end: region.end,
            },
          ]
        : matchRanges(region.code, twigPatterns, region.start)
    );

    if (!context.hasLanguage('html')) {
      return ranges;
    }

    // Twig is blanked out for the HTML, so Twig strings and comparisons
    // don't read as markup, and markup ranges are split around it.
    const markup = code.replace(twigRegion, (match) =>
      ' '.repeat(match.length)
    );

    for (const range of context.tokenize(markup, 'html')) {
      let start = range.start;

      for (const region of regions) {
        if (region.end <= start || region.start >= range.end) {
          continue;
        }
        if (region.start > start) {
          ranges.push({...range, start, end: region.start});
        }
        start = region.end;
      }

      if (start < range.end) {
        ranges.push({...range, start});
      }
    }

    return ranges;
  },
});

/** GraphQL queries and schemas. */
export const graphql = defineLanguage({
  name: 'graphql',
  aliases: ['gql'],
  tokenize: (code) =>
    matchRanges(code, [
      {
        className: (match) => (match.startsWith('#') ? 'comment' : 'string'),
        regex: /#[^\n]*|"""[\s\S]*?(?:"""|$)|"(?:\\.|[^"\\\n])*"/g,
      },
      {className: 'variable', regex: /\$[A-Za-z_]\w*/g},
      {className: 'attr', regex: /@[A-Za-z_]\w*/g},
      {className: 'property', regex: /\b[A-Za-z_]\w*(?=\s*:)/g},
      {
        className: 'keyword',
        regex:
          /\b(?:directive|enum|extend|fragment|implements|input|interface|mutation|on|query|repeatable|scalar|schema|subscription|type|union)\b/g,
      },
      {className: 'literal', regex: /\b(?:true|false|null)\b/g},
      {className: 'type', regex: /\b[A-Z]\w*/g},
      {className: 'function', regex: /\b[A-Za-z_]\w*(?=\s*\()/g},
      {className: 'number', regex: /-?\b\d+(?:\.\d+)?(?:e[+-]?\d+)?\b/gi},
      {className: 'operator', regex: /\.\.\.|!/g},
    ]),
});
