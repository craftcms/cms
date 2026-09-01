import {beforeEach, describe, expect, test, vi} from 'vite-plus/test';

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
    expect(links[0]!.getAttribute('rel')).toBe('stylesheet');
    expect(links[0]!.getAttribute('href')).toBe(
      'https://example.com/style.css'
    );
  });

  test('appends a script element to head', async () => {
    const {appendHeadHtml} = await freshImport();
    const append = appendHeadHtml(
      '<script src="https://example.com/script.js"></script>'
    );
    document.head.querySelector('script')!.dispatchEvent(new Event('load'));
    await append;

    const scripts = document.head.querySelectorAll('script');
    expect(scripts.length).toBe(1);
    expect(scripts[0]!.getAttribute('src')).toBe(
      'https://example.com/script.js'
    );
  });

  test('appends an inline script to head', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml('<script>console.log("hello")</script>');
    const scripts = document.head.querySelectorAll('script');
    expect(scripts.length).toBe(1);
    expect(scripts[0]!.textContent).toBe('console.log("hello")');
  });

  test('appends arbitrary HTML to head', async () => {
    const {appendHeadHtml} = await freshImport();
    await appendHeadHtml('<meta name="description" content="test">');
    const metas = document.head.querySelectorAll('meta[name="description"]');
    expect(metas.length).toBe(1);
    expect(metas[0]!.getAttribute('content')).toBe('test');
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
    const append = appendHeadHtml(
      '<script src="https://example.com/b.js" type="module" defer></script>'
    );
    document.head.querySelector('script')!.dispatchEvent(new Event('load'));
    await append;

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
    const append = appendBodyHtml(
      '<script src="https://example.com/body.js"></script>'
    );
    document.body.querySelector('script')!.dispatchEvent(new Event('load'));
    await append;

    const scripts = document.body.querySelectorAll('script');
    expect(scripts.length).toBe(1);
    expect(scripts[0]!.getAttribute('src')).toBe('https://example.com/body.js');
  });

  test('appends elements to a provided parent', async () => {
    const {appendElementHtml} = await freshImport();
    const parent = document.createElement('div');

    const dispose = await appendElementHtml('<p id="child">Hello</p>', parent);

    expect(parent.querySelector('#child')!.textContent).toBe('Hello');

    dispose();

    expect(parent.querySelector('#child')).toBeNull();
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
    const firstAppend = appendBodyHtml(js);
    document.body.querySelector('script')!.dispatchEvent(new Event('load'));
    await firstAppend;

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

  test('waits for external scripts before appending subsequent nodes', async () => {
    const {appendElementHtml} = await freshImport();
    const parent = document.createElement('div');
    const append = appendElementHtml(
      '<script src="https://example.com/ordered.js"></script><span id="after-script"></span>',
      parent
    );

    await Promise.resolve();

    expect(parent.querySelector('#after-script')).toBeNull();

    parent.querySelector('script')!.dispatchEvent(new Event('load'));
    await append;

    expect(parent.querySelector('#after-script')).not.toBeNull();
  });

  test('rejects failed external scripts and removes them', async () => {
    const {appendElementHtml} = await freshImport();
    const append = appendElementHtml(
      '<script src="https://example.com/missing.js"></script>',
      document.body,
      true
    );
    const script = document.body.querySelector('script')!;

    script.dispatchEvent(new Event('error'));

    await expect(append).rejects.toThrow('https://example.com/missing.js');
    expect(script.isConnected).toBe(false);
  });

  test('preserves script failures for strict shared consumers', async () => {
    const {appendElementHtml} = await freshImport();
    const html =
      '<script src="https://example.com/shared-missing.js"></script>';
    const firstAppend = appendElementHtml(html, document.body);

    document.body.querySelector('script')!.dispatchEvent(new Event('error'));
    const firstDispose = await firstAppend;

    await expect(appendElementHtml(html, document.body, true)).rejects.toThrow(
      'https://example.com/shared-missing.js'
    );

    firstDispose();
  });

  test('keeps shared assets until every owner disposes them', async () => {
    const {appendBodyHtml} = await freshImport();
    const firstAppend = appendBodyHtml(
      '<script src="https://example.com/shared.js"></script>'
    );
    const script = document.body.querySelector('script')!;

    script.dispatchEvent(new Event('load'));
    const firstDispose = await firstAppend;
    const secondDispose = await appendBodyHtml(
      '<script src="https://example.com/shared.js"></script>'
    );

    firstDispose();
    expect(script.isConnected).toBe(true);

    secondDispose();
    expect(script.isConnected).toBe(false);
  });
});

