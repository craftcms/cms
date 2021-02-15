/** global: Craft */
/** global: Garnish */
/**
 * Handle Generator
 */
Craft.HandleGenerator = Craft.BaseInputGenerator.extend({
    generateTargetValue: function(sourceVal) {
        // Remove HTML tags
        var handle = sourceVal.replace("/<(.*?)>/g", '');

        // Remove inner-word punctuation
        handle = handle.replace(/['"‘’“”\[\]\(\)\{\}:]/g, '');

        // Make it lowercase
        handle = handle.toLowerCase();

        // Convert extended ASCII characters to basic ASCII
        handle = Craft.asciiString(handle);

        if (!this.settings.allowNonAlphaStart) {
            // Handle must start with a letter
            handle = handle.replace(/^[^a-z]+/, '');
        }

        // Get the "words"
        var words = Craft.filterArray(handle.split(/[^a-z0-9]+/));
        handle = '';

        if (Craft.handleCasing === 'snake') {
            return words.join('_');
        }

        // Make it camelCase
        for (let i = 0; i < words.length; i++) {
            if (Craft.handleCasing !== 'pascal' && i === 0) {
                handle += words[i];
            } else {
                handle += words[i].charAt(0).toUpperCase() + words[i].substr(1);
            }
        }

        return handle;
    }
});
