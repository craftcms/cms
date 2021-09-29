const _ = require('lodash')
const index = require('tailwindcss/plugin')
const colors = require('./colors/colors')
const createSemanticTailwindColors = require('./utils/createSemanticTailwindColors')

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
                ...semanticTailwindColors.colors
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

                // `ringColor` doesn't work with semantic colors
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
                    // Customize the Tailwind color palette
                    ...colors,
                    ...configTheme.colors,
                },

                gradients: theme => ({
                    'primary-gradient': [theme('colors.primary-gradient-1'), theme('colors.primary-gradient-2')]
                }),

                boxShadow: {
                    xs: '0 0 0 1px var(--craftui-colors-shadow-05)',
                    sm: '0 1px 2px 0 var(--craftui-colors-shadow-05)',
                    DEFAULT: '0 1px 3px 0 var(--craftui-colors-shadow-1), 0 1px 2px 0 var(--craftui-colors-shadow-06)',
                    md: '0 4px 6px -1px var(--craftui-colors-shadow-1), 0 2px 4px -1px var(--craftui-colors-shadow-06)',
                    lg: '0 10px 15px -3px var(--craftui-colors-shadow-1), 0 4px 6px -2px var(--craftui-colors-shadow-05)',
                    xl: '0 20px 25px -5px var(--craftui-colors-shadow-1), 0 10px 10px -5px var(--craftui-colors-shadow-04)',
                    '2xl': '0 25px 50px -12px var(--craftui-colors-shadow-25)',
                    inner: 'inset 0 2px 4px 0 var(--craftui-colors-shadow-06)',
                    // outline: '0 0 0 3px var(--craftui-colors-shadow-outline)',
                    outline: '0 0 0 3px red',
                    none: 'none',
                },

                // ringColor: (theme) => ({
                //     DEFAULT: theme('colors.green.500', '#41bd00'),
                //     ...theme('colors'),
                // }),
            },
        }

        return finalConfig
    }
)