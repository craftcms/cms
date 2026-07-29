import {BaseInputGenerator} from './base-input-generator';

/**
 * Generates the target value from a caller-supplied callback. Port of
 * `Craft.DynamicGenerator` — the callback-based extension point plugins use for
 * custom generation without subclassing {@link BaseInputGenerator}.
 *
 * The callback is assigned before {@link BaseInputGenerator.init} runs (via the
 * `new.target` guard), so it's available the first time the target updates.
 */
export class DynamicGenerator extends BaseInputGenerator {
  callback: (sourceVal: string) => string;

  constructor(
    source?: any,
    target?: any,
    callback: (sourceVal: string) => string = (sourceVal) => sourceVal
  ) {
    super(source, target);
    this.callback = callback;
    if (new.target === DynamicGenerator) {
      this.init(source, target);
    }
  }

  override generateTargetValue(sourceVal: string): string {
    return this.callback(sourceVal);
  }
}
