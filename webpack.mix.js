let mix = require('laravel-mix');

mix.webpackConfig({
    externals: {
        'vue': 'Vue',
        'vue-router': 'VueRouter',
        'vuex': 'Vuex',
        'axios': 'axios'
    }
});

const sourcePath = 'src/web/assets/pluginstore/src';
const distPath = 'src/web/assets/pluginstore/dist';

mix.setPublicPath(distPath);

mix.options({
    sourcemaps: 'source-map',
    uglify: {
        sourceMap: true,
        uglifyOptions: {
            sourceMap: true,
            compress: {
                warnings: false,
                drop_console: true,
            },
            output: {
                comments: false
            }
        }
    },
    postCss: [
        require('autoprefixer')({
            grid: true,
            browsers: ['ie > 11'],
        })
    ]
});

mix
    .js(sourcePath + '/js/main.js', 'js')
    .sass(sourcePath + '/sass/main.scss', 'css')
        .options({
            processCssUrls: false
        })
    .copy(sourcePath + '/images', distPath + '/images/')
    .sourceMaps();
