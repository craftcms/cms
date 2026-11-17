import {describe, expect, it} from 'vite-plus/test';
import {formatAge} from './history';

describe('formatAge', () => {
  it('gives an age in its largest whole unit', () => {
    const now = new Date(2026, 9, 24, 12);

    expect(formatAge(new Date(2026, 9, 24, 11, 49), now).long).toBe(
      '11 minutes ago'
    );
    expect(formatAge(new Date(2026, 9, 24, 9, 30), now).long).toBe(
      '2 hours ago'
    );
    expect(formatAge(new Date(2026, 9, 23, 11), now).long).toBe('yesterday');
    expect(formatAge(new Date(2026, 9, 10, 12), now).long).toBe('2 weeks ago');
  });
});
