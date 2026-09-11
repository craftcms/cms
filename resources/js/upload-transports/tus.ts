import Tus from '@uppy/tus';
import type {UploadTransportContext} from '../upload-client';
import {assertSameOrigin} from '../upload-request';

export function configureTus({
  uppy,
  session,
  headers,
}: UploadTransportContext): void {
  const {url} = session.transport.options;
  assertSameOrigin(url);
  uppy.use(Tus, {
    uploadUrl: url,
    chunkSize: session.chunkSize,
    headers: {Accept: 'application/json', ...headers},
    storeFingerprintForResuming: false,
    removeFingerprintOnSuccess: true,
  });
}
