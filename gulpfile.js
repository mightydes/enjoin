require('dotenv').load();
var gulp = require('gulp');
var phpunit = require('gulp-phpunit');
var del = require('del');
var path = require('path');
var lib = require('./test/js');

var testList = [
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
    'testModelUpdate',
    'testModelCreateEmpty'
];

gulp.task('create-tables', function (callback) {
    return lib.createTables(callback);
});

gulp.task('create-compare-trait', lib.createCompareTrait);

testList.forEach(function (task) {
    gulp.task(task, ['create-tables'], lib[task]);
});

gulp.task('phpunit', ['create-tables'], phpUnit);

gulp.task('test-all', testAll);

gulp.task('default', ['phpunit']);

function phpUnit() {
    gulp.src('phpunit.xml')
        .pipe(phpunit());
}

function testAll() {
    var dir = path.normalize(__dirname + '/test/php/compare');
    var compareTrait = path.normalize(__dirname + '/test/php/CompareTrait.php');
    del([dir + '/*.json', compareTrait]).then(function () {
        testList.forEach(function (test) {
            lib.createTables(function () {
                lib[test]();
            });
        });
    });
}
