type ElementLike = Element | ArrayLike<Element>;

function toElements(target: ElementLike | null | undefined): Element[] {
  if (!target) {
    return [];
  }

  return target instanceof Element ? [target] : Array.from(target);
}

function isVisible(element: Element): boolean {
  return element.getClientRects().length > 0;
}

/** Lists errors under a field, replacing any it was already showing. */
export function addErrorsToField(
  field: ElementLike,
  errors: string | string[] | null | undefined
): void {
  if (!errors) {
    return;
  }

  clearErrorsFromField(field);

  for (const el of toElements(field)) {
    el.classList.add('has-errors');
    el.querySelector(':scope > .input')?.classList.add('errors', 'prevalidate');

    const list = document.createElement('ul');
    list.className = 'errors';
    list.tabIndex = -1;
    if (el.id) {
      list.id = el.id.replace(/-field$/, '-errors');
    }
    // <craft-field> only renders feedback that's slotted in
    if (el.localName === 'craft-field') {
      list.slot = 'feedback';
    }

    for (const error of Array.isArray(errors) ? errors : [errors]) {
      const item = document.createElement('li');
      item.textContent = error.split('*').join('');
      list.append(item);
    }

    el.append(list);
  }
}

export function clearErrorsFromField(field: ElementLike): void {
  for (const el of toElements(field)) {
    el.classList.remove('has-errors');
    el.querySelector(':scope > .input')?.classList.remove(
      'errors',
      'prevalidate'
    );
    el.querySelectorAll(':scope > ul.errors').forEach((list) => list.remove());
  }
}

export function clearErrorSummary(container: ElementLike): void {
  for (const el of toElements(container)) {
    el.querySelectorAll('.error-summary').forEach((summary) =>
      summary.remove()
    );
  }
}

/**
 * Focuses the error summary, and makes its links jump to the field each error
 * belongs to.
 */
export function setFocusOnErrorSummary(container: ElementLike): void {
  for (const el of toElements(container)) {
    const summary = el.querySelector<HTMLElement>('.error-summary');
    if (!summary) {
      continue;
    }

    summary.focus();
    summary.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', (ev) => {
        if (!link.classList.contains('cross-site-validate')) {
          ev.preventDefault();
          focusFieldForError(link, el);
        }
      });
    });
  }
}

/** The tab links whose panels contain the given field. */
export function findTabAnchorForField(
  field: ElementLike,
  container: ElementLike
): HTMLElement[] {
  const anchors: HTMLElement[] = [];
  const [start] = toElements(field);
  const roots = toElements(container);
  let panel = start?.parentElement?.closest<HTMLElement>(
    'div[data-id^="tab"][role="tabpanel"]'
  );

  while (panel) {
    for (const root of roots) {
      const anchor = root.querySelector<HTMLElement>(
        `[role="tablist"] a[href="#${CSS.escape(panel.id)}"]`
      );
      if (anchor) {
        anchors.push(anchor);
      }
    }
    panel = panel.parentElement?.closest<HTMLElement>(
      'div[data-id^="tab"][role="tabpanel"]'
    );
  }

  return anchors;
}

function focusFieldForError(link: Element, container: Element): void {
  const key = link.getAttribute('data-field-error-key');
  if (!key) {
    return;
  }

  const errors = container.querySelector(
    `[data-error-key="${CSS.escape(key)}"] ul.errors`
  );
  if (!errors) {
    return;
  }

  for (const anchor of findTabAnchorForField(errors, container)) {
    if (anchor.getAttribute('aria-selected') === 'false') {
      anchor.click();
    }
  }

  expandCollapsedParents(errors);

  const field = errors.closest<HTMLElement>('.field');
  if (!field) {
    return;
  }

  const focus = () => {
    field.tabIndex = -1;
    field.focus();
  };

  if (isVisible(field)) {
    focus();
  } else {
    // Collapsed blocks take a moment to expand
    setTimeout(focus, 201);
  }
}

// Collapsed legacy blocks keep their controller in jQuery data, or expand on a
// double-tap of their title bar.
function expandCollapsedParents(element: Element): void {
  const $ = (window as {jQuery?: any}).jQuery;
  const collapsed: Element[] = [];
  let parent = element.parentElement?.closest(
    '.collapsed, .is-collapsed, [data-collapsed]'
  );

  while (parent) {
    collapsed.push(parent);
    parent = parent.parentElement?.closest(
      '.collapsed, .is-collapsed, [data-collapsed]'
    );
  }

  if (!$) {
    return;
  }

  // Outermost first
  for (const item of collapsed.reverse()) {
    const block = $(item).data('block');
    if (block) {
      block.expand();
    } else {
      $(item)
        .find('.titlebar, [data-matrix-block-titlebar]')
        .trigger('doubletap');
    }
  }
}
