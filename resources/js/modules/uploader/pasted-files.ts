/**
 * Gives pasted image data a unique filename.
 *
 * Browsers name image data pasted from the clipboard `image.png` (or leave it
 * unnamed), so every pasted screenshot would otherwise conflict with the last.
 * Files with any other name are returned as-is.
 */
export function namePastedFiles(files: File[], now = new Date()): File[] {
  // The sv locale formats local times as YYYY-MM-DD HH:MM:SS
  const timestamp = now
    .toLocaleString('sv')
    .replace(/[^\d ]/g, '')
    .replace(' ', '-');

  return files.map((file, index) => {
    if (file.name && !/^image\.\w+$/i.test(file.name)) {
      return file;
    }

    const extension =
      file.name.match(/\.(\w+)$/)?.[1] ||
      {'image/jpeg': 'jpg', 'image/svg+xml': 'svg'}[file.type] ||
      file.type.split('/')[1];
    const suffix = files.length > 1 ? `-${index + 1}` : '';

    return new File([file], `pasted-image-${timestamp}${suffix}.${extension}`, {
      type: file.type,
      lastModified: file.lastModified,
    });
  });
}
