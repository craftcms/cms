import {useBreakpoints, breakpointsTailwind} from '@vueuse/core';

/**
 * The CP's breakpoints, read from the `--breakpoint-*` custom properties the
 * stylesheets define, so a query in CSS and a check in JS can't drift apart.
 *
 * Read through getters rather than up front: the properties only exist once the
 * stylesheet has landed, which is after this module is first imported. Where
 * they can't be read at all — SSR, or a test with no stylesheet — Tailwind's
 * scale stands in, which is what the stylesheets mirror.
 */
const NAMES = ['sm', 'md', 'lg', 'xl', '2xl'] as const;

type BreakpointName = (typeof NAMES)[number];

function readBreakpoint(name: BreakpointName): number | string {
  if (typeof document === 'undefined') {
    return breakpointsTailwind[name];
  }

  const value = getComputedStyle(document.documentElement)
    .getPropertyValue(`--breakpoint-${name}`)
    .trim();

  return value || breakpointsTailwind[name];
}

const cpBreakpointValues = Object.fromEntries(
  NAMES.map((name) => [name, () => readBreakpoint(name)])
) as Record<BreakpointName, () => number | string>;

export const useCpBreakpoints = () => useBreakpoints(cpBreakpointValues);

export const cpBreakpoints = useCpBreakpoints();
