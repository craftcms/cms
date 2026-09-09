import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {UploadOptions, UploadState} from '@/upload-client';
import type {UploadConflictChoice} from './upload-notification';

const ui = vi.hoisted(() => ({on: vi.fn(), post: vi.fn()}));
vi.mock('@inertiajs/vue3', () => ({router: {on: ui.on}}));
vi.mock('@craftcms/ui', () => ({
  t: (message: string, parameters?: {num: number}) =>
    parameters?.num ? `${parameters.num} files uploaded.` : message,
  actionClient: {post: ui.post},
}));

const notices: HTMLElement[] = [];
function displayNotice(message: string, {details}: {details: HTMLElement}) {
  const notice = document.createElement('div');
  notice.append(message, details);
  document.body.append(notice);
  notices.push(notice);
  return {
    close: () => notice.remove(),
    on: (_event: string, callback: () => void) => queueMicrotask(callback),
    $closeBtn: {hide: () => {}, on: () => {}},
  };
}

function clickNotice(index: number, label: string): void {
  const button = [
    ...notices[index]!.querySelectorAll<HTMLElement>('craft-button'),
  ].find((button) => button.textContent === label && !button.hidden);
  expect(button, `Visible ${label} button`).toBeDefined();
  button!.click();
}

const transfers: TestUpload[] = [];
class TestUpload {
  state: UploadState = 'ready';
  result = deferred<CraftCms.Cms.Asset.Data.UploadResult>();
  upload = vi.fn(() => {
    this.state = 'uploading';
    this.options.onStateChange?.(this.state);
    return this.result.promise;
  });
  cancel = vi.fn(async () => {
    this.state = 'canceled';
    this.result.reject(new Error('Canceled'));
  });
  constructor(
    public file: File,
    public options: UploadOptions
  ) {
    // A queued upload can be canceled before its promise has a consumer.
    void this.result.promise.catch(() => {});
    transfers.push(this);
  }
  complete(result: CraftCms.Cms.Asset.Data.UploadResult = {assetId: 12}): void {
    this.state = 'completed';
    this.options.onStateChange?.(this.state);
    this.result.resolve(result);
  }
  fail(message: string): void {
    this.state = 'failed';
    this.result.reject(new Error(message));
  }
}
vi.mock('@/upload-client', () => ({
  FileUpload: class {
    constructor(file: File, options: UploadOptions) {
      return new TestUpload(file, options);
    }
  },
}));

import {AssetUploadQueue} from './asset-upload-queue';

let queue: AssetUploadQueue;
const destination = {folderId: 7, url: '/admin/assets/images', label: 'Images'};
const file = () => new File(['contents'], 'photo.png');
const conflict = {
  assetId: 21,
  filename: 'photo.png',
  conflict: 'That filename exists.',
  conflictingAssetId: 20,
};

beforeEach(() => {
  ui.on.mockReset().mockReturnValue(vi.fn());
  transfers.length = 0;
  notices.length = 0;
  ui.post.mockReset().mockResolvedValue({data: {}});
  vi.stubGlobal('Craft', {
    csrfTokenValue: 'token',
    cp: {displayNotice, displayError: vi.fn(), runQueue: vi.fn()},
  });
  queue = new AssetUploadQueue();
});

afterEach(async () => {
  await queue.cancelAll();
  document.body.replaceChildren();
  vi.unstubAllGlobals();
});

it('serializes selections and retains their original destinations', async () => {
  const folder = {...destination};
  queue.enqueue(file(), folder, []);
  queue.enqueue(file(), {...destination, folderId: 8}, []);
  folder.folderId = 99;

  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalledOnce());
  expect(transfers[1]!.upload).not.toHaveBeenCalled();
  expect(transfers.map((transfer) => transfer.options.parameters)).toEqual([
    {folderId: 7},
    {folderId: 8},
  ]);

  transfers[0]!.complete();
  await vi.waitFor(() => expect(transfers[1]!.upload).toHaveBeenCalledOnce());
  transfers[1]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(transfers[0]!.cancel).not.toHaveBeenCalled();
});

it('leaves failures actionable, continues the queue, and retries the same transfer', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.fail('You no longer have permission to upload here.');
  await vi.waitFor(() => expect(transfers[1]!.upload).toHaveBeenCalled());
  expect(notices[0]!.textContent).toContain(
    'You no longer have permission to upload here.'
  );
  transfers[0]!.result = deferred();
  clickNotice(0, 'Retry');
  expect(transfers[0]!.upload).toHaveBeenCalledTimes(1);
  transfers[1]!.complete();
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalledTimes(2));
  transfers[0]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
});

it('does not interrupt navigation with a conflict dialog or block later uploads', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.complete(conflict);
  await vi.waitFor(() => expect(transfers[1]!.upload).toHaveBeenCalled());
  expect(document.querySelector('craft-dialog')).toBeNull();
  expect(notices[0]!.isConnected).toBe(true);
  expect(notices[0]!.textContent).toContain(conflict.conflict);

  clickNotice(0, 'Action required');
  expect(document.querySelector('craft-dialog')!.textContent).toContain(
    conflict.conflict
  );
  choose('keepBoth');
  transfers[1]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(ui.post).not.toHaveBeenCalled();
});

