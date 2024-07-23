/** global: Craft */
/** global: Garnish */
/**
 * Environment Variable Name Generator
 */
Craft.EnvVarGenerator = Craft.BaseInputGenerator.extend({
  generateTargetValue: function (sourceVal) {
    // Remove HTML tags
    let name = sourceVal.replace(/<(.*?)>/g, '');

    // Remove inner-word punctuation
    name = name.replace(/['"‘’“”\[\]\(\)\{\}:]/g, '');

    // Make it lowercase
    name = name.toLowerCase();

    // Convert extended ASCII characters to basic ASCII
    name = Craft.asciiString(name);

    // Start with a letter
    name = name.replace(/^[^a-z]+/, '');

    // Get the "words"
    const words = Craft.filterArray(name.split(/[^a-z0-9]+/));

    return words.join('_').toUpperCase();
  },
});
