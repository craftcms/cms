import {describe, expect, it} from 'vite-plus/test';
import {emphasisParts, stripEmphasis} from './emphasis';

describe('emphasisParts', () => {
  it.each([
    [
      'The *Date* field is required.',
      [
        {text: 'The ', strong: false},
        {text: 'Date', strong: true},
        {text: ' field is required.', strong: false},
      ],
    ],
    [
      '*Time* must be no later than 6:00 PM.',
      [
        {text: 'Time', strong: true},
        {text: ' must be no later than 6:00 PM.', strong: false},
      ],
    ],
    [
      'Fix *Blocks* and *Authors*.',
      [
        {text: 'Fix ', strong: false},
        {text: 'Blocks', strong: true},
        {text: ' and ', strong: false},
        {text: 'Authors', strong: true},
        {text: '.', strong: false},
      ],
    ],
    ['Title is required.', [{text: 'Title is required.', strong: false}]],
    ['Use * as a wildcard.', [{text: 'Use * as a wildcard.', strong: false}]],
    [
      'Spans *two\nlines* here.',
      [{text: 'Spans *two\nlines* here.', strong: false}],
    ],
  ])('splits %j', (message, expected) => {
    expect(emphasisParts(message)).toEqual(expected);
  });
});

describe('stripEmphasis', () => {
  it.each([
    ['The *Date* field is required.', 'The Date field is required.'],
    ['Fix *Blocks* and *Authors*.', 'Fix Blocks and Authors.'],
    ['Use * as a wildcard.', 'Use * as a wildcard.'],
  ])('strips %j', (message, expected) => {
    expect(stripEmphasis(message)).toBe(expected);
  });
});
