const {colord} = require('colord')

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
            let replaceValue = true

            if (semanticColors[colorSetKey][colorKey].light) {
                if (colord(semanticColors[colorSetKey][colorKey].light).alpha() !== 1) {
                    replaceValue = false
                }
            }

            if (semanticColors[colorSetKey][colorKey].dark) {
                if (colord(semanticColors[colorSetKey][colorKey].dark).alpha() !== 1) {
                    replaceValue = false
                }
            }

            if (!replaceValue) {
                semanticTailwindColors[colorSetKey][colorKey] = 'var(--craftui-' + (colorSetKey + '-' + colorKey).toLowerCase() + ')'
            } else {
                if (colorSetKey === 'textColor') {
                    semanticTailwindColors[colorSetKey][colorKey] = 'rgba(var(--craftui-' + (colorSetKey + '-' + colorKey).toLowerCase() + '))'
                } else {
                    semanticTailwindColors[colorSetKey][colorKey] = 'rgba(var(--craftui-' + (colorSetKey + '-' + colorKey).toLowerCase() + '), var(--tw-bg-opacity))'
                }
            }
        }
    }

    return semanticTailwindColors
}
