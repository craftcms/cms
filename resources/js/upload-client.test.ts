import {Blob as NodeBlob, File as NodeFile} from 'node:buffer';
import {afterEach, beforeEach, expect, it, vi} from 'vitest';
import {AssetUpload, UploadError} from './upload-client';

const control = vi.fn();
const sent: Blob[] = [];
const transfers: {
  url: string;
  method: string;
  headers: Record<string, string>;
}[] = [];
let statuses: number[];
let holdTransfers: boolean;
let partRequest: {url: string; method: string; headers: Record<string, string>};

class Transfer {
  upload = new EventTarget();
  status = 200;
  statusText = '';
  readyState = 0;
  response: unknown = null;
  responseText = '';
  onload = () => {};
  onloadend = () => {};
  onabort = () => {};
  onerror = () => {};
  ontimeout = () => {};
  private url = '';
  private method = '';
  private headers: Record<string, string> = {};
  private aborted = false;
  open(method: string, url: string) {
    this.method = method;
    this.url = url;
    this.headers = {};
  }
  setRequestHeader(name: string, value: string) {
    this.headers[name] = value;
  }
  getAllResponseHeaders = () => '';
  abort() {
    this.aborted = true;
    this.onabort();
    this.onloadend();
  }
  send(bytes: Blob | string | null) {
    const respond = async () => {
      if (bytes instanceof Blob) {
        sent.push(bytes);
        transfers.push({
          url: this.url,
          method: this.method,
          headers: this.headers,
        });
        if (holdTransfers) {
          return;
        }
        this.upload.dispatchEvent(
          new ProgressEvent('progress', {
            lengthComputable: true,
            loaded: 1,
            total: bytes.size,
          })
        );
        this.status = statuses.shift() ?? 200;
        if (this.status === 0) {
          this.onerror();
          return;
        }
        if (this.status === -1) {
          this.ontimeout();
          return;
        }
      } else {
        const response = await control(this.url, {
          method: this.method,
          headers: this.headers,
          body: bytes ?? undefined,
        });
        this.status = response.status;
        this.responseText = await response.text();
      }
      this.readyState = 4;
      if (!this.aborted) {
        this.onload();
        this.onloadend();
      }
    };
    void respond();
  }
}

beforeEach(() => {
  sent.length = 0;
  transfers.length = 0;
  holdTransfers = false;
  statuses = [];
  partRequest = {
    url: 'https://storage.example/part',
    method: 'PUT',
    headers: {},
  };
  control.mockReset();
  control.mockImplementation(async (url: string) => {
    const body =
      url === '/start'
        ? {
            id: 'one',
            chunkSize: 3,
            partCount: 2,
            urls: {part: '/part', complete: '/complete', cancel: '/cancel'},
          }
        : url === '/part'
          ? partRequest
          : {assetId: 42};
    return new Response(JSON.stringify(body), {status: 200});
  });
  vi.stubGlobal('Blob', NodeBlob);
  vi.stubGlobal('File', NodeFile);
  vi.stubGlobal('XMLHttpRequest', Transfer);
});

afterEach(() => {
  document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/';
  vi.unstubAllGlobals();
  vi.useRealTimers();
});

it.each<typeof partRequest>([
  {
    url: 'https://storage.example/part',
    method: 'PUT',
    headers: {'X-Storage': 'signed'},
  },
  {url: '/chunk', method: 'POST', headers: {'X-CSRF-TOKEN': 'local-token'}},
])(
  'uploads bytes via $method and reports the saved asset',
  async (destination) => {
    partRequest = destination;
    const progress = vi.fn();
    const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
      url: '/start',
      csrfToken: 'token',
      onProgress: progress,
    });

    await expect(upload.upload()).resolves.toEqual({assetId: 42});
    expect(await Promise.all(sent.map((part) => part.text()))).toEqual([
      'abc',
      'def',
    ]);
    expect(control).toHaveBeenCalledWith(
      '/start',
      expect.objectContaining({
        headers: expect.objectContaining({'X-CSRF-TOKEN': 'token'}),
      })
    );
    expect(transfers).toEqual([destination, destination]);
    expect(progress).toHaveBeenCalledWith(1, 6);
    expect(progress).toHaveBeenCalledWith(4, 6);
    expect(progress).toHaveBeenLastCalledWith(6, 6);
    await expect(upload.upload()).resolves.toEqual({assetId: 42});
    expect(sent).toHaveLength(2);
  }
);

