import {createElement, useEffect, useState} from 'react';
import type {ComponentProps} from 'react';
import {DocsContainer} from '@storybook/addon-docs/blocks';
import {GLOBALS_UPDATED, SET_GLOBALS} from 'storybook/internal/core-events';
import {addons} from 'storybook/preview-api';
import {create, themes} from 'storybook/theming';
import type {ThemeVars} from 'storybook/theming';

type DocsContainerProps = ComponentProps<typeof DocsContainer>;
type GlobalsPayload = {globals?: {theme?: string}};

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
 */
export function ThemedDocsContainer(props: DocsContainerProps) {
  const [theme, setTheme] = useState(() => currentTheme(props.context));
  const [darkTheme] = useState(cpDarkTheme);

  useEffect(() => {
    const channel = addons.getChannel();
    const update = ({globals}: GlobalsPayload) => {
      setTheme(globals?.theme ?? 'light');
    };
    channel.on(SET_GLOBALS, update);
    channel.on(GLOBALS_UPDATED, update);

    return () => {
      channel.off(SET_GLOBALS, update);
      channel.off(GLOBALS_UPDATED, update);
    };
  }, []);

  return createElement(DocsContainer, {
    ...props,
    theme: theme === 'dark' ? darkTheme : themes.light,
  });
}
