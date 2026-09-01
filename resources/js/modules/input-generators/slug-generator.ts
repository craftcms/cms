import {asciiString} from '@craftcms/ui/utilities/string';
import {BaseInputGenerator} from './base-input-generator';

declare const Craft: any;
declare const XRegExp: any;

/**
 * Generates a slug from a source value. Port of `Craft.SlugGenerator`.
 *
 * Unlike handle/URI generation, slug generation has no shared `@craftcms/ui`
 * transform: it depends on the page-global `XRegExp` (unicode word matching) and
 * live `Craft.*` config (`slugWordSeparator`, `limitAutoSlugsToAscii`,
 * `allowUppercaseInSlug`), which don't belong in the component package — so the
 * transform stays here. It still reuses the shared `asciiString`.
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

    // Get the "words". Split on anything that is not alphanumeric.
    // Reference: http://www.regular-expressions.info/unicode.html
    const words: string[] = XRegExp.matchChain(sourceVal, [
      XRegExp('[\\p{L}\\p{N}\\p{M}]+'),
    ]).filter(Boolean);

    if (words.length) {
      return words.join(Craft.slugWordSeparator);
    } else {
      return '';
    }
  }
}
