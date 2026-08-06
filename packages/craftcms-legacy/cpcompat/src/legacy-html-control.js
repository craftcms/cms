(() => {
  const elementName = 'craft-legacy-html-control';
  const componentNames = ['craft-legacy:html-field', 'craft-legacy:html'];
  const ownedAssets = new Map();

  class LegacyHtmlControl extends HTMLElement {
    _control = null;
    _values = null;
    _scope = [];
    _refreshable = false;
    _fragmentKey = '';
    _disposers = [];
    _runId = 0;
    _form = null;
    _error = null;
    _errors = [];

    set node(node) {
      this.control = node?.control ?? null;
    }

    set control(control) {
      const fragment = control?.props?.fragment;
      const fragmentKey = fragment
        ? `${fragment.headHtml ?? ''}\u0000${fragment.html ?? ''}\u0000${fragment.bodyHtml ?? ''}`
        : '';

      if (fragmentKey === this._fragmentKey) {
        this._control = control;
        applyMode(this, control?.mode);
        this.renderErrors();

        return;
      }

      const file = selectedFile(this);

      if (file) {
        this.invalidate(
          `Legacy HTML Control cannot replace a selected file [${file.name}].`
        );

        return;
      }

      this._control = control;
      this._fragmentKey = fragmentKey;

      if (this.isConnected) {
        void this.mount();
      }
    }

    set values(values) {
      this._values = values;
    }

    set errors(errors) {
      this._errors = Array.isArray(errors) ? errors : [];
      this.renderErrors();
    }

    set value(value) {
      this._value = value;
    }

    set scope(scope) {
      this._scope = scope;
    }

    set formScope(scope) {
      this._scope = scope;
    }

    set refreshable(refreshable) {
      this._refreshable = refreshable;
    }

    set formRefreshable(refreshable) {
      this._refreshable = refreshable;
    }

    connectedCallback() {
      this._form = this.closest('form');
      this._form?.addEventListener('submit', this.handleSubmit, true);
      this.addEventListener('input', this.handleInput);
      this.addEventListener('change', this.handleInput);
      void this.mount();
    }

    disconnectedCallback() {
      this._form?.removeEventListener('submit', this.handleSubmit, true);
      this.removeEventListener('input', this.handleInput);
      this.removeEventListener('change', this.handleInput);
      this._form = null;
      this._runId++;
      this.dispose();
    }

    handleInput = (event) => {
      if (event instanceof CustomEvent) {
        return;
      }

      try {
        this.publish(isTypingEvent(event) ? 'typing' : 'discrete');
      } catch (error) {
        this.invalidate(error);
      }
    };

    handleSubmit = (event) => {
      if (this._error) {
        event.preventDefault();

        return;
      }

      try {
        this.publish('discrete');
      } catch (error) {
        event.preventDefault();
        this.invalidate(error);
      }
    };

    async mount() {
      const fragment = this._control?.props?.fragment;
      const runId = ++this._runId;

      this.dispose();
      this.clearError();

      if (!fragment) {
        this.invalidate(
          'Legacy HTML Control is missing its captured fragment.'
        );

        return;
      }

      if (
        typeof fragment.html !== 'string' ||
        typeof fragment.headHtml !== 'string' ||
        typeof fragment.bodyHtml !== 'string'
      ) {
        this.invalidate(
          'Legacy HTML Control received an invalid captured fragment.'
        );

        return;
      }

      try {
        if (!(await this.append(fragment.headHtml, document.head, runId))) {
          return;
        }

        if (!(await this.append(fragment.html, this, runId))) {
          return;
        }

        if (!(await this.append(fragment.bodyHtml, document.body, runId))) {
          return;
        }

        applyMode(this, this._control?.mode);
        restoreFormInputs(this, this.currentValue());
        this.renderErrors();
        window.Craft?.initUiElements?.(this);
      } catch (error) {
        if (runId === this._runId) {
          this.invalidate(error);
        }
      }
    }

    async append(html, parent, runId) {
      if (!html) {
        return true;
      }

      const dispose = await appendHtml(html, parent);

      if (runId !== this._runId) {
        dispose();

        return false;
      }

      this._disposers.push(dispose);

      return true;
    }

    currentValue() {
      if (this._value && typeof this._value === 'object') {
        return this._value;
      }

      return valueAt(this._values, this._control?.path ?? []);
    }

    publish(kind) {
      const file = selectedFile(this);

      if (file) {
        throw new Error(
          `Legacy HTML Control cannot submit selected file [${file.name}].`
        );
      }

      const value = readFormInputs(this);

      if (this._values && this._control?.path) {
        if (this._control.props?.expandValues) {
          unsetValue(this._values, this._control.path);
          mergeValues(this._values, expandValues(value));
        } else {
          setValue(this._values, this._control.path, value);
        }
      }

      this.dispatchEvent(
        new CustomEvent('change', {
          bubbles: true,
          detail: {
            kind,
            path: this._control?.path ?? [],
            scope: this._scope,
            refreshable: this._refreshable,
          },
        })
      );
    }

    invalidate(error) {
      this._error = error instanceof Error ? error.message : String(error);
      let message = this.querySelector('[data-legacy-html-error]');

      if (!message) {
        message = document.createElement('p');
        message.dataset.legacyHtmlError = '';
        message.setAttribute('role', 'alert');
        this.appendChild(message);
      }

      message.textContent = this._error;
    }

    clearError() {
      this._error = null;
      this.querySelector('[data-legacy-html-error]')?.remove();
    }

    renderErrors() {
      this.querySelector('[data-legacy-form-errors]')?.remove();
      this.removeAttribute('aria-invalid');

      const path = this._control?.path ?? [];
      const errors = this._errors.flatMap((error) =>
        pathsMatch(error?.path, path) ? (error.messages ?? []) : []
      );

      if (!errors.length) {
        return;
      }

      const list = document.createElement('ul');
      list.dataset.legacyFormErrors = '';
      list.className = 'error-list';
      list.setAttribute('role', 'alert');
      errors.forEach((error) => {
        const item = document.createElement('li');
        item.textContent = String(error);
        list.appendChild(item);
      });
      this.setAttribute('aria-invalid', 'true');
      this.appendChild(list);
    }

    dispose() {
      while (this._disposers.length) {
        this._disposers.pop()?.();
      }
    }
  }

  function applyMode(root, mode) {
    if (mode !== 'disabled') {
      return;
    }

    root
      .querySelectorAll('input, select, textarea, button')
      .forEach((control) => (control.disabled = true));
  }

  function pathsMatch(left, right) {
    return (
      Array.isArray(left) &&
      left.length === right.length &&
      left.every((segment, index) => segment === right[index])
    );
  }

  async function appendHtml(html, parent) {
    const template = document.createElement('template');
    const appended = [];
    const releases = [];

    template.innerHTML = html.trim();

    const dispose = () => {
      appended.forEach((node) => node.remove());
      releases.forEach((release) => release());
    };

    try {
      for (const source of template.content.childNodes) {
        const asset = assetDetails(source);

        if (asset) {
          const ownedAsset = ownedAssets.get(asset.key);

          if (ownedAsset) {
            ownedAsset.references++;
            releases.push(() => releaseAsset(asset.key, ownedAsset));
            await ownedAsset.loaded;

            continue;
          }

          if (hasAsset(asset.selector, asset.property, asset.value)) {
            continue;
          }
        }

        const node = cloneNode(source);
        const loaded = asset
          ? waitForAsset(node, asset.value)
          : Promise.resolve();

        parent.appendChild(node);

        if (asset) {
          const ownedAsset = {node, loaded, references: 1};

          ownedAssets.set(asset.key, ownedAsset);
          releases.push(() => releaseAsset(asset.key, ownedAsset));
        } else {
          appended.push(node);
        }

        await loaded;
      }

      return dispose;
    } catch (error) {
      dispose();

      throw error;
    }
  }

  function cloneNode(source) {
    if (!(source instanceof HTMLScriptElement)) {
      return source.cloneNode(true);
    }

    const script = document.createElement('script');

    for (const attribute of source.attributes) {
      script.setAttribute(attribute.name, attribute.value);
    }

    script.textContent = source.textContent;
    script.async = false;

    return script;
  }

  function assetDetails(node) {
    if (node instanceof HTMLLinkElement && node.href) {
      return {
        key: `link:${node.href}`,
        selector: 'link[href]',
        property: 'href',
        value: node.href,
      };
    }

    if (node instanceof HTMLScriptElement && node.src) {
      return {
        key: `script:${node.src}`,
        selector: 'script[src]',
        property: 'src',
        value: node.src,
      };
    }

    return null;
  }

  function waitForAsset(node, url) {
    if (
      !(node instanceof HTMLScriptElement || node instanceof HTMLLinkElement)
    ) {
      return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
      node.addEventListener('load', resolve, {once: true});
      node.addEventListener(
        'error',
        () => reject(new Error(`Failed to load legacy asset [${url}].`)),
        {once: true}
      );
    });
  }

  function hasAsset(selector, property, value) {
    return Array.from(document.querySelectorAll(selector)).some(
      (element) => element[property] === value
    );
  }

  function releaseAsset(key, asset) {
    asset.references--;

    if (asset.references > 0) {
      return;
    }

    asset.node.remove();
    ownedAssets.delete(key);
  }

  function readFormInputs(container) {
    const values = {};

    for (const control of formControls(container)) {
      if (control.disabled) {
        continue;
      }

      if (control instanceof HTMLInputElement) {
        if (
          ['file', 'submit', 'button', 'reset', 'image'].includes(control.type)
        ) {
          continue;
        }

        if (['checkbox', 'radio'].includes(control.type) && !control.checked) {
          continue;
        }
      }

      if (control instanceof HTMLSelectElement && control.multiple) {
        for (const option of control.selectedOptions) {
          appendValue(values, control.name, option.value);
        }

        continue;
      }

      appendValue(values, control.name, control.value);
    }

    return values;
  }

  function restoreFormInputs(container, values) {
    if (!values || typeof values !== 'object') {
      return;
    }

    const indexes = new Map();

    for (const control of formControls(container)) {
      if (control.disabled) {
        continue;
      }

      const value = values[control.name];
      const expected = Array.isArray(value)
        ? value.map(String)
        : [String(value ?? '')];

      if (
        control instanceof HTMLInputElement &&
        ['checkbox', 'radio'].includes(control.type)
      ) {
        control.checked = expected.includes(control.value);

        continue;
      }

      if (control instanceof HTMLSelectElement && control.multiple) {
        for (const option of control.options) {
          option.selected = expected.includes(option.value);
        }

        continue;
      }

      const index = indexes.get(control.name) ?? 0;
      control.value = expected[index] ?? expected.at(-1) ?? '';
      indexes.set(control.name, index + 1);
    }
  }

  function formControls(container) {
    return container.querySelectorAll(
      'input[name], select[name], textarea[name]'
    );
  }

  function selectedFile(container) {
    return Array.from(container.querySelectorAll('input[type="file"]'))
      .map((input) => input.files?.[0])
      .find(Boolean);
  }

  function isTypingEvent(event) {
    const target = event.target;

    if (target instanceof HTMLTextAreaElement) {
      return event.type === 'input';
    }

    return (
      event.type === 'input' &&
      target instanceof HTMLInputElement &&
      ['text', 'email', 'url', 'tel', 'password', 'search'].includes(
        target.type
      )
    );
  }

  function appendValue(values, name, value) {
    if (!(name in values)) {
      values[name] = value;

      return;
    }

    values[name] = Array.isArray(values[name])
      ? [...values[name], value]
      : [values[name], value];
  }

  function valueAt(values, path) {
    return path.reduce((value, segment) => value?.[segment], values);
  }

  function setValue(values, path, value) {
    let target = values;

    path.forEach((segment, index) => {
      if (index === path.length - 1) {
        target[segment] = value;

        return;
      }

      target[segment] ??= {};
      target = target[segment];
    });
  }

  function unsetValue(values, path) {
    const parents = [values];
    const parent = path.slice(0, -1).reduce((value, segment) => {
      const child = value?.[segment];
      parents.push(child);

      return child;
    }, values);

    if (parent && path.length) {
      delete parent[path.at(-1)];
    }

    for (let index = path.length - 2; index >= 0; index--) {
      const child = parents[index + 1];

      if (!child || Object.keys(child).length) {
        break;
      }

      delete parents[index][path[index]];
    }
  }

  function expandValues(values) {
    const expanded = Object.create(null);

    for (const [name, value] of Object.entries(values)) {
      const path = inputPath(name);

      for (const item of Array.isArray(value) ? value : [value]) {
        setInputValue(expanded, path, item);
      }
    }

    return normalizeArrays(expanded);
  }

  function inputPath(name) {
    const path = [];
    const pattern = /(^[^[]+)|\[([^\]]*)\]/g;
    let match;
    let length = 0;

    while ((match = pattern.exec(name))) {
      const segment = match[1] ?? match[2];

      if (['__proto__', 'constructor', 'prototype'].includes(segment)) {
        throw new Error(`Legacy input [${name}] contains an unsafe path.`);
      }

      path.push(path.length ? segment : segment.replace(/[ .]/g, '_'));
      length += match[0].length;
    }

    if (!path.length || length !== name.length) {
      throw new Error(`Legacy input [${name}] has an invalid name.`);
    }

    return path;
  }

  function setInputValue(values, path, value) {
    let target = values;

    path.forEach((segment, index) => {
      const key = segment === '' ? nextIndex(target) : segment;

      if (index === path.length - 1) {
        target[key] = value;

        return;
      }

      if (!target[key] || typeof target[key] !== 'object') {
        target[key] = Object.create(null);
      }

      target = target[key];
    });
  }

  function nextIndex(values) {
    return (
      Math.max(
        -1,
        ...Object.keys(values)
          .filter((key) => /^(0|[1-9]\d*)$/.test(key))
          .map(Number)
      ) + 1
    );
  }

  function normalizeArrays(value) {
    if (!value || typeof value !== 'object') {
      return value;
    }

    const entries = Object.entries(value).map(([key, item]) => [
      key,
      normalizeArrays(item),
    ]);
    const numeric = entries.every(([key]) => /^(0|[1-9]\d*)$/.test(key));

    if (
      entries.length &&
      numeric &&
      entries.every(([key], index) => Number(key) === index)
    ) {
      return entries.map(([, item]) => item);
    }

    return Object.fromEntries(entries);
  }

  function mergeValues(target, source) {
    for (const [key, value] of Object.entries(source)) {
      if (
        value &&
        typeof value === 'object' &&
        !Array.isArray(value) &&
        target[key] &&
        typeof target[key] === 'object' &&
        !Array.isArray(target[key])
      ) {
        mergeValues(target[key], value);
      } else {
        target[key] = value;
      }
    }
  }

  if (!customElements.get(elementName)) {
    customElements.define(elementName, LegacyHtmlControl);
  }

  function register() {
    if (!window.Cp?.$components) {
      return false;
    }

    componentNames.forEach((name) =>
      window.Cp.$components.register(name, elementName)
    );

    return true;
  }

  function registerWhenCpInitializes() {
    if (register()) {
      return;
    }

    let cp = window.Cp;

    Object.defineProperty(window, 'Cp', {
      configurable: true,
      get() {
        return cp;
      },
      set(value) {
        cp = value;

        if (!register()) {
          return;
        }

        Object.defineProperty(window, 'Cp', {
          configurable: true,
          enumerable: true,
          value,
          writable: true,
        });
      },
    });
  }

  registerWhenCpInitializes();
})();
