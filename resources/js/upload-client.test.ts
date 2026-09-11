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
let offset: number;
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
      if (holdTransfers) {
        return;
      }
    }
    queueMicrotask(() => {
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
          offset += bytes.size;
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
      this.responseHeaders['upload-offset'] = String(offset);
      this.onload();
    });
  }
}

beforeEach(() => {
  sent.length = 0;
  transfers.length = 0;
  holdTransfers = false;
  statuses = [];
  offset = 0;
  s3 = false;
  transport = undefined;
  uploaded = false;
  control.mockReset();
  control.mockImplementation(async (url: string, options?: RequestInit) => {
    const body =
      url === '/start'
        ? {
            id: 'one',
            chunkSize: s3 ? 8388608 : 3,
            partCount: s3 ? 1 : 2,
            transport:
              transport ??
              (s3
                ? {
                    type: 's3',
                    options: {uploadId: 'multipart-id', key: 'staged/file'},
                  }
                : {type: 'tus', options: {url: '/tus'}}),
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
  vi.useRealTimers();
});

it.each([
  [false, 20],
  [true, 6],
] as const)(
  'shares Uppy’s default concurrency across file instances (S3: %s)',
  async (useS3, limit) => {
    s3 = useS3;
    holdTransfers = true;
    const uploads = Array.from(
      {length: limit + 2},
      (_, index) =>
        new FileUpload(new File(['abcdef'], `file-${index}.txt`), {
          url: '/start',
        })
    );
    const results = Promise.allSettled(
      uploads.map((upload) => upload.upload())
    );

    try {
      await vi.waitFor(() => expect(sent).toHaveLength(limit));
      expect(uploads[limit]!.state).toBe('ready');
      expect(uploads[limit]!.canPause).toBe(false);

      await uploads[limit]!.cancel();
      await uploads[0]!.cancel();
      await vi.waitFor(() => expect(sent).toHaveLength(limit + 1));
      expect(uploads[limit + 1]!.state).toBe('uploading');
    } finally {
      await Promise.all(uploads.map((upload) => upload.cancel()));
      await results;
    }
  }
);

it('connects a Craft session to tus and returns the handler response idempotently', async () => {
  const progress = vi.fn();
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    csrfToken: 'token',
    parameters: {folderId: 12},
    onProgress: progress,
  });
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(transfers[1]?.headers).toMatchObject({
    'x-csrf-token': 'token',
  });
  expect(progress).toHaveBeenLastCalledWith(6, 6);
  expect(control).toHaveBeenCalledWith(
    '/start',
    expect.objectContaining({
      body: JSON.stringify({folderId: 12, filename: 'file.txt', size: 6}),
    })
  );
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(sent).toHaveLength(2);
});

it('reuses the Craft session when retrying a failed transfer', async () => {
  statuses = [200, 403];
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
  });
  await expect(upload.upload()).rejects.toThrow();
  expect(upload.state).toBe('failed');
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(control.mock.calls.filter(([url]) => url === '/start')).toHaveLength(
    1
  );
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
    expect(signatures).toEqual([
      {method: 'GET', key: 'staged/file', uploadId: 'multipart-id'},
      {
        method: 'PUT',
        key: 'staged/file',
        uploadId: 'multipart-id',
        partNumber: 1,
      },
      {method: 'POST', key: 'staged/file', uploadId: 'multipart-id'},
    ]);
  } finally {
    axios.defaults.headers.common = defaults;
  }
});

it.each([false, true])(
  'cancels a paused transfer and cleans up its session (S3: %s)',
  async (useS3) => {
    s3 = useS3;
    holdTransfers = true;
    const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
      url: '/start',
    });
    const result = expect(upload.upload()).rejects.toMatchObject({
      name: 'AbortError',
    });
    await vi.waitFor(() => expect(sent).toHaveLength(1));
    upload.pause();
    await upload.cancel();
    await result;
    expect(control).toHaveBeenCalledWith(
      '/cancel',
      expect.objectContaining({method: 'DELETE'})
    );
    expect(upload.state).toBe('canceled');
  }
);

it('retries finalization without sending the file again', async () => {
  vi.useFakeTimers();
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
  await vi.runAllTimersAsync();
  await failure;
  fail = false;
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(sent).toHaveLength(2);
});

it('recognizes completed S3 storage when retrying a failed transfer', async () => {
  vi.useFakeTimers();
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
  await vi.runAllTimersAsync();
  await failure;
  uploaded = true;
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(sent).toHaveLength(1);
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
  const configure = vi.fn(
    ({
      uppy,
      session,
      headers,
      request,
      beginCompletion,
    }: UploadTransportContext) => {
      expect(headers).toEqual({'X-CSRF-TOKEN': 'csrf-token'});
      uppy.addUploader(async ([id]: string[]) => {
        const file = uppy.getFile(id!);
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
      });
    }
  );
  registerTransport('custom', configure);
  const progress = vi.fn();
  const state = vi.fn();
  const upload = new FileUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    csrfToken: 'csrf-token',
    onProgress: progress,
    onStateChange: state,
  });

  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(configure).toHaveBeenCalledOnce();
  expect(progress).toHaveBeenCalledWith(6, 6);
  expect(state).toHaveBeenCalledWith('completing');
  expect(control).toHaveBeenCalledWith(
    '/custom/sign',
    expect.objectContaining({body: JSON.stringify({ticket: 'upload-ticket'})})
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
    expect(upload.upload()).toBe(result);
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
