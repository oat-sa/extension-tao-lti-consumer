module.exports = function(grunt) {

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoLtiConsumer/views/';

    sass.taolticonsumer = { };
    sass.taolticonsumer.files = { };
    sass.taolticonsumer.files[root + 'css/wizard.css'] = root + 'scss/wizard.scss';

    watch.taolticonsumersass = {
        files : [root + 'scss/**/*.scss'],
        tasks : ['sass:taolticonsumer', 'notify:taolticonsumer'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taolticonsumersass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taolticonsumersass', ['sass:taolticonsumer']);
};
