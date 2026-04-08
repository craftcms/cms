import {describe, test, expect, beforeEach, vi} from 'vitest';

// Prevent happy-dom from making real network requests for CSS/JS files
const happyDOM = (window as any).happyDOM;
if (happyDOM?.settings) {
  happyDOM.settings.disableCSSFileLoading = true;
  happyDOM.settings.disableJavaScriptFileLoading = true;
}

// Helper to get a fresh module instance (resets cached existingCss/existingJs)
async function freshImport() {
  vi.resetModules();
  return await import('./dom.js');
}

describe('appendHeadHtml', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  test('does nothing for empty string', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml('');
    expect(document.head.children.length).toBe(0);
  });

  test('appends a link element to head', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml(
      '<link rel="stylesheet" href="https://example.com/style.css">'
    );
    const links = document.head.querySelectorAll('link');
    expect(links.length).toBe(1);
    expect(links[0].getAttribute('rel')).toBe('stylesheet');
    expect(links[0].getAttribute('href')).toBe(
      'https://example.com/style.css'
    );
  });

  test('appends a script element to head', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml(
      '<script src="https://example.com/script.js"></script>'
    );
    const scripts = document.head.querySelectorAll('script');
    expect(scripts.length).toBe(1);
    expect(scripts[0].getAttribute('src')).toBe(
      'https://example.com/script.js'
    );
  });

  test('appends an inline script to head', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml('<script>console.log("hello")</script>');
    const scripts = document.head.querySelectorAll('script');
    expect(scripts.length).toBe(1);
    expect(scripts[0].textContent).toBe('console.log("hello")');
  });

  test('appends arbitrary HTML to head', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml('<meta name="description" content="test">');
    const metas = document.head.querySelectorAll('meta[name="description"]');
    expect(metas.length).toBe(1);
    expect(metas[0].getAttribute('content')).toBe('test');
  });

  test('preserves link attributes when appending', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml(
      '<link rel="stylesheet" href="https://example.com/a.css" media="print" crossorigin="anonymous">'
    );
    const link = document.head.querySelector('link')!;
    expect(link.getAttribute('rel')).toBe('stylesheet');
    expect(link.getAttribute('href')).toBe('https://example.com/a.css');
    expect(link.getAttribute('media')).toBe('print');
    expect(link.getAttribute('crossorigin')).toBe('anonymous');
  });

  test('preserves script attributes when appending', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml(
      '<script src="https://example.com/b.js" type="module" defer></script>'
    );
    const script = document.head.querySelector('script')!;
    expect(script.getAttribute('src')).toBe('https://example.com/b.js');
    expect(script.getAttribute('type')).toBe('module');
    expect(script.hasAttribute('defer')).toBe(true);
  });
});

describe('appendBodyHtml', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  test('appends elements to body', async () => {
    const {appendBodyHtml} = await freshImport();
    await appendBodyHtml('<div id="test-div">Hello</div>');
    const div = document.body.querySelector('#test-div');
    expect(div).not.toBeNull();
    expect(div!.textContent).toBe('Hello');
  });

  test('appends script with src to body', async () => {
    const {appendBodyHtml} = await freshImport();
    await appendBodyHtml(
      '<script src="https://example.com/body.js"></script>'
    );
    const scripts = document.body.querySelectorAll('script');
    expect(scripts.length).toBe(1);
    expect(scripts[0].getAttribute('src')).toBe(
      'https://example.com/body.js'
    );
  });
});

describe('CSS deduplication', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  test('does not add duplicate CSS links', async () => {
    const {appendHeadHtml} = await freshImport();
    const css = '<link rel="stylesheet" href="https://example.com/dup.css">';
    await appendHeadHtml(css);
    await appendHeadHtml(css);
    const links = document.head.querySelectorAll('link');
    expect(links.length).toBe(1);
  });

  test('adds different CSS links', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml(
      '<link rel="stylesheet" href="https://example.com/one.css">'
    );
    await appendHeadHtml(
      '<link rel="stylesheet" href="https://example.com/two.css">'
    );
    const links = document.head.querySelectorAll('link');
    expect(links.length).toBe(2);
  });
});

describe('JS deduplication', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  test('does not add duplicate script src', async () => {
    const {appendBodyHtml} = await freshImport();
    const js = '<script src="https://example.com/dup.js"></script>';
    await appendBodyHtml(js);
    await appendBodyHtml(js);
    const scripts = document.body.querySelectorAll('script');
    expect(scripts.length).toBe(1);
  });

  test('inline scripts are always added (no deduplication)', async () => {
    const {appendBodyHtml} = await freshImport();
    const inline = '<script>console.log("run")</script>';
    await appendBodyHtml(inline);
    await appendBodyHtml(inline);
    const scripts = document.body.querySelectorAll('script');
    expect(scripts.length).toBe(2);
  });
});
