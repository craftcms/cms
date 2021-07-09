const semanticColors = require('../colors/semanticColors')

module.exports = function(colors, pluginOptions) {
    if (pluginOptions && pluginOptions.semanticColors) {
        return pluginOptions.semanticColors
    }

    return semanticColors(colors)
}
