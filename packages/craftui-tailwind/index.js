const _ = require('lodash')
const index = require('tailwindcss/plugin')
const colors = require('./colors/colors')
const createSemanticTailwindColors = require('./utils/createSemanticTailwindColors')
const createSemanticColors = require('./utils/createSemanticColors')
const defaultConfig = require('./stubs/defaultConfig.stub')

module.exports = index.withOptions(
    function(pluginOptions) {
        return function(options) {
            const { addBase, addUtilities, theme, variants, e } = options

            // Colors
            const semanticColors = createSemanticColors(colors, pluginOptions)

            // Define CSS variables
            let baseStyleColors = {
                light: {},
                highContrast: {},
                dark: {},
                darkHighContrast: {},
            }

            for (let colorSetKey in semanticColors) {
                if (!semanticColors.hasOwnProperty(colorSetKey)) {
                    continue;
                }

                const colorSet = semanticColors[colorSetKey]

                for (let colorKey in colorSet) {
                    const semanticColor = semanticColors[colorSetKey][colorKey]


                    if (semanticColor.light) {
                        baseStyleColors.light['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = semanticColor.light
                    }

                    if (semanticColor.highContrast) {
                        baseStyleColors.highContrast['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = semanticColor.highContrast
                    }

                    if (semanticColor.dark) {
                        baseStyleColors.dark['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = semanticColor.dark
                    }

                    if (semanticColor.darkHighContrast) {
                        baseStyleColors.darkHighContrast['--craftui-'+ (colorSetKey + '-' + colorKey).toLowerCase()] = semanticColor.darkHighContrast
                    }
                }
            }


            // Set colors for each context (light, dark, high contrast)

            addBase({
                // `light` color scheme
                'body': baseStyleColors.light,
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
                })
            }

            addBase({
                // Misc
                'label': {
                    display: 'inline-block',
                    marginBottom: theme('margin.1'),
                },
            })


            // Gradients

            const gradients = theme('gradients', {})
            const gradientVariants = variants('gradients', [])
            const utilities = _.map(gradients, ([start, end], name) => ({
                [`.bg-gradient-${e(name)}`]: {
                    backgroundImage: `linear-gradient(to bottom, ${start}, ${end})`
                }
            }))

            addUtilities(utilities, gradientVariants)
        }
    },

    function(pluginOptions = {}) {
        const semanticColors = createSemanticColors(colors, pluginOptions)
        const semanticTailwindColors = createSemanticTailwindColors(semanticColors)

        /*
        semanticTailwindColors['backgroundColor-danger']
        semanticTailwindColors['global-info']
        */

        const finalConfig =  _.merge(defaultConfig, {
            theme: {
                colors: {
                    // Customize the Tailwind color palette
                    ...colors,

                    // Add semantic colors to the Tailwind color palette
                    ...semanticTailwindColors.global,
                },
                backgroundColor: theme => ({
                    ...theme('colors'),
                    ...semanticTailwindColors.backgroundColor,
                }),
                textColor: theme => ({
                    ...theme('colors'),
                    ...semanticTailwindColors.textColor,
                }),
                borderColor: theme => ({
                    ...theme('colors'),
                    ...semanticTailwindColors.borderColor,
                })
            },
            future: {
                removeDeprecatedGapUtilities: true,
            },
        })

        return finalConfig
    }
)