import {
  type MaybeRefOrGetter,
  onMounted,
  onUnmounted,
  toValue,
  watch,
} from 'vue';

/**
 * Injects bridged HTML assets from Yii2's View into the DOM and executes any scripts.
 *
 * Scripts added via innerHTML don't execute automatically, so this composable
 * recreates script elements to ensure they run.
 *
 * Injected elements are automatically removed when the component unmounts or
 * when navigating to a page without bridged assets.
 *
 * @param headHtml - HTML to inject into <head>
 * @param bodyHtml - HTML to inject into <body>
 */
export function useBridgedAssets(
  headHtml: MaybeRefOrGetter<string | undefined>,
  bodyHtml: MaybeRefOrGetter<string | undefined>
) {
  // Track injected elements for cleanup
  const injectedElements: Element[] = [];
  let lastHeadHtml: string | undefined;
  let lastBodyHtml: string | undefined;

  const cleanup = () => {
    injectedElements.forEach((el) => el.remove());
    injectedElements.length = 0;
  };

  const inject = () => {
    const head = toValue(headHtml);
    const body = toValue(bodyHtml);

    // Check if content has changed
    const headChanged = head !== lastHeadHtml;
    const bodyChanged = body !== lastBodyHtml;

    if (!headChanged && !bodyChanged) {
      return;
    }

    // Clean up previous injections before adding new ones
    cleanup();

    // Update tracking
    lastHeadHtml = head;
    lastBodyHtml = body;

    // Inject new content
    if (head) {
      injectHtml(document.head, head, injectedElements);
    }
    if (body) {
      injectHtml(document.body, body, injectedElements);
    }
  };

  // Inject on mount for initial page load
  onMounted(inject);

  // Watch for changes (Inertia navigation)
  watch([() => toValue(headHtml), () => toValue(bodyHtml)], inject);

  // Clean up when component unmounts
  onUnmounted(cleanup);
}

/**
 * Injects HTML into a target element and executes any script tags.
 * Tracks injected elements in the provided array for later cleanup.
 */
function injectHtml(
  target: HTMLElement,
  html: string,
  tracker: Element[]
): void {
  const template = document.createElement('template');
  template.innerHTML = html;

  // Scripts added via innerHTML don't execute - we need to recreate them
  template.content.querySelectorAll('script').forEach((oldScript) => {
    const newScript = document.createElement('script');

    // Copy all attributes (type, src, async, defer, etc.)
    Array.from(oldScript.attributes).forEach((attr) => {
      newScript.setAttribute(attr.name, attr.value);
    });

    // Copy inline script content
    if (oldScript.textContent) {
      newScript.textContent = oldScript.textContent;
    }

    oldScript.replaceWith(newScript);
  });

  // Track all top-level elements being injected
  Array.from(template.content.children).forEach((child) => {
    tracker.push(child);
  });

  target.append(template.content);
}
