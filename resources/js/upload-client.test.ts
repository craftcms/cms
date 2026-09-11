import {Blob as NodeBlob, File as NodeFile} from 'node:buffer';
import axios, {AxiosError} from 'axios';
import {afterEach, beforeEach, expect, it, vi} from 'vitest';
import {
  FileUpload,
  registerTransport,
  type UploadTransportContext,
  type UploadSession,
} from './upload-client';

// Vitest runs in Node; exercise the browser tus transport used by Vite.
vi.mock('tus-js-client', () =>
  vi.importActual('tus-js-client/lib.es5/browser/index.js')
);

const control = vi.fn();
const sent: Blob[] = [];
const transfers: Transfer[] = [];
let statuses: number[];
const offsets = new Map<string, number>();
let sessionCount: number;
let holdTransfers: boolean;
let s3: boolean;
let uploaded: boolean;
let adapter: typeof axios.defaults.adapter;
let transport: UploadSession['transport'] | undefined;

class Transfer {
  upload = {onprogress: (_event: ProgressEvent) => {}};
  status = 200;
  responseText = '';
  onload = () => {};
  onabort = () => {};
  onerror = () => {};
  ontimeout = () => {};
  url = '';
  method = '';
  headers: Record<string, string> = {};
  responseHeaders: Record<string, string> = {};
  aborted = false;
  finish = () => {};
  open(method: string, url: string) {
    this.method = method;
    this.url = url;
  }
  setRequestHeader(name: string, value: string) {
    this.headers[name.toLowerCase()] = value;
  }
  getResponseHeader(name: string) {
    return this.responseHeaders[name.toLowerCase()] ?? null;
  }
  abort() {
    this.aborted = true;
    this.onabort();
  }
  send(bytes?: Blob | string) {
    transfers.push(this);
    if (bytes instanceof Blob) {
      sent.push(bytes);
    }
    this.finish = () => {
      if (this.aborted) {
        return;
      }
      this.responseHeaders = {'tus-resumable': '1.0.0', 'upload-length': '6'};
      if (bytes instanceof Blob) {
        this.upload.onprogress(
          new ProgressEvent('progress', {
            lengthComputable: true,
            loaded: 1,
            total: bytes.size,
          })
        );
        this.status = statuses.shift() ?? 200;
        if (this.status === 200) {
          offsets.set(this.url, (offsets.get(this.url) ?? 0) + bytes.size);
          this.status = this.method === 'PATCH' ? 204 : 200;
          this.responseHeaders.etag = '"part-etag"';
        }
      } else if (this.method === 'GET') {
        this.responseText = '<ListPartsResult></ListPartsResult>';
      } else if (this.method === 'POST') {
        uploaded = true;
        this.responseText =
          '<CompleteMultipartUploadResult><Location>https://storage.example/file</Location><Key>staged/file</Key><ETag>file-etag</ETag></CompleteMultipartUploadResult>';
      } else if (this.method === 'DELETE') {
        this.status = 204;
      }
      this.responseHeaders['upload-offset'] = String(
        offsets.get(this.url) ?? 0
      );
      this.onload();
    };
    if (!holdTransfers || !(bytes instanceof Blob)) {
      queueMicrotask(this.finish);
    }
  }
}

beforeEach(() => {
  sent.length = 0;
  transfers.length = 0;
  holdTransfers = false;
  statuses = [];
  offsets.clear();
  sessionCount = 0;
  s3 = false;
  transport = undefined;
  uploaded = false;
  control.mockReset();
  control.mockImplementation(async (url: string, options?: RequestInit) => {
    if (url === '/start') sessionCount++;
    const body =
      url === '/start'
        ? {
            id: String(sessionCount),
            chunkSize: s3 ? 5242880 : 3,
            partCount: s3 ? 1 : 2,
            transport:
              transport ??
              (s3
                ? {
                    type: 's3',
                    options: {
                      uploadId: `multipart-${sessionCount}`,
                      key: 'staged/file',
                    },
                  }
                : {type: 'tus', options: {url: `/tus/${sessionCount}`}}),
            urls: {
              sign: '/sign',
              status: '/status',
              complete: '/complete',
              cancel: '/cancel',
            },
          }
        : url === '/status'
          ? {uploaded}
          : url === '/sign'
            ? {
                url: `https://storage.example/file?method=${JSON.parse(options?.body as string).method}`,
              }
            : {assetId: 42};
    return new Response(JSON.stringify(body), {status: 200});
  });
  vi.stubGlobal('Blob', NodeBlob);
  vi.stubGlobal('File', NodeFile);
  vi.stubGlobal('XMLHttpRequest', Transfer);
  adapter = axios.defaults.adapter;
  axios.defaults.adapter = async (config) => {
    const response = await control(config.url, {
      method: config.method?.toUpperCase(),
      body: config.data,
      headers: config.headers.toJSON(),
      signal: config.signal,
    });
    const result = {
      data: await response.text(),
      status: response.status,
      statusText: response.statusText,
      headers: {},
      config,
    };
    if (!response.ok) {
      throw new AxiosError(
        'Request failed',
        undefined,
        config,
        undefined,
        result
      );
    }
    return result;
  };
});

