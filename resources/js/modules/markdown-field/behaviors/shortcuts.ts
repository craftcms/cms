import {t} from '@craftcms/ui/utilities/translate';
import type {OverType as OverTypeInstance} from 'overtype';
import {registerShortcut} from '@/common/shortcuts';
import type {PreviewController} from './preview';

/**
 * The editor's keyboard shortcuts: OverType's own, and Craft's code, quote and
 * preview toggles. They're registered on the textarea, and stop propagation so
 * OverType's document-level handler doesn't run the same action again.
 */
export function registerShortcutBehavior(
  editor: OverTypeInstance,
  preview: PreviewController
): () => void {
  const action = (actionId: string) => (event: KeyboardEvent) =>
    void editor.performAction(actionId, event);

  const shortcuts: Array<[string, (event: KeyboardEvent) => void, string]> = [
    ['Mod+B', action('toggleBold'), t('Bold')],
    ['Mod+I', action('toggleItalic'), t('Italic')],
    ['Mod+E', action('toggleCode'), t('Code')],
    ['Mod+K', action('insertLink'), t('Insert a link')],
    ['Mod+Shift+.', action('toggleQuote'), t('Quote')],
    ['Mod+Shift+7', action('toggleNumberedList'), t('Numbered list')],
    ['Mod+Shift+8', action('toggleBulletList'), t('Bulleted list')],
    ['Mod+]', () => editor.indentSelection(), t('Indent')],
    ['Mod+[', () => editor.outdentSelection(), t('Outdent')],
    ['Mod+Shift+P', () => void preview.toggle(), t('Show or hide the preview')],
  ];

  const registrations = shortcuts.map(([hotkey, callback, description]) =>
    registerShortcut(hotkey as 'Mod+B', callback, {
      target: editor.textarea,
      description,
      group: t('Markdown fields'),
      stopPropagation: true,
    })
  );

  return () => registrations.forEach(({unregister}) => unregister());
}
