import {afterEach, describe, expect, it} from 'vite-plus/test';
import {
  isExternalUrl,
  observeExternalLinks,
  openExternalLinksInNewTabs,
} from './external-links';
import {setTranslations} from './translate';

const here = `${location.protocol}//${location.host}`;

function render(html: string): HTMLAnchorElement {
  document.body.innerHTML = html;
  return document.querySelector('a')!;
}

/** Waits for a MutationObserver's callback. */
function observed(): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve));
}

afterEach(() => {
  document.body.innerHTML = '';
  setTranslations({});
});

describe('isExternalUrl', () => {
  it.each([
    ['another host', 'https://craftcms.com/docs', true],
    ['another host without a scheme', '//console.craftcms.com/', true],
    [
      'a subdomain of this host',
      `${location.protocol}//www.${location.hostname}/`,
      true,
    ],
    ['this host', `${here}/admin/entries`, false],
    [
      'this host on another port',
      `${location.protocol}//${location.hostname}:4321/`,
      false,
    ],
    ['a path', '/admin/entries', false],
    ['a fragment', '#content', false],
    ['an email address', 'mailto:support@craftcms.com', false],
    ['a phone number', 'tel:+15555550100', false],
    ['nothing', '', false],
  ])('%s', (_case, url, expected) => {
    expect(isExternalUrl(url)).toBe(expected);
  });
});

describe('openExternalLinksInNewTabs', () => {
  it('opens a link to another host in a new tab and says so', () => {
    setTranslations({
      app: {'Opens in a new window': 'Öffnet in neuem Fenster'},
    });
    const link = render('<a href="https://craftcms.com/">Craft CMS</a>');

    openExternalLinksInNewTabs(document.body);

    expect(link.getAttribute('target')).toBe('_blank');
    expect(link.getAttribute('rel')).toBe('noopener');
    expect(link.textContent).toBe('Craft CMSÖffnet in neuem Fenster');
    expect(link.querySelector('.sr-only')!.textContent).toBe(
      'Öffnet in neuem Fenster'
    );
  });

  it('keeps the rest of a link’s rel', () => {
    const link = render(
      '<a href="https://craftcms.com/" rel="nofollow">Craft CMS</a>'
    );

    openExternalLinksInNewTabs(document.body);

    expect(link.getAttribute('rel')).toBe('nofollow noopener');
  });

  it.each([
    ['a link to this host', `<a href="${here}/admin">Dashboard</a>`],
    ['a path', '<a href="/admin">Dashboard</a>'],
    ['an email address', '<a href="mailto:a@example.com">Email</a>'],
    [
      'a link with a target of its own',
      '<a href="https://craftcms.com/" target="_self">Craft CMS</a>',
    ],
    [
      'a link in editable content',
      '<div contenteditable="true"><p><a href="https://craftcms.com/">Craft CMS</a></p></div>',
    ],
  ])('leaves %s alone', (_case, html) => {
    const link = render(html);
    const before = link.outerHTML;

    openExternalLinksInNewTabs(document.body);

    expect(link.outerHTML).toBe(before);
  });

  it('describes a link named by aria-label, which hides its text', () => {
    const link = render(
      '<a href="https://craftcms.com/" aria-label="Craft CMS" aria-describedby="hint">Go</a><span id="hint">Docs</span>'
    );

    openExternalLinksInNewTabs(document.body);

    const ids = link.getAttribute('aria-describedby')!.split(' ');
    expect(ids[0]).toBe('hint');
    expect(document.getElementById(ids[1])!.textContent).toBe(
      'Opens in a new window'
    );
  });

  it('adds the cue once, however often it runs', () => {
    const link = render('<a href="https://craftcms.com/">Craft CMS</a>');

    openExternalLinksInNewTabs(document.body);
    openExternalLinksInNewTabs(document.body);

    expect(link.querySelectorAll('.sr-only')).toHaveLength(1);
  });
});

describe('observeExternalLinks', () => {
  it('opens links added later in a new tab', async () => {
    const stop = observeExternalLinks(document.body);
    document.body.innerHTML =
      '<div><p>See <a href="https://craftcms.com/">Craft CMS</a></p></div>';
    await observed();
    stop();

    const link = document.querySelector('a')!;
    expect(link.getAttribute('target')).toBe('_blank');
    expect(link.querySelector('.sr-only')).not.toBeNull();
  });

  it('puts the cue back when the link’s text is rewritten', async () => {
    const link = render('<a href="https://craftcms.com/">Craft CMS</a>');
    const stop = observeExternalLinks(document.body);
    await observed();

    link.textContent = 'Craft';
    await observed();
    stop();

    expect(link.textContent).toBe('CraftOpens in a new window');
  });

  it('puts a link back when it no longer leads off-site', async () => {
    const link = render('<a href="https://craftcms.com/">Craft CMS</a>');
    const stop = observeExternalLinks(document.body);
    await observed();

    link.setAttribute('href', '/admin');
    await observed();
    stop();

    expect(link.hasAttribute('target')).toBe(false);
    expect(link.textContent).toBe('Craft CMS');
  });

  it('opens a link in a new tab once it leads off-site', async () => {
    const link = render('<a href="/admin">Craft CMS</a>');
    const stop = observeExternalLinks(document.body);
    await observed();

    link.setAttribute('href', 'https://craftcms.com/');
    await observed();
    stop();

    expect(link.getAttribute('target')).toBe('_blank');
  });
});
