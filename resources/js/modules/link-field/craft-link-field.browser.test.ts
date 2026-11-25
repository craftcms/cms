import {actionClient} from '@craftcms/ui';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/input/input';
import '@craftcms/ui/components/select/select';
import '@craftcms/ui/components/switch/switch';
import {router} from '@inertiajs/vue3';
import {effectScope} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {broadcast} from '@/common/craft/broadcast';
import {useElementPresentations} from '@/modules/elements/element-presentations';
import './craft-link-field';
import type {
  LinkChangeDetail,
  LinkFieldValue,
  LinkTypeConfig,
} from './craft-link-field';

const selector = vi.hoisted(() => {
  const listeners = new Map<string, () => void>();

  return {
    listeners,
    createElementSelectorModal: vi.fn(async () => ({
      on: (event: string, listener: () => void) => {
        listeners.set(event, listener);
      },
      destroy: () => {},
    })),
  };
});

type SelectorSettings = {
  triggerElement: () => HTMLElement;
  onHandoff: (settled: Promise<void>) => void;
  onSelect: (elements: Array<Record<string, unknown>>) => void;
};

vi.mock(
  '@/modules/element-selector-modal/create-element-selector-modal',
  () => selector
);

const slideouts = vi.hoisted(() => ({
  openSlideout: vi.fn(async () => ({id: 'slideout-1'})),
}));

vi.mock('@/common/slideouts', () => slideouts);

const imageEditor = vi.hoisted(() => ({
  openImageEditorDialog: vi.fn(async () => ({closed: Promise.resolve()})),
}));

vi.mock('@/modules/image-editor/open-image-editor-dialog', () => imageEditor);

/**
 * The link type and its destination render as one grouped input, like a
 * grouped UI field (“Field Limit”). Runs in Chromium so names, descriptions
 * and layout are the browser's own.
 */

const types: LinkTypeConfig[] = [
  {
    id: 'url',
    label: 'URL',
    kind: 'text',
    prefixes: ['https://', 'http://'],
    pattern: '^https?://',
  },
  {id: 'entry', label: 'Entry', kind: 'element', elementType: 'Entry'},
];

let linkField: HTMLElementTagNameMap['craft-link-field'];

beforeEach(async () => {
  linkField = document.createElement('craft-link-field');
  linkField.types = types;
  document.body.append(linkField);
  await linkField.updateComplete;
});

afterEach(() => {
  linkField.remove();
});

it('sets the link type beside its destination, each with its own label', async () => {
  const type = page.getByRole('combobox', {name: 'Link Type'});
  const url = page.getByRole('textbox', {name: 'URL'});

  await expect.element(type).toBeVisible();
  await expect.element(url).toBeVisible();

  const typeBox = type.element().getBoundingClientRect();
  const urlBox = url.element().getBoundingClientRect();
  expect(urlBox.top).toBe(typeBox.top);
  expect(urlBox.left).toBeGreaterThanOrEqual(typeBox.right);
});

it('labels the element chooser with its link type', async () => {
  await chooseType('entry');

  await expect
    .element(
      page
        .getByRole('group', {name: 'Entry'})
        .getByRole('button', {name: 'Choose'})
    )
    .toBeVisible();
});

it('shows the linked element as a chip with its status', async () => {
  linkField.modelValue = {
    type: 'entry',
    value: '{entry:12@1:url}',
    defaultLabel: 'pending',
    element: {
      id: 12,
      label: 'pending',
      siteId: 1,
      status: {fill: 'blue', label: 'Pending', draft: false},
    },
  };
  await linkField.updateComplete;

  const chip = linkField.querySelector('craft-chip');
  expect(chip).not.toBeNull();
  await expect.element(chip!).toHaveTextContent('pending');
  await expect
    .element(page.getByRole('img', {name: 'Status: Pending'}))
    .toBeVisible();
});

it('keeps a missing element’s link, showing the Choose button, as the server treats it as empty', async () => {
  const applied = vi.fn();
  linkField.addEventListener('apply', (event) =>
    applied((event as CustomEvent<LinkFieldValue>).detail)
  );
  linkField.modelValue = {
    type: 'entry',
    value: '{entry:12@1:url}',
    defaultLabel: '',
  };
  await linkField.updateComplete;

  await expect
    .element(page.getByRole('button', {name: 'Choose'}))
    .toBeVisible();
  expect(linkField.querySelector('craft-chip')).toBeNull();
  expect(linkField.textContent).not.toContain('{entry:12@1:url}');

  await page.getByRole('button', {name: 'Apply'}).click();

  expect(applied).toHaveBeenCalledWith(
    expect.objectContaining({type: 'entry', value: '{entry:12@1:url}'})
  );
});

