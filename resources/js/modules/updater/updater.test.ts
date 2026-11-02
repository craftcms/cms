import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {bootUpdater} from './updater';

function page(state: object): void {
  const root = document.createElement('div');
  root.dataset.updater = 'updater';
  root.dataset.updaterState = JSON.stringify(state);
  root.innerHTML = '<div id="graphic"></div><div id="status"></div>';
  document.body.append(root);
}

const status = () => document.querySelector('#status')!;

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(() => {
  vi.restoreAllMocks();
});

it('runs each step the server names, carrying its data along', async () => {
  const post = vi.spyOn(actionClient, 'post').mockResolvedValueOnce({
    data: {status: 'Updating `craftcms/cms`…\n\nDone.', data: 'next'},
  } as never);
  page({status: 'Preparing…', data: 'first', nextAction: 'composer-install'});

  bootUpdater();

  expect(status().textContent).toBe('Preparing…');
  await vi.waitFor(() => expect(status().innerHTML).toContain('<code>'));
  expect(post).toHaveBeenCalledWith('updater/composer-install', {
    data: 'first',
  });
  expect(status().innerHTML).toBe(
    '<p>Updating <code>craftcms/cms</code>…</p><p>Done.</p>'
  );
});

it('offers a step’s options, and continues with the one chosen', () => {
  page({
    error: 'Couldn’t back up the database.',
    options: [
      {label: 'Try again', submit: true, status: 'Retrying…'},
      {label: 'Troubleshoot', url: 'https://craftcms.com'},
    ],
  });

  bootUpdater();

  expect(document.querySelector('#graphic')!.classList).toContain('error');
  const [retry, troubleshoot] =
    document.querySelectorAll<HTMLElement>('#options .btn');
  expect(troubleshoot!.getAttribute('target')).toBe('_blank');

  retry!.click();

  expect(status().textContent).toBe('Retrying…');
});
