import {t} from '@craftcms/ui';
import type {OverType as OverTypeInstance} from 'overtype';

const markdownGuideUrl = 'https://www.markdownguide.org/basic-syntax/';

export function replaceMarkdownGuideButton(
  editor: OverTypeInstance
): () => void {
  const button = editor.toolbar?.buttons?.guide;

  if (!(button instanceof HTMLButtonElement)) {
    return () => {};
  }

  const label = t('Markdown Guide (opens in a new tab)');
  const link = document.createElement('a');
  link.className = button.className;
  link.href = markdownGuideUrl;
  link.innerHTML = button.innerHTML;
  link.target = '_blank';
  link.title = label;
  link.rel = 'noopener';
  link.setAttribute('aria-label', label);
  link.setAttribute('data-button', 'guide');

  button.replaceWith(link);
  delete editor.toolbar.buttons.guide;

  return () => {
    link.replaceWith(button);
    editor.toolbar.buttons.guide = button;
  };
}
