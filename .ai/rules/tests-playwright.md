---
paths:
  - 'tests-playwright/**'
---

# Playwright tests

## Use the Craft Playwright entry point
Import `test` and `expect` from the relative `tests-playwright/index.js` entry
point, not directly from `@playwright/test`. The entry point provides the Craft
fixture extensions; `playwright.config.js` provides the repository config.

Run tests through `pnpm exec craft-playwright test <path>`. The wrapper prepares
and tears down its DDEV environment around the Playwright run.

## Keep browser tests behavioral
Assert user-visible navigation, state, and accessibility behavior. Prefer role,
label, and stable application locators over incidental DOM structure. Reuse the
Craft fixtures before adding setup through the browser UI.
