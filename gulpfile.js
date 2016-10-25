require('dotenv').load();
var gulp = require('gulp');
var phpunit = require('gulp-phpunit');
var async = require('async');
var debug = require('debug')('gulp');
var lib = require('./test/js');
var createTables = require('./test/js/create-tables');

var testList = [
    'testModelCreate',
    'testModelCreateEmpty',
    'testModelCreateWithDateField',

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
    'testFindOneEagerSelfNestedNoSubQuery',

    'testFindAll',
    'testFindAllEmptyList',
    'testFindAllEagerOneThenMany',
    'testFindAllEagerOneThenManyMean',
    'testFindAllEagerOneThenManyMeanOrdered',
    'testFindAllEagerOneThenManyMeanGrouped',
    'testFindAllEagerNestedDeep',
    'testFindAllEagerNestedDeepLimited',

    'testCount',
    'testCountConditional',
    'testCountEagerOneThenMany',
    'testCountEagerOneThenManyMean',
    'testCountEagerRequired',
    'testCountEagerRequiredLimited',

    'testFindAndCountAll',
    'testFindAndCountAllConditional',
    'testFindAndCountAllEagerOneThenMany',
    'testFindAndCountAllEagerOneThenManyMean',
    'testFindAndCountAllEagerRequired',
    'testFindAndCountAllEagerRequiredLimited',

    'testModelDestroy',
    'testModelUpdate'
];

gulp.task('create-tables', function (callback) {
    return createTables(callback);
});

testList.forEach(function (task) {
    gulp.task(task, ['create-tables'], function () {
        lib[task](function () {
        });
    });
});

gulp.task('phpunit', ['create-tables'], phpUnit);

gulp.task('test-all', testAll);

gulp.task('default', ['phpunit']);

function phpUnit() {
    gulp.src('phpunit.xml')
        .pipe(phpunit());
}

function testAll() {
    async.eachSeries(testList, function (task, done) {
        createTables(function () {
            debug('Run ' + task + '...');
            lib[task](function () {
                done();
            });
        });
    }, function (err) {
        if (err) {
            debug(err);
        }
    });
}
