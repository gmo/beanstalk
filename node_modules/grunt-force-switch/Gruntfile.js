/*
 * grunt-force-switch
 * https://github.com/gmodev/grunt-force-switch
 *
 * Copyright (c) 2013 Jason Gill
 * Licensed under the MIT license.
 */

'use strict';

module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    jshint: {
      all: [
        'Gruntfile.js',
        'tasks/*.js',
        'test/*_test.js'
      ],
      options: {
        jshintrc: '.jshintrc'
      }
    },

    clean: {
      tests: ['tmp']
    },

    mochaTest: {
      all: {
        options: {
          reporter: 'spec'
        },
        src: ['test/*_test.js']
      }
    },

  });

  // These plugins provide necessary tasks.
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-mocha-test');


  // Tasks
  grunt.registerTask('build', ['clean','jshint']);
  grunt.registerTask('default', ['build', 'mochaTest']);

};
