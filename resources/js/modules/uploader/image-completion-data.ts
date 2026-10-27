import {readImageColors, type ImageColors} from './image-colors';
import {readImageMetadata, type ImageMetadata} from './image-metadata';

/**
 * Completion data for asset uploads: what the browser could read from an
 * image file, its colors and embedded metadata, so the server can store them
 * without downloading or decoding the file. Anything it couldn't read is left
 * for the server to find.
 */
export async function imageCompletionData(
  file: File
): Promise<{colors?: ImageColors; metadata?: ImageMetadata} | null> {
  const [colors, metadata] = await Promise.all([
    readImageColors(file),
    readImageMetadata(file),
  ]);

  if (!colors && !metadata) {
    return null;
  }

  return {...(colors ? {colors} : {}), ...(metadata ? {metadata} : {})};
}
