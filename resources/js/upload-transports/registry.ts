import Uppy from '@uppy/core';
import {configureTus} from './tus';
import {configureS3} from './s3';
import {UploadError, type UploadRequestOptions} from '../upload-request';

export type UploadSession = CraftCms.Cms.Filesystem.Data.UploadSessionData;

export interface UploadTransportContext {
  session: UploadSession;
  headers: Record<string, string>;
  request: <T>(
    url: string,
    method: string,
    data?: unknown,
    options?: UploadRequestOptions
  ) => Promise<T>;
  beginCompletion: () => void;
}

export type PrepareUpload = (
  fileId: string,
  context: UploadTransportContext
) => void | (() => void);

export type UploadTransport = (uppy: Uppy) => PrepareUpload | void;

interface TransportInstance {
  uppy: Uppy;
  prepare: PrepareUpload | void;
}

const transports = new Map<string, UploadTransport>();
const instances = new Map<string, TransportInstance>();

export function registerTransport(
  type: string,
  configure: UploadTransport
): void {
  if (instances.has(type)) {
    throw new UploadError(
      `Upload transport "${type}" has already been used and cannot be replaced.`,
      409
    );
  }

  transports.set(type, configure);
}

export function transportInstance(type: string): TransportInstance {
  const configure = transports.get(type);

  if (!configure) {
    throw new UploadError(
      `No upload transport is registered for "${type}".`,
      400
    );
  }

  if (!instances.has(type)) {
    const uppy = new Uppy({
      onBeforeFileAdded: (file) => ({...file, id: crypto.randomUUID()}),
    });

    try {
      instances.set(type, {uppy, prepare: configure(uppy)});
    } catch (error) {
      uppy.destroy();

      throw error;
    }
  }

  return instances.get(type)!;
}

registerTransport('tus', configureTus);
registerTransport('s3', configureS3);
