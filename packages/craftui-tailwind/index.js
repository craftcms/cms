const _ = require('lodash')
const index = require('tailwindcss/plugin')
const colors = require('./colors/colors')
const createSemanticTailwindColors = require('./utils/createSemanticTailwindColors')
const {colord} = require('colord')

module.exports = index.withOptions(
    // Plugin function
    function(pluginOptions) {
        return function(options) {
            const { addBase, addUtilities, theme, variants, e } = options

            // Semantic Colors
            if (!pluginOptions) {
                return false
            }

            if (!pluginOptions.semanticColors) {
                return false
            }

            const semanticColors = pluginOptions.semanticColors

            // Define CSS variables
            let baseStyleColors = {
                light: {},
                highContrast: {},
                dark: {},
                darkHighContrast: {},
            }

            const colorToRgbColorString = function(colorSetKey, color) {
                const parsedColor = colord(color)

                if (colorSetKey === 'shadowColor' || colorSetKey === 'textColor') {
                    return parsedColor.rgba.r + ', ' + parsedColor.rgba.g + ', ' + parsedColor.rgba.b + ', ' + parsedColor.rgba.a
                }

                if (parsedColor.alpha() !== 1 && colorSetKey !== 'ringColor') {
                    return parsedColor.toRgbString()
                }

                return parsedColor.rgba.r + ', ' + parsedColor.rgba.g + ', ' + parsedColor.rgba.b
            }

            for (let colorSetKey in semanticColors) {
                if (!semanticColors.hasOwnProperty(colorSetKey)) {
                    continue;
                }

                const colorSet = semanticColors[colorSetKey]

                for (let colorKey in colorSet) {
                    const semanticColor = semanticColors[colorSetKey][colorKey]

                    if (semanticColor.light) {
                        baseStyleColors.light['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = colorToRgbColorString(colorSetKey, semanticColor.light)
                    }

                    if (semanticColor.highContrast) {
                        baseStyleColors.highContrast['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = colorToRgbColorString(colorSetKey, semanticColor.highContrast)
                    }

                    if (semanticColor.dark) {
                        baseStyleColors.dark['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = colorToRgbColorString(colorSetKey, semanticColor.dark)
                    }

                    if (semanticColor.darkHighContrast) {
                        baseStyleColors.darkHighContrast['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = colorToRgbColorString(colorSetKey, semanticColor.darkHighContrast)
                    }
                }
            }


            // Set colors for each context (light, dark, high contrast)
//             console.log('baseStyleColors.light', baseStyleColors.light)
            addBase({
                // `light` color scheme
                'body': {
                    ...baseStyleColors.light,
                    ...{
                        '--tw-bg-opacity': '1',
                    }
                },
            })


            if (pluginOptions && pluginOptions.darkModeSupport && pluginOptions.darkModeSupport === true) {
                addBase({
                    // `dark` color scheme
                    '@media (prefers-color-scheme: dark)': {
                        'body': baseStyleColors.dark,
                    },

                    // `high` contrast
                    '@media (prefers-contrast: high)': {
                        'body': baseStyleColors.highContrast,
                    },
                    '@media (prefers-color-scheme: dark) and (prefers-contrast: high)': {
                        'body': baseStyleColors.darkHighContrast,
                    },

                    // Add support for `.theme-light` to force `light` context
                    'body.theme-light': baseStyleColors.light,

                    // Add support for `.theme-dark` to force `dark` context
                    'body.theme-dark': baseStyleColors.dark,

                    // Add support for `.high-contrast` when browser don’t support `prefers-contrast`
                    'body.high-contrast, body.theme-dark.high-contrast': baseStyleColors.highContrast,
                    'body.theme-dark.high-contrast': baseStyleColors.darkHighContrast,

                    /*
                    "[type='text']:focus, [type='email']:focus, [type='url']:focus, [type='password']:focus, [type='number']:focus, [type='date']:focus, [type='datetime-local']:focus, [type='month']:focus, [type='search']:focus, [type='tel']:focus, [type='time']:focus, [type='week']:focus, [multiple]:focus, textarea:focus, select:focus": {
                        'borderColor':theme('colors.orange.700'),
                        '--tw-ring-color':theme('colors.orange.700'),
                    }
                    */
                })
            }
        }
    },

    // Config function
    function(pluginOptions = {}) {
        if (!pluginOptions) {
            return false
        }

        if (!pluginOptions.semanticColors) {
            return false
        }

        const semanticColors = pluginOptions.semanticColors

        const semanticTailwindColors = createSemanticTailwindColors(semanticColors)

        const tailwindColorKeys = [
             'backgroundColor',
             'borderColor',
             'divideColor',
             'placeholderColor',
             'ringColor',
             'ringOffsetColor',
             'textColor',
        ]

        const configTheme = {}


        // Add semantic colors to the Tailwind color palette
        if (semanticTailwindColors.colors) {
            configTheme.colors = {
                ...configTheme.colors,
                ...semanticTailwindColors.colors,
            }
        }

        tailwindColorKeys.forEach(tailwindColorKey => {
            configTheme[tailwindColorKey] = (theme) => {
                if (tailwindColorKey === 'borderColor') {
                    return {
                        ...theme('colors'),
                        ...semanticTailwindColors[tailwindColorKey],

                        // Set the default border color
                        DEFAULT: semanticTailwindColors.borderColor.separator
                    }
                }

                // DOESN'T WORK
                // `ringColor` doesn't work with semantic colors because Tailwind’s ring opacity is dynamic
                // if (tailwindColorKey === 'ringColor') {
                //     return {
                //         ...theme('colors'),
                //         ...semanticTailwindColors[tailwindColorKey],
                //         DEFAULT: semanticTailwindColors.ringColor.outline
                //     }
                // }

                return {
                    ...theme('colors'),
                    ...semanticTailwindColors[tailwindColorKey]
                }
            }
        })

        const finalConfig = {
            darkMode: 'media',
            variants: {
                extend: {
                    backgroundColor: ['active', 'disabled'],
                    textColor: ['active'],
                    borderColor: ['active', 'disabled'],
                    opacity: ['disabled'],
                    cursor: ['disabled', 'hover', 'active'],
                    ringColor: ['focus-visible'],
                    ringOffsetColor: ['focus-visible'],
                    ringOffsetWidth: ['focus-visible'],
                    ringOpacity: ['focus-visible'],
                    ringWidth: ['focus-visible'],
                }
            },
            theme: {
                // Config theme colors
                ...configTheme,

                colors: {
                    // Color palette
                    ...colors,

                    // Custom colors
                    ...configTheme.colors,
                },

                // WORKS
                // Lets us override the default ring color value
                // ringColor: (theme) => ({
                //     DEFAULT: theme('colors.green.500', '#41bd00'),
                //     ...theme('colors'),
                // }),
            },
        }

        return finalConfig
    }
)