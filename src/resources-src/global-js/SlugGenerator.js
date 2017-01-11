/** global: Craft */
/** global: Garnish */
/**
 * Slug Generator
 */
Craft.SlugGenerator = Craft.BaseInputGenerator.extend(
    {
        generateTargetValue: function(sourceVal) {
            // Remove HTML tags
            sourceVal = sourceVal.replace(/<(.*?)>/g, '');

            // Remove inner-word punctuation
            sourceVal = sourceVal.replace(/['"‘’“”\[\]\(\)\{\}:]/g, '');

            // Make it lowercase
            sourceVal = sourceVal.toLowerCase();

            if (Craft.limitAutoSlugsToAscii) {
                // Convert extended ASCII characters to basic ASCII
                sourceVal = Craft.asciiString(sourceVal);
            }

            // Get the "words". Split on anything that is not alphanumeric.
            // Reference: http://www.regular-expressions.info/unicode.html
            var words = Craft.filterArray(XRegExp.matchChain(sourceVal, [XRegExp('[\\p{L}\\p{N}\\p{M}]+')]));

            if (words.length) {
                return words.join(Craft.slugWordSeparator);
            }
            else {
                return '';
            }
        }
    });
