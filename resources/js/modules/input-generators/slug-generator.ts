import {asciiString} from '@craftcms/ui/utilities/string';
import {BaseInputGenerator} from './base-input-generator';

declare const Craft: any;

/**
 * Generates a slug from a source value. Port of `Craft.SlugGenerator`.
 *
 * Unlike handle/URI generation, slug generation has no shared `@craftcms/ui`
 * transform because its live `Craft.*` config (`slugWordSeparator`,
 * `limitAutoSlugsToAscii`, `allowUppercaseInSlug`) doesn't belong in the
 * component package. It still reuses the shared `asciiString`.
 */
export class SlugGenerator extends BaseInputGenerator {
  constructor(source?: any, target?: any, settings?: any) {
    super(source, target, settings);
    if (new.target === SlugGenerator) {
      this.init(source, target, settings);
    }
  }

  override generateTargetValue(sourceVal: string): string {
    // Remove HTML tags
    sourceVal = sourceVal.replace(/<(.*?)>/g, '');

    // Remove inner-word punctuation
    sourceVal = sourceVal.replace(/['"‘’“”ʻ[\](){}:]/g, '');

    if (Craft.limitAutoSlugsToAscii) {
      // Convert extended ASCII characters to basic ASCII
      sourceVal = asciiString(sourceVal, this.settings!.charMap ?? undefined);
    }

    // Make it lowercase
    if (!Craft.allowUppercaseInSlug) {
      sourceVal = sourceVal.toLowerCase();
    }

    // Get the "words". Keep XRegExp's previous BMP-only Unicode matching.
    const words =
      sourceVal.match(/(?:(?![\u{10000}-\u{10FFFF}])[\p{L}\p{N}\p{M}])+/gu) ??
      [];

    return words.join(Craft.slugWordSeparator);
  }
}
