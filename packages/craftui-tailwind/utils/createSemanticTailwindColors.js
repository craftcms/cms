module.exports = function(semanticColors) {
    let semanticTailwindColors = {};

    // Define a CSS `--craftui-{colorSetKey}-{colorKey}` variable for each semantic color key
    for (let colorSetKey in semanticColors) {
        if (!semanticColors.hasOwnProperty(colorSetKey)) {
            continue;
        }

        const colorSet = semanticColors[colorSetKey]
        semanticTailwindColors[colorSetKey] = {}

        for (let colorKey in colorSet) {
            semanticTailwindColors[colorSetKey][colorKey] = 'var(--craftui-' + (colorSetKey + '-' + colorKey).toLowerCase() + ')'
        }
    }

    return semanticTailwindColors
}
