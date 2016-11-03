require('shelljs/global');

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
    gulp.watch('resources/**/*.less', ['default']);
});

gulp.task('deploy', function () {
    exec('git checkout master -- README.md');
    mv('README.md', 'index.md');
    exec('sed -i -e "1s/^/---\\nlayout: index\\n---\\n/" index.md');
    exec('git add -A');
    exec('git commit -m"Bump gh-pages"');
    exec('git push origin gh-pages');
});
