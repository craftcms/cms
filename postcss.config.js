import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';

/**
 * A media query can't read a custom property, so `var(--breakpoint-*)` inside
 * one is substituted here instead, from the same declarations the stylesheets
 * publish and `useCpBreakpoints` reads. Authoring stays plain CSS an editor can
 * parse, and the widths have one home.
 */
const VARIABLES = fileURLToPath(
  new URL(
    './packages/craftcms-ui/src/styles/shared/variables.css',
    import.meta.url
  )
);

const BREAKPOINT = /(?<![\w-])var\(\s*(--breakpoint-[a-z0-9]+)\s*\)/gi;

let widths;

function breakpoints() {
  if (widths) {
    return widths;
  }

  widths = new Map();

  for (const [, name, value] of readFileSync(VARIABLES, 'utf8').matchAll(
    /(--breakpoint-[a-z0-9]+):\s*([^;]+);/g
  )) {
    widths.set(name, value.trim());
  }

  return widths;
}

const breakpointVars = {
  postcssPlugin: 'craft-breakpoint-vars',
  Once: (_root, {result}) => {
    result.messages.push({
      type: 'dependency',
      plugin: 'craft-breakpoint-vars',
      file: VARIABLES,
      parent: result.opts.from ?? '',
    });
  },
  AtRule: {
    media: (rule) => {
      if (!rule.params.includes('--breakpoint-')) {
        return;
      }

      rule.params = rule.params.replace(BREAKPOINT, (match, name) => {
        const width = breakpoints().get(name);

        if (!width) {
          throw rule.error(`Unknown breakpoint \`${name}\`.`);
        }

        return width;
      });

      if (rule.params.includes('--breakpoint-')) {
        throw rule.error(
          `Couldn't resolve every breakpoint in \`@media ${rule.params}\`.`
        );
      }
    },
  },
};

export default {
  plugins: [breakpointVars],
};
