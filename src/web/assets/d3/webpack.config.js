/* jshint esversion: 6 */
/* globals module, require, __dirname */
const { getConfig } = require('@craftcms/webpack');
const {join} = require('path');

module.exports = getConfig({
    context: __dirname,
    watchPaths: [join(__dirname, 'src')],
    config: {
        entry: {
            d3: './index.js',
        },
        module: {
            rules: [
                {
                    test: require.resolve('./src/index.js'),
                    loader: "expose-loader",
                    options: {
                        exposes: [
                            {
                                globalName: "d3",
                                moduleLocalName: "default",
                            }
                        ]
                    },
                },
            ],

        }
    }
});
