#  Craft UI Colors

Craft UI defines semantic colors which support different contexts like light (default), dark or high contrast:

1. Semantic colors are defined for each context in `src/colors/semanticColors.js` using Tailwind’s color palette 
    
    ```
    'danger': {
        light: colors.red[600],
        dark: colors.red[400],
    },
    ```
   
2. Tailwind’s default color palette get extended with semantic colors in `tailwind.config.js` line 17. 
   Semantic colors get changed into CSS variables in `src/colors/semanticTailwindColors.js`, and end up in this format:
   
    ```
    semanticTailwindColors['danger'] = 'var(--craftui-danger)'
    ```
   
   The value of the CSS variable changes based on the context.

3. CSS variables are defined for each context in `tailwind.config.js` line 30.
   At this point the `baseStyleColors` object gets populated to something like this:

    ```
    let baseStyleColors = {
        light: {
            '--craftui-danger': colors.red[600]
            
            // ...
        },
        dark: {
            '--craftui-danger': colors.red[400]
            
            // ...
        },
        highContrast: {
            // ...
        },
        darkHighContrast: {
            // ...
        },
    }
    ```

4. The `baseStyleColors` is used to define colors based on the context in `tailwind.config.js` line 58:
    1. The `light` colors are always being applied.
    2. The `dark` colors get applied when `prefers-color-scheme: dark`. If a `dark` color is not available, the `light` gets used.
    3. `highContrast` colors are applied when `@media (prefers-contrast: high)`. If a `highContrast` color is not available, the `light` gets used.
    4. `darkHighContrast` colors are applied when `@media (prefers-color-scheme: dark) and (prefers-contrast: high)`. If a `darkHighContrast` color is not available, the `dark` gets used.