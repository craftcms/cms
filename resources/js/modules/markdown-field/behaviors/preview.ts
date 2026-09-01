import {t} from '@craftcms/ui';
import {useHttp} from '@inertiajs/vue3';
import type {OverType as OverTypeInstance} from 'overtype';
import {markdown as renderMarkdown} from '@actions/App/RenderController';

type PreviewRequest = {
  encode: boolean;
  flavor: string;
  htmlSanitizer: string | null;
  inlineOnly: boolean;
  markdown: string;
  sanitizeHtml: boolean;
};

type PreviewResponse = {
  html: string;
};

export type PreviewController = {
  destroy: () => void;
  isActive: () => boolean;
  render: (markdown: string) => Promise<void>;
  toggle: () => Promise<void>;
};

export function createPreviewController(
  editor: OverTypeInstance,
  flavor: string,
  encode: boolean,
  inlineOnly: boolean,
  sanitizeHtml: boolean,
  htmlSanitizer: string | null,
  previewDelay: number
): PreviewController {
  let active = false;
  let requestId = 0;
  let timeout: number | null = null;
  const previewRequest = useHttp<PreviewRequest, PreviewResponse>({
    encode,
    flavor,
    htmlSanitizer,
    inlineOnly,
    markdown: '',
    sanitizeHtml,
  });

  function updateButton(): void {
    const button = editor.container.querySelector<HTMLButtonElement>(
      '[data-button="preview"]'
    );

    if (!button) {
      return;
    }

    button.classList.toggle('active', active);
    button.setAttribute('aria-pressed', active.toString());
  }

  function updatePreviewInteractivity(): void {
    editor.preview.toggleAttribute('inert', !active);

    if (active) {
      editor.preview.removeAttribute('aria-hidden');

      return;
    }

    editor.preview.setAttribute('aria-hidden', 'true');
  }

  async function render(markdown: string): Promise<void> {
    const currentRequestId = ++requestId;

    if (timeout) {
      window.clearTimeout(timeout);
    }

    timeout = window.setTimeout(async () => {
      try {
        previewRequest.encode = encode;
        previewRequest.flavor = flavor;
        previewRequest.htmlSanitizer = htmlSanitizer;
        previewRequest.inlineOnly = inlineOnly;
        previewRequest.markdown = markdown;
        previewRequest.sanitizeHtml = sanitizeHtml;

        const data = await previewRequest.post(renderMarkdown().url);

        if (active && currentRequestId === requestId) {
          editor.preview.innerHTML = data.html;
        }
      } catch {
        if (active && currentRequestId === requestId) {
          editor.preview.textContent = t('Couldn’t render Markdown preview.');
        }
      }
    }, previewDelay);
  }

  async function toggle(): Promise<void> {
    active = !active;
    updateButton();
    updatePreviewInteractivity();

    if (!active) {
      editor.showNormalEditMode();
      editor.focus();

      return;
    }

    editor.showPreviewMode();
    await render(editor.getValue());
  }

  updatePreviewInteractivity();

  return {
    destroy() {
      if (timeout) {
        window.clearTimeout(timeout);
        timeout = null;
      }
    },
    isActive: () => active,
    render,
    toggle,
  };
}
