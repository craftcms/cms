const colors = require('../../../../packages/craftui-tailwind/colors/colors')
const semanticColors = require('./semanticColors')(colors)
// const tailwindConf = require('../../../../packages/craftui-tailwind/config')
const path = require('path')

module.exports = {
    prefix: 'tw-',
    // ...tailwindConf,
    purge: [
        path.resolve(__dirname, './src/**/*.{js,jsx,ts,tsx,vue}'),
        // '../../../templates/**/*.{html,twig}',
    ],
    plugins: [
        // ...tailwindConf.plugins,

        require('../../../../packages/craftui-tailwind')({
            semanticColors,
            darkModeSupport: false,
        }),
    ],
}