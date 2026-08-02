(() => {
  const elementName = 'craft-legacy-settings-island';
  const rendererName = 'form-element:yii2-adapter:legacy-settings';
  const ownedAssets = new Map();

  class LegacySettingsIsland extends HTMLElement {
    #fragment = null;
    #fragmentKey = '';
    #disposers = [];
    #runId = 0;
    #form = null;
    #mounted = false;
    #mounting = false;

    set fragment(fragment) {
      const key = fragmentKey(fragment);

      if (key === this.#fragmentKey) {
        return;
      }

      const state = this.#mounted || this.#mounting ? this.#serialize() : null;
      this.#fragment = fragment;
      this.#fragmentKey = key;

      if (this.isConnected) {
        void this.#mount(state);
      }
    }

    connectedCallback() {
      if (!this.#fragment && this.hasAttribute('data-fragment')) {
        this.#fragment = JSON.parse(this.dataset.fragment);
        this.#fragmentKey = fragmentKey(this.#fragment);
      }

      this.#form = this.closest('form');
      this.#form?.addEventListener('submit', this.#handleSubmit, true);

      if (this.#fragment && !this.#mounted) {
        void this.#mount();
      }
    }

    disconnectedCallback() {
      this.#form?.removeEventListener('submit', this.#handleSubmit, true);
      this.#form = null;
      this.#runId++;
      this.#dispose();
    }

    #handleSubmit = () => {
      this.#serialize();
    };

    async #mount(state = null) {
      const runId = ++this.#runId;

      this.#dispose();

      if (!this.#fragment) {
        return;
      }

      this.#mounting = true;

      if (
        !(await this.#append(this.#fragment.headHtml, document.head, runId))
      ) {
        return;
      }

      if (!(await this.#append(this.#fragment.html, this, runId))) {
        return;
      }

      if (
        !(await this.#append(this.#fragment.bodyHtml, document.body, runId))
      ) {
        return;
      }

      if (state) {
        restoreFormInputs(this, state);
      }

      window.Craft?.initUiElements?.(this);
      this.#mounting = false;
      this.#mounted = true;
    }

    async #append(html, parent, runId) {
      if (!html) {
        return true;
      }

      const dispose = await appendHtml(html, parent);

      if (runId !== this.#runId) {
        dispose();

        return false;
      }

      this.#disposers.push(dispose);

      return true;
    }

    #serialize() {
      const {states, values} = readFormInputs(this);

      this.dispatchEvent(
        new CustomEvent('legacy-settings:serialized', {
          bubbles: true,
          detail: values,
        })
      );

      return states;
    }

    #dispose() {
      this.#mounted = false;
      this.#mounting = false;

      while (this.#disposers.length) {
        this.#disposers.pop()?.();
      }
    }
  }

  function fragmentKey(fragment) {
    return fragment
      ? `${fragment.headHtml ?? ''}\u0000${fragment.html ?? ''}\u0000${fragment.bodyHtml ?? ''}`
      : '';
  }

  async function appendHtml(html, parent) {
    const template = document.createElement('template');
    const appended = [];
    const releases = [];

    template.innerHTML = html.trim();

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

      let node = source.cloneNode(true);
      let scriptLoaded = null;

      if (source instanceof HTMLScriptElement) {
        node = document.createElement('script');
        for (const attribute of source.attributes) {
          node.setAttribute(attribute.name, attribute.value);
        }
        node.textContent = source.textContent;

        if (node.src) {
          node.async = false;
          scriptLoaded = new Promise((resolve) => {
            node.addEventListener('load', resolve, {once: true});
            node.addEventListener('error', resolve, {once: true});
          });
        }
      }

      parent.appendChild(node);

      if (asset) {
        const ownedAsset = {
          node,
          loaded: scriptLoaded ?? Promise.resolve(),
          references: 1,
        };

        ownedAssets.set(asset.key, ownedAsset);
        releases.push(() => releaseAsset(asset.key, ownedAsset));
      } else {
        appended.push(node);
      }

      if (scriptLoaded) {
        await scriptLoaded;
      }
    }

    return () => {
      for (const node of appended) {
        node.remove();
      }

      for (const release of releases) {
        release();
      }
    };
  }

  function assetDetails(element) {
    if (element instanceof HTMLLinkElement && element.href) {
      return {
        key: `link:${element.href}`,
        selector: 'link[href]',
        property: 'href',
        value: element.href,
      };
    }

    if (element instanceof HTMLScriptElement && element.src) {
      return {
        key: `script:${element.src}`,
        selector: 'script[src]',
        property: 'src',
        value: element.src,
      };
    }

    return null;
  }

  function releaseAsset(key, asset) {
    asset.references--;

    if (asset.references !== 0) {
      return;
    }

    asset.node.remove();
    ownedAssets.delete(key);
  }

  function hasAsset(selector, property, value) {
    return Array.from(document.querySelectorAll(selector)).some(
      (element) => element[property] === value
    );
  }

  function formControls(container) {
    return container.querySelectorAll(
      'input[name], select[name], textarea[name]'
    );
  }

  function readFormInputs(container) {
    const values = new URLSearchParams();
    const states = [];

    for (const control of formControls(container)) {
      if (control instanceof HTMLInputElement && control.type === 'file') {
        continue;
      }

      if (
        control instanceof HTMLInputElement &&
        ['checkbox', 'radio'].includes(control.type)
      ) {
        states.push({name: control.name, checked: control.checked});

        if (!control.disabled && control.checked) {
          values.append(control.name, control.value);
        }

        continue;
      }

      if (control instanceof HTMLSelectElement && control.multiple) {
        const selectedValues = Array.from(
          control.selectedOptions,
          (option) => option.value
        );

        states.push({name: control.name, values: selectedValues});

        if (!control.disabled) {
          for (const value of selectedValues) {
            values.append(control.name, value);
          }
        }

        continue;
      }

      states.push({name: control.name, value: control.value});

      if (
        control.disabled ||
        (control instanceof HTMLInputElement &&
          ['submit', 'button', 'reset', 'image'].includes(control.type))
      ) {
        continue;
      }

      values.append(control.name, control.value);
    }

    return {states, values};
  }

  function restoreFormInputs(container, states) {
    const indexes = new Map();
    const statesByName = new Map();

    for (const state of states) {
      const matchingStates = statesByName.get(state.name) ?? [];

      matchingStates.push(state);
      statesByName.set(state.name, matchingStates);
    }

    for (const control of formControls(container)) {
      const index = indexes.get(control.name) ?? 0;
      const state = statesByName.get(control.name)?.[index];

      indexes.set(control.name, index + 1);

      if (!state) {
        continue;
      }

      if (
        control instanceof HTMLInputElement &&
        ['checkbox', 'radio'].includes(control.type)
      ) {
        control.checked = state.checked;

        continue;
      }

      if (control instanceof HTMLSelectElement && control.multiple) {
        for (const option of control.options) {
          option.selected = state.values.includes(option.value);
        }

        continue;
      }

      control.value = state.value;
    }
  }

  if (!customElements.get(elementName)) {
    customElements.define(elementName, LegacySettingsIsland);
  }

  function registerRenderer() {
    if (!window.Cp?.$components) {
      return false;
    }

    window.Cp.$components.register(rendererName, elementName);

    return true;
  }

  function registerWhenCpInitializes() {
    if (registerRenderer()) {
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

        if (!registerRenderer()) {
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
