import {afterEach, expect, it, vi} from 'vite-plus/test';
import {SlugGenerator} from './slug-generator';

class TestSlugGenerator extends SlugGenerator {}

afterEach(() => vi.unstubAllGlobals());

it('generates Unicode slugs without a regex library', () => {
  vi.stubGlobal('Craft', {
    allowUppercaseInSlug: true,
    limitAutoSlugsToAscii: false,
    slugWordSeparator: '-',
  });

  const generator = new TestSlugGenerator();

  expect(
    generator.generateTargetValue('<b>Cafe\u0301</b>—العَرَبِيَّة + foo𐐷bar 42')
  ).toBe('Cafe\u0301-العَرَبِيَّة-foo-bar-42');
});

it('applies the Craft slug settings before matching words', () => {
  vi.stubGlobal('Craft', {
    allowUppercaseInSlug: false,
    limitAutoSlugsToAscii: true,
    slugWordSeparator: '_',
  });

  const generator = new TestSlugGenerator();
  generator.setSettings({charMap: {Ø: 'Oe'}});

  expect(generator.generateTargetValue('ØRESUND𐐷TEST')).toBe('oeresund_test');
});