afterEach(() => {
  axios.defaults.adapter = adapter;
  vi.unstubAllGlobals();
});

it('connects a Craft session to tus and returns the handler response idempotently', async () => {
  const progress = vi.fn();
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    csrfToken: 'token',
    parameters: {folderId: 12},
    onProgress: progress,
  });
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(
    transfers.find(({method}) => method === 'PATCH')?.headers
  ).toMatchObject({
    'x-csrf-token': 'token',
  });
  expect(progress).toHaveBeenLastCalledWith(6, 6);
  expect(
    JSON.parse(control.mock.calls.find(([url]) => url === '/start')![1].body)
  ).toEqual({
    folderId: 12,
    filename: 'file.txt',
    size: 6,
  });
  const transferred = [...sent];
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(sent).toEqual(transferred);
  expect(
    control.mock.calls.filter(([url]) => url === '/complete')
  ).toHaveLength(1);
});

it('uses the server-created S3 multipart upload without application headers on storage requests', async () => {
  s3 = true;
  const defaults = axios.defaults.headers.common;
  axios.defaults.headers.common = {...defaults, Authorization: 'global-token'};
  try {
    const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
      url: '/start',
      headers: {Authorization: 'app-token'},
      csrfToken: 'csrf-token',
    });
    await expect(upload.upload()).resolves.toEqual({assetId: 42});
    for (const transfer of transfers) {
      expect(transfer.url).toContain('https://storage.example/');
      expect(transfer.headers).not.toHaveProperty('authorization');
      expect(transfer.headers).not.toHaveProperty('x-csrf-token');
    }
    const signatures = control.mock.calls
      .filter(([url]) => url === '/sign')
      .map(([, options]) => JSON.parse(options.body));
    expect(signatures).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          method: 'PUT',
          key: 'staged/file',
          uploadId: 'multipart-1',
        }),
      ])
    );
    for (const signature of signatures) {
      expect(signature).toMatchObject({
        key: 'staged/file',
        uploadId: 'multipart-1',
      });
    }
  } finally {
    axios.defaults.headers.common = defaults;
  }
});

it('retries finalization without sending the file again', async () => {
  const implementation = control.getMockImplementation()!;
  let fail = true;
  control.mockImplementation(async (url: string, options: RequestInit) => {
    if (url === '/complete' && fail) {
      return new Response(JSON.stringify({message: 'Unavailable'}), {
        status: 503,
      });
    }
    return implementation(url, options);
  });
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
  });
  const failure = expect(upload.upload()).rejects.toThrow('Unavailable');
  await failure;
  expect(
    control.mock.calls.filter(([url]) => url === '/complete')
  ).toHaveLength(1);
  fail = false;
  const transferred = [...sent];
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(sent).toEqual(transferred);
});

it('recognizes completed S3 storage when retrying a failed transfer', async () => {
  s3 = true;
  const implementation = control.getMockImplementation()!;
  control.mockImplementation(async (url: string, options: RequestInit) => {
    if (
      url === '/sign' &&
      JSON.parse(options.body as string).method === 'POST'
    ) {
      return new Response(JSON.stringify({message: 'Unavailable'}), {
        status: 503,
      });
    }
    return implementation(url, options);
  });
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
  });
  const failure = expect(upload.upload()).rejects.toThrow('Unavailable');
  await failure;
  uploaded = true;
  const transferred = [...sent];
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(sent).toEqual(transferred);
});

it('preserves control request errors without retrying session creation', async () => {
  control.mockResolvedValue(
    new Response(JSON.stringify({message: 'Invalid destination'}), {
      status: 422,
    })
  );
  const upload = new FileUpload(new File(['abc'], 'file.txt'), {url: '/start'});

  await expect(upload.upload()).rejects.toMatchObject({
    name: 'UploadError',
    status: 422,
    message: 'Invalid destination',
    data: {message: 'Invalid destination'},
  });
  expect(control).toHaveBeenCalledOnce();
  expect(sent).toHaveLength(0);
});

