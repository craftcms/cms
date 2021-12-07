const colors = require('@craftcms/tailwind/colors/colors')
const semanticColors = require('./semanticColors')(colors)
const path = require('path')

module.exports = {
    prefix: 'tw-',
    purge: [
        path.resolve(__dirname, './src/**/*.{js,jsx,ts,tsx,vue}'),
    ],
    plugins: [
        require('@craftcms/tailwind')({
            semanticColors,
            darkModeSupport: false,
        }),
    ],
}
