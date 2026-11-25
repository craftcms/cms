# Playwright tests setup for Craft CMS

At the moment, you can run the commands listed under Usage on your host machine. You'll need docker, node and ideally nvm.

## Installation
- pull the branch
- go to where your cms repo lives (e.g. packages/cms)
- run `nvm use`
- run `npm ci`
- run `npx playwright install`
- go to your `cms` repo location and into `tests-playwright` directory, copy `.env.example` file to `.env` (and adjust if needed)

## Usage

All commands should be run from the cms repo’s location
- run `npx craft-playwright test` to boot up docker environment, install Craft CMS in it, run all the tests and shut down the docker environment
- run `npx craft-playwright test elementindex/sorting` to boot up docker environment, install Craft CMS in it, run only “elementindex/sorting” tests and shut down the docker environment
- `npx craft-playwright boot` can be used to set up the docker env & install Craft CMS in it. 
  - After which you can run `npx playwright test` to run tests.
  - You can run specific tests via `npx playwright test elements/inituielements`
  - To shut down the testing environment, use `npx craft-playwright down`.
- You can add the `--ui` flag to tun tests in interactive UI mode
- You can add the `--debug` flag to tun tests in interactive UI mode with debugger that lets you step over the test line by line

- to start test generator
  - run `npx craft-playwright boot`
  - run `npx playwright codegen playwright.ddev.site/admin`


## Notes

- it uses the same fixtures mechanism as our Codeception tests (`*Fixture.php` files from `<cms repo directory>/tests/fixtures` are copied over to the ddev env and have their namespace adjusted)
- it uses ddev with MySQL 8 as a default, but can be switched to PostgreSQL by editing `<cms repo directory>/tests-playwright/ddev-config/config.local.yaml` file; remember to also check your `<cms repo directory>/tests-playwright/.env` file; 
- tests are located here: `<cms repo directory>/tests-playwright/tests`

> [!TIP]
> For tests on pages that use `ElementEditor`, use `.pressSequentially('text', { delay: 100 })` instead of `.fill('text')` because we have custom keyboard handling. Using `fill` will cause the tests to be flaky (the fact that text was written won't always be acknowledged). 