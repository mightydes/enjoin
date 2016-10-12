var _ = require('underscore');
var async = require('async');
var models = require('./models');

var order = [
    models.Authors,
    models.Articles,
    models.Languages,
    models.Books,
    models.Reviews,
    models.Publishers,
    models.PublishersBooks,
    models.Preorders,
    models.Shipped,
    models.Pile
];

module.exports = function (callback) {
    return dropTables(createTables);

    function dropTables(callback) {
        return async.eachSeries(_.clone(order).reverse(), function (model, done) {
            return model.drop().then(function () {
                return done();
            });
        }, callback);
    }

    function createTables() {
        return async.eachSeries(order, function (model, done) {
            return model.sync().then(function () {
                return done();
            });
        }, callback);
    }
};
