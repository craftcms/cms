/* jshint esversion: 9, strict: false */
const {test, expect} = require('@playwright/test');
const path = require('path');

const pageErrors = new WeakMap();

test.beforeEach(async ({page}) => {
  const errors = [];
  pageErrors.set(page, errors);
  page.on('pageerror', (error) => errors.push(error.message));
});

test.afterEach(async ({page}) => {
  expect(pageErrors.get(page)).toEqual([]);
});

// Exercise Craft 5's real MatrixInput, Garnish menus, selection, and form
// observer in a browser. Persistence is covered by MatrixSiteStatusTest.php.
const assets = path.resolve(__dirname, '../../..', 'src/web/assets');

function blockHtml(
  index,
  {enabled = true, enabledForSite = true, site = 'Test site'} = {}
) {
  const statusActions = site
    ? ['disableForSite', 'enableForSite', 'disableGlobally', 'enableGlobally']
    : ['disable', 'enable'];
  const actions = ['collapse', 'expand', ...statusActions];
  return `<div class="matrixblock ${!enabled || !enabledForSite ? 'disabled-entry' : ''}"
    data-id="${index + 1}" data-ui-label="Status block"
    ${!enabled ? 'data-disabled-global' : ''}
    ${!enabledForSite ? 'data-disabled-site' : ''}
    ${site ? `data-site-name="${site}"` : ''}>
    <input type="hidden" name="matrix[entries][${index}][enabled]" value="${enabled ? '1' : ''}">
    <input type="hidden" name="matrix[entries][${index}][enabledForSite]" value="${enabledForSite ? '1' : ''}">
    <div class="titlebar"><div class="blocktype">Status block</div><div class="preview"></div></div>
    <div class="actions">
      <div class="status off"><span class="visually-hidden"></span></div>
      <div class="checkbox" role="checkbox" aria-label="Select" tabindex="0" aria-checked="false"></div>
      <button type="button" class="action-btn" aria-controls="menu-${index}" aria-expanded="false">Actions</button>
      <div class="menu hidden" id="menu-${index}"><ul>${actions
        .map(
          (action) =>
            `<li><button type="button" data-action="${action}"><span class="menu-item-label">${action}</span></button></li>`
        )
        .join('')}</ul></div>
    </div>
    <div class="fields"><input name="matrix[entries][${index}][fields][text]" value="Translation"></div>
  </div>`;
}

async function loadMatrix(page, states = [{}, {}], siteId = 1) {
  await page.route('https://matrix.test/', (route) =>
    route.fulfill({
      contentType: 'text/html',
      body: `<style>
      .hidden {display:none} .matrixblock {position:relative;min-height:40px;margin:20px}
      .actions {position:absolute;top:0;right:0;display:flex;align-items:center;gap:8px;height:30px}
      .menu {background:white;padding:10px;z-index:10} .checkbox {width:20px;height:20px;border:1px solid}
      .visually-hidden {position:absolute;width:1px;height:1px;overflow:hidden}
    </style><form><div id="matrix" class="matrix"><div class="blocks">${states.map((state, i) => blockHtml(i, state)).join('')}</div></div></form>`,
    })
  );
  await page.goto('https://matrix.test/');
  await page.addScriptTag({path: path.join(assets, 'jquery/dist/jquery.js')});
  await page.addScriptTag({
    path: path.join(assets, 'jqueryui/dist/jquery-ui.js'),
  });
  await page.addScriptTag({
    path: path.join(assets, 'velocity/dist/velocity.js'),
  });
  await page.addScriptTag({path: path.join(assets, 'garnish/dist/garnish.js')});
  // Substitute the page shell and localization/clipboard services.
  await page.evaluate(() => {
    window.Craft = {
      systemUid: 'matrix-status-test',
      cp: {getCopiedElements: () => [], onCopyElements: () => {}},
      t: (category, message, params = {}) =>
        message.replace(/\{(\w+)\}/g, (match, key) => params[key] ?? match),
      hasMousePointerEvents: () => false,
      formatInputId: (name) => name,
      filterArray: (values) => values.filter(Boolean),
      escapeHtml: (value) => {
        const el = document.createElement('span');
        el.textContent = value;
        return el.innerHTML;
      },
    };
  });
  await page.addScriptTag({
    path: path.join(assets, 'cp/src/js/FormObserver.js'),
  });
  await page.addScriptTag({
    path: path.join(assets, 'matrix/src/MatrixInput.js'),
  });
  await page.evaluate((siteId) => {
    // Conditional field layout updates require a Craft server. Keep the real
    // form observers, while substituting this unrelated server interaction.
    Craft.MatrixInput.Entry.prototype.updateFieldLayout = async () => {};
    new Craft.MatrixInput(
      'matrix',
      [{id: 1, handle: 'statusBlock'}],
      'matrix',
      {siteId}
    );
  }, siteId);
}

