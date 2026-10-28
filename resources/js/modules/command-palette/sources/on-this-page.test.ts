import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {CommandPaletteItem} from '../types';
import {onThisPageProvider} from './on-this-page';

/**
 * An entry's Content and Related tabs, with a CTA Caption block in Content
 * and a field on Related, marked up as the control panel renders them.
 */
async function renderPage(): Promise<void> {
  document.body.innerHTML = `
    <main>
      <craft-tabs>
        <craft-tab id="tab-content">Content</craft-tab>
        <craft-tab id="tab-related">Related</craft-tab>
      </craft-tabs>
      <section role="tabpanel" aria-label="Content" aria-labelledby="tab-content">
        <craft-field id="content-blocks">
        <div data-matrix-block data-ui-label="Exhibition Location" data-type-name="CTA Caption" data-color="orange" tabindex="0">
          <section role="tabpanel" aria-label="Content" aria-labelledby="tab-cta-content">
            <craft-field id="cta-link" data-icon="link">
              <div slot="actions"><craft-copy-attribute id="cta-link-handle"></craft-copy-attribute></div>
              <input slot="input" />
            </craft-field>
          </section>
          <craft-tabs>
            <craft-tab id="tab-cta-content">Content</craft-tab>
            <craft-tab id="tab-cta-appearance">Appearance</craft-tab>
          </craft-tabs>
        </div>
        </craft-field>
      </section>
      <craft-field id="programme">
        <table class="editable">
          <tr><td><craft-field id="programme-date"><input slot="input" /></craft-field></td></tr>
        </table>
      </craft-field>
      <craft-field id="search-terms"><input slot="input" /></craft-field>
      <section role="tabpanel" aria-label="Related" aria-labelledby="tab-related" hidden>
        <craft-field id="related-works">
          <input slot="input" />
        </craft-field>
      </section>
    </main>
  `;

  // Set as properties, the way Vue renders them, and labelled by the fields
  // themselves.
  Object.assign(document.getElementById('cta-link')!, {
    label: 'CTA Link',
    required: true,
  });
  // What the field's handle chip holds, when field handles are shown.
  Object.assign(document.getElementById('cta-link-handle')!, {
    value: 'ctaLink',
  });
  Object.assign(document.getElementById('content-blocks')!, {
    label: 'Content Blocks',
  });
  Object.assign(document.getElementById('related-works')!, {
    label: 'Related Works',
  });
  Object.assign(document.getElementById('programme')!, {label: 'Programme'});
  Object.assign(document.getElementById('programme-date')!, {
    label: 'Programme date, row 1',
  });
  Object.assign(document.getElementById('search-terms')!, {
    label: 'Programme search terms',
    labelSrOnly: true,
  });

  await Promise.all(
    [
      ...document.querySelectorAll<
        HTMLElement & {updateComplete?: Promise<unknown>}
      >('craft-field'),
    ].map((field) => field.updateComplete ?? Promise.resolve())
  );
}

async function fieldResults(query: string): Promise<CommandPaletteItem[]> {
  const results = await onThisPageProvider.search!(query);

  return results;
}

describe('onThisPageProvider', () => {
  afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('finds fields by their labels, described by the tabs, fields and nested entries they’re in, with their field types’ icons', async () => {
    await renderPage();

    expect(
      (await fieldResults('cta link')).map(({label, description, icon}) => ({
        label,
        description,
        icon,
      }))
    ).toEqual([
      {
        label: 'CTA Link',
        description: 'Content →\u00A0Content Blocks →\u00A0Exhibition Location',
        icon: 'link',
      },
    ]);
  });

  it('hints at a field’s handle when the form shows it, and finds the field by it', async () => {
    await renderPage();

    expect(
      (await fieldResults('ctaLink')).map(({label, hint}) => ({label, hint}))
    ).toEqual([{label: 'CTA Link', hint: 'ctaLink'}]);
  });

  it('finds nested entries by their UI labels, with their entry types as hints and colors', async () => {
    await renderPage();

    const results = (
      await onThisPageProvider.search!('exhibition location')
    ).map(({label, hint, description, iconColor}) => ({
      label,
      hint,
      description,
      iconColor,
    }));

    expect(results).toEqual([
      {
        label: 'Exhibition Location',
        hint: 'CTA Caption',
        description: 'Content →\u00A0Content Blocks',
        iconColor: 'orange',
      },
    ]);
  });

  it('lists the secondary nav’s other pages as links, described by the items they’re under', async () => {
    document.body.innerHTML = `
      <main>
        <nav id="nav-container" aria-label="Secondary">
          <craft-nav-item id="profile">Profile</craft-nav-item>
          <craft-nav-item id="account-type">
            Account Type
            <craft-nav-list>
              <craft-nav-item id="admins">Admins</craft-nav-item>
            </craft-nav-list>
          </craft-nav-item>
        </nav>
      </main>
    `;
    Object.assign(document.getElementById('profile')!, {
      href: '/admin/users/1',
      current: true,
    });
    Object.assign(document.getElementById('account-type')!, {
      href: '/admin/users',
      icon: 'user-group',
    });
    Object.assign(document.getElementById('admins')!, {
      href: '/admin/users?source=admins',
    });

    const results = (await onThisPageProvider.search!('admins')).map(
      ({label, description, icon, href}) => ({label, description, icon, href})
    );

    expect(results).toEqual([
      {
        label: 'Admins',
        description: 'Secondary navigation →\u00A0Account Type',
        icon: 'user-group',
        href: '/admin/users?source=admins',
      },
    ]);
    expect(
      (await onThisPageProvider.search!('profile')).filter(({description}) =>
        description?.startsWith('Secondary navigation')
      )
    ).toEqual([]);
  });

  it('leaves out an editable table’s cells, and fields whose labels are hidden', async () => {
    await renderPage();

    expect((await fieldResults('programme')).map(({label}) => label)).toEqual([
      'Programme',
    ]);
  });

  it('finds fields in closed tabs, opening the tab before focusing the field', async () => {
    await renderPage();
    const tab = document.getElementById('tab-related')!;
    tab.addEventListener('click', () => {
      document.querySelector<HTMLElement>('[aria-label="Related"]')!.hidden =
        false;
    });
    Element.prototype.scrollIntoView = vi.fn();
    vi.spyOn(window, 'requestAnimationFrame').mockImplementation((callback) => {
      callback(0);

      return 0;
    });

    const [result] = await fieldResults('related works');
    expect(result?.description).toBe('Related');

    await result!.run!();

    expect(document.activeElement).toBe(
      document.querySelector('#related-works input')
    );
  });
});