describe('a linked element', () => {
  beforeEach(async () => {
    selector.createElementSelectorModal.mockClear();
    slideouts.openSlideout.mockClear();
    linkField.types = [
      types[0]!,
      {
        id: 'entry',
        label: 'Entry',
        kind: 'element',
        elementType: 'Entry',
        refHandle: 'entry',
      },
    ];
    linkField.modelValue = {
      type: 'entry',
      value: '{entry:12@1:url}',
      defaultLabel: 'pending',
      element: {
        id: 12,
        label: 'pending',
        siteId: 1,
        url: 'https://example.com/pending',
        cpEditUrl: '/admin/entries/pending',
        canEdit: true,
        canCopy: true,
      },
    };
    await linkField.updateComplete;
  });

  async function openMenu(): Promise<void> {
    await page.getByRole('button', {name: 'Actions'}).click();
    await expect
      .element(page.getByRole('button', {name: 'Remove'}))
      .toBeVisible();
  }

  it('takes the place of the Choose button', async () => {
    await expect
      .element(page.getByRole('button', {name: 'Choose'}))
      .not.toBeInTheDocument();
    await expect
      .element(page.getByRole('button', {name: 'Change'}))
      .not.toBeInTheDocument();
  });

  it('offers to view, edit, copy, replace and remove it', async () => {
    await openMenu();

    for (const name of [
      'View in a new tab',
      'Copy Entry',
      'Edit Entry',
      'Replace',
      'Remove',
    ]) {
      await expect.element(page.getByRole('button', {name})).toBeVisible();
    }
  });

  it('only offers its own actions when the field is disabled', async () => {
    linkField.disabled = true;
    await linkField.updateComplete;
    await page.getByRole('button', {name: 'Actions'}).click();

    await expect
      .element(page.getByRole('button', {name: 'View in a new tab'}))
      .toBeVisible();
    await expect
      .element(page.getByRole('button', {name: 'Replace'}))
      .not.toBeInTheDocument();
    await expect
      .element(page.getByRole('button', {name: 'Remove'}))
      .not.toBeInTheDocument();
  });

  it('can’t be edited in a preview', async () => {
    linkField.previewing = true;
    linkField.disabled = true;
    await linkField.updateComplete;

    await userEvent.dblClick(page.getByText('pending'));
    await page.getByRole('button', {name: 'Actions'}).click();

    await expect
      .element(page.getByRole('button', {name: 'View in a new tab'}))
      .toBeVisible();
    await expect
      .element(page.getByRole('button', {name: 'Edit Entry'}))
      .not.toBeInTheDocument();
    expect(slideouts.openSlideout).not.toHaveBeenCalled();
  });

  it('is removed, with focus moving to the Choose button', async () => {
    await openMenu();
    await page.getByRole('button', {name: 'Remove'}).click();

    expect(linkField.querySelector('craft-chip')).toBeNull();
    await expect
      .element(page.getByRole('button', {name: 'Choose'}))
      .toHaveFocus();
  });

  it('opens its editor when double-clicked', async () => {
    await userEvent.dblClick(page.getByText('pending'));

    expect(slideouts.openSlideout).toHaveBeenCalledWith(
      '/admin/entries/pending',
      expect.anything()
    );
  });

  it('leaves a double-click on its action menu to the menu', async () => {
    await userEvent.dblClick(
      page.getByRole('button', {name: 'Actions'}).element()
    );

    expect(slideouts.openSlideout).not.toHaveBeenCalled();
  });

  it('doesn’t open an editor it can’t be edited in', async () => {
    linkField.modelValue = {
      ...linkField.modelValue,
      element: {id: 12, label: 'pending', siteId: 1, canEdit: false},
    };
    await linkField.updateComplete;

    await userEvent.dblClick(page.getByText('pending'));

    expect(slideouts.openSlideout).not.toHaveBeenCalled();
  });

  it('is unlinked by applying it once removed', async () => {
    const applied = vi.fn();
    linkField.addEventListener('apply', (event) =>
      applied((event as CustomEvent<LinkFieldValue>).detail)
    );

    await openMenu();
    await page.getByRole('button', {name: 'Remove'}).click();
    await page.getByRole('button', {name: 'Apply'}).click();

    expect(applied).toHaveBeenCalledWith(
      expect.objectContaining({type: 'entry', value: ''})
    );
    await expect
      .element(page.getByText('Entry cannot be blank.'))
      .not.toBeInTheDocument();
  });

  it('isn’t applied empty when it was chosen and removed in a new link', async () => {
    const applied = vi.fn();
    linkField.addEventListener('apply', applied);
    linkField.modelValue = {type: 'entry', value: ''};
    await linkField.updateComplete;

    await page.getByRole('button', {name: 'Choose'}).click();
    const settings = await selectorSettings();
    settings.onSelect([{id: 12, siteId: 1, label: 'pending'}]);
    await openMenu();
    await page.getByRole('button', {name: 'Remove'}).click();
    await page.getByRole('button', {name: 'Apply'}).click();

    expect(applied).not.toHaveBeenCalled();
    await expect
      .element(page.getByText('Entry cannot be blank.'))
      .toBeVisible();
  });

  describe('saved in its editor', () => {
    async function save(data: Record<string, unknown> = {}): Promise<{
      changes: string[];
      post: ReturnType<typeof vi.spyOn>;
    }> {
      const changes: string[] = [];
      linkField.addEventListener('link-change', (event) =>
        changes.push(
          (event as CustomEvent<LinkChangeDetail>).detail.value.value
        )
      );
      const post = vi
        .spyOn(actionClient, 'post')
        .mockImplementation(() => new Promise(() => {}));
      await userEvent.dblClick(page.getByText('pending'));
      const [, options] = slideouts.openSlideout.mock.calls[0] as unknown as [
        string,
        {onSaved: (result: {data?: Record<string, unknown>}) => void},
      ];
      options.onSaved({data});

      return {changes, post};
    }

    beforeEach(async () => {
      linkField.live = true;
      await linkField.updateComplete;
    });

    afterEach(() => {
      vi.restoreAllMocks();
    });

    it('links to the new asset when it’s saved as one', async () => {
      const {changes, post} = await save({replacedAssetId: 12, newAssetId: 34});

      expect(changes).toEqual(['{entry:34@1:url}']);
      expect(post).toHaveBeenCalledWith(
        'relational-fields/structured-elements',
        expect.objectContaining({elementIds: [34]})
      );
    });

    it('keeps its link when it’s saved without being replaced', async () => {
      const {changes} = await save();

      expect(changes).toEqual([]);
    });

    it('keeps its link when the field is disabled', async () => {
      linkField.disabled = true;
      await linkField.updateComplete;

      const {changes, post} = await save({replacedAssetId: 12, newAssetId: 34});

      expect(changes).toEqual([]);
      expect(post).not.toHaveBeenCalledWith(
        'relational-fields/structured-elements',
        expect.objectContaining({elementIds: [34]})
      );
    });

    it.each([1.5, -34, 'new'])(
      'keeps its link when the new asset’s ID is %s',
      async (newAssetId) => {
        const {changes} = await save({replacedAssetId: 12, newAssetId});

        expect(changes).toEqual([]);
      }
    );

    it('keeps a link that isn’t an element reference', async () => {
      linkField.modelValue = {...linkField.modelValue, value: 'pending'};
      await linkField.updateComplete;

      const {changes} = await save({replacedAssetId: 12, newAssetId: 34});

      expect(changes).toEqual([]);
    });
  });

  it('is redrawn when its element is saved, and applied with its new title', async () => {
    const post = respondWithElement({
      id: 12,
      label: 'live',
      siteId: 1,
      status: {fill: 'green', label: 'Live', draft: false},
    });

    broadcast({event: 'saveElement', id: 12});

    await expect
      .element(page.getByRole('img', {name: 'Status: Live'}))
      .toBeVisible();
    await expect
      .element(linkField.querySelector('craft-chip')!)
      .toHaveTextContent('live');
    expect(post).toHaveBeenCalledWith(
      'relational-fields/structured-elements',
      expect.objectContaining({
        elementType: 'Entry',
        elementIds: [12],
        siteId: 1,
      })
    );
    post.mockRestore();

    const applied = new Promise<LinkFieldValue>((resolve) => {
      linkField.addEventListener(
        'apply',
        (event) => resolve((event as CustomEvent<LinkFieldValue>).detail),
        {once: true}
      );
    });
    await page.getByRole('button', {name: 'Apply'}).click();
    expect((await applied).defaultLabel).toBe('live');
  });

  it('draws a newer chip the server gives it, in every field linking its element', async () => {
    const other = document.createElement('craft-link-field');
    other.types = linkField.types;
    other.modelValue = {...linkField.modelValue};
    document.body.append(other);
    await other.updateComplete;

    try {
      linkField.modelValue = {
        ...linkField.modelValue,
        element: {...linkField.modelValue!.element!, label: 'renamed'},
      };

      await expect
        .element(other.querySelector('craft-chip')!)
        .toHaveTextContent('renamed');
      await expect
        .element(linkField.querySelector('craft-chip')!)
        .toHaveTextContent('renamed');
    } finally {
      other.remove();
    }
  });

  it('shares one request with other fields linking its element when it’s saved', async () => {
    const other = document.createElement('craft-link-field');
    other.types = linkField.types;
    other.modelValue = {...linkField.modelValue};
    document.body.append(other);
    await other.updateComplete;
    let resolve!: (response: unknown) => void;
    const promise = new Promise((settle) => (resolve = settle));
    const post = vi
      .spyOn(actionClient, 'post')
      .mockReturnValue(promise as never);

    try {
      broadcast({event: 'saveElement', id: 12});
      await new Promise((settle) => setTimeout(settle, 100));
      resolve({
        data: {
          elements: [
            {
              id: 12,
              label: 'live',
              siteId: 1,
              status: {fill: 'green', label: 'Live', draft: false},
            },
          ],
        },
      });

      await expect
        .poll(() =>
          document.querySelectorAll('craft-chip [label="Status: Live"]')
        )
        .toHaveLength(2);
      expect(post).toHaveBeenCalledTimes(1);
    } finally {
      post.mockRestore();
      other.remove();
    }
  });

  it('doesn’t take back other fields’ newer chip when given its old one again', async () => {
    const original = {...linkField.modelValue};
    const other = document.createElement('craft-link-field');
    other.types = linkField.types;
    other.modelValue = {...original};
    document.body.append(other);
    await other.updateComplete;
    const post = respondWithElement({
      id: 12,
      label: 'live',
      siteId: 1,
      status: {fill: 'green', label: 'Live', draft: false},
    });

    try {
      broadcast({event: 'saveElement', id: 12});
      await expect
        .poll(() =>
          document.querySelectorAll('craft-chip [label="Status: Live"]')
        )
        .toHaveLength(2);

      linkField.modelValue = {type: 'entry', value: ''};
      await linkField.updateComplete;
      linkField.modelValue = {...original};
      await new Promise((settle) => setTimeout(settle));

      expect(other.querySelector('craft-chip')).toHaveTextContent('live');
    } finally {
      post.mockRestore();
      other.remove();
    }
  });

  it('shares one request with relation fields drawing its element when it’s saved', async () => {
    const scope = effectScope();
    scope.run(() =>
      useElementPresentations(
        {elementType: 'Entry', viewMode: 'list', thumbSize: 'sm'},
        [{id: 12, siteId: 1}]
      )
    );
    const post = respondWithElement({
      id: 12,
      label: 'live',
      siteId: 1,
      status: {fill: 'green', label: 'Live', draft: false},
    });

    try {
      broadcast({event: 'saveElement', id: 12});
      await expect
        .element(page.getByRole('img', {name: 'Status: Live'}))
        .toBeVisible();
      await new Promise((settle) => setTimeout(settle, 100));

      expect(post).toHaveBeenCalledTimes(1);
    } finally {
      post.mockRestore();
      scope.stop();
    }
  });

  it('draws the server’s chip when a render brings back what an earlier one replaced', async () => {
    const original = linkField.modelValue!;
    linkField.modelValue = {
      ...original,
      element: {...original.element!, label: 'renamed'},
    };
    await linkField.updateComplete;
    await expect
      .element(linkField.querySelector('craft-chip')!)
      .toHaveTextContent('renamed');

    linkField.modelValue = {...original, element: {...original.element!}};
    await linkField.updateComplete;

    await expect
      .element(linkField.querySelector('craft-chip')!)
      .toHaveTextContent('pending');
  });

  it('draws only the latest fetch when it’s saved again while an earlier one is waiting', async () => {
    const responses: Array<(response: unknown) => void> = [];
    const post = vi.spyOn(actionClient, 'post').mockImplementation(
      () =>
        new Promise((settle) => {
          responses.push(settle);
        }) as never
    );
    const respond = (request: number, label: string, fill: string) =>
      responses[request]!({
        data: {
          elements: [
            {
              id: 12,
              label,
              siteId: 1,
              status: {fill, label, draft: false},
            },
          ],
        },
      });

    try {
      broadcast({event: 'saveElement', id: 12});
      await expect.poll(() => post.mock.calls.length).toBe(1);
      broadcast({event: 'saveElement', id: 12});
      await expect.poll(() => post.mock.calls.length).toBe(2);

      respond(1, 'Live', 'green');
      await expect
        .element(page.getByRole('img', {name: 'Status: Live'}))
        .toBeVisible();
      respond(0, 'Pending', 'blue');
      await new Promise((settle) => setTimeout(settle));

      await expect
        .element(page.getByRole('img', {name: 'Status: Live'}))
        .toBeVisible();
    } finally {
      post.mockRestore();
    }
  });

  it('draws a fetch that was waiting while it was moved', async () => {
    let resolve!: (response: unknown) => void;
    const post = vi
      .spyOn(actionClient, 'post')
      .mockReturnValue(new Promise((settle) => (resolve = settle)) as never);
    const parent = linkField.parentElement!;
    const host = document.createElement('div');
    document.body.append(host);

    try {
      broadcast({event: 'saveElement', id: 12});
      await expect.poll(() => post.mock.calls.length).toBe(1);
      host.append(linkField);
      resolve({
        data: {
          elements: [
            {
              id: 12,
              label: 'live',
              siteId: 1,
              status: {fill: 'green', label: 'Live', draft: false},
            },
          ],
        },
      });

      await expect
        .element(page.getByRole('img', {name: 'Status: Live'}))
        .toBeVisible();
    } finally {
      post.mockRestore();
      parent.append(linkField);
      host.remove();
    }
  });

  it('keeps focus in its open menu when its element is saved', async () => {
    await openMenu();
    const button = page.getByRole('button', {name: 'Remove'}).element();
    const remove = (button.getRootNode() as ShadowRoot).host as HTMLElement;
    (button as HTMLElement).focus();
    await expect.element(remove).toHaveFocus();
    const post = respondWithElement({
      id: 12,
      label: 'live',
      siteId: 1,
      status: {fill: 'green', label: 'Live', draft: false},
    });

    broadcast({event: 'saveElement', id: 12});

    await expect
      .element(page.getByRole('img', {name: 'Status: Live'}))
      .toBeInTheDocument();
    await linkField.updateComplete;
    await expect.element(remove).toHaveFocus();
    post.mockRestore();
  });

  it('keeps its refreshed chip when the link is given back as it was', async () => {
    const post = respondWithElement({
      id: 12,
      label: 'renamed',
      siteId: 1,
      status: {fill: 'green', label: 'Live', draft: false},
    });
    broadcast({event: 'saveElement', id: 12});
    await expect
      .element(page.getByRole('img', {name: 'Status: Live'}))
      .toBeVisible();

    linkField.modelValue = {...linkField.modelValue};
    await linkField.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));
    await linkField.updateComplete;

    await expect
      .element(page.getByRole('img', {name: 'Status: Live'}))
      .toBeVisible();
    await expect
      .element(linkField.querySelector('craft-chip')!)
      .toHaveTextContent('renamed');
    post.mockRestore();
  });

  it('gives way to the Choose button when the link comes back without its element', async () => {
    linkField.modelValue = {
      type: 'entry',
      value: '{entry:12@1:url}',
      defaultLabel: '',
    };
    await linkField.updateComplete;

    await expect
      .element(page.getByRole('button', {name: 'Choose'}))
      .toBeVisible();
    expect(linkField.querySelector('craft-chip')).toBeNull();
  });

  it('fetches the rest of a chip it was only given a label for', async () => {
    const post = respondWithElement({
      id: 13,
      label: 'later',
      siteId: 1,
      status: {fill: 'green', label: 'Live', draft: false},
    });

    linkField.modelValue = {
      type: 'entry',
      value: '{entry:13@1:url}',
      defaultLabel: 'later',
      element: {id: 13, label: 'later', siteId: 1},
    };

    await expect
      .element(page.getByRole('img', {name: 'Status: Live'}))
      .toBeVisible();
    expect(post).toHaveBeenCalledWith(
      'relational-fields/structured-elements',
      expect.objectContaining({elementIds: [13], siteId: 1})
    );
    post.mockRestore();
  });

  it('fetches a chip again when its element is chosen again after the fetch failed', async () => {
    const post = vi
      .spyOn(actionClient, 'post')
      .mockRejectedValueOnce(new Error('Network Error'))
      .mockResolvedValue({
        data: {
          elements: [
            {
              id: 13,
              label: 'later',
              siteId: 1,
              status: {fill: 'green', label: 'Live', draft: false},
            },
          ],
        },
      } as never);

    try {
      for (let attempt = 1; attempt <= 2; attempt++) {
        selector.createElementSelectorModal.mockClear();
        await openMenu();
        await page.getByRole('button', {name: 'Replace'}).click();
        const settings = await selectorSettings();
        settings.onSelect([{id: 13, siteId: 1, label: 'later'}]);
        await expect.poll(() => post.mock.calls.length).toBe(attempt);
      }

      await expect
        .element(page.getByRole('img', {name: 'Status: Live'}))
        .toBeVisible();
    } finally {
      post.mockRestore();
    }
  });

  it('doesn’t fetch a chip it was given in full', async () => {
    const post = vi.spyOn(actionClient, 'post');

    linkField.modelValue = {
      type: 'entry',
      value: '{entry:14@1:url}',
      defaultLabel: 'other',
      element: {id: 14, label: 'other', siteId: 1, canEdit: true},
    };
    await linkField.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    await expect
      .element(linkField.querySelector('craft-chip')!)
      .toHaveTextContent('other');
    expect(post).not.toHaveBeenCalled();
    post.mockRestore();
  });

  it('keeps its chip when the applied link is restored after it was removed', async () => {
    linkField.modelValue = {
      ...linkField.modelValue,
      element: {
        id: 12,
        label: 'pending',
        siteId: 1,
        status: {fill: 'blue', label: 'Pending', draft: false},
      },
    };
    await linkField.updateComplete;
    const applied = new Promise<LinkFieldValue>((resolve) => {
      linkField.addEventListener(
        'apply',
        (event) => resolve((event as CustomEvent<LinkFieldValue>).detail),
        {once: true}
      );
    });

    await page.getByRole('button', {name: 'Apply'}).click();
    const value = await applied;
    await openMenu();
    await page.getByRole('button', {name: 'Remove'}).click();
    linkField.modelValue = {...value};
    await linkField.updateComplete;

    await expect
      .element(page.getByRole('img', {name: 'Status: Pending'}))
      .toBeVisible();
  });

  it('keeps its loaded chip when restored as applied before it loaded', async () => {
    let respond: () => void = () => {};
    const post = vi.spyOn(actionClient, 'post').mockImplementation(
      () =>
        new Promise((resolve) => {
          respond = () =>
            resolve({
              data: {
                elements: [
                  {
                    id: 12,
                    label: 'pending',
                    siteId: 1,
                    cpEditUrl: '/admin/entries/pending',
                    canEdit: true,
                    status: {fill: 'blue', label: 'Pending', draft: false},
                  },
                ],
              },
            } as never);
        })
    );
    linkField.modelValue = {type: 'entry', value: ''};
    await linkField.updateComplete;
    const applied = new Promise<LinkFieldValue>((resolve) => {
      linkField.addEventListener(
        'apply',
        (event) => resolve((event as CustomEvent<LinkFieldValue>).detail),
        {once: true}
      );
    });

    await page.getByRole('button', {name: 'Choose'}).click();
    const settings = await selectorSettings();
    settings.onSelect([{id: 12, siteId: 1, label: 'pending'}]);
    await page.getByRole('button', {name: 'Apply'}).click();
    const value = await applied;
    respond();
    await expect
      .element(page.getByRole('img', {name: 'Status: Pending'}))
      .toBeVisible();
    linkField.modelValue = {...value};
    await linkField.updateComplete;

    await expect
      .element(page.getByRole('img', {name: 'Status: Pending'}))
      .toBeVisible();
    await openMenu();
    await expect
      .element(page.getByRole('button', {name: 'Edit'}))
      .toBeVisible();
    post.mockRestore();
  });

  describe('with an action that opens a dialog', () => {
    let events: string[];
    let close: () => void;
    let craft: typeof window.Craft;

    beforeEach(async () => {
      events = [];
      for (const name of ['element-select-start', 'element-select-end']) {
        linkField.addEventListener(name, () => events.push(name));
      }
      const closed = new Promise<void>((resolve) => (close = resolve));
      craft = window.Craft;
      window.Craft = Object.assign(Object.create(null), craft, {
        PreviewFileModal: vi.fn(function () {
          events.push('preview');
          return {closed};
        }),
      });
      linkField.modelValue = {
        ...linkField.modelValue,
        element: {
          ...linkField.modelValue!.element!,
          actions: [
            {
              label: 'Preview file',
              icon: 'view',
              behavior: {type: 'previewFile', assetId: 12},
            },
          ],
        },
      };
      await openMenu();
      await page.getByRole('button', {name: 'Preview file'}).click();
      await expect
        .poll(() => events)
        .toEqual(['element-select-start', 'preview']);
    });

    afterEach(() => {
      window.Craft = craft;
    });

    it('suspends while the dialog is showing', async () => {
      await new Promise((settle) => setTimeout(settle));
      expect(events).toHaveLength(2);

      close();
      await expect.poll(() => events.at(-1)).toBe('element-select-end');
    });

    it('resumes once the dialog closes, after it was moved', async () => {
      const host = document.createElement('div');
      document.body.append(host);

      try {
        host.append(linkField);
        close();
        await expect.poll(() => events.at(-1)).toBe('element-select-end');
      } finally {
        host.remove();
      }
    });

    it('doesn’t resume once it’s removed', async () => {
      linkField.remove();
      close();
      await new Promise((settle) => setTimeout(settle));

      expect(events).toHaveLength(2);
    });
  });

  it('resumes when the image editor can’t be opened', async () => {
    const events: string[] = [];
    for (const name of ['element-select-start', 'element-select-end']) {
      linkField.addEventListener(name, () => events.push(name));
    }
    imageEditor.openImageEditorDialog.mockRejectedValueOnce(
      new Error('Failed to fetch dynamically imported module')
    );
    const error = vi.spyOn(console, 'error').mockImplementation(() => {});
    linkField.modelValue = {
      ...linkField.modelValue,
      element: {
        ...linkField.modelValue!.element!,
        actions: [
          {
            label: 'Open in Image Editor',
            icon: 'paintbrush',
            behavior: {type: 'editImage', assetId: 12, settings: {} as never},
          },
        ],
      },
    };

    try {
      await openMenu();
      await page.getByRole('button', {name: 'Open in Image Editor'}).click();

      await expect
        .poll(() => events)
        .toEqual(['element-select-start', 'element-select-end']);
    } finally {
      error.mockRestore();
    }
  });

  describe('saved in the image editor', () => {
    async function save(result: Record<string, unknown>): Promise<{
      changes: string[];
      reload: ReturnType<typeof vi.spyOn>;
    }> {
      const changes: string[] = [];
      linkField.addEventListener('link-change', (event) =>
        changes.push(
          (event as CustomEvent<LinkChangeDetail>).detail.value.value
        )
      );
      const reload = vi.spyOn(router, 'reload').mockImplementation(() => {});
      vi.spyOn(actionClient, 'post').mockImplementation(
        () => new Promise(() => {})
      );
      imageEditor.openImageEditorDialog.mockClear();
      linkField.modelValue = {
        ...linkField.modelValue,
        element: {
          ...linkField.modelValue!.element!,
          actions: [
            {
              label: 'Open in Image Editor',
              icon: 'paintbrush',
              behavior: {type: 'editImage', assetId: 12, settings: {} as never},
            },
          ],
        },
      };
      await linkField.updateComplete;
      await page.getByRole('button', {name: 'Actions'}).click();
      await page.getByRole('button', {name: 'Open in Image Editor'}).click();
      await expect
        .poll(() => imageEditor.openImageEditorDialog.mock.calls.length)
        .toBe(1);
      const [, onSaved] = imageEditor.openImageEditorDialog.mock
        .calls[0] as unknown as [unknown, (result: unknown) => void];
      onSaved(result);

      return {changes, reload};
    }

    beforeEach(async () => {
      linkField.live = true;
      await linkField.updateComplete;
    });

    afterEach(() => {
      vi.restoreAllMocks();
    });

    it('leaves the page as it is when the image is saved in place', async () => {
      const {changes, reload} = await save({});

      expect(changes).toEqual([]);
      expect(reload).not.toHaveBeenCalled();
    });

    it('links to the new asset when the image is saved as one', async () => {
      const {changes} = await save({newAssetId: 34});

      expect(changes).toEqual(['{entry:34@1:url}']);
    });

    it('keeps its link when the field is disabled', async () => {
      linkField.disabled = true;
      await linkField.updateComplete;

      const {changes} = await save({newAssetId: 34});

      expect(changes).toEqual([]);
    });
  });

  it('is replaced through the element selector, from the menu button', async () => {
    await openMenu();
    await page.getByRole('button', {name: 'Replace'}).click();

    const settings = await selectorSettings();
    expect(settings.triggerElement()).toBe(
      page.getByRole('button', {name: 'Actions'}).element()
    );
  });
});