it('retains acknowledged parts for manual retry', async () => {
  statuses = [200, 500];
  const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    retries: 0,
  });
  await expect(upload.upload()).rejects.toBeInstanceOf(UploadError);
  expect(upload.state).toBe('failed');

  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(await Promise.all(sent.map((part) => part.text()))).toEqual([
    'abc',
    'def',
    'def',
  ]);
  expect(control.mock.calls.filter(([url]) => url === '/start')).toHaveLength(
    1
  );
});

it('stops after the configured number of automatic retries', async () => {
  vi.useFakeTimers();
  statuses = [503, 503, 200];
  const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    retries: 1,
  });
  const result = expect(upload.upload()).rejects.toMatchObject({status: 503});
  await vi.runAllTimersAsync();
  await result;

  expect(sent).toHaveLength(2);
  expect(upload.state).toBe('failed');
});

it.each([403, 0, -1])(
  'refreshes signed URLs after transfer failure %s without leaking application headers',
  async (status) => {
    vi.useFakeTimers();
    statuses = [status, 200, 200];
    document.cookie = 'XSRF-TOKEN=application-csrf; path=/';
    const implementation = control.getMockImplementation()!;
    let signature = 0;
    control.mockImplementation(async (url: string) => {
      if (url === '/part') {
        return new Response(
          JSON.stringify({
            url: `https://storage.example/part?signature=${++signature}`,
            method: 'PUT',
            headers: {'X-Storage': 'signed'},
          })
        );
      }
      return implementation(url);
    });
    const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
      url: '/start',
      headers: {Authorization: 'application-token'},
    });
    const result = upload.upload();
    await vi.runAllTimersAsync();
    await expect(result).resolves.toEqual({assetId: 42});
    expect(transfers.map(({url}) => url)).toEqual([
      'https://storage.example/part?signature=1',
      'https://storage.example/part?signature=2',
      'https://storage.example/part?signature=3',
    ]);
    for (const transfer of transfers) {
      expect(transfer.method).toBe('PUT');
      expect(transfer.headers).toEqual({'X-Storage': 'signed'});
    }
  }
);

it('stops retrying when permission to sign another part is revoked', async () => {
  statuses = [403];
  const implementation = control.getMockImplementation()!;
  let requests = 0;
  control.mockImplementation(async (url: string) => {
    if (url === '/part' && ++requests > 1) {
      return new Response(JSON.stringify({message: 'Permission revoked'}), {
        status: 403,
      });
    }
    return implementation(url);
  });
  const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
  });

  await expect(upload.upload()).rejects.toThrow('Permission revoked');
  expect(sent).toHaveLength(1);
  expect(upload.state).toBe('failed');
});

it.each(['transfer', 'retry delay'])(
  'cancels during %s and cleans up its session',
  async (phase) => {
    vi.useFakeTimers();
    holdTransfers = phase === 'transfer';
    statuses = [503];
    const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
      url: '/start',
    });
    const result = expect(upload.upload()).rejects.toMatchObject({
      name: 'AbortError',
    });
    await vi.waitFor(() => expect(sent).toHaveLength(1));

    await upload.cancel();
    await result;
    await vi.runAllTimersAsync();
    expect(control).toHaveBeenCalledWith(
      '/cancel',
      expect.objectContaining({method: 'DELETE'})
    );
    expect(upload.state).toBe('canceled');
    expect(sent).toHaveLength(1);
  }
);

it('cancels a failed session and prevents another transfer', async () => {
  statuses = [500];
  const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    retries: 0,
  });
  await expect(upload.upload()).rejects.toBeInstanceOf(UploadError);
  await upload.cancel();
  expect(control).toHaveBeenCalledWith(
    '/cancel',
    expect.objectContaining({method: 'DELETE'})
  );
  await expect(upload.upload()).rejects.toThrow();
  expect(upload.state).toBe('canceled');
  expect(sent).toHaveLength(1);
});

it('retries finalization without sending the file again', async () => {
  const implementation = control.getMockImplementation()!;
  let fail = true;
  control.mockImplementation(async (url: string) => {
    if (url === '/complete' && fail) {
      fail = false;
      return new Response(JSON.stringify({message: 'Unavailable'}), {
        status: 503,
      });
    }
    return implementation(url);
  });
  const upload = new AssetUpload(new File(['abcdef'], 'file.txt'), {
    url: '/start',
    retries: 0,
  });
  await expect(upload.upload()).rejects.toThrow('Unavailable');
  await expect(upload.upload()).resolves.toEqual({assetId: 42});
  expect(sent).toHaveLength(2);
});
