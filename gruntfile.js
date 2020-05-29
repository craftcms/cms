module.exports = function(grunt) {
    // Project Configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        watch: {
            cpjs: {
                files: ['src/web/assets/cp/src/js/*.js'],
                tasks: ['cpjs']
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
        babel: {
            options: {
                presets: [
                    ['@babel/preset-env', {
                        targets: {
                            esmodules: true,
                        }
                    }]
                ],
                compact: false,
            },
            dist: {
                files: {
                    'src/web/assets/cp/dist/js/Craft.js': 'src/web/assets/cp/dist/js/Craft.js'
                }
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
                    '!src/web/assets/graphiql/**/*.js',
                    '!src/web/assets/pluginstore/**/*.js',
                    '!src/web/assets/admintable/**/*.js'
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
                src: [
                    '*/dist/*.js',
                    '!*/dist/*.min.js',
                    '!graphiql/dist/*.js',
                    '!tests/dist/tests.js',
                ],
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
                esversion: 6,
                asi: true, // suppress "Missing semicolon" errors
                expr: true,
                laxbreak: true,
                loopfunc: true, // suppress "Don't make functions within a loop." errors
                strict: false,
                '-W041': true,
                '-W061': true
            },
            beforeconcat: [
                'gruntfile.js',
                'src/web/assets/**/*.js',
                '!src/web/assets/**/*.min.js',
                '!src/web/assets/cp/dist/js/Craft.js',
                '!src/web/assets/graphiql/**/*.js',
                '!src/web/assets/pluginstore/**/*.js',
                '!src/web/assets/admintable/**/*.js'
            ],
            afterconcat: [
                'src/web/assets/cp/dist/js/Craft.js'
            ]
        }
    });

    //Load NPM tasks
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify-es');
    grunt.loadNpmTasks('grunt-babel');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-jshint');

    // Default task(s).
    grunt.registerTask('js', ['jshint:beforeconcat', 'concat', 'jshint:afterconcat', 'uglify']);
    grunt.registerTask('cpjs', ['concat', 'babel', 'uglify:cpjs']);
    grunt.registerTask('default', ['js']);
};
