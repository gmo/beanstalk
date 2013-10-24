# grunt-force-switch

> A grunt plugin to turn force on and off

## Getting Started
This plugin requires Grunt `~0.4.1`

If you haven't used [Grunt](http://gruntjs.com/) before, be sure to check out the [Getting Started](http://gruntjs.com/getting-started) guide, as it explains how to create a [Gruntfile](http://gruntjs.com/sample-gruntfile) as well as install and use Grunt plugins. Once you're familiar with that process, you may install this plugin with this command:

```shell
npm install grunt-force-switch --save-dev
```

Once the plugin has been installed, it may be enabled inside your Gruntfile with this line of JavaScript:

```js
grunt.loadNpmTasks('grunt-force-switch');
```

## The "turnForce[On/Off]" tasks

### Overview
A grunt plugin to turn force on and off at will.

The code for this plugin was obtained from [this](http://stackoverflow.com/questions/16612495/continue-certain-tasks-in-grunt-even-if-one-fails/16972894#16972894) answer by [explunit](http://stackoverflow.com/users/151212/explunit) on StackOverflow.

Another variation of this plugin is called [grunt-continue](https://npmjs.org/package/grunt-continue).

### Simple Example

```js
module.exports = function(grunt) {

  // Add the grunt-force-switch task
  grunt.loadNpmTasks('grunt-force-switch');

  // Other tasks and configuration
  ...

  grunt.registerTask('build', [
    'init',
    'turnForceOn',
    // All tasks after this point will be run with the force
    // option so that grunt will continue after failures
    'run_phpunit',
    'mochaTest',
    'mocha_phantomjs',
    'turnForceOff',
    // Tasks after this point will be run without the force
    // option so that grunt exits if they fail
    'mergeTestResults'
  ]);

};
```

`turnForceOff` does not turn off force if `--force` was specified at the command line.

## Contributing
In lieu of a formal styleguide, take care to maintain the existing coding style. Add unit tests for any new or changed functionality. Lint and test your code using [Grunt](http://gruntjs.com/).

## Release History
_(Nothing yet)_
