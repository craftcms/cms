import {toUriFormat} from '@craftcms/ui/utilities/string';
import {BaseInputGenerator} from './base-input-generator';

/**
 * Generates a URI format from a source value. Port of `Craft.UriFormatGenerator`.
 *
 * Delegates to the shared `toUriFormat` from `@craftcms/ui`. Note: `toUriFormat`
 * joins words with `-`, whereas the legacy class used `Craft.slugWordSeparator`.
 * `-` is the modern canonical (what a Vue-migrated URI-format field produces), so
 * this aligns the transitional DOM boot with that behavior.
 */
export class UriFormatGenerator extends BaseInputGenerator {
  constructor(source?: any, target?: any, settings?: any) {
    super(source, target, settings);
    if (new.target === UriFormatGenerator) {
      this.init(source, target, settings);
    }
  }

  override generateTargetValue(sourceVal: string): string {
    return toUriFormat(sourceVal);
  }
}
