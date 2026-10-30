export interface PreviewUrlParams {
  /** The `tokenParam` config setting's name, and the token. */
  tokenParam: string;
  token: string;
  /** `x-craft-preview` or `x-craft-live-preview`. */
  previewParam: string;
  paramValue: string;
  siteTokenParam: string;
  siteToken: string | null;
}

/**
 * A preview target's URL with what the front end needs to show the draft.
 * Relative URLs resolve against the control panel's origin, as links would.
 */
export function tokenizedPreviewUrl(
  url: string,
  params: PreviewUrlParams,
  base: string = window.location.href
): string {
  const tokenized = new URL(url, base);

  tokenized.searchParams.set(params.previewParam, params.paramValue);

  if (params.siteToken) {
    tokenized.searchParams.set(params.siteTokenParam, params.siteToken);
  }

  tokenized.searchParams.set(params.tokenParam, params.token);

  return tokenized.toString();
}

/** Whether a frame showing `url` can be reached into, to read its scroll. */
export function isSameOrigin(
  url: string,
  origin: string = window.location.origin
): boolean {
  try {
    return new URL(url, origin).origin === origin;
  } catch {
    return false;
  }
}
