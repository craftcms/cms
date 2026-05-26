let existingCss: string[] | null = null;
let existingJs: string[] | null = null;

async function appendHtml(html: string, parent: HTMLElement): Promise<void> {
  if (!html) {
    return;
  }

  const div = document.createElement('div');
  div.innerHTML = html.trim();
  const nodes = Array.from(div.childNodes);

  for (const node of nodes) {
    if (node instanceof HTMLLinkElement && node.href) {
      if (!existingCss) {
        existingCss = Array.from(document.querySelectorAll('link[href]')).map(
          (n) => (n as HTMLLinkElement).href.replace(/&/g, '&amp;')
        );
      }

      const href = node.href.replace(/&/g, '&amp;');
      if (existingCss.includes(href)) {
        continue;
      }

      existingCss.push(href);
      const link = document.createElement('link');
      Array.from(node.attributes).forEach((attr) => {
        link.setAttribute(attr.name, attr.value);
      });
      parent.appendChild(link);
      continue;
    }

    if (node instanceof HTMLScriptElement) {
      const script = document.createElement('script');
      Array.from(node.attributes).forEach((attr) => {
        script.setAttribute(attr.name, attr.value);
      });

      if (node.src) {
        if (!existingJs) {
          existingJs = Array.from(document.querySelectorAll('script[src]')).map(
            (n) => (n as HTMLScriptElement).src.replace(/&/g, '&amp;')
          );
        }

        const src = node.src.replace(/&/g, '&amp;');
        if (existingJs.includes(src)) {
          continue;
        }

        existingJs.push(src);
        script.async = false;
      } else {
        script.textContent = node.textContent;
      }

      parent.appendChild(script);
      continue;
    }

    parent.appendChild(node.cloneNode(true));
  }
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