async function openMenu(page, index = 0) {
  const button = page.locator('.matrixblock').nth(index).locator('.action-btn');
  await button.click();
  await expect(button).toHaveAttribute('aria-expanded', 'true');
  return page.locator(`#menu-${index}`);
}

async function choose(page, action, index = 0) {
  const menu = await openMenu(page, index);
  await menu.locator(`[data-action="${action}"]`).click();
}

async function selectAll(page) {
  for (const checkbox of await page
    .getByRole('checkbox', {name: 'Select'})
    .all()) {
    await checkbox.click();
  }
}

test.use({reducedMotion: 'reduce'});

test('disabling a block only remembers its collapsed state for the current site', async ({
  page,
}) => {
  await loadMatrix(page, [{site: 'op5'}], 1);
  await choose(page, 'disableForSite');
  await expect(page.locator('.matrixblock')).toHaveClass(/collapsed/);

  await loadMatrix(page, [{site: 'op5 French'}], 3);
  await expect(page.locator('.matrixblock')).not.toHaveClass(/collapsed/);
  await expect(page.locator('input[name$="[enabledForSite]"]')).toHaveValue(
    '1'
  );

  await loadMatrix(page, [{site: 'op5', enabledForSite: false}], 1);
  await expect(page.locator('.matrixblock')).toHaveClass(/collapsed/);
  await choose(page, 'enableForSite');
  await expect(page.locator('.matrixblock')).not.toHaveClass(/collapsed/);

  await loadMatrix(page, [{site: 'op5'}], 1);
  await expect(page.locator('.matrixblock')).not.toHaveClass(/collapsed/);
});

test('expanding a block preserves the other site’s manual collapse preference', async ({
  page,
}) => {
  await loadMatrix(page, [{site: 'op5 French'}], 3);
  await choose(page, 'collapse');

  await loadMatrix(page, [{site: 'op5'}], 1);
  await expect(page.locator('.matrixblock')).not.toHaveClass(/collapsed/);
  await choose(page, 'collapse');
  await choose(page, 'expand');

  await loadMatrix(page, [{site: 'op5 French'}], 3);
  await expect(page.locator('.matrixblock')).toHaveClass(/collapsed/);
});

test('legacy collapse preferences do not leak into a site', async ({page}) => {
  await loadMatrix(page, [{site: 'op5'}], 1);
  await page.evaluate(() => {
    localStorage[Craft.MatrixInput.collapsedEntryStorageKey] = '1';
  });

  await loadMatrix(page, [{site: 'op5 French'}], 3);
  await expect(page.locator('.matrixblock')).not.toHaveClass(/collapsed/);
});

test('site status changes notify the parent form observer used for autosave', async ({
  page,
}) => {
  await loadMatrix(page);
  await page.evaluate(() => {
    window.observedMatrixChanges = [];
    new Craft.FormObserver(document.querySelector('form'), (data) => {
      window.observedMatrixChanges.push(data);
    });
  });

  await choose(page, 'disableForSite');
  await expect
    .poll(() =>
      page.evaluate(() => {
        const data = window.observedMatrixChanges.at(-1);
        if (data === undefined) {
          return null;
        }
        const params = new URLSearchParams(data);
        return [
          params.get('matrix[entries][0][enabled]'),
          params.get('matrix[entries][0][enabledForSite]'),
        ];
      })
    )
    .toEqual(['1', '']);
});

