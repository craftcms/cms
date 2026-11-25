/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

const createSlideout = async (page, transition = null) => {
  await page.evaluate((transition) => {
    const $underlyingButton = window
      .jQuery(
        '<button id="slideout-underlying-button" type="button">Underlying button</button>'
      )
      .appendTo(document.body);

    window.slideoutUnderlyingClicks = 0;
    $underlyingButton.on('click', () => {
      window.slideoutUnderlyingClicks++;
    });

    window.slideoutCloseEvents = 0;
    window.testSlideout = new window.Craft.Slideout(
      '<button type="button">Slideout button</button>',
      {triggerElement: $underlyingButton}
    );
    window.testSlideout.on('close', () => {
      window.slideoutCloseEvents++;
    });

    if (transition) {
      window.testSlideout.$shade.css('transition', transition);
      window.testSlideout.$container.css('transition', transition);
    }
  }, transition);
};

const slideoutState = (page) =>
  page.evaluate(() => ({
    closeEvents: window.slideoutCloseEvents,
    containerHidden: window.testSlideout.$outerContainer.hasClass('hidden'),
    isOpen: window.testSlideout.isOpen,
    shadeDisplay: window.testSlideout.$shade.css('display'),
  }));

test.beforeEach(async ({page}) => {
  await page.goto('./dashboard');
});

test('completes the normal close lifecycle once', async ({page}) => {
  await createSlideout(page);

  await page.evaluate(() => window.testSlideout.close());

  await expect
    .poll(() => slideoutState(page))
    .toEqual({
      closeEvents: 1,
      containerHidden: true,
      isOpen: false,
      shadeDisplay: 'none',
    });

  await page.waitForTimeout(100);
  await expect(page.evaluate(() => window.slideoutCloseEvents)).resolves.toBe(
    1
  );
});

test('falls back when an effective transition emits no event', async ({
  page,
}) => {
  await createSlideout(page, 'transform 100ms linear');

  await page.evaluate(() => window.testSlideout.close());

  await expect
    .poll(() => slideoutState(page))
    .toEqual({
      closeEvents: 1,
      containerHidden: true,
      isOpen: false,
      shadeDisplay: 'none',
    });

  await page.locator('#slideout-underlying-button').click();
  await expect(
    page.evaluate(() => window.slideoutUnderlyingClicks)
  ).resolves.toBe(1);
});

test('cancels stale close completion when reopened', async ({page}) => {
  await createSlideout(page, 'transform 250ms linear');

  await page.evaluate(() => {
    window.testSlideout.close();
    window.testSlideout.open();
  });

  await page.waitForTimeout(400);

  await expect(slideoutState(page)).resolves.toEqual({
    closeEvents: 0,
    containerHidden: false,
    isOpen: true,
    shadeDisplay: 'block',
  });
  await expect(
    page.locator('[data-slideout] button', {hasText: 'Slideout button'})
  ).toBeEnabled();
});

test('completes cleanup immediately with reduced motion', async ({page}) => {
  await page.emulateMedia({reducedMotion: 'reduce'});
  await createSlideout(page);

  await page.evaluate(() => window.testSlideout.close());

  await expect(slideoutState(page)).resolves.toEqual({
    closeEvents: 1,
    containerHidden: true,
    isOpen: false,
    shadeDisplay: 'none',
  });
});
