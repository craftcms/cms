const path = require('path')
const tailwindConf = require('../../../../packages/craftui-tailwind/config')

module.exports = {
    prefix: 'tw-',
    corePlugins: {
        preflight: false,
    },
    ...tailwindConf,
    purge: [
        path.resolve(__dirname, '../../../templates/**/*.{html,twig}'),
        path.resolve(__dirname, '../pluginstore/src/**/*.{vue,js}'),
    ],
    plugins: [
        ...tailwindConf.plugins,

        require('../../../../packages/craftui-tailwind')({
            darkModeSupport: true,
        }),
    ],
}