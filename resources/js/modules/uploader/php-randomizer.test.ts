import {describe, expect, it} from 'vite-plus/test';
import {PhpRandomizer} from './php-randomizer';

/** What PHP's `new Randomizer(new Mt19937(1024))` returns for the same calls, in order. */
describe('PhpRandomizer', () => {
  it('draws the same integers and floats as PHP', () => {
    const random = new PhpRandomizer(1024);

    expect(
      [1200, 7, 1, 2, 3, 1000, 10000, 4294967295, 65536].map((count) =>
        random.getInt(0, count - 1)
      )
    ).toEqual([267, 1, 0, 0, 2, 201, 2641, 2491341008, 14669]);
    expect(
      [1.0, 0.5, 12.345678, 1e-9, 3000.25, 0.1234567].map((max) =>
        random.getFloat(0, max)
      )
    ).toEqual([
      0.7181076405237795, 0.48294816350307174, 0.8925998591647115,
      6.688816586697786e-10, 557.2672752091848, 0.07694217769856565,
    ]);
  });
});
