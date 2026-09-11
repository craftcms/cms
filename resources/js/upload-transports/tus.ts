import Tus from '@uppy/tus';
import type Uppy from '@uppy/core';
import type {PrepareUpload} from './registry';
import {assertSameOrigin} from '../upload-request';

export function configureTus(uppy: Uppy): PrepareUpload {
  uppy.use(Tus, {
    storeFingerprintForResuming: false,
    removeFingerprintOnSuccess: true,
  });

  return (fileId, {session, headers}) => {
    const {url} = session.transport.options;
    assertSameOrigin(url);

    uppy.setFileState(fileId, {
      tus: {
        uploadUrl: url,
        chunkSize: session.chunkSize,
        headers: {Accept: 'application/json', ...headers},
      },
    });
  };
}
