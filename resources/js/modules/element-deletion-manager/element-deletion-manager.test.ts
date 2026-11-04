import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {initLegacyUi} from '@/common/craft/legacy-ui';
import {ElementDeletionManager} from './element-deletion-manager';

vi.mock('@/common/craft/legacy-ui', () => ({initLegacyUi: vi.fn()}));

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

it('lists the blockers and resolves one from its action', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      blockers: [
        {
          summary: 'The entry has authors.',
          details: '<p>Reassign them first.</p>',
          actions: [
            {
              label: 'Reassign',
              class: 'reassign',
              attributes: {'data-reassign': 'yes'},
              callback: "resolve('Reassigned.')",
            },
          ],
        },
      ],
      elementPreview: '<strong>Home</strong>',
      totalElements: 1,
      headHtml: '',
      bodyHtml: '',
    },
  } as never);

  const manager = new ElementDeletionManager('craft\\elements\\Entry', [1]);
  await vi.waitFor(() =>
    expect(document.querySelector('.edm-blocker')).not.toBeNull()
  );

  const dialog = document.querySelector('.element-deletion-modal')!;
  expect(dialog.querySelector('.edm-heading strong')?.textContent).toBe('Home');
  await vi.waitFor(() =>
    expect(dialog.querySelector('.edm-details')?.textContent).toBe(
      'Reassign them first.'
    )
  );

  const button = dialog.querySelector<HTMLElement>('.edm-actions .reassign')!;
  expect(button.dataset.reassign).toBe('yes');
  button.click();

  await vi.waitFor(() =>
    expect(dialog.querySelector('.edm-blocker--resolved')).not.toBeNull()
  );
  expect(
    dialog.querySelector('.edm-blocker-resolved-message')?.textContent
  ).toBe('Reassigned.');
  expect(manager.blockers).toHaveLength(0);
});

it('initializes a blocker once all of its details are in', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      blockers: [
        {
          summary: 'The entry has authors.',
          details:
            '<script src="https://example.com/library.js"></script><div class="later-control"></div>',
        },
      ],
      elementPreview: '<strong>Home</strong>',
      totalElements: 1,
      headHtml: '',
      bodyHtml: '',
    },
  } as never);
  let controlWasIn: boolean | null = null;
  vi.mocked(initLegacyUi).mockImplementation((container) => {
    controlWasIn = !!(container as Element).querySelector('.later-control');
  });

  new ElementDeletionManager('craft\\elements\\Entry', [1]);

  await vi.waitFor(() => expect(controlWasIn).not.toBeNull());
  expect(controlWasIn).toBe(true);
});
