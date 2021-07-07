const tailwindConf = require('../../../../packages/craftui-tailwind/config')

module.exports = {
    ...tailwindConf,
    purge: [
        './src/**/*.{js,jsx,ts,tsx,vue}',
        // '../../../templates/**/*.{html,twig}',
    ],
    plugins: [
        ...tailwindConf.plugins,

        require('../../../../packages/craftui-tailwind')({
            darkModeSupport: true,
        }),
    ],
}