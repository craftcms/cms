module.exports = function(grunt) {
	// Project Configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		watch: {
			sass: {
				files: ['src/resources-src/sass/*.scss'],
				tasks: 'sass'
			},
			globaljs: {
				files: ['src/resources-src/global-js/*.js'],
				tasks: ['concat', 'uglify:globaljs'],
			},
			otherjs: {
				files: ['src/resources/js/*.js', '!src/resources/js/Craft.js'],
				tasks: ['uglify:otherjs']
			}
		},
		sass: {
			options: {
				style: 'compact',
				unixNewlines: true
			},
			dist: {
				expand: true,
				cwd: 'src/resources-src/sass',
				src: '*.scss',
				dest: 'src/resources/css',
				ext: '.css'
			}
		},
		concat: {
			globaljs: {
				options: {
					banner: '/*! <%= pkg.name %> <%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %> */\n' +
						'(function($){\n\n',
					footer: '\n})(jQuery);\n',
				},
				src: [
					'src/resources-src/global-js/Craft.js',
					'src/resources-src/global-js/Base*.js',
					'src/resources-src/global-js/*.js',
					'!(src/resources-src/global-js/Craft.js|src/resources-src/global-js/Base*.js)'
				],
				dest: 'src/resources/js/Craft.js'
			}
		},
		uglify: {
			options: {
				sourceMap: true,
				preserveComments: 'some',
				screwIE8: true
			},
			globaljs: {
				src: 'src/resources/js/Craft.js',
				dest: 'src/resources/js/Craft.min.js'
			},
            otherjs: {
				expand: true,
				cwd: 'src/resources/js',
				src: ['*.js', '!Craft.js'],
				dest: 'src/resources/js',
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
				'src/resources/js/*.js',
				'!src/resources/js/*.min.js',
				'!src/resources/js/Craft.js',
				'src/resources-src/global-js/*.js'
			],
			afterconcat: [
				'src/resources/js/Craft.js'
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
