import type {Options} from 'overtype';

export function themeOptions(): NonNullable<Options['theme']> {
  const fill =
    'var(--c-input-fill, var(--c-form-control-fill, var(--c-surface-form)))';
  const border =
    'var(--c-input-border-color, var(--c-form-control-border-color, var(--c-color-neutral-border-quiet)))';
  const codeFill = 'var(--c-color-neutral-fill-quiet)';
  const icon = 'var(--c-color-neutral-on-quiet, var(--c-text-default))';
  const quietText = 'var(--c-text-quiet)';
  const selection =
    'var(--markdown-field-selection-bg, var(--c-color-accent-fill-quiet))';
  const text = 'var(--c-input-text, var(--c-text-default))';

  return {
    name: 'craft',
    colors: {
      bgPrimary: fill,
      bgSecondary: fill,
      border,
      code: text,
      codeBg: codeFill,
      cursor: text,
      del: text,
      h1: text,
      h2: text,
      h3: text,
      link: text,
      listMarker: text,
      placeholder: quietText,
      primary: icon,
      rawLine: text,
      selection,
      strong: text,
      syntax: text,
      syntaxMarker: text,
      text,
      textPrimary: text,
      textSecondary: text,
      toolbarActive: codeFill,
      toolbarBg: fill,
      toolbarBorder: border,
      toolbarHover: codeFill,
      toolbarIcon: icon,
      em: text,
      blockquote: text,
      hr: text,
    },
    previewColors: {
      bg: 'transparent',
      blockquote: text,
      code: text,
      codeBg: codeFill,
      em: text,
      h1: text,
      h2: text,
      h3: text,
      hr: text,
      link: text,
      strong: text,
      text,
    },
  };
}
