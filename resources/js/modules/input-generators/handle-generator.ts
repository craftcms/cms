import {toHandle} from '@craftcms/ui/utilities/string';
import {BaseInputGenerator} from './base-input-generator';

declare const Craft: any;

/**
 * Generates a handle from a source value. Port of `Craft.HandleGenerator`.
 *
 * The transform is the shared `toHandle` from `@craftcms/ui` — the same function
 * the Vue settings pages use via `useInputGenerator` — so DOM-driven (legacy
 * Twig) and Vue-driven surfaces stay in lockstep. This class only supplies the
 * source/target orchestration (see {@link BaseInputGenerator}).
 */
export class HandleGenerator extends BaseInputGenerator {
  constructor(source?: any, target?: any, settings?: any) {
    super(source, target, settings);
    if (new.target === HandleGenerator) {
      this.init(source, target, settings);
    }
  }

  override generateTargetValue(sourceVal: string): string {
    return toHandle(sourceVal, {
      allowNonAlphaStart: this.settings!.allowNonAlphaStart ?? false,
      handleCasing: Craft.handleCasing,
    });
  }
}