it('cancels an in-flight session request without retrying', async () => {
  control.mockImplementation(
    (_url, {signal}: RequestInit) =>
      new Promise((_resolve, reject) => {
        signal!.addEventListener('abort', () => reject(signal!.reason), {
          once: true,
        });
      })
  );
  const upload = new FileUpload(new File(['abc'], 'file.txt'), {url: '/start'});
  const result = expect(upload.upload()).rejects.toMatchObject({
    name: 'AbortError',
  });
  await vi.waitFor(() => expect(control).toHaveBeenCalledOnce());
  await upload.cancel();
  await result;
  expect(control).toHaveBeenCalledOnce();
  expect(upload.state).toBe('canceled');
});

it('preserves the HTTP status of non-JSON control errors', async () => {
  control.mockResolvedValue(
    new Response('<html>Unavailable</html>', {status: 503})
  );
  const upload = new FileUpload(new File(['abc'], 'file.txt'), {url: '/start'});
  await expect(upload.upload()).rejects.toMatchObject({status: 503});
});

it('configures a registered transport while Craft retains the session lifecycle', async () => {
  transport = {type: 'custom', options: {ticket: 'upload-ticket'}};
  registerTransport('custom', (uppy) => {
    const contexts = new Map<string, UploadTransportContext>();
    uppy.addUploader(async (ids: string[]) => {
      await Promise.all(
        ids.map(async (id) => {
          const {session, request, beginCompletion} = contexts.get(id)!;
          const file = uppy.getFile(id);
          await request('/custom/sign', 'POST', {
            ticket: session.transport.options.ticket,
          });
          uppy.emit('upload-progress', file, {
            uploadStarted: Date.now(),
            bytesUploaded: 6,
            bytesTotal: 6,
          });
          beginCompletion();
          uppy.emit('upload-success', file, {status: 200, body: {}});
        })
      );
    });
    return (id: string, context: UploadTransportContext) => {
      contexts.set(id, context);
      return () => contexts.delete(id);
    };
  });
  const progress = vi.fn();
  const state = vi.fn();
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    csrfToken: 'csrf-token',
    onProgress: progress,
    onStateChange: state,
  });

  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(progress).toHaveBeenCalledWith(6, 6);
  expect(state).toHaveBeenCalledWith('completing');
  expect(control).toHaveBeenCalledWith(
    '/custom/sign',
    expect.objectContaining({
      body: JSON.stringify({ticket: 'upload-ticket'}),
      headers: expect.objectContaining({'X-CSRF-TOKEN': 'csrf-token'}),
    })
  );
  expect(control).toHaveBeenCalledWith(
    '/complete',
    expect.objectContaining({method: 'POST'})
  );
});

it('reports an unregistered transport without falling back to another protocol', async () => {
  transport = {type: 'unregistered', options: {}};
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
  });

  await expect(upload.upload()).rejects.toThrow(
    'No upload transport is registered for "unregistered".'
  );
  expect(upload.state).toBe('failed');
  expect(transfers).toHaveLength(0);
  await upload.cancel();
  expect(control).toHaveBeenCalledWith(
    '/cancel',
    expect.objectContaining({method: 'DELETE'})
  );
});

it.each([false, true])(
  'pauses and resumes the same Craft upload (S3: %s)',
  async (useS3) => {
    s3 = useS3;
    holdTransfers = true;
    const states = vi.fn();
    const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
      url: '/start',
      onStateChange: states,
    });
    const result = upload.upload();
    await vi.waitFor(() => expect(sent).toHaveLength(1));

    expect(upload.canPause).toBe(true);
    upload.pause();
    expect(upload.state).toBe('paused');
    expect(states).toHaveBeenLastCalledWith('paused');
    expect(
      control.mock.calls.some(([url]) => ['/complete', '/cancel'].includes(url))
    ).toBe(false);

    holdTransfers = false;
    upload.resume();
    await expect(result).resolves.toEqual({assetId: 42});
    expect(control.mock.calls.filter(([url]) => url === '/start')).toHaveLength(
      1
    );
    expect(upload.canPause).toBe(false);
  }
);

