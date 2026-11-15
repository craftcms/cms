import {nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {useAssistantContext} from './context';
import {
  finishUploads,
  forgetUploads,
  isUploading,
  removeUpload,
  uploadFiles,
} from './upload-context';

const mocks = vi.hoisted(() => {
  class FakeUpload {
    static instances: FakeUpload[] = [];
    state = 'ready';
    canCancel = true;
    resolve!: (result: Record<string, unknown>) => void;
    readonly promise = new Promise<Record<string, unknown>>((resolve) => {
      this.resolve = resolve;
    });

    constructor(public readonly file: File) {
      FakeUpload.instances.push(this);
    }

    upload(): Promise<Record<string, unknown>> {
      this.state = 'uploading';

      return this.promise;
    }

    cancel(): Promise<void> {
      if (!this.canCancel) {
        return Promise.reject(new Error('The file is being saved.'));
      }

      this.state = 'canceled';

      return Promise.resolve();
    }
  }

  return {
    FakeUpload,
    post: vi.fn(() => Promise.resolve({data: {}})),
  };
});

const thread = ref('thread-1');

vi.mock('@craftcms/ui', async () => ({
  ...(await vi.importActual('@craftcms/ui')),
  t: (message: string) => message,
  actionClient: {post: mocks.post, get: vi.fn()},
}));
vi.mock('@/upload-client', () => ({FileUpload: mocks.FakeUpload}));
vi.mock('@/modules/messages/useMessages', () => ({
  useMessages: () => ({error: vi.fn()}),
}));
vi.mock('./assistant', () => ({useAssistantThread: () => thread}));

function file(name: string): File {
  return new File(['text'], name, {type: 'text/plain'});
}

afterEach(() => {
  forgetUploads();
  useAssistantContext().takeForMessage();
  mocks.FakeUpload.instances = [];
  mocks.post.mockClear();
  thread.value = 'thread-1';
});

it('waits for files added while it waits for the others to upload', async () => {
  uploadFiles([file('first.txt')]);
  let finished = false;
  const waiting = finishUploads().then(() => {
    finished = true;
  });
  uploadFiles([file('second.txt')]);

  mocks.FakeUpload.instances[0]!.resolve({assetId: 1});
  await new Promise((resolve) => setTimeout(resolve));

  expect(finished).toBe(false);

  mocks.FakeUpload.instances[1]!.resolve({assetId: 2});
  await waiting;

  expect(isUploading()).toBe(false);
});

it('deletes a file removed while it was being saved, once it’s saved', async () => {
  uploadFiles([file('notes.txt')]);
  const upload = mocks.FakeUpload.instances[0]!;
  upload.canCancel = false;
  const item = useAssistantContext().items.value[0]!;

  const removing = removeUpload(item.ref);
  upload.resolve({assetId: 7});
  await removing;

  expect(useAssistantContext().items.value).toEqual([]);
  expect(mocks.post).toHaveBeenCalledWith(expect.any(String), {
    threadId: 'thread-1',
    assetId: 7,
  });
});

it('removes the composer’s uploads when the user switches conversations', async () => {
  uploadFiles([file('notes.txt')]);
  mocks.FakeUpload.instances[0]!.resolve({assetId: 5});
  await finishUploads();

  thread.value = 'thread-2';
  await nextTick();
  await Promise.resolve();

  expect(useAssistantContext().items.value).toEqual([]);
  expect(mocks.post).toHaveBeenCalledWith(expect.any(String), {
    threadId: 'thread-1',
    assetId: 5,
  });
});