describe('serializeFormInputs', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  test('serializes named inputs, selects, and textareas', async () => {
    const {serializeFormInputs} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <input name="a" value="1">
        <textarea name="b">two</textarea>
        <select name="c"><option value="3" selected>3</option></select>
        <input value="no-name">
      </div>
    `;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'a=1&b=two&c=3'
    );
  });

  test('reads live property values, not attributes', async () => {
    const {serializeFormInputs} = await freshImport();
    document.body.innerHTML = `<div id="host"><input name="a" value="old"></div>`;
    document.querySelector<HTMLInputElement>('[name=a]')!.value = 'new';
    expect(serializeFormInputs(document.getElementById('host')!)).toBe('a=new');
  });

  test('omits disabled controls and unchecked checkboxes/radios', async () => {
    const {serializeFormInputs} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <input name="a" value="1" disabled>
        <input type="checkbox" name="b" value="1">
        <input type="checkbox" name="c" value="1" checked>
        <input type="radio" name="d" value="x">
        <input type="radio" name="d" value="y" checked>
      </div>
    `;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'c=1&d=y'
    );
  });

  test('omits buttons and file inputs', async () => {
    const {serializeFormInputs} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <input type="submit" name="a" value="Go">
        <input type="button" name="b" value="Press">
        <input type="file" name="c">
        <input type="hidden" name="d" value="kept">
      </div>
    `;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'd=kept'
    );
  });

  test('expands multi-selects into repeated params', async () => {
    const {serializeFormInputs} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <select name="a[]" multiple>
          <option value="1" selected>1</option>
          <option value="2">2</option>
          <option value="3" selected>3</option>
        </select>
      </div>
    `;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'a%5B%5D=1&a%5B%5D=3'
    );
  });

  test('url-encodes namespaced names and values', async () => {
    const {serializeFormInputs} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <input name="types[PlainText][placeholder]" value="a b&c">
      </div>
    `;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'types%5BPlainText%5D%5Bplaceholder%5D=a+b%26c'
    );
  });

  test('serializes custom elements that hold their value on the host', async () => {
    const {serializeFormInputs} = await freshImport();
    if (!customElements.get('fake-rich-select')) {
      customElements.define(
        'fake-rich-select',
        class extends HTMLElement {
          name = 'volume';
          serializedValue = '11';
        }
      );
    }
    document.body.innerHTML = `<div id="host"><fake-rich-select></fake-rich-select></div>`;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'volume=11'
    );
  });

  test('expands array serializedValues into repeated params', async () => {
    const {serializeFormInputs} = await freshImport();
    if (!customElements.get('fake-multi-select')) {
      customElements.define(
        'fake-multi-select',
        class extends HTMLElement {
          name = 'kinds[]';
          serializedValue = ['image', 'video'];
        }
      );
    }
    document.body.innerHTML = `<div id="host"><fake-multi-select></fake-multi-select></div>`;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'kinds%5B%5D=image&kinds%5B%5D=video'
    );
  });

  test('applies choice semantics to hosts with a Lion choice modelValue', async () => {
    const {serializeFormInputs} = await freshImport();
    if (!customElements.get('fake-choice')) {
      customElements.define(
        'fake-choice',
        class extends HTMLElement {
          name = '';
          modelValue = {value: '', checked: false};
        }
      );
    }
    document.body.innerHTML = `
      <div id="host">
        <fake-choice id="on"></fake-choice>
        <fake-choice id="off"></fake-choice>
      </div>
    `;
    const on = document.getElementById('on') as HTMLElement & {
      name: string;
      modelValue: {value: string; checked: boolean};
    };
    const off = document.getElementById('off') as typeof on;
    on.name = 'agree';
    on.modelValue = {value: '1', checked: true};
    off.name = 'decline';
    off.modelValue = {value: '1', checked: false};

    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'agree=1'
    );
  });

  test('skips disabled custom elements', async () => {
    const {serializeFormInputs} = await freshImport();
    if (!customElements.get('fake-disabled-host')) {
      customElements.define(
        'fake-disabled-host',
        class extends HTMLElement {
          name = 'muted';
          disabled = true;
          serializedValue = 'x';
        }
      );
    }
    document.body.innerHTML = `<div id="host"><fake-disabled-host></fake-disabled-host></div>`;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe('');
  });

  test('does not double-count hosts whose light DOM posts natively', async () => {
    const {serializeFormInputs} = await freshImport();
    if (!customElements.get('fake-lion-input')) {
      customElements.define(
        'fake-lion-input',
        class extends HTMLElement {
          name = 'title';
          serializedValue = 'from-host';
        }
      );
    }
    document.body.innerHTML = `
      <div id="host">
        <fake-lion-input>
          <input slot="input" name="title" value="from-input">
        </fake-lion-input>
      </div>
    `;
    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'title=from-input'
    );
  });

  test('captures an SSR-hydrated craft-checkbox exactly once', async () => {
    const {serializeFormInputs} = await freshImport();
    await import('../components/checkbox/checkbox.js');
    document.body.innerHTML = `
      <div id="host">
        <craft-checkbox>
          <input slot="input" type="checkbox" name="allowedKinds[]" value="image" checked>
        </craft-checkbox>
      </div>
    `;
    const checkbox = document.querySelector<
      HTMLElement & {updateComplete: Promise<boolean>}
    >('craft-checkbox')!;
    await checkbox.updateComplete;

    expect(serializeFormInputs(document.getElementById('host')!)).toBe(
      'allowedKinds%5B%5D=image'
    );
  });
});

describe('serializeFormInputsAsObject', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  test('returns raw names and values without url-encoding', async () => {
    const {serializeFormInputsAsObject} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <input name="types[PlainText][placeholder]" value="a b&c">
        <textarea name="b">two</textarea>
        <input type="checkbox" name="c" value="1">
      </div>
    `;
    expect(
      serializeFormInputsAsObject(document.getElementById('host')!)
    ).toEqual({
      'types[PlainText][placeholder]': 'a b&c',
      b: 'two',
    });
  });

  test('groups repeated names into arrays', async () => {
    const {serializeFormInputsAsObject} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <select name="a[]" multiple>
          <option value="1" selected>1</option>
          <option value="2">2</option>
          <option value="3" selected>3</option>
        </select>
        <input name="a[]" value="4">
      </div>
    `;
    expect(
      serializeFormInputsAsObject(document.getElementById('host')!)
    ).toEqual({
      'a[]': ['1', '3', '4'],
    });
  });

  test('keeps a single value as a string', async () => {
    const {serializeFormInputsAsObject} = await freshImport();
    document.body.innerHTML = `
      <div id="host">
        <input name="a" value="1">
      </div>
    `;
    expect(
      serializeFormInputsAsObject(document.getElementById('host')!)
    ).toEqual({
      a: '1',
    });
  });
});
