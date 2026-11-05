import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {showCopyTextPrompt} from './copy-text-prompt.js';
import type CraftTextarea from '../components/textarea/textarea.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('showCopyTextPrompt', () => {
  it('opens a craft-dialog with a read-only copy field', () => {
    const dialog = showCopyTextPrompt({
      label: 'Full URL',
      value: 'https://x',
    });

    expect(dialog.tagName.toLowerCase()).toBe('craft-dialog');
    expect(dialog.getAttribute('label')).toBe('Full URL');
    expect(dialog.hasAttribute('open')).toBe(true);
    expect(dialog.isConnected).toBe(true);

    const field = dialog.querySelector('craft-input-copy');
    expect(field?.label).toBe('Full URL');
    expect(field?.labelSrOnly).toBe(true);
    expect(field?.modelValue).toBe('https://x');
    expect(field?.inputSize).toBe(25);
  });

  it('renders a textarea when config.textarea is set', () => {
    const dialog = showCopyTextPrompt({
      label: 'composer.json',
      value: '{"a":1}',
      textarea: true,
      class: 'code',
      rows: 10,
    });

    const field = dialog.querySelector<CraftTextarea>('craft-textarea.code');
    expect(field?.labelSrOnly).toBe(true);
    expect(field?.monospace).toBe(true);
    expect(field?.readOnly).toBe(true);
    expect(Number(field?.rows)).toBe(10);
    expect(field?.modelValue).toBe('{"a":1}');

    const copyButton = dialog.querySelector('craft-copy-button');
    expect(copyButton?.slot).toBe('footer');
    expect(copyButton?.value).toBe('{"a":1}');
  });

  it('removes the dialog once it has closed', () => {
    const dialog = showCopyTextPrompt({value: 'x'});
    const child = dialog.querySelector('craft-input-copy')!;

    child.dispatchEvent(new CustomEvent('craft-after-hide', {bubbles: true}));
    expect(dialog.isConnected).toBe(true);

    dialog.dispatchEvent(new CustomEvent('craft-after-hide', {bubbles: true}));
    expect(dialog.isConnected).toBe(false);
  });

  it('hands returnFocus to the dialog', () => {
    const returnFocus = () => null;
    const dialog = showCopyTextPrompt({value: 'x', returnFocus});

    expect(dialog.returnFocus).toBe(returnFocus);
  });
});
