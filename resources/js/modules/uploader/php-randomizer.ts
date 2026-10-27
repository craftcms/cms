/**
 * PHP's `Random\Randomizer` over a `Random\Engine\Mt19937` engine, so code
 * ported from PHP that draws seeded random numbers, such as Intervention
 * Image's k-means clustering, draws the same ones in the browser.
 *
 * Follows PHP's `ext/random`: the standard MT19937 generator, unbiased range
 * reduction for `getInt()`, and the γ-section algorithm for `getFloat()`.
 */

const STATE_SIZE = 624;
const SHIFT_SIZE = 397;
const UINT32_MAX = 0xffffffff;
const UINT64_MAX = 0xffffffffffffffffn;

class Mt19937 {
  #state = new Uint32Array(STATE_SIZE);
  #index = STATE_SIZE;

  constructor(seed: number) {
    this.#state[0] = seed >>> 0;

    for (let i = 1; i < STATE_SIZE; i++) {
      const previous = this.#state[i - 1]! ^ (this.#state[i - 1]! >>> 30);
      this.#state[i] = (Math.imul(1812433253, previous) + i) >>> 0;
    }
  }

  /** The next 32-bit output. */
  next(): number {
    if (this.#index >= STATE_SIZE) {
      this.#reload();
    }

    let value = this.#state[this.#index++]!;
    value ^= value >>> 11;
    value ^= (value << 7) & 0x9d2c5680;
    value ^= (value << 15) & 0xefc60000;
    value ^= value >>> 18;

    return value >>> 0;
  }

  #reload(): void {
    const state = this.#state;

    for (let i = 0; i < STATE_SIZE; i++) {
      const next = state[(i + 1) % STATE_SIZE]!;
      const mixed = (state[i]! & 0x80000000) | (next & 0x7fffffff);
      state[i] =
        state[(i + SHIFT_SIZE) % STATE_SIZE]! ^
        (mixed >>> 1) ^
        (next & 1 ? 0x9908b0df : 0);
    }

    this.#index = 0;
  }
}

export class PhpRandomizer {
  #engine: Mt19937;

  constructor(seed: number) {
    this.#engine = new Mt19937(seed);
  }

  /** An integer from `min` to `max`, inclusive, as `Randomizer::getInt()` returns. */
  getInt(min: number, max: number): number {
    const range = max - min;

    if (range > UINT32_MAX) {
      return min + Number(this.#range64(BigInt(range)));
    }

    return min + this.#range32(range);
  }

  /**
   * A float from `min`, inclusive, to `max`, exclusive, as
   * `Randomizer::getFloat()` returns by default.
   */
  getFloat(min: number, max: number): number {
    const gap = gammaMax(min, max);
    const high = ceilInt(min, max, gap);

    if (max <= min || high < 1n) {
      return NaN;
    }

    const step = 1n + this.#range64(high - 1n);

    if (Math.abs(min) <= Math.abs(max)) {
      if (step === high) {
        return min;
      }

      const [stepHigh, stepLow] = splitInt64(step);

      return 4 * (max / 4 - stepHigh * gap) - stepLow * gap;
    }

    const [stepHigh, stepLow] = splitInt64(step - 1n);

    return 4 * (min / 4 + stepHigh * gap) + stepLow * gap;
  }

  #range32(max: number): number {
    let result = this.#engine.next();

    if (max === UINT32_MAX) {
      return result;
    }

    const count = max + 1;

    // Powers of two divide evenly, so they need no rejection.
    if ((count & (count - 1)) === 0) {
      return (result & (count - 1)) >>> 0;
    }

    const limit = UINT32_MAX - (UINT32_MAX % count) - 1;

    while (result > limit) {
      result = this.#engine.next();
    }

    return result % count;
  }

  #range64(max: bigint): bigint {
    let result = this.#next64();

    if (max === UINT64_MAX) {
      return result;
    }

    const count = max + 1n;

    if ((count & (count - 1n)) === 0n) {
      return result & (count - 1n);
    }

    const limit = UINT64_MAX - (UINT64_MAX % count) - 1n;

    while (result > limit) {
      result = this.#next64();
    }

    return result % count;
  }

  /** Two outputs, low word first, as PHP combines them for 64-bit ranges. */
  #next64(): bigint {
    const low = BigInt(this.#engine.next());

    return low | (BigInt(this.#engine.next()) << 32n);
  }
}

/** The gap between `value` and the next float toward `direction`. */
function nextAfter(value: number, direction: number): number {
  if (value === direction) {
    return value;
  }

  if (value === 0) {
    return direction > 0 ? Number.MIN_VALUE : -Number.MIN_VALUE;
  }

  const view = new DataView(new ArrayBuffer(8));
  view.setFloat64(0, value);
  const bits = view.getBigUint64(0);
  view.setBigUint64(0, direction > value === value > 0 ? bits + 1n : bits - 1n);

  return view.getFloat64(0);
}

function gammaMax(min: number, max: number): number {
  return Math.abs(min) > Math.abs(max)
    ? nextAfter(min, Number.MAX_VALUE) - min
    : max - nextAfter(max, -Number.MAX_VALUE);
}

/** How many steps of `gap` fit between `min` and `max`, rounded up. */
function ceilInt(min: number, max: number, gap: number): bigint {
  const steps = max / gap - min / gap;
  const error =
    Math.abs(min) <= Math.abs(max)
      ? -min / gap - (steps - max / gap)
      : max / gap - (steps + min / gap);
  const ceiling = Math.ceil(steps);

  return BigInt(ceiling) + (steps === ceiling && error > 0 ? 1n : 0n);
}

function splitInt64(value: bigint): [number, number] {
  return [Number(value >> 2n), Number(value & 3n)];
}
