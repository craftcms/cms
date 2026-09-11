import AwsS3 from '@uppy/aws-s3';
import type {UploadTransportContext} from '../upload-client';

export function configureS3({
  uppy,
  session,
  request,
  beginCompletion,
}: UploadTransportContext): void {
  uppy.use(AwsS3, {
    shouldUseMultipart: true,
    getChunkSize: () => session.chunkSize,
    allowedMetaFields: false,
    signRequest: (parameters) => {
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
  uppy.once('file-added', (file) => {
    const fileState = {
      ...file,
      s3Multipart: {
        key: session.transport.options.key,
        uploadId: session.transport.options.uploadId,
      },
    };
    uppy.setFileState(file.id, fileState);
  });
}
