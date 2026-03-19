let existingCss: Array<string> | null = null;
let existingJs: Array<string> | null = null;

let appendQueue: Promise<void> = Promise.resolve();

async function appendHtml(html: string, parent: HTMLElement | null) {
  if (!html || !parent) {
    return;
  }

  // Ensure appendHtml calls are serialized
  return appendQueue = appendQueue.then(async () => {
    try {
      const template = document.createElement('template');
      template.innerHTML = html.trim();

      const nodes = Array.from(template.content.childNodes);

      for (const node of nodes) {
        if (node.nodeName === 'LINK' && (node as HTMLLinkElement).href) {
          if (!existingCss) {
            existingCss = Array.from(document.querySelectorAll('link[href]')).map(
              (n) => (n as HTMLLinkElement).href
            );
          }

          if (existingCss.includes((node as HTMLLinkElement).href)) {
            continue;
          }

          existingCss.push((node as HTMLLinkElement).href);
        }

        if (node.nodeName === 'SCRIPT') {
          const script = node as HTMLScriptElement;

          if (script.src) {
            if (!existingJs) {
              existingJs = Array.from(document.querySelectorAll('script[src]')).map(
                (n) => (n as HTMLScriptElement).src
              );
            }

            if (existingJs.includes(script.src)) {
              continue;
            }
          }

          // Re-create the script element to ensure it executes
          const newScript = document.createElement('script');

          // Explicitly set async to false, because scripts created via createElement are async by default
          newScript.async = false;

          for (const attr of Array.from(script.attributes)) {
            newScript.setAttribute(attr.name, attr.value);
          }

          newScript.textContent = script.textContent;

          if (newScript.src) {
            existingJs?.push(newScript.src);

            if (newScript.async || newScript.defer) {
              parent.append(newScript);
            } else {
              await new Promise((resolve) => {
                newScript.onload = resolve;
                newScript.onerror = resolve;
                parent.append(newScript);
              });
            }
          } else {
            parent.append(newScript);
          }
          continue;
        }

        parent.append(node);
      }
    } catch (e) {
      console.error('Error appending HTML:', e);
    }
  });
}
/**
 * Appends HTML to the page `<head>`.
 */
export async function appendHeadHtml(html: string): Promise<void> {
  await appendHtml(html, document.head);
}

/**
 * Appends HTML to the page `<body>`.
 */
export async function appendBodyHtml(html: string): Promise<void> {
  await appendHtml(html, document.body);
}