test('site and global actions update independent inputs and the disabled indicator', async ({
  page,
}) => {
  await loadMatrix(page);
  const block = page.locator('.matrixblock').first();
  const globalInput = block.locator('input[name$="[enabled]"]');
  const siteInput = block.locator('input[name$="[enabledForSite]"]');

  await choose(page, 'disableForSite');
  await expect(globalInput).toHaveValue('1');
  await expect(siteInput).toHaveValue('');
  await expect(block).toHaveClass(/disabled-entry/);
  await expect(block.locator('.status.off')).toHaveAttribute(
    'title',
    'Disabled for Test site'
  );

  await choose(page, 'enableForSite');
  await expect(siteInput).toHaveValue('1');
  await expect(block).not.toHaveClass(/disabled-entry/);

  await choose(page, 'disableForSite');
  await choose(page, 'disableGlobally');
  await expect(block.locator('.status.off')).toHaveAttribute(
    'title',
    'Disabled globally'
  );
  await choose(page, 'enableGlobally');
  await expect(globalInput).toHaveValue('1');
  await expect(siteInput).toHaveValue('');
  await expect(block).toHaveClass(/disabled-entry/);
  await expect(block.locator('.status.off .visually-hidden')).toHaveText(
    'Disabled for Test site'
  );
});

test('mixed global selections suppress site actions and resolve against the whole selection', async ({
  page,
}) => {
  await loadMatrix(page, [{enabledForSite: false}, {enabled: false}]);
  await selectAll(page);
  const menu = await openMenu(page);
  await expect(menu.locator('[data-action="disableForSite"]')).toBeHidden();
  await expect(menu.locator('[data-action="enableForSite"]')).toBeHidden();
  await expect(menu.locator('[data-action="enableGlobally"]')).toBeHidden();
  await expect(menu.locator('[data-action="disableGlobally"]')).toHaveText(
    'Disable selected blocks globally'
  );
  await menu.locator('[data-action="disableGlobally"]').click();

  for (const input of await page.locator('input[name$="[enabled]"]').all()) {
    await expect(input).toHaveValue('');
  }
  await choose(page, 'enableGlobally', 1);
  for (const input of await page.locator('input[name$="[enabled]"]').all()) {
    await expect(input).toHaveValue('1');
  }
  await expect(page.locator('.matrixblock').first()).toHaveClass(
    /disabled-entry/
  );
  await expect(page.locator('.matrixblock').nth(1)).not.toHaveClass(
    /disabled-entry/
  );
});

test('mixed site selections can be disabled and enabled together', async ({
  page,
}) => {
  await loadMatrix(page, [{enabledForSite: false}, {}]);
  await selectAll(page);
  let menu = await openMenu(page);
  await expect(menu.locator('[data-action="enableForSite"]')).toBeHidden();
  await expect(menu.locator('[data-action="disableForSite"]')).toHaveText(
    'Disable selected blocks for Test site'
  );
  await menu.locator('[data-action="disableForSite"]').click();
  menu = await openMenu(page, 1);
  await expect(menu.locator('[data-action="enableForSite"]')).toHaveText(
    'Enable selected blocks for Test site'
  );
  await menu.locator('[data-action="enableForSite"]').click();
  for (const input of await page
    .locator('input[name$="[enabledForSite]"]')
    .all()) {
    await expect(input).toHaveValue('1');
  }
  await expect(page.locator('.disabled-entry')).toHaveCount(0);
});

test('single-site actions continue to change the global flag', async ({
  page,
}) => {
  await loadMatrix(page, [{site: null}]);
  await choose(page, 'disable');
  await expect(page.locator('input[name$="[enabled]"]')).toHaveValue('');
  await expect(page.locator('input[name$="[enabledForSite]"]')).toHaveValue(
    '1'
  );
  await choose(page, 'enable');
  await expect(page.locator('input[name$="[enabled]"]')).toHaveValue('1');
  await expect(page.locator('input[name$="[enabledForSite]"]')).toHaveValue(
    '1'
  );
  await expect(page.locator('.disabled-entry')).toHaveCount(0);
});
