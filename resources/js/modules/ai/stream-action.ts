import {actionHeaders, csrf, getActionUrl} from '@craftcms/ui';

/** An event from a streamed action response. */
export interface StreamedActionEvent {
  type: string;
  [key: string]: unknown;
}

interface Options {
  signal?: AbortSignal;
  /** Called with each event as it arrives. An error it throws ends the request. */
  onEvent: (event: StreamedActionEvent) => void;
}

/**
 * Posts to an action that streams newline-delimited JSON events, calling
 * `onEvent` with each one as it arrives.
 *
 * Sends the same headers as `actionClient`, and retries once with a fresh CSRF
 * token. A response that fails before streaming, such as a validation error,
 * rejects with `{response: {status, data}}`, as `actionClient` errors do, and
 * so does an `error` event, with its `status`, once streaming has started.
 */
export async function streamAction(
  url: string,
  data: unknown,
  {signal, onEvent}: Options,
  retry: {csrfToken: string | null} | null = null
): Promise<void> {
  const csrfToken = retry?.csrfToken;

  const response = await fetch(getActionUrl(url), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      // Laravel only reads the first type: JSON is what makes it return
      // validation and other errors as JSON, rather than redirecting.
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...actionHeaders(),
      ...(csrfToken ? {'X-CSRF-Token': csrfToken} : {}),
    },
    body: JSON.stringify(data),
    signal,
  });

  if (!response.ok) {
    if ((response.status === 419 || response.status === 403) && !retry) {
      csrf.clearToken();

      return streamAction(
        url,
        data,
        {signal, onEvent},
        {csrfToken: await csrf.refreshToken()}
      );
    }

    const text = await response.text();
    let body: unknown = text;

    try {
      body = JSON.parse(text);
    } catch {
      // Not JSON, so the text is the error.
    }

    throw {response: {status: response.status, data: body}};
  }

  const reader = response
    .body!.pipeThrough(new TextDecoderStream())
    .getReader();
  let buffer = '';

  try {
    for (;;) {
      const {done, value} = await reader.read();

      if (value) {
        buffer += value;
      }

      const lines = buffer.split('\n');
      buffer = done ? '' : lines.pop()!;

      for (const line of lines) {
        if (!line.trim()) {
          continue;
        }

        const event = JSON.parse(line) as StreamedActionEvent;

        if (event.type === 'error') {
          throw {response: {status: event.status, data: event}};
        }

        onEvent(event);
      }

      if (done) {
        return;
      }
    }
  } catch (error) {
    // Closes the connection, so the server stops generating a reply nobody reads.
    reader.cancel().catch(() => {});

    throw error;
  }
}
