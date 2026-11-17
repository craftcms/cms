import type {OverType as OverTypeInstance} from 'overtype';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import {createLinkPopoverController} from './link-popover';
import type {PreviewController} from './preview';

afterEach(() => {
  document.body.replaceChildren();
});

it('returns focus to the link button when Escape closes the link editor', async () => {
  document.body.innerHTML = `
    <div class="editor">
      <button type="button" data-button="link">Link</button>
      <textarea></textarea>
    </div>`;
  const container = document.querySelector<HTMLElement>('.editor')!;
  const button = container.querySelector<HTMLElement>('button')!;
  // SAFETY: The controller only reads these members of the editor.
  const editor = {
    container,
    textarea: container.querySelector('textarea')!,
    wrapper: container,
  } as unknown as OverTypeInstance;
  // SAFETY: Applying a link is the only path that touches the preview.
  const preview = {isActive: () => false} as unknown as PreviewController;
  const controller = createLinkPopoverController(editor, preview, {
    advancedFields: [],
    showLabelField: false,
    types: [],
  });

  button.focus();
  controller.open();
  await vi.waitFor(() =>
    expect(
      document.querySelector('craft-popover')?.contains(document.activeElement)
    ).toBe(true)
  );

  await userEvent.keyboard('{Escape}');

  await vi.waitFor(() =>
    expect(document.querySelector('craft-popover')).toBeNull()
  );
  expect(document.activeElement).toBe(button);
});
