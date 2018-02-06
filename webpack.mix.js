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

mix
    .js(sourcePath + '/js/main.js', distPath + '/js/main.min.js')
    .sass(sourcePath + '/sass/main.scss', distPath + '/css/')
        .options({
            processCssUrls: false
        })
    .copy(sourcePath + '/images', distPath + '/images/')
    .sourceMaps();
