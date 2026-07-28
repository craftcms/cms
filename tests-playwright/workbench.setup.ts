import {expect, test as setup} from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const authFile = path.join(__dirname, '.auth/user.json');
const hotFile = path.join(__dirname, '../cms-assets/resources/hot');

// CP assets are resolved through the Vite hot file when one exists. A stale
// hot file (left behind by a killed `npm run dev`) makes every CP page load
// with dead assets, so fail fast with a useful message instead.
setup('vite assets are resolvable', async ({playwright}) => {
  if (!fs.existsSync(hotFile)) {
    return; // built manifest will be used
  }

  const devServer = fs.readFileSync(hotFile, 'utf8').trim();
  const request = await playwright.request.newContext({ignoreHTTPSErrors: true});

  try {
    const response = await request.get(`${devServer}/@vite/client`, {timeout: 5_000});
    expect(response.ok(), `Vite dev server at ${devServer} responded ${response.status()}`).toBe(true);
  } catch {
    throw new Error(
      `A Vite hot file exists (${hotFile}) pointing at ${devServer}, but that dev server ` +
        'is not reachable, so CP pages will render without assets. Either start `npm run dev` ' +
        'or delete the stale hot file.'
    );
  } finally {
    await request.dispose();
  }
});

// The workbench exposes /_workbench/login/{userId} (session login, no
// credentials needed). User 1 is the admin created by the workbench seeder.
setup('authenticate', async ({page}) => {
  await page.goto('/_workbench/login/1');

  const response = await page.request.get('/_workbench/user');
  expect(await response.json()).toMatchObject({id: 1});

  await page.context().storageState({path: authFile});
});
