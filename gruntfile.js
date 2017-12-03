module.exports = function(grunt) {
    // Project Configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        watch: {
            sass: {
                files: ['lib/craftcms-sass/_mixins.scss', 'src/web/assets/**/*.scss', '!src/web/assets/pluginstore/**/*.scss'],
                tasks: 'css'
            },
            cpjs: {
                files: ['src/web/assets/cp/src/js/*.js'],
                tasks: ['concat', 'uglify:cpjs']
            },
            otherjs: {
                files: ['src/web/assets/*/dist/*.js', '!src/web/assets/*/dist/*.min.js', '!src/web/assets/pluginstore/**/*.js'],
                tasks: ['uglify:otherjs']
            },
            colorpickerjs: {
                files: ['lib/colorpicker/js/colorpicker.js'],
                tasks: ['uglify:colorpickerjs']
            }
        },
        sass: {
            options: {
                style: 'compact',
                unixNewlines: true
            },
            dist: {
                expand: true,
                cwd: 'src/web/assets',
                src: [
                    '**/*.scss',
                    '!pluginstore/**/*.scss'
                ],
                dest: 'src/web/assets',
                rename: function(dest, src) {
                    // Keep them where they came from
                    return dest + '/' + src;
                },
                ext: '.css'
            }
        },
        postcss: {
            options: {
                map: true,
                processors: [
                    require('autoprefixer')({browsers: 'last 2 versions'})
                ]
            },
            dist: {
                expand: true,
                cwd: 'src/web/assets',
                src: [
                    '**/*.css',
                    '!pluginstore/**/*.css'
                ],
                dest: 'src/web/assets'
            }
        },
        concat: {
            cpjs: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %> */\n' +
                    '(function($){\n\n',
                    footer: '\n})(jQuery);\n'
                },
                src: [
                    'src/web/assets/cp/src/js/Craft.js',
                    'src/web/assets/cp/src/js/Base*.js',
                    'src/web/assets/cp/src/js/*.js',
                    '!(src/web/assets/cp/src/js/Craft.js|src/web/assets/cp/src/js/Base*.js)',
                    '!src/web/assets/pluginstore/**/*.js'
                ],
                dest: 'src/web/assets/cp/dist/js/Craft.js'
            }
        },
        uglify: {
            options: {
                sourceMap: true,
                preserveComments: 'some',
                screwIE8: true
            },
            cpjs: {
                src: 'src/web/assets/cp/dist/js/Craft.js',
                dest: 'src/web/assets/cp/dist/js/Craft.min.js'
            },
            otherjs: {
                expand: true,
                cwd: 'src/web/assets',
                src: ['*/dist/*.js', '!*/dist/*.min.js', '!tests/dist/tests.js'],
                dest: 'src/web/assets',
                rename: function(dest, src) {
                    // Keep them where they came from
                    return dest + '/' + src;
                },
                ext: '.min.js'
            }
        },
        jshint: {
            options: {
                expr: true,
                laxbreak: true,
                loopfunc: true, // Supresses "Don't make functions within a loop." errors
                shadow: true,
                strict: false,
                '-W041': true,
                '-W061': true
            },
            beforeconcat: [
                'gruntfile.js',
                'src/web/assets/**/*.js',
                '!src/web/assets/**/*.min.js',
                '!src/web/assets/cp/dist/js/Craft.js',
                '!src/web/assets/pluginstore/**/*.js'
            ],
            afterconcat: [
                'src/web/assets/cp/dist/js/Craft.js'
            ]
        }
    });

    //Load NPM tasks
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-postcss');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-jshint');

    // Default task(s).
    grunt.registerTask('css', ['sass', 'postcss']);
    grunt.registerTask('js', ['jshint:beforeconcat', 'concat', 'jshint:afterconcat', 'uglify']);
    grunt.registerTask('default', ['css', 'js']);
};
