module.exports = function(grunt) {
    // Project Configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        watch: {
            sass: {
                files: ['cms/resources/sass/*.scss'],
                tasks: 'sass'
            },
            craftjs: {
                files: ['cms/resources/js/Craft/*.js'],
                tasks: ['concat', 'uglify:craft']
            },
            otherjs: {
                files: ['cms/resources/js/*.js', '!cms/resources/js/Craft.js'],
                tasks: ['uglify:other']
            }
        },
        sass: {
            options: {
                style: 'compact',
                unixNewlines: true
            },
            dist: {
                expand: true,
                cwd: 'cms/resources/sass',
                src: '*.scss',
                dest: 'cms/resources/css',
                ext: '.css'
            }
        },
        concat: {
            craft: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %> */\n' +
                    '(function($){\n\n',
                    footer: '\n})(jQuery);\n',
                },
                src: [
                    'cms/resources/js/Craft/Craft.js',
                    'cms/resources/js/Craft/Base*.js',
                    'cms/resources/js/Craft/*.js',
                    '!(cms/resources/js/Craft/Craft.js|cms/resources/js/Craft/Base*.js)'
                ],
                dest: 'cms/resources/js/Craft.js'
            }
        },
        uglify: {
            options: {
                sourceMap: true,
                preserveComments: 'some',
                screwIE8: true
            },
            craft: {
                src: 'cms/resources/js/Craft.js',
                dest: 'cms/resources/js/compressed/Craft.js'
            },
            other: {
                expand: true,
                cwd: 'cms/resources/js',
                src: ['*.js', '!Craft.js'],
                dest: 'cms/resources/js/compressed'
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
                'cms/resources/js/*.js',
                '!cms/resources/js/Craft.js',
                'cms/resources/js/Craft/*.js'
            ],
            afterconcat: [
                'cms/resources/js/Craft.js'
            ]
        }
    });

    //Load NPM tasks
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-jshint');

    // Default task(s).
    grunt.registerTask('default', ['sass', 'jshint:beforeconcat', 'concat', 'jshint:afterconcat', 'uglify']);
};
