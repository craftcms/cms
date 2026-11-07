// Screenshot helper for control panel pages. Paste this whole file into the
// page as one JavaScript call, after setting the options on its last line.
// It saves itself and its options to localStorage, so after a full page load
// one call restores both:
//
//   (0, eval)(localStorage.__shotCode)
//
// Options, all optional, and changed later with __shot.set({...}):
//   theme      'light' or 'dark'. Held against the person's own preference,
//              which the control panel reapplies on Inertia visits.
//   inset      true to set the whole page in 24px from the window's edges,
//              out of reach of the extension's border.
//   feature    A selector for the feature to isolate; everything else is
//              hidden, over `bg`.
//   bg         The color behind an isolated feature: '#000', '#fff', or a
//              CSS value such as 'var(--c-surface-default)'.
//   css        Extra CSS for this shot.
//   keepFocus  true to keep focus rings, which ready() otherwise blurs.
//
// Call __shot.ready() right before each capture; __shot.off() when done.
(function install(options) {
  const root = document.documentElement;
  const opts = {
    theme: null,
    inset: false,
    feature: null,
    bg: null,
    css: '',
    keepFocus: false,
    ...options,
  };
  if (!('originalTheme' in opts)) {
    opts.originalTheme = window.__shot
      ? window.__shot.options.originalTheme
      : (root.dataset.theme ?? null);
  }
  const TARGET = 'SCREENSHOT-TARGET';
  let realTitle =
    document.title === TARGET ? window.__shot?.realTitle() : document.title;

  // Everything that sizes itself from the window moves in with #app: the
  // sidebar and the details pane through the variable the control panel
  // subtracts from 100dvh, and what lives outside #app explicitly.
  const INSET = `
    #app { position: fixed !important; inset: 24px !important; overflow: hidden !important; transform: translateZ(0); }
    .page-screen { --cp-debug-bar-height: 48px !important; }
    .cp-content__details-pane { --cp-debug-bar-height: 24px !important; }
    .cp-sidebar__footer { --cp-debug-bar-height: 0px !important; }
    #messages { translate: 24px -24px; }
    .live-preview { inset: 24px !important; width: auto !important; height: auto !important; overflow: hidden !important; transform: translateZ(0); }
    body:has(> .live-preview:not([style*="display: none"])) > #app { visibility: hidden !important; }
    :is(craft-dialog, craft-element-selector-modal)[fullscreen] {
      --c-dialog-inline-size: calc(100vw - 48px); --c-dialog-min-inline-size: calc(100vw - 48px); --c-dialog-max-inline-size: calc(100vw - 48px);
      --c-dialog-block-size: calc(100dvh - 48px); --c-dialog-min-block-size: calc(100dvh - 48px); --c-dialog-max-block-size: calc(100dvh - 48px);
    }
  `;

  const isolate = (feature) => `
    html, body { background: var(--shot-bg) !important; background-image: none !important; }
    body * { visibility: hidden !important; }
    ${feature}, :is(${feature}) * { visibility: visible !important; }
    craft-dialog::part(dialog)::backdrop { background: transparent !important; }
  `;

  function apply() {
    if (opts.theme && root.dataset.theme !== opts.theme) {
      root.dataset.theme = opts.theme;
    }
    let style = document.getElementById('shot-style');
    if (!style) {
      style = document.createElement('style');
      style.id = 'shot-style';
      document.head.append(style);
    }
    style.textContent = [
      'input, textarea { caret-color: transparent !important; }',
      opts.inset ? INSET : '',
      opts.feature ? isolate(opts.feature) : '',
      opts.css,
    ].join('\n');
    if (opts.bg) {
      root.style.setProperty('--shot-bg', opts.bg);
    } else {
      root.style.removeProperty('--shot-bg');
    }
    if (document.title !== TARGET) {
      realTitle = document.title;
      document.title = TARGET;
    }
    // Popovers in the top layer and measured sizes only update on resize.
    window.dispatchEvent(new Event('resize'));
  }

  function blurAll(doc) {
    let active = doc.activeElement;
    while (active?.shadowRoot?.activeElement) {
      active = active.shadowRoot.activeElement;
    }
    active?.blur?.();
    for (const frame of doc.querySelectorAll('iframe')) {
      try {
        if (frame.contentDocument) {
          blurAll(frame.contentDocument);
        }
      } catch {
        // A cross-origin frame; it has no focus ring we can reach.
      }
    }
  }

  window.__shot?.observer?.disconnect();
  const observer = new MutationObserver(() => {
    if (opts.theme && root.dataset.theme !== opts.theme) {
      root.dataset.theme = opts.theme;
    }
  });
  observer.observe(root, {attributes: true, attributeFilter: ['data-theme']});

  const save = () => {
    localStorage.__shotCode = `(${install})(${JSON.stringify(opts)})`;
  };

  window.__shot = {
    observer,
    options: opts,
    realTitle: () => realTitle,
    set(changes) {
      Object.assign(opts, changes);
      save();
      apply();
      return opts;
    },
    // Reapplies everything, since an Inertia visit can reset the theme and
    // title, and returns what origin.sh and capture.sh need. Wait about 800ms
    // after a theme change before capturing, for colors to finish transitioning.
    ready() {
      apply();
      if (!opts.keepFocus) {
        blurAll(document);
      }
      return {
        innerWidth: window.innerWidth,
        innerHeight: window.innerHeight,
        theme: root.dataset.theme ?? 'light',
      };
    },
    off() {
      observer.disconnect();
      document.getElementById('shot-style')?.remove();
      root.style.removeProperty('--shot-bg');
      if (opts.originalTheme) {
        root.dataset.theme = opts.originalTheme;
      } else {
        delete root.dataset.theme;
      }
      if (realTitle) {
        document.title = realTitle;
      }
      delete localStorage.__shotCode;
      delete window.__shot;
      window.dispatchEvent(new Event('resize'));
    },
  };

  save();
  apply();
  return opts;
})({theme: null, inset: false});