it('describes the destination with its error', async () => {
  const url = page.getByRole('textbox', {name: 'URL'});

  await page.getByRole('button', {name: 'Apply'}).click();

  await expect.element(url).toHaveAttribute('aria-invalid', 'true');
  await expect
    .element(url)
    .toHaveAccessibleDescription(/URL cannot be blank\./);
});

/** The settings the field opened its element selector with. */
async function selectorSettings(): Promise<SelectorSettings> {
  await expect
    .poll(() => selector.createElementSelectorModal.mock.calls.length)
    .toBe(1);
  const [, settings] = selector.createElementSelectorModal.mock
    .calls[0] as unknown as [string, SelectorSettings];

  return settings;
}

/** Answers chip fetches with the given element. */
function respondWithElement(
  element: Record<string, unknown>
): ReturnType<typeof vi.spyOn> {
  return vi
    .spyOn(actionClient, 'post')
    .mockResolvedValue({data: {elements: [element]}} as never);
}

async function chooseType(id: string): Promise<void> {
  await page.getByRole('combobox', {name: 'Link Type'}).selectOptions(id);
  await linkField.updateComplete;
}

async function openSelector(): Promise<{
  choose: ReturnType<typeof page.getByRole>;
  settings: SelectorSettings;
}> {
  selector.createElementSelectorModal.mockClear();
  selector.listeners.clear();
  linkField.types = [
    types[0]!,
    {
      id: 'entry',
      label: 'Entry',
      kind: 'element',
      elementType: 'Entry',
      refHandle: 'entry',
    },
  ];
  await linkField.updateComplete;
  await chooseType('entry');
  const choose = page
    .getByRole('group', {name: 'Entry'})
    .getByRole('button', {name: 'Choose'});
  await choose.click();
  const settings = await selectorSettings();

  return {choose, settings};
}

