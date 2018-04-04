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

mix
    .js(sourcePath + '/js/main.js', 'js')
    .sass(sourcePath + '/sass/main.scss', 'css')
        .options({
            processCssUrls: false
        })
    .copy(sourcePath + '/images', distPath + '/images/')
    .sourceMaps();
