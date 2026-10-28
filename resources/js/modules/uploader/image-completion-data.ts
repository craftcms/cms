import {readImageSample, type ImageColors} from './image-colors';
import {readImageMetadata, type ImageMetadata} from './image-metadata';

/**
 * Completion data for asset uploads: what the browser could read from an
 * image file, its colors, difference hash, and embedded metadata, shaped like
 * the analysis the server stores them in, so it can store them without
 * downloading or decoding the file. Anything it couldn't read is left for the
 * server to find.
 */
export async function imageCompletionData(file: File): Promise<{
  analysis: {colors?: ImageColors; hash?: string; metadata?: ImageMetadata};
} | null> {
  const [sample, metadata] = await Promise.all([
    readImageSample(file),
    readImageMetadata(file),
  ]);

  if (!sample && !metadata) {
    return null;
  }

  return {
    analysis: {
      ...sample,
      ...(metadata ? {metadata} : {}),
    },
  };
}
