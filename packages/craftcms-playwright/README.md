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

- it uses the same fixtures mechanism as our codeception tests (`*Fixture.php` files from `<cms repo directory>/tests/fixtures` are copied over to the docker env and have their namespace adjusted)
  - I just had to make small tweaks so that logging works here too (`src/test/ActiveFixture.php`, `src/test/fixtures/elements/BaseElementFixture.php`; commit: https://github.com/craftcms/cms/commit/b6407e68ab63a21550f9367b1712311108c7a444)
  - the fixture data for the playwright tests is separate to the data for the codeception tests, and it sits in `<cms repo directory>/packages/craft-playwright/fixtures`; at this point it's a copy of `<cms repo directory>/tests/fixtures/data` with some tweaks
- it uses php alpine docker image and there's configuration for both mysql and pgsql; mysql is on by default, but you can switch to pgsql by editing `<cms repo directory>/tests-playwright/.env` and `<cms repo directory>/packages/craft-playwright/docker-compose.yaml`
- tests are located here: `<cms repo directory>/tests-playwright/`

> [!TIP]
> For tests on pages that use `ElementEditor`, use `.pressSequentially('text', { delay: 100 })` instead of `.fill('text')` because we have custom keyboard handling. Using `fill` will cause the tests to be flaky (the fact that text was written won't always be acknowledged). 