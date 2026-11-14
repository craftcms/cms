import OverType from 'overtype';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import './markdown-field';

type MarkdownField = HTMLElement & {
  value: string;
  maxHeight: number | null;
  updateComplete: Promise<boolean>;
};

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

function lines(count: number): string {
  return Array.from({length: count}, (_, i) => `Line ${i + 1}`).join('\n');
}

function mount(
  attributes: Record<string, string> = {},
  parent: HTMLElement = document.body
): MarkdownField {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  const field = document.createElement('craft-markdown-field') as MarkdownField;
  for (const [name, value] of Object.entries(attributes)) {
    field.setAttribute(name, value);
  }
  field.style.width = '400px';
  parent.append(field);

  return field;
}

function parts(field: MarkdownField) {
  return {
    wrapper: field.querySelector<HTMLElement>('.overtype-wrapper')!,
    textarea: field.querySelector<HTMLTextAreaElement>('.overtype-input')!,
    preview: field.querySelector<HTMLElement>('.overtype-preview')!,
  };
}

function type(textarea: HTMLTextAreaElement, value: string): void {
  textarea.focus();
  textarea.value = value;
  textarea.setSelectionRange(value.length, value.length);
  textarea.dispatchEvent(new Event('input', {bubbles: true}));
}

it('is as tall as its text, and never shorter than its rows', () => {
  const field = mount({rows: '3'});
  const {wrapper, textarea, preview} = parts(field);
  const minimum = wrapper.offsetHeight;

  type(textarea, lines(10));
  const grown = wrapper.offsetHeight;

  // The textarea's text is laid over the preview's, so both have to be as tall
  // as the text: neither may have any of it scrolled out of view.
  expect(grown).toBeGreaterThan(minimum * 2);
  expect(textarea.offsetHeight).toBe(grown);
  expect(preview.offsetHeight).toBe(grown);
  expect(textarea.scrollHeight).toBeLessThanOrEqual(textarea.clientHeight);

  type(textarea, 'One line');

  expect(wrapper.offsetHeight).toBe(minimum);
});

it('keeps the text and the caret line aligned after a trailing line break', () => {
  const field = mount({rows: '2'});
  const {textarea, preview} = parts(field);

  type(textarea, `${lines(4)}\n`);

  expect(textarea.scrollHeight).toBeLessThanOrEqual(textarea.clientHeight);
  expect(textarea.scrollTop).toBe(0);
  expect(preview.offsetHeight).toBe(textarea.offsetHeight);
});

it('fits its text once a field that was hidden when it was created is shown', () => {
  const hidden = document.createElement('div');
  hidden.style.display = 'none';
  document.body.append(hidden);
  const field = mount({rows: '2'}, hidden);
  field.value = lines(8);

  hidden.style.display = '';
  const {textarea} = parts(field);

  expect(textarea.offsetHeight).toBeGreaterThan(0);
  expect(textarea.scrollHeight).toBeLessThanOrEqual(textarea.clientHeight);
});

it('stops growing at its max height, and scrolls the rest', async () => {
  const field = mount({rows: '2', 'max-height': '120'});
  await field.updateComplete;
  const {wrapper, textarea} = parts(field);

  type(textarea, lines(30));

  expect(wrapper.offsetHeight).toBeLessThanOrEqual(120);
  expect(textarea.scrollHeight).toBeGreaterThan(textarea.clientHeight);
  expect(getComputedStyle(textarea).overflowY).toBe('auto');

  field.maxHeight = null;
  await field.updateComplete;

  expect(wrapper.offsetHeight).toBeGreaterThan(120);
});

it('doesn’t redraw its preview when it’s given the value it already shows', () => {
  const field = mount();
  const {textarea, preview} = parts(field);
  type(textarea, 'Some **bold** text');
  const redraw = vi.spyOn(OverType.getInstance(field)!, 'updatePreview');

  // A host bound to the value hands every edit straight back.
  field.value = 'Some **bold** text';

  expect(redraw).not.toHaveBeenCalled();

  field.value = 'Other text';

  expect(redraw).toHaveBeenCalled();
  expect(preview.textContent).toContain('Other text');
});

it('doesn’t redraw its preview when only the cursor moves', async () => {
  const field = mount();
  const {textarea} = parts(field);
  type(textarea, `${lines(3)}\nSome **bold** text`);
  await new Promise((resolve) => setTimeout(resolve, 100));
  const redraw = vi.spyOn(OverType.getInstance(field)!, 'updatePreview');

  textarea.setSelectionRange(2, 2);
  await new Promise((resolve) => setTimeout(resolve, 100));

  expect(redraw).not.toHaveBeenCalled();
});

it('redraws its preview into the nodes it already has', () => {
  const field = mount();
  const {textarea, preview} = parts(field);
  type(textarea, `${lines(2)}\nSome **bold** text`);
  const line = preview.children[2];
  const bold = line!.querySelector('strong');

  type(textarea, `${lines(2)}\nSome **bold** text!`);

  expect(preview.children[2]).toBe(line);
  expect(line!.querySelector('strong')).toBe(bold);
  expect(line!.textContent).toContain('text!');

  // Lines that change shape, go away, or are new, including code blocks,
  // which OverType styles after drawing them.
  const value = '# Heading\n\n- **item**\n\n```\ncode\n```\nplain';
  type(textarea, `${lines(5)}\nSome _bold_ text`);
  type(textarea, value);
  const fresh = parts(mount());
  type(fresh.textarea, value);

  expect(preview.innerHTML).toBe(fresh.preview.innerHTML);
});
