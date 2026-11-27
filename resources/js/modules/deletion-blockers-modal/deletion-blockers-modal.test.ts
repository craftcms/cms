import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {DeletionBlockersModal} from './deletion-blockers-modal';

const blockers = {
  blockers: [{summary: 'The section contains 2 entries.', actions: []}],
  label: 'Blog',
  headHtml: '',
  bodyHtml: '',
};

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

async function openModal(): Promise<DeletionBlockersModal> {
  const modal = new DeletionBlockersModal('section', 1);
  void modal.run();
  await vi.waitFor(() =>
    expect(document.querySelector('.edm-blocker')).not.toBeNull()
  );

  return modal;
}

it('focuses and relabels the dialog after a refresh clears the blockers', async () => {
  const post = vi
    .spyOn(actionClient, 'post')
    .mockResolvedValue({data: blockers} as never);
  const modal = await openModal();
  const dialog = document.querySelector<HTMLElement & {label: string}>(
    '.element-deletion-modal'
  )!;
  expect(dialog.label).toBe(
    'Before deleting “Blog”, please address the following issue:'
  );

  post.mockResolvedValue({data: {...blockers, blockers: []}} as never);
  await modal.refresh();

  const heading = dialog.querySelector('.edm-heading');
  expect(heading?.textContent).toBe(
    'Nothing is blocking the deletion anymore.'
  );
  expect(dialog.label).toBe('Nothing is blocking the deletion anymore.');
  expect(document.activeElement).toBe(heading);
});

it('ignores a refresh that finishes after the modal is closed', async () => {
  const post = vi
    .spyOn(actionClient, 'post')
    .mockResolvedValue({data: blockers} as never);
  const modal = await openModal();

  let finish!: (value: unknown) => void;
  post.mockReturnValue(new Promise((resolve) => (finish = resolve)) as never);
  const refresh = modal.refresh();
  modal.destroy();
  finish({data: blockers});

  await expect(refresh).resolves.toBeUndefined();
});
