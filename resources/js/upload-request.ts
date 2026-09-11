export class UploadError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly data: Record<string, unknown> = {}
  ) {
    super(message);
    this.name = 'UploadError';
  }
}

export function assertSameOrigin(url: string): void {
  if (new URL(url, location.href).origin !== location.origin) {
    throw new UploadError(
      'Upload control requests must use the current origin.',
      400
    );
  }
}
