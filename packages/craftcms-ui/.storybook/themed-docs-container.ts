import {DocsContainer} from '@storybook/addon-docs/blocks';
import {create, themes} from 'storybook/theming';
import type {ThemeVars} from 'storybook/theming';

type DocsContainerProps = Parameters<typeof DocsContainer>[0];

let darkTheme: ThemeVars | undefined;

/**
 * Builds the docs theme from the control panel's own dark tokens, so a docs
 * page's surface and text match what its stories render on.
 */
function cpDarkTheme(): ThemeVars {
  const probe = document.createElement('div');
  probe.dataset.theme = 'dark';
  probe.style.display = 'none';
  document.body.append(probe);
  const context = document.createElement('canvas').getContext('2d', {
    willReadFrequently: true,
  })!;
  // Resolved through a canvas pixel, since tokens mixed with `color-mix()`
  // compute to `oklab()`, which Storybook's color helpers can't parse.
  const token = (name: string): string => {
    probe.style.color = `var(${name})`;
    context.clearRect(0, 0, 1, 1);
    context.fillStyle = getComputedStyle(probe).color;
    context.fillRect(0, 0, 1, 1);
    const [red, green, blue] = context.getImageData(0, 0, 1, 1).data;

    return `rgb(${red}, ${green}, ${blue})`;
  };
  const theme = create({
    base: 'dark',
    appBg: token('--c-surface-sunken'),
    appContentBg: token('--c-surface-sunken'),
    appPreviewBg: token('--c-surface-sunken'),
    appBorderColor: token('--c-color-neutral-border-quiet'),
    textColor: token('--c-text-default'),
    textMutedColor: token('--c-text-quiet'),
  });
  probe.remove();

  return theme;
}

function currentTheme(context: DocsContainerProps['context']): string {
  const store = (
    context as unknown as {
      store?: {userGlobals?: {get(): {theme?: string}}};
    }
  ).store;

  return store?.userGlobals?.get().theme ?? 'light';
}

/**
 * The docs container, themed to follow the toolbar's theme switcher. Storybook
 * only themes docs pages from a static `docs.theme` parameter, so the page
 * chrome would otherwise stay light while the stories on it turn dark.
 *
 * Storybook re-renders a docs page when globals change, so the theme is read
 * on each render. This wraps `DocsContainer` by calling it, rather than through
 * `createElement()`, so the file doesn't import React: only the docs addon's
 * Vite preset aliases it, and the Vitest runner loads this preview without it.
 */
export function ThemedDocsContainer(props: DocsContainerProps) {
  const theme =
    currentTheme(props.context) === 'dark'
      ? (darkTheme ??= cpDarkTheme())
      : themes.light;

  return DocsContainer({...props, theme});
}