it.each([
  [conflict, {sourceAssetId: 21, assetId: 20}],
  [
    {...conflict, conflictingAssetId: null},
    {sourceAssetId: 21, targetFilename: 'photo.png'},
  ],
])(
  'resolves an indexed or unindexed filename conflict only on request',
  async (result, parameters) => {
    await uploadConflict(result);
    clickNotice(0, 'Action required');
    choose('replace');
    await vi.waitFor(() => expect(queue.hasPending).toBe(false));
    expect(ui.post).toHaveBeenCalledWith(
      '/admin/actions/assets/resolve-upload-conflict',
      parameters
    );
  }
);

it('retains a conflict and its error when replacement fails', async () => {
  await uploadConflict();
  ui.post.mockRejectedValue(new Error('The destination was deleted.'));
  clickNotice(0, 'Action required');
  choose('replace');
  await vi.waitFor(() =>
    expect(notices[0]!.textContent).toContain('The destination was deleted.')
  );
  expect(queue.hasPending).toBe(true);
});

it('discards the uploaded copy when canceling a conflict', async () => {
  await uploadConflict();
  clickNotice(0, 'Cancel');
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(ui.post).toHaveBeenCalledWith('/admin/actions/assets/delete-asset', {
    assetId: 21,
  });
  expect(queue.hasPending).toBe(false);
});

it('cancels a waiting file without ever starting its transfer', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  clickNotice(1, 'Cancel');
  transfers[0]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(transfers[1]!.upload).not.toHaveBeenCalled();
  expect(transfers[1]!.cancel).toHaveBeenCalledOnce();
});

it('clears queued work without canceling a final save or displaying its result', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.state = 'completing';
  const changed = vi.fn();
  queue.addEventListener('change', changed);

  await queue.cancelAll();
  expect(queue.hasPending).toBe(false);
  expect(transfers[0]!.cancel).not.toHaveBeenCalled();
  expect(transfers[1]!.cancel).toHaveBeenCalledOnce();
  transfers[0]!.complete();
  await Promise.resolve();
  expect(changed).not.toHaveBeenCalled();
  expect(document.body.textContent).not.toContain('Upload complete.');
  expect(transfers[1]!.upload).not.toHaveBeenCalled();
});

it('groups completed files from one selection into one destination notice', async () => {
  const selection: File[] = [];
  queue.enqueue(file(), destination, selection);
  queue.enqueue(file(), destination, selection);
  transfers[0]!.complete();
  await vi.waitFor(() => expect(transfers[1]!.upload).toHaveBeenCalled());
  transfers[1]!.complete();
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));

  expect(document.body.textContent).toContain('2 files uploaded.');
  const links = document.querySelectorAll('a');
  expect(links).toHaveLength(1);
  expect(links[0]!.getAttribute('href')).toBe(destination.url);
  expect(links[0]!.textContent).toBe(destination.label);
});

it('warns before leaving with pending uploads and stops warning after cancellation', async () => {
  queue.enqueue(file(), destination, []);
  queue.enqueue(file(), destination, []);
  const active = new Event('beforeunload', {cancelable: true});
  window.dispatchEvent(active);
  expect(active.defaultPrevented).toBe(true);

  await queue.cancelAll();
  const idle = new Event('beforeunload', {cancelable: true});
  window.dispatchEvent(idle);
  expect(idle.defaultPrevented).toBe(false);
});

it('keeps uploads on ordinary visits and cancels them on authentication visits', async () => {
  queue.enqueue(file(), destination, []);
  const navigate = ui.on.mock.calls[0]![1];
  navigate({detail: {page: {component: 'entries/Edit'}}});
  expect(queue.hasPending).toBe(true);
  navigate({detail: {page: {component: 'auth/Login'}}});
  await vi.waitFor(() => expect(queue.hasPending).toBe(false));
  expect(transfers[0]!.cancel).toHaveBeenCalledOnce();
});

async function uploadConflict(
  result: CraftCms.Cms.Asset.Data.UploadResult = conflict
): Promise<void> {
  queue.enqueue(file(), destination, []);
  await vi.waitFor(() => expect(transfers[0]!.upload).toHaveBeenCalled());
  transfers[0]!.complete(result);
  await vi.waitFor(() =>
    expect(notices[0]!.textContent).toContain(result.conflict!)
  );
}

function choose(choice: UploadConflictChoice): void {
  const label = {
    keepBoth: 'Keep both',
    replace: 'Replace',
    cancel: 'Cancel upload',
  }[choice];
  const button = [
    ...document.querySelectorAll<HTMLElement>('craft-dialog craft-button'),
  ].find((button) => button.textContent === label);
  expect(button).toBeDefined();
  button!.click();
}

function deferred<T>(): {
  promise: Promise<T>;
  resolve: (value: T) => void;
  reject: (reason: Error) => void;
} {
  let resolve!: (value: T) => void;
  let reject!: (reason: Error) => void;
  const promise = new Promise<T>((success, failure) => {
    resolve = success;
    reject = failure;
  });
  return {promise, resolve, reject};
}
