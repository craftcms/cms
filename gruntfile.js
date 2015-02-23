module.exports = function(grunt) {
	// Project Configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		watch: {
			sass: {
				files: ['Source/craft/app/resources/sass/*.scss'],
				tasks: 'sass'
			},
			craftjs: {
				files: ['Source/craft/app/resources/js/Craft/*.js'],
				tasks: ['concat', 'uglify:craft'],
			},
			otherjs: {
				files: ['Source/craft/app/resources/js/*.js', '!Source/craft/app/resources/js/Craft.js'],
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
				cwd: 'Source/craft/app/resources/sass',
				src: '*.scss',
				dest: 'Source/craft/app/resources/css',
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
					'Source/craft/app/resources/js/Craft/Craft.js',
					'Source/craft/app/resources/js/Craft/Base*.js',
					'Source/craft/app/resources/js/Craft/*.js',
					'!(Source/craft/app/resources/js/Craft/Craft.js|Source/craft/app/resources/js/Craft/Base*.js)'
				],
				dest: 'Source/craft/app/resources/js/Craft.js'
			}
		},
		uglify: {
			options: {
				sourceMap: true,
				preserveComments: 'some',
				screwIE8: true
			},
			craft: {
				src: 'Source/craft/app/resources/js/Craft.js',
				dest: 'Source/craft/app/resources/js/compressed/Craft.js'
			},
			other: {
				expand: true,
				cwd: 'Source/craft/app/resources/js',
				src: ['*.js', '!Craft.js'],
				dest: 'Source/craft/app/resources/js/compressed'
			}
		},
		jshint: {
			options: {
				expr: true,
				laxbreak: true,
				loopfunc: true, // Supresses "Don't make functions within a loop." errors
				shadow: true,
				strict: false,
				'-W041': true
			},
			beforeconcat: [
				'gruntfile.js',
				'Source/craft/app/resources/js/*.js',
				'!Source/craft/app/resources/js/Craft.js',
				'Source/craft/app/resources/js/Craft/*.js'
			],
			afterconcat: [
				'Source/craft/app/resources/js/Craft.js'
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
