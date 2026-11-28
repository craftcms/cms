import '../../../css/cp.css';
import '@craftcms/ui/components/action-menu/action-menu';
import '@craftcms/ui/components/badge/badge';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/dialog/dialog';
import '@craftcms/ui/components/pane/pane';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {openUploadDuplicatesModal} from './open-upload-duplicates-modal';
import type {UploadDuplicate} from './upload-duplicates-notification.ce';

let opener: HTMLButtonElement;
let file: File;
let thumb: string;

beforeEach(async () => {
  await page.viewport(1200, 800);
  const canvas = new OffscreenCanvas(30, 20);
  canvas.getContext('2d')!.fillRect(0, 0, 30, 20);
  const blob = await canvas.convertToBlob();
  file = new File([blob], 'photo.png', {type: 'image/png'});
  thumb = await new Promise<string>((resolve) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result as string);
    reader.readAsDataURL(blob);
  });
  opener = document.createElement('button');
  opener.textContent = 'Compare';
  document.body.append(opener);
  opener.focus();
});

afterEach(() => {
  document.body.replaceChildren();
  document.documentElement.removeAttribute('dir');
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
    thumbUrl: thumb,
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

function shownPanel(): HTMLElement {
  return document.querySelector<HTMLElement>('[data-upload-duplicates-modal]')!;
}

/** The details whose rows say they differ, by their row headers. */
function marked(panel: HTMLElement): string[] {
  return [...panel.querySelectorAll('th[scope="rowgroup"]')]
    .filter((header) => header.querySelector('.image-comparison__badge'))
    .map((header) => header.firstChild!.textContent!.trim());
}

/** A detail's two rows: its labels, and its values. */
function rows(panel: HTMLElement, label: string): HTMLTableRowElement[] {
  const header = [...panel.querySelectorAll('th[scope="rowgroup"]')].find(
    (header) => header.firstChild!.textContent!.trim() === label
  )!;

  return [...header.closest('tbody')!.rows];
}

it('shows the upload beside the image it looks like, marking the rows that differ across the whole dialog', async () => {
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
    .element(page.getByRole('columnheader', {name: 'New upload'}))
    .toBeVisible();
  await expect
    .element(page.getByRole('columnheader', {name: 'Existing image'}))
    .toBeVisible();
  expect(
    [
      ...document.querySelectorAll<HTMLElement>(
        'craft-pane craft-asset-conflict-resolution craft-button'
      ),
    ].map((button) => button.textContent!.trim())
  ).toEqual(['Keep both', 'Discard upload', 'Replace']);

  const panel = shownPanel();
  await expect
    .poll(() => marked(panel))
    .toEqual(['Filename', 'Dimensions', 'File type', 'Location']);
  expect(panel.textContent).toContain('30 × 20');
  expect(panel.textContent).toContain('Iwona');

  const [changedLabels, changedValues] = rows(panel, 'Filename');
  const [, sameValues] = rows(panel, 'File size');
  const fill = getComputedStyle(changedValues!).backgroundColor;
  expect(fill).not.toBe('rgba(0, 0, 0, 0)');
  expect(getComputedStyle(changedLabels!).backgroundColor).toBe(fill);
  expect(getComputedStyle(sameValues!).backgroundColor).toBe(
    'rgba(0, 0, 0, 0)'
  );
  const modalBox = panel.closest('craft-pane')!.getBoundingClientRect();
  const rowBox = changedValues!.getBoundingClientRect();
  expect(rowBox.left).toBeCloseTo(modalBox.left, 0);
  expect(rowBox.right).toBeCloseTo(modalBox.right, 0);

  for (const label of ['Filename', 'Location', 'Uploaded by']) {
    const [, values] = rows(panel, label);
    const [from, to] = [...values!.cells].map(
      (cell) => cell.getBoundingClientRect().top
    );
    expect(from).toBe(to);
  }
  const [uploadPreview, existingPreview] = [
    ...panel.querySelectorAll('.image-comparison__preview'),
  ].map((preview) => preview.getBoundingClientRect());
  expect([uploadPreview!.top, uploadPreview!.height]).toEqual([
    existingPreview!.top,
    existingPreview!.height,
  ]);

  const link = page.getByRole('link', {
    name: 'Harbor at dusk (opens in a new window)',
  });
  await expect.element(link).toHaveAttribute('href', '/admin/assets/edit/5');
  expect(link).toHaveAttribute('target', '_blank');
  const toHeader = page
    .getByRole('columnheader', {name: /Existing image/})
    .element()
    .getBoundingClientRect();
  const linkBox = (link.element() as HTMLElement).getBoundingClientRect();
  expect(toHeader.right - linkBox.right).toBeLessThan(40);
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
  await expect
    .element(page.getByRole('dialog', {name: 'Compare “photo.png”'}))
    .toBeVisible();
  expect(
    [
      ...document.querySelectorAll<HTMLElement>(
        'craft-pane craft-asset-conflict-resolution craft-button'
      ),
    ].map((button) => button.textContent!.trim())
  ).toEqual(['Keep all', 'Discard upload', 'Replace']);
  await expect.poll(status).toBe('None of these details differ.');
  expect(
    page.getByRole('columnheader', {name: 'Existing image'}).query()
  ).toBeNull();

  await page
    .getByRole('button', {name: 'Harbor at dusk Choose an image'})
    .click();
  await page
    .getByRole('button', {name: 'Harbor at night', exact: true})
    .click();
  await expect.poll(status).toBe('These details differ: File size');
  await expect
    .element(
      page.getByRole('button', {name: 'Harbor at night Choose an image'})
    )
    .toBeVisible();
  await expect
    .element(
      page.getByRole('link', {name: 'Harbor at night (opens in a new window)'})
    )
    .toHaveAttribute('href', '/admin/assets/edit/6');
  expect(marked(shownPanel())).toEqual(['File size']);

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

it('keeps both sides lined up in a narrow viewport, without scrolling sideways', async () => {
  await page.viewport(360, 800);
  void open([
    duplicate(5, 'Harbor at dusk', {
      filename: 'a-long-filename-for-a-narrow-viewport.png',
    }),
  ]);
  await expect
    .element(page.getByRole('dialog', {name: 'Compare “photo.png”'}))
    .toBeVisible();

  const panel = shownPanel();
  const [from, to] = [
    ...panel.querySelectorAll('.image-comparison__preview'),
  ].map((preview) => preview.getBoundingClientRect());
  expect(from!.top).toBe(to!.top);
  expect(to!.left).toBeGreaterThan(from!.right);
  expect(from!.height).toBeLessThan(200);
  expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(360);
});
