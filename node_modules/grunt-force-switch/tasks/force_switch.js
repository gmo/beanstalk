/*
 * grunt-force-switch
 * https://github.com/gmodev/grunt-force-switch
 *
 * Copyright (c) 2013 Jason Gill
 * Licensed under the MIT license.
 */

'use strict';

module.exports = function(grunt) {

    grunt.registerTask('turnForceOn', 'Turn force on only if it has not already been set.', function() {

        if (!grunt.option('force')) {
            grunt.config.set('usingForce', true);
            grunt.option('force', true);
        }

    });

    grunt.registerTask('turnForceOff', 'Turn force off only if it was set using turnForceOn.', function() {

        if (grunt.config.get('usingForce')) {
            grunt.option('force', false);
        }

    });

};
