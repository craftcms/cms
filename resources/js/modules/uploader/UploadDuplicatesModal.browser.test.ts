import '../../../css/cp.css';
import '@craftcms/ui/components/badge/badge';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/dialog/dialog';
import '@craftcms/ui/components/pane/pane';
import '@craftcms/ui/components/tab/tab';
import '@craftcms/ui/components/tabs/tabs';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {openUploadDuplicatesModal} from './open-upload-duplicates-modal';
import type {UploadDuplicate} from './upload-duplicates-notification.ce';

let opener: HTMLButtonElement;
let file: File;

beforeEach(async () => {
  await page.viewport(1200, 800);
  const canvas = new OffscreenCanvas(30, 20);
  canvas.getContext('2d')!.fillRect(0, 0, 30, 20);
  file = new File([await canvas.convertToBlob()], 'photo.png', {
    type: 'image/png',
  });
  opener = document.createElement('button');
  opener.textContent = 'Compare';
  document.body.append(opener);
  opener.focus();
});

afterEach(() => {
  document.body.replaceChildren();
});

function duplicate(
  id: number,
  title: string,
  details: Partial<UploadDuplicate> = {}
): UploadDuplicate {
  return {
    id,
    title,
    url: `/admin/assets/edit/${id}`,
    chipHtml: '',
    thumbUrl: null,
    filename: 'photo.png',
    width: 30,
    height: 20,
    size: file.size,
    extension: 'png',
    location: 'Photos / Harbor',
    dateCreated: '11/2/26, 9:41 AM',
    uploader: 'Iwona',
    ...details,
  };
}

function open(duplicates: UploadDuplicate[]) {
  return openUploadDuplicatesModal({
    file,
    location: 'Photos / Harbor',
    duplicates,
  });
}

function status(): string | undefined {
  return document
    .querySelector('[data-upload-duplicates-modal] [role="status"]')
    ?.textContent?.trim();
}

function flagged(panel: Element): string[] {
  return [...panel.querySelectorAll('dd')]
    .filter((value) => value.querySelector('craft-badge'))
    .map((value) => value.previousElementSibling!.textContent!.trim());
}

it('shows the upload beside the image it looks like, flagging the details that differ', async () => {
  const choice = open([
    duplicate(5, 'Harbor at dusk', {
      filename: 'harbor.jpg',
      extension: 'jpg',
      width: 60,
      height: 40,
      location: 'Photos',
    }),
  ]);
  const dialog = page.getByRole('dialog', {name: 'Compare “photo.png”'});
  await expect.element(dialog).toBeVisible();

  await expect
    .element(page.getByRole('heading', {name: 'New upload'}))
    .toBeInTheDocument();
  await expect
    .element(page.getByRole('img', {name: 'photo.png'}))
    .toBeVisible();
  const [upload, existing] = document.querySelectorAll(
    '[data-upload-duplicates-modal] section'
  );
  await expect.poll(() => upload!.textContent).toContain('30 × 20');
  expect(flagged(upload!)).toEqual([]);
  expect(flagged(existing!)).toEqual([
    'Filename',
    'Dimensions',
    'File type',
    'Location',
  ]);
  expect(existing!.textContent).toContain('Iwona');
  expect(
    page.getByRole('link', {name: 'Harbor at dusk Opens in a new window'})
  ).toHaveAttribute('target', '_blank');
  await expect
    .poll(status)
    .toBe('These details differ: Filename, Dimensions, File type, Location');

  await page.getByRole('button', {name: 'Replace'}).click();
  expect(await choice).toEqual({choice: 'replace', assetId: 5});
  expect(document.activeElement).toBe(opener);
  expect(document.querySelector('[data-upload-duplicates-modal]')).toBeNull();
});

it('switches between several matches, and replaces the one shown', async () => {
  const choice = open([
    duplicate(5, 'Harbor at dusk'),
    duplicate(6, 'Harbor at night', {size: file.size + 1000}),
  ]);
  const dialog = page.getByRole('dialog', {name: 'Compare “photo.png”'});
  await expect.element(dialog).toBeVisible();
  await expect
    .element(page.getByRole('button', {name: 'Keep all'}))
    .toBeVisible();
  await expect.poll(status).toBe('None of these details differ.');

  await page.getByRole('tab', {name: 'Harbor at night'}).click();
  await expect.poll(status).toBe('These details differ: File size');
  expect(
    [
      ...document.querySelectorAll<HTMLAnchorElement>(
        '[data-upload-duplicates-modal] a'
      ),
    ]
      .filter((link) => link.checkVisibility())
      .map((link) => link.getAttribute('href'))
  ).toEqual(['/admin/assets/edit/6']);
  expect(
    flagged(page.getByRole('tabpanel', {name: 'Harbor at night'}).element())
  ).toEqual(['File size']);

  await page.getByRole('button', {name: 'Replace'}).click();
  expect(await choice).toEqual({choice: 'replace', assetId: 6});
});

it('resolves with no choice when it’s closed, returning focus', async () => {
  const choice = open([duplicate(5, 'Harbor at dusk')]);
  await expect
    .element(page.getByRole('dialog', {name: 'Compare “photo.png”'}))
    .toBeVisible();

  await userEvent.keyboard('{Escape}');
  expect(await choice).toBeUndefined();
  expect(document.activeElement).toBe(opener);
});

it('stacks the images in a narrow viewport, with visible headings', async () => {
  await page.viewport(400, 800);
  void open([duplicate(5, 'Harbor at dusk')]);
  const dialog = page.getByRole('dialog', {name: 'Compare “photo.png”'});
  await expect.element(dialog).toBeVisible();

  const [upload, existing] = document.querySelectorAll<HTMLElement>(
    '[data-upload-duplicates-modal] section'
  );
  await expect
    .element(page.getByRole('heading', {name: 'Existing image'}))
    .toBeVisible();
  expect(existing!.getBoundingClientRect().top).toBeGreaterThan(
    upload!.getBoundingClientRect().bottom - 1
  );
  expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(400);
});
