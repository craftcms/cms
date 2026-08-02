import {describe, expect, it} from 'vite-plus/test';
import CraftInput from './input.js';

describe('craft-input', () => {
  it('formats server-serialized date and time values for native inputs', () => {
    const input = new CraftInput();

    input.type = 'date';
    expect(input.formatter('2026-01-02T03:04:05+00:00')).toBe('2026-01-02');

    input.type = 'time';
    expect(input.formatter('08:30:59')).toBe('08:30');
  });
});
