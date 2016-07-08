require('dotenv').load();
var gulp = require('gulp');
var phpunit = require('gulp-phpunit');
var lib = require('./test/js');

gulp.task('create-tables', function (callback) {
    return lib.createTables(callback);
});

gulp.task('create-compare-trait', lib.createCompareTrait);

[
    'testFindById',

    'testFindOneEager',
    'testFindOneEagerRequired',
    'testFindOneEagerById',
    'testFindOneEagerByIdRequired',
    'testFindOneEagerByIdMean',
    'testFindOneEagerMean',
    'testFindOneEagerMeanRequired',

    'testFindOneEagerReversed',
    'testFindOneEagerReversedRequired',
    'testFindOneEagerReversedById',
    'testFindOneEagerReversedByIdRequired',
    'testFindOneEagerReversedByIdMean',
    'testFindOneEagerReversedMean',
    'testFindOneEagerReversedMeanRequired',

    'testFindOneComplex',
    'testFindOneAndOr',

    'testFindOneEagerMulti',
    'testFindOneEagerMultiRequired',
    'testFindOneEagerMultiWhere',

    'testFindOneEagerNested',
    'testFindOneEagerNestedById',
    'testFindOneEagerNestedMean',
    'testFindOneEagerNestedDeep',

    'testFindAll',
    'testFindAllEagerOneThenMany',
    'testFindAllEagerOneThenManyMean',
    'testFindAllEagerOneThenManyMeanOrdered'
].forEach(function (task) {
    gulp.task(task, ['create-tables'], lib[task]);
});

gulp.task('phpunit', ['create-tables'], function () {
    gulp.src('phpunit.xml')
        .pipe(phpunit());
});

gulp.task('default', ['phpunit']);
