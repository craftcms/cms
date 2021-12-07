module.exports = function(colors) {
    return {
        backgroundColor: {
            'interactive-danger': { light: colors.red[600], dark: colors.red[600] },
            'interactive-danger-active': { light: colors.red[800], dark: colors.red[800] },
            'interactive-danger-hover': { light: colors.red[700], dark: colors.red[700] },
            'interactive-primary': { light: colors.blue[500], dark: colors.blue[600] },
            'interactive-primary-active': { light: colors.blue[800], dark: colors.blue[400] },
            'interactive-primary-hover': { light: colors.blue[700], dark: colors.blue[500] },
            'interactive-secondary': { light: colors['gray'][200], dark: colors['gray'][700] },
            'interactive-secondary-active': { light: colors['gray'][400], dark: colors['gray'][500] },
            'interactive-secondary-hover': { light: colors['gray'][300], dark: colors['gray'][600] },
        },
        textColor: {
            'interactive': { light: colors.black, dark: colors.white },
            'interactive-inverse': { light: colors.white, dark: colors.white },
        },
        borderColor: {
            'danger': { light: colors.red[400], dark: colors.red[400] },
            'field': { light: colors['gray'][300], dark: colors['gray'][600], highContrast: colors['gray'][500] },
            'interactive-danger': { light: colors.red[600], dark: colors.red[600] },
            'interactive-danger-active': { light: colors.red[800], dark: colors.red[800] },
            'interactive-danger-hover': { light: colors.red[700], dark: colors.red[700] },
            'interactive-primary': { light: colors.blue[500], dark: colors.blue[600] },
            'interactive-primary-active': { light: colors.blue[800], dark: colors.blue[400] },
            'interactive-primary-hover': { light: colors.blue[700], dark: colors.blue[500] },
            'interactive-secondary': { light: colors['gray'][200], dark: colors['gray'][700] },
            'interactive-secondary-active': { light: colors['gray'][400], dark: colors['gray'][500] },
            'interactive-secondary-hover': { light: colors['gray'][300], dark: colors['gray'][600] },
            'separator': { light: colors['gray'][300], dark: colors['gray'][700] },
        },
    }
}