it('places slideouts opened from its element selector in its own panel', async () => {
  const {choose, settings} = await openSelector();

  expect(settings.triggerElement()).toBe(choose.element());
});

it('stays suspended while its element selector hands off to a slideout', async () => {
  const ends: Event[] = [];
  linkField.addEventListener('element-select-end', (event) => ends.push(event));
  const {settings} = await openSelector();

  let settle!: () => void;
  settings.onHandoff(new Promise((resolve) => (settle = resolve)));
  selector.listeners.get('close')!();

  // A link popover resuming now would be dismissed by the slideout taking
  // focus, taking the link with it.
  await Promise.resolve();
  expect(ends).toHaveLength(0);

  settle();
  await expect.poll(() => ends.length).toBe(1);
});

it('resumes as soon as its element selector closes otherwise', async () => {
  const ends: Event[] = [];
  linkField.addEventListener('element-select-end', (event) => ends.push(event));
  await openSelector();

  selector.listeners.get('close')!();

  expect(ends).toHaveLength(1);
});

describe('live', () => {
  let changes: LinkChangeDetail[];

  beforeEach(async () => {
    changes = [];
    linkField.live = true;
    linkField.showLabelField = true;
    linkField.advancedFields = ['target'];
    linkField.addEventListener('link-change', (event) => {
      changes.push((event as CustomEvent<LinkChangeDetail>).detail);
    });
    await linkField.updateComplete;
  });

  it('applies each edit without rewriting what’s being typed', async () => {
    const url = page.getByRole('textbox', {name: 'URL'});

    await url.fill('craftcms.com');

    expect(changes.at(-1)).toMatchObject({
      kind: 'typing',
      value: {type: 'url', value: 'https://craftcms.com'},
    });
    // Echo the value back, as the field's owner does.
    linkField.modelValue = {...changes.at(-1)!.value};
    await linkField.updateComplete;
    await expect.element(url).toHaveValue('craftcms.com');
    await expect
      .element(page.getByRole('button', {name: 'Apply'}))
      .not.toBeInTheDocument();
  });

  it('draws the latest chip an echo brings', async () => {
    linkField.modelValue = {
      type: 'entry',
      value: '{entry:12@1:url}',
      defaultLabel: 'pending',
      element: {
        id: 12,
        label: 'pending',
        siteId: 1,
        status: {fill: 'blue', label: 'Pending', draft: false},
      },
    };
    await linkField.updateComplete;
    await page.getByRole('button', {name: 'Advanced'}).click();
    await page.getByRole('textbox', {name: 'Label'}).fill('Read more');
    await expect
      .element(page.getByRole('img', {name: 'Status: Pending'}))
      .toBeVisible();

    linkField.modelValue = {
      ...changes.at(-1)!.value,
      element: {
        id: 12,
        label: 'pending',
        siteId: 1,
        status: {fill: 'green', label: 'Live', draft: false},
      },
    };
    await linkField.updateComplete;

    await expect
      .element(page.getByRole('img', {name: 'Status: Live'}))
      .toBeVisible();
    await expect
      .element(page.getByRole('textbox', {name: 'Label'}))
      .toHaveValue('Read more');
  });

  it('sits in a pane, with its label and attributes under “Advanced”', async () => {
    expect(linkField.querySelector(':scope > craft-pane')).not.toBeNull();

    await page.getByRole('button', {name: 'Advanced'}).click();
    await expect
      .element(page.getByRole('textbox', {name: 'Label'}))
      .toBeVisible();
    page.getByRole('switch', {name: 'Open in a new tab'}).element().focus();
    await userEvent.keyboard(' ');

    expect(changes.at(-1)).toMatchObject({
      kind: 'discrete',
      value: {target: '_blank'},
    });
  });
});
