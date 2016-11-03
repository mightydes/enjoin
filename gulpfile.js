var gulp = require('gulp');
var $ = require('gulp-load-plugins')();

var config = {
    autoprefixer: {browsers: ['last 2 versions', 'not ie <= 8']},
    less: {paths: ['resources']}
};

gulp.task('default', function () {
    gulp.src('resources/enjoin.less')
        .pipe($.less(config.less))
        .pipe(gulp.dest('resources'));
});

gulp.watch('resources/**/*.less', ['default']);
