import {expect, it} from 'vite-plus/test';
import {
  constraintOptions,
  defaultConstraintKey,
  orientConstraint,
} from './constraints';

// Mirrors the shipped `imageEditorRatios` config.
const ratios = {
  Unconstrained: 'none',
  Original: 'original',
  Square: 1,
  '16:9': 1.78,
  '10:8': 1.25,
  '3:2': 1.5,
};

it('leaves every ratio alone in landscape', () => {
  for (const [label, ratio] of Object.entries(ratios)) {
    expect(orientConstraint(String(ratio), label, 'landscape')).toEqual({
      value: String(ratio),
      label,
    });
  }
});

it('inverts a numeric ratio and reverses its label in portrait', () => {
  expect(orientConstraint('1.78', '16:9', 'portrait')).toEqual({
    value: String(1 / 1.78),
    label: '9:16',
  });
});

it('leaves the ratios that have no orientation alone in portrait', () => {
  expect(orientConstraint('none', 'Unconstrained', 'portrait')).toEqual({
    value: 'none',
    label: 'Unconstrained',
  });
  expect(orientConstraint('original', 'Original', 'portrait')).toEqual({
    value: 'original',
    label: 'Original',
  });
});

it('reads a square the same either way', () => {
  const portrait = orientConstraint('1', 'Square', 'portrait');

  expect(parseFloat(portrait.value)).toBe(1);
  expect(portrait.label).toBe('Square');
});

it('keeps each option key stable across an orientation change', () => {
  // The regression this guards: the selection used to be tracked by value, so
  // flipping the orientation lost it and re-applied the previous ratio.
  const landscape = constraintOptions(ratios, 'landscape');
  const portrait = constraintOptions(ratios, 'portrait');

  expect(portrait.map((option) => option.key)).toEqual(
    landscape.map((option) => option.key)
  );
});

it('turns the selected ratio over when the orientation changes', () => {
  const selected = '16:9';
  const landscape = constraintOptions(ratios, 'landscape').find(
    (option) => option.key === selected
  );
  const portrait = constraintOptions(ratios, 'portrait').find(
    (option) => option.key === selected
  );

  expect(parseFloat(landscape!.value)).toBeGreaterThan(1);
  expect(parseFloat(portrait!.value)).toBeLessThan(1);
  expect(
    parseFloat(landscape!.value) * parseFloat(portrait!.value)
  ).toBeCloseTo(1, 10);
});

it('ends the list with Custom in both orientations', () => {
  for (const orientation of ['landscape', 'portrait'] as const) {
    const options = constraintOptions(ratios, orientation);

    expect(options.at(-1)?.key).toBe('custom');
  }
});

it('starts on the unconstrained option', () => {
  expect(defaultConstraintKey(ratios)).toBe('Unconstrained');
});
