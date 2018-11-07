const sass = require('node-sass');

module.exports = function(grunt) {
	grunt.initConfig({
		sass: {
			options: {
				implementation : sass,
				sourcemap : false
			},
			dist: {
				files: {
					'css/gallery.css': 'src/styles/gallery.scss'
				}
			}
		},

		browserify: {
			dist: {
				files: {
					'js/gallery.js': 'src/scripts/gallery.js'
				},
				options: {
					transform: [
						['babelify', { presets: 'es2015'}],
						['vueify']

					],
					browserifyOptions: {
						debug: true
					}
				}
			}
		},

		watch: {
			default: {
				options: {
					spawn: false
				},
				files: [
					'src/**/*.js',
					'src/**/*.scss',
					'src/**/*.vue'
				],
				tasks: [
					'force:on',
					'sass',
					'browserify',
					'force:off'
				]
			}
		}
	}); //initConfig
	//
	grunt.loadNpmTasks('grunt-force');
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-browserify');

	grunt.registerTask('default', [
		'sass',
		'browserify'
	]);

	grunt.registerTask('watcher', [
		'sass',
		'browserify',
		'watch'
	]);
};
