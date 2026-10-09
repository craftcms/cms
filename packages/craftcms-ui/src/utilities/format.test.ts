import {describe, test, expect} from 'vite-plus/test';
import {formatNumber} from './format.js';

describe('formatNumber', () => {
  describe('default formatting (no format string)', () => {
    test.for([
      // Large numbers with thousand separators
      [1000000000, undefined, '1,000,000,000'],
      [1234567, undefined, '1,234,567'],
      [1000, undefined, '1,000'],

      // Small numbers
      [100, undefined, '100'],
      [42, undefined, '42'],
      [0, undefined, '0'],

      // Negative numbers
      [-1000, undefined, '-1,000'],
      [-42, undefined, '-42'],

      // Decimal numbers (should truncate to integer)
      [1234.56, undefined, '1,235'],
      [99.9, undefined, '100'],
      [1.5, undefined, '2'],

      // String numbers
      ['1000', undefined, '1,000'],
      ['1234567.89', undefined, '1,234,568'],

      // Invalid strings (should return as-is)
      ['cheese', undefined, 'cheese'],
      ['not a number', undefined, 'not a number'],
      ['', undefined, ''],
    ])('formatNumber(%s, %s) -> %s', ([input, format, expected]) => {
      expect(
        formatNumber(input as string | number, format as string | undefined)
      ).toBe(expected);
    });
  });

  describe('d3 format patterns', () => {
    test.for([
      // With thousand separators, no decimals
      [1234567, ',.0f', '1,234,567'],
      [1000, ',.0f', '1,000'],

      // With thousand separators and decimals
      [1234.5678, ',.2f', '1,234.57'],
      [1000000, ',.2f', '1,000,000.00'],
      [99.9, ',.1f', '99.9'],
      [1.23456, ',.4f', '1.2346'],

      // Without thousand separators
      [1234567, '.0f', '1234567'],
      [1234.56, '.2f', '1234.56'],
      [99.999, '.1f', '100.0'],

      // Various decimal precisions
      [3.14159, '.0f', '3'],
      [3.14159, '.1f', '3.1'],
      [3.14159, '.2f', '3.14'],
      [3.14159, '.3f', '3.142'],
      [3.14159, '.5f', '3.14159'],

      // Zero decimal places
      [12345.6789, ',.0f', '12,346'],
      [999.5, '.0f', '1000'],
    ])('formatNumber(%s, %s) -> %s', ([input, format, expected]) => {
      expect(
        formatNumber(input as string | number, format as string | undefined)
      ).toBe(expected);
    });
  });

  describe('edge cases', () => {
    test.for([
      // Zero
      [0, ',.2f', '0.00'],
      [0, '.0f', '0'],

      // Very large numbers
      [999999999999, undefined, '999,999,999,999'],
      [999999999999, ',.2f', '999,999,999,999.00'],

      // Very small decimals
      [0.001, '.3f', '0.001'],
      [0.0001, '.4f', '0.0001'],
      [0.12345, '.2f', '0.12'],

      // Negative numbers with formats
      [-1234.56, ',.2f', '-1,234.56'],
      [-999, '.0f', '-999'],
      [-1000, ',.0f', '-1,000'],

      // String inputs with formats
      ['1234.56', ',.2f', '1,234.56'],
      ['999', '.2f', '999.00'],
    ])('formatNumber(%s, %s) -> %s', ([input, format, expected]) => {
      expect(
        formatNumber(input as string | number, format as string | undefined)
      ).toBe(expected);
    });
  });
});
