import AwsS3 from '@uppy/aws-s3';
import {UploadError} from '../upload-request';
import type Uppy from '@uppy/core';
import type {PrepareUpload, UploadTransportContext} from './registry';

export function configureS3(uppy: Uppy): PrepareUpload {
  const sessions = new Map<string, UploadTransportContext>();
  uppy.use(AwsS3, {
    shouldUseMultipart: (file) => {
      const {session} = sessions.get(file.id)!;
      // Uppy reads getChunkSize immediately after this callback, before starting I/O.
      uppy.getPlugin('AwsS3')!.setOptions({
        getChunkSize: () => session.chunkSize,
      });
      return true;
    },
    allowedMetaFields: false,
    signRequest: (parameters) => {
      const context = [...sessions.values()].find(
        ({session}) =>
          session.transport.options.key === parameters.key &&
          'uploadId' in parameters &&
          session.transport.options.uploadId === parameters.uploadId
      );
      if (!context) {
        throw new UploadError('No upload session matches the S3 request.', 400);
      }
      const {session, request, beginCompletion} = context;
      if (parameters.method === 'POST') {
        beginCompletion();
      }
      return request<{url: string}>(
        session.urls.sign,
        'POST',
        parameters,
        parameters.method === 'DELETE' ? {signal: null} : {}
      );
    },
  });
  return (fileId, context) => {
    const {key, uploadId} = context.session.transport.options;
    sessions.set(fileId, context);
    const fileState = {...uppy.getFile(fileId), s3Multipart: {key, uploadId}};
    uppy.setFileState(fileId, fileState);
    return () => sessions.delete(fileId);
  };
}
