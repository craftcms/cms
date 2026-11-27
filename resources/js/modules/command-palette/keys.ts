import {t} from '@craftcms/ui';
import {
  detectPlatform,
  parseHotkey,
  type ParsedHotkeyChord,
} from '@tanstack/hotkeys';

/** A key of a shortcut, as shown and as named for screen readers. */
export interface Key {
  symbol: string;
  name: string;
}

/**
 * The keys of a chord, modifiers first, in the platform's usual order. Each
 * is shown as its symbol on the platform it's pressed on.
 */
export function keysOf(chord: ParsedHotkeyChord): Key[] {
  const isMac = detectPlatform() === 'mac';
  const modifiers: Key[] = isMac
    ? [
        ...(chord.ctrl ? [{symbol: '⌃', name: t('Control')}] : []),
        ...(chord.alt ? [{symbol: '⌥', name: t('Option')}] : []),
        ...(chord.shift ? [{symbol: '⇧', name: t('Shift')}] : []),
        ...(chord.meta ? [{symbol: '⌘', name: t('Command')}] : []),
      ]
    : [
        ...(chord.ctrl ? [{symbol: 'Ctrl', name: t('Control')}] : []),
        ...(chord.alt ? [{symbol: 'Alt', name: t('Alt')}] : []),
        ...(chord.shift ? [{symbol: 'Shift', name: t('Shift')}] : []),
        ...(chord.meta ? [{symbol: 'Meta', name: t('Meta')}] : []),
      ];

  return [...modifiers, keyOf(chord.key ?? chord.code ?? '')];
}

/** The keys of a shortcut written as `registerShortcut()` takes it, such as `Mod+Shift+S`. */
export function hotkeyKeys(hotkey: string): Key[] {
  return keysOf(parseHotkey(hotkey));
}

function keyOf(key: string): Key {
  switch (key) {
    case 'Enter':
      return detectPlatform() === 'mac'
        ? {symbol: '↩', name: t('Return')}
        : {symbol: 'Enter', name: t('Enter')};
    case 'Escape':
      return {symbol: 'Esc', name: t('Escape')};
    case '/':
      return {symbol: '/', name: t('Slash')};
    case '.':
      return {symbol: '.', name: t('Period')};
    // Shortcuts bound to the physical key have its code rather than a key.
    case 'Backslash':
      return {symbol: '\\', name: t('Backslash')};
    case '[':
      return {symbol: '[', name: t('Left bracket')};
    case ']':
      return {symbol: ']', name: t('Right bracket')};
    default:
      return {symbol: key, name: key};
  }
}
