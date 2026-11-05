// The `iframe-resizer` package ships no types. Declare the one call we use.
declare module 'iframe-resizer' {
  export function iframeResize(
    options: Record<string, unknown>,
    target: HTMLIFrameElement
  ): unknown;
}
