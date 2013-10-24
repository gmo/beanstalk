module.exports = function(grunt) {
	'use strict';

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		exec: {
			composerInstall: {
				cmd: 'composer install'
			}
		},

		phpunit: {
			classes: {
				dir: 'tests/'
			},
			options: {
				bin: 'vendor/bin/phpunit',
				colors: true
			}
		}
	});

	grunt.loadNpmTasks('grunt-exec');
	grunt.loadNpmTasks('grunt-phpunit');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-force-switch');

	grunt.registerTask('default', function() { grunt.log(grunt.version); });

	grunt.registerTask('force', function(task) {
		grunt.task.run(['turnForceOn', task, 'turnForceOff']);
	});

	grunt.registerTask('build', ['exec:composerInstall']);
	grunt.registerTask('test', ['build', 'phpunit']);

};