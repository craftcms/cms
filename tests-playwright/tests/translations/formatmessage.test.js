/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test.beforeEach(async ({craftDashboard}) => {
  await craftDashboard.goTo();
});

// Craft.formatMessage() has to read plural and select options that contain
// commas, such as nested arguments, the way PHP's ICU formatter does.
// See https://github.com/craftcms/cms/issues/21100
test.describe('Craft.formatMessage()', () => {
  const cases = [
    {
      name: 'select nested in a plural option',
      pattern:
        'Möchten Sie {numElements, plural, =1{{type, select, Eintrag{diesen Eintrag} Asset{dieses Asset} other{dieses Element ({type})}}} other{diese {type}}} wirklich löschen?',
      args: {numElements: 1, type: 'Eintrag'},
      expected: 'Möchten Sie diesen Eintrag wirklich löschen?',
    },
    {
      name: 'select nested in a plural option, falling back to other',
      pattern:
        'Möchten Sie {numElements, plural, =1{{type, select, Eintrag{diesen Eintrag} Asset{dieses Asset} other{dieses Element ({type})}}} other{diese {type}}} wirklich löschen?',
      args: {numElements: 1, type: 'Kategorie'},
      expected: 'Möchten Sie dieses Element (Kategorie) wirklich löschen?',
    },
    {
      name: 'select nested in a plural option, other count',
      pattern:
        'Möchten Sie {numElements, plural, =1{{type, select, Eintrag{diesen Eintrag} Asset{dieses Asset} other{dieses Element ({type})}}} other{diese {type}}} wirklich löschen?',
      args: {numElements: 3, type: 'Einträge'},
      expected: 'Möchten Sie diese Einträge wirklich löschen?',
    },
    {
      name: 'commas in plural option text',
      pattern:
        '{count, plural, =1{One task can’t run, so set it up or disable it:} other{# tasks can’t run, so set them up or disable them:}}',
      args: {count: 4},
      expected: '4 tasks can’t run, so set them up or disable them:',
    },
    {
      name: 'number nested in a plural option',
      pattern: '{num, plural, =1{Last day} other{Last {num, number} days}}',
      args: {num: 7},
      expected: 'Last 7 days',
    },
    {
      name: 'number before a plural',
      pattern: '{num, number} {num, plural, =1{result} other{results}}',
      args: {num: 1},
      expected: '1 result',
    },
  ];

  for (const {name, pattern, args, expected} of cases) {
    test(name, async ({page}) => {
      const result = await page.evaluate(
        ({pattern, args}) => Craft.formatMessage(pattern, args),
        {pattern, args}
      );
      expect(result).toBe(expected);
    });
  }
});