it.each([false, true])(
  'pauses and cancels one file without interrupting another with the same name (S3: %s)',
  async (useS3) => {
    s3 = useS3;
    holdTransfers = true;
    const file = new File(['abcdef'], 'same.txt');
    const first = new FileUpload(file, {url: '/start', csrfToken: 'first'});
    const secondProgress = vi.fn();
    const second = new FileUpload(file, {
      url: '/start',
      onProgress: secondProgress,
      csrfToken: 'second',
    });
    const firstResult = expect(first.upload()).rejects.toMatchObject({
      name: 'AbortError',
    });
    const secondResult = second.upload();

    await vi.waitFor(() => expect(sent).toHaveLength(2));
    const [firstTransfer, secondTransfer] = transfers.filter(
      ({method}) => method === (useS3 ? 'PUT' : 'PATCH')
    );
    if (!useS3) {
      expect(firstTransfer!.headers['x-csrf-token']).toBe('first');
      expect(secondTransfer!.headers['x-csrf-token']).toBe('second');
    }
    first.pause();
    expect(first.state).toBe('paused');
    expect(second.state).toBe('uploading');

    await first.cancel();
    await firstResult;
    holdTransfers = false;
    secondTransfer!.finish();
    await expect(secondResult).resolves.toEqual({assetId: 42});
    expect(secondProgress).toHaveBeenLastCalledWith(6, 6);
    expect(first.state).toBe('canceled');
    expect(control).toHaveBeenCalledWith(
      '/cancel',
      expect.objectContaining({method: 'DELETE'})
    );
  }
);

it.each([false, true])(
  'reuses each Craft session when retrying files independently (S3: %s)',
  async (useS3) => {
    s3 = useS3;
    statuses = [403];
    const first = new FileUpload(new File(['abcdef'], 'first.txt'), {
      url: '/start',
    });
    await expect(first.upload()).rejects.toThrow();
    holdTransfers = true;
    const second = new FileUpload(new File(['abcdef'], 'second.txt'), {
      url: '/start',
    });
    const secondResult = expect(second.upload()).rejects.toMatchObject({
      name: 'AbortError',
    });
    await vi.waitFor(() => expect(sent).toHaveLength(2));
    expect(first.state).toBe('failed');

    const retry = first.upload();
    await vi.waitFor(() => expect(sent).toHaveLength(3));
    const retriedTransfer = transfers
      .filter(({method}) => method === (useS3 ? 'PUT' : 'PATCH'))
      .at(-1)!;
    await second.cancel();
    await secondResult;
    holdTransfers = false;
    retriedTransfer.finish();
    await expect(retry).resolves.toEqual({assetId: 42});
    expect(control.mock.calls.filter(([url]) => url === '/start')).toHaveLength(
      2
    );
  }
);

it('completes a file while another file is still uploading', async () => {
  holdTransfers = true;
  const first = new FileUpload(new File(['abcdef'], 'first.txt'), {
    url: '/start',
  });
  const second = new FileUpload(new File(['abcdef'], 'second.txt'), {
    url: '/start',
  });
  const firstResult = first.upload();
  const secondResult = expect(second.upload()).rejects.toMatchObject({
    name: 'AbortError',
  });
  await vi.waitFor(() => expect(sent).toHaveLength(2));
  holdTransfers = false;
  transfers.find(({method}) => method === 'PATCH')!.finish();
  await expect(firstResult).resolves.toEqual({assetId: 42});
  expect(second.state).toBe('uploading');
  await second.cancel();
  await secondResult;
});

it('uses each S3 session’s signing headers', async () => {
  s3 = true;
  const file = new File(['abcdef'], 'same.txt');
  const uploads = ['first', 'second'].map(
    (csrfToken) => new FileUpload(file, {url: '/start', csrfToken})
  );
  await expect(
    Promise.all(uploads.map((upload) => upload.upload()))
  ).resolves.toEqual([{assetId: 42}, {assetId: 42}]);

  for (const [, options] of control.mock.calls.filter(
    ([url]) => url === '/sign'
  )) {
    const {uploadId} = JSON.parse(options.body);
    expect(options.headers['X-CSRF-TOKEN']).toBe(
      uploadId === 'multipart-1' ? 'first' : 'second'
    );
  }
});

it('allows transport replacement only before its first use', async () => {
  transport = {type: 'replaceable', options: {}};
  const unavailable = () => {
    throw new Error('Unavailable');
  };
  registerTransport('replaceable', unavailable);
  registerTransport('replaceable', (uppy) => {
    uppy.addUploader(async ([id]) => {
      uppy.emit('upload-success', uppy.getFile(id!), {status: 200, body: {}});
    });
  });
  const file = new File(['abcdef'], 'file.txt');
  await expect(new FileUpload(file, {url: '/start'}).upload()).resolves.toEqual(
    {assetId: 42}
  );

  expect(() => registerTransport('replaceable', unavailable)).toThrow(
    'Upload transport "replaceable" has already been used and cannot be replaced.'
  );
  await expect(new FileUpload(file, {url: '/start'}).upload()).resolves.toEqual(
    {assetId: 42}
  );
});
