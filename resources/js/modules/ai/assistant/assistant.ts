import {actionClient, getActionUrl, isCancel, isHttpError} from '@craftcms/ui';
import {fetchServerSentEvents, type ConnectionAdapter} from '@tanstack/ai-vue';
import {streamingMarkdownExtension} from '@tanstack/markdown/extensions/streaming';
import {renderHtml} from '@tanstack/markdown/html';
import type {InlineNode, MarkdownExtension} from '@tanstack/markdown';
import {createGlobalState} from '@vueuse/core';
import {usePage} from '@inertiajs/vue3';
import {computed} from 'vue';
import {screenPanels} from '@/common/composables/screenPanels';
import type {CraftData} from '@/common/composables/useCraftData';
import {useSessionStorage} from '@/common/composables/useStorage';
import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';

/**
 * Whether an image is a file on the control panel’s own origin. Other paths
 * there could be actions, such as signing out, which an image would request.
 */
function isOwnImageFile(src: string): boolean {
  const url = URL.canParse(src, window.location.href)
    ? new URL(src, window.location.href)
    : null;

  return (
    url?.origin === window.location.origin &&
    /\.(avif|gif|jpe?g|png|svg|webp)$/i.test(url.pathname)
  );
}

/**
 * Renders images as links to them, except those in `inlineImages` and image
 * files on the control panel’s own origin. An image
 * loads as soon as the reply shows, so a reply steered by injected content
 * could send data to any server in an image’s URL, without a click.
 */
function imagesAsLinks(inlineImages: ReadonlySet<string>): MarkdownExtension {
  return {
    name: 'craft-images-as-links',
    renderHtml: (node, {renderInline}) => {
      if (
        node.type !== 'image' ||
        (node.alt && (inlineImages.has(node.src) || isOwnImageFile(node.src)))
      ) {
        return undefined;
      }

      const label: InlineNode = {type: 'text', value: node.alt || node.src};

      // The parser empties URLs it won’t link to, such as `javascript:` ones.
      return node.src
        ? renderInline({type: 'link', href: node.src, children: [label]})
        : renderInline(label);
    },
  };
}

/**
 * Renders a reply’s Markdown as HTML, as it streams in: incomplete blocks at
 * the end are left out until they’re written. Raw HTML is escaped, links
 * with executable URLs are dropped, and images are links, except those with
 * alt text that are in `inlineImages` or files on the control panel’s origin.
 */
export function renderMarkdown(
  markdown: string,
  inlineImages: ReadonlySet<string> = new Set()
): string {
  return renderHtml(markdown, {
    extensions: [streamingMarkdownExtension(), imagesAsLinks(inlineImages)],
    frontmatter: false,
    headingIds: false,
  });
}

/** The id of the AI assistant’s panel among the shell’s details panels. */
export const ASSISTANT_PANEL_ID = 'ai-assistant';

/** The id of the header button that opens the AI assistant. */
export const ASSISTANT_TOGGLE_ID = 'cp-assistant-toggle';

/** An error response from the assistant’s endpoint, with the message the server gave. */
export class AssistantRequestError extends Error {}

/**
 * The AI assistant’s panel, one of the shell’s details panels. It’s there
 * while the chat is open, and beside a page’s own panels. The chat stays open
 * from page to page, and after a full reload, for the browser tab. One state
 * for the whole control panel, since the toggle and the panel are rendered
 * apart.
 */
export const useAssistantPanel = createGlobalState(() => {
  /** Whether it was opened in this browser tab, and hasn’t been closed. */
  const opened = useSessionStorage('aiAssistant.opened', false);
  const isOpen = computed(
    () => screenPanels.openPanelId.value === ASSISTANT_PANEL_ID
  );

  function show(): void {
    opened.value = true;
    screenPanels.open(ASSISTANT_PANEL_ID);
  }

  return {
    opened,
    isOpen,
    /** Opens the panel, adding it if needed, and moves focus to its message box. */
    show,
    /** Opens the panel, or folds it while keeping focus where it is. */
    toggle(): void {
      if (isOpen.value) {
        screenPanels.close(ASSISTANT_PANEL_ID, {returnFocus: false});
      } else {
        show();
      }
    },
  };
});

/** The current conversation’s id, kept for the browser tab, for each user. */
export const useAssistantThread = createGlobalState(() => {
  const userId =
    usePage<{craft?: CraftData}>().props.craft?.currentUser?.id ?? 0;

  return useSessionStorage(`aiAssistant.thread.${userId}`, crypto.randomUUID());
});

/**
 * An error from a request to one of the assistant’s actions, with the
 * server’s message, if it gave one.
 */
export function requestError(error: unknown): unknown {
  if (!isHttpError<{message?: string} | string>(error) || isCancel(error)) {
    return error;
  }

  const data = error.response?.data;

  return new AssistantRequestError(
    (typeof data === 'object' && data?.message) || error.message,
    {cause: error}
  );
}

/**
 * Sends the chat’s requests with Craft’s action client, as `fetch` would, so
 * the reply can be read as it streams. A failed response throws an
 * {@link AssistantRequestError} with the server’s message.
 */
export async function streamFetch(
  input: RequestInfo | URL,
  init: RequestInit = {}
): Promise<Response> {
  try {
    const response = await actionClient.request<ReadableStream | null>({
      url: input instanceof Request ? input.url : String(input),
      method: init.method ?? 'GET',
      headers: Object.fromEntries(new Headers(init.headers)),
      data: init.body ?? undefined,
      signal: init.signal ?? undefined,
      responseType: 'stream',
    });

    return new Response(response.data, {
      status: response.status,
      statusText: response.statusText,
      headers: response.headers,
    });
  } catch (error) {
    throw requestError(error);
  }
}

/**
 * The connection to the assistant’s endpoint, which streams AG-UI events.
 *
 * @param body Sent with every request, including the user’s answers to
 *   approval requests, so a turn they resume has what the message had, such
 *   as its context.
 */
export function assistantConnection(
  body: () => Record<string, unknown>
): ConnectionAdapter {
  return fetchServerSentEvents(
    () => getActionUrl(AssistantController.run.url()),
    () => ({fetchClient: streamFetch, body: body()})
  );
}
