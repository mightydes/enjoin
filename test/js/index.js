var models = require('./models');
var debug = require('debug')('phpunit');
var _ = require('underscore');

module.exports = {
    createTables: require('./create-tables'),
    testFindById: testFindById,

    testFindOneEager: testFindOneEager,
    testFindOneEagerRequired: testFindOneEagerRequired,
    testFindOneEagerById: testFindOneEagerById,
    testFindOneEagerByIdRequired: testFindOneEagerByIdRequired,
    testFindOneEagerByIdMean: testFindOneEagerByIdMean,
    testFindOneEagerMean: testFindOneEagerMean,
    testFindOneEagerMeanRequired: testFindOneEagerMeanRequired,

    testFindOneEagerReversed: testFindOneEagerReversed,
    testFindOneEagerReversedRequired: testFindOneEagerReversedRequired,
    testFindOneEagerReversedById: testFindOneEagerReversedById,
    testFindOneEagerReversedByIdRequired: testFindOneEagerReversedByIdRequired,
    testFindOneEagerReversedByIdMean: testFindOneEagerReversedByIdMean,
    testFindOneEagerReversedMean: testFindOneEagerReversedMean,
    testFindOneEagerReversedMeanRequired: testFindOneEagerReversedMeanRequired,

    testFindOneComplex: testFindOneComplex,
    testFindOneAndOr: testFindOneAndOr,

    testFindOneEagerMulti: testFindOneEagerMulti,
    testFindOneEagerMultiRequired: testFindOneEagerMultiRequired,
    testFindOneEagerMultiWhere: testFindOneEagerMultiWhere,

    testFindOneEagerNested: testFindOneEagerNested,
    testFindOneEagerNestedById: testFindOneEagerNestedById,
    testFindOneEagerNestedMean: testFindOneEagerNestedMean,
    testFindOneEagerNestedDeep: testFindOneEagerNestedDeep,

    testFindAll: testFindAll
};

function camelize(str) {
    return str.replace(/(?:^|[-_])(\w)/g, function (_, c) {
        return c ? c.toUpperCase() : '';
    });
}

function toPhpParams(params) {
    debug('Php params: ' + handle(params));

    function handle(params) {
        var r = '[';
        var length = _.isArray(params) ? params.length : _.allKeys(params).length;
        var i = 0;
        _.forEach(params, function (v, k) {
            if (_.isString(k)) {
                if (k.substr(0, 1) === '$') {
                    k = k.substr(1);
                }
                r += "'" + k + "'=>";
            }
            // TODO: if `null`...
            if (_.isObject(v) && _.has(v, 'sequelize')) {
                r += "Enjoin::get('" + camelize(v.getTableName()) + "')";
            } else if (_.isObject(v) || _.isArray(v)) {
                r += handle(v);
            } else if (_.isBoolean(v)) {
                r += v ? 'true' : 'false';
            } else if (_.isNull(v)) {
                r += 'null';
            } else {
                r += _.isNumber(v) ? v : "'" + v + "'";
            }
            i++;
            if (i < length) {
                r += ',';
            }
        });
        r += ']';
        return r;
    }
}

function testFindById() {
    models.Authors.findById(1);
}

function testFindOneEager() {
    var params = {
        include: models.Books
    };
    toPhpParams(params);
    models.Authors.findOne(params);
}

function testFindOneEagerRequired() {
    var params = {
        include: {
            model: models.Books,
            required: true
        }
    };
    toPhpParams(params);
    models.Authors.findOne(params);
}

function testFindOneEagerById() {
    var params = {
        where: {id: 1},
        include: models.Books
    };
    toPhpParams(params);
    models.Authors.findOne(params);
}

function testFindOneEagerByIdRequired() {
    var params = {
        where: {id: 1},
        include: {
            model: models.Books,
            required: true
        }
    };
    toPhpParams(params);
    models.Authors.findOne(params);
}

function testFindOneEagerByIdMean() {
    var params = {
        where: {
            id: 1,
            name: ['Alice', 'Bob']
        },
        include: models.Books
    };
    toPhpParams(params);
    models.Authors.findOne(params);
}

function testFindOneEagerMean() {
    var params = {
        where: {
            id: [1, 2, 3],
            name: ['Alice', 'Bob']
        },
        include: models.Books
    };
    toPhpParams(params);
    models.Authors.findOne(params);
}

function testFindOneEagerMeanRequired() {
    var params = {
        where: {
            id: [1, 2, 3],
            name: ['Alice', 'Bob']
        },
        include: {
            model: models.Books,
            required: true
        }
    };
    toPhpParams(params);
    models.Authors.findOne(params);
}

function testFindOneEagerReversed() {
    var params = {
        include: models.Authors
    };
    toPhpParams(params);
    models.Books.findOne(params);
}

function testFindOneEagerReversedRequired() {
    var params = {
        include: {
            model: models.Authors,
            required: true
        }
    };
    toPhpParams(params);
    models.Books.findOne(params);
}

function testFindOneEagerReversedById() {
    var params = {
        where: {id: 1},
        include: models.Authors
    };
    toPhpParams(params);
    models.Books.findOne(params);
}

function testFindOneEagerReversedByIdRequired() {
    var params = {
        where: {id: 1},
        include: {
            model: models.Authors,
            required: true
        }
    };
    toPhpParams(params);
    models.Books.findOne(params);
}

function testFindOneEagerReversedByIdMean() {
    var params = {
        where: {
            id: [1, 2, 3],
            title: ['Alice', 'Bob']
        },
        include: models.Authors
    };
    toPhpParams(params);
    models.Books.findOne(params);
}

function testFindOneEagerReversedMean() {
    var params = {
        where: {
            id: {
                $in: [1, 2, 3],
                $gte: 2,
                $lt: 10
            },
            title: ['Alice', 'Bob']
        },
        include: models.Authors
    };
    toPhpParams(params);
    models.Books.findOne(params);
}

function testFindOneEagerReversedMeanRequired() {
    var params = {
        where: {
            id: {
                $in: [1, 2, 3],
                $gte: 2,
                $lt: 10
            },
            title: ['Alice', 'Bob']
        },
        include: {
            model: models.Authors,
            required: true
        }
    };
    toPhpParams(params);
    models.Books.findOne(params);
}

function testFindOneComplex() {
    var params = {
        where: {
            id: {
                $in: [1, 2, 3],
                $gte: 2,
                $lt: 10
            },
            name: {
                $notIn: ['Alice', 'Bob'],
                $ne: null
            }
        }
    };
    models.Authors.findOne(params);
    toPhpParams(params);
}

function testFindOneAndOr() {
    var params = {
        where: {
            $or: [
                {
                    $and: [
                        {id: 1},
                        {name: {$ne: null}},
                        {
                            $or: [
                                {id: 4},
                                {id: 5}
                            ]
                        }
                    ]
                },
                {
                    $or: [
                        {
                            $and: [
                                {name: {$like: 'Stephen'}},
                                {id: [22, 33]}
                            ]
                        },
                        {
                            $and: [
                                {id: {$notIn: [1, 2, 3]}},
                                {name: {$notLike: 'Tolkien'}}
                            ]
                        }
                    ]
                }
            ]
        }
    };
    models.Authors.findOne(params);
    toPhpParams(params);
}

function testFindOneEagerMulti() {
    var params = {
        include: [
            models.Authors,
            models.Reviews,
            models.PublishersBooks
        ]
    };
    models.Books.findOne(params);
    toPhpParams(params);
}

function testFindOneEagerMultiRequired() {
    var params = {
        include: [
            {
                model: models.Authors,
                required: true
            },
            {
                model: models.Reviews,
                required: true
            },
            models.PublishersBooks
        ]
    };
    models.Books.findOne(params);
    toPhpParams(params);
}

/**
 * Note: when an eager loaded model is filtered using include.where then include.required is implicitly set to true.
 */
function testFindOneEagerMultiWhere() {
    var params = {
        include: [
            {
                model: models.Authors,
                where: {
                    name: {$like: '%tol%'}
                }
            },
            {
                model: models.Reviews,
                where: {
                    resource: {$notLike: 'wiki'}
                }
            },
            {
                model: models.PublishersBooks,
                where: {
                    pressrun: {$gt: 10000}
                }
            }
        ]
    };
    models.Books.findOne(params);
    toPhpParams(params);
}

function testFindOneEagerNested() {
    var params = {
        include: {
            model: models.Books,
            where: {
                title: {$notLike: 'sad'},
                $or: [
                    {year: {$lt: 1920}},
                    {year: {$gt: 1930}}
                ]
            },
            attributes: ['year', 'title'],
            include: [
                models.Reviews,
                {
                    model: models.PublishersBooks,
                    where: {
                        $or: [
                            {
                                $and: [
                                    {mistakes: {$ne: ''}},
                                    {pressrun: {$gte: 5000}}
                                ]
                            },
                            {year: 1855}
                        ]
                    },
                    attributes: ['year', 'pressrun', 'mistakes']
                }
            ]
        }
    };
    models.Authors.findOne(params);
    toPhpParams(params);
}

function testFindOneEagerNestedById() {
    var params = {
        where: {
            id: {
                $in: [1, 2, 3],
                $gte: 2,
                $lt: 10
            }
        },
        include: {
            model: models.Books,
            where: {
                title: {$notLike: 'sad'},
                $or: [
                    {year: {$lt: 1920}},
                    {year: {$gt: 1930}}
                ]
            },
            attributes: ['year', 'title'],
            include: [
                models.Reviews,
                {
                    model: models.PublishersBooks,
                    where: {
                        $or: [
                            {
                                $and: [
                                    {mistakes: {$ne: ''}},
                                    {pressrun: {$gte: 5000}}
                                ]
                            },
                            {year: 1855}
                        ]
                    },
                    attributes: ['year', 'pressrun', 'mistakes']
                }
            ]
        }
    };
    models.Authors.findOne(params);
    toPhpParams(params);
}

function testFindOneEagerNestedMean() {
    var params = {
        where: {
            id: {
                $gte: 0,
                $lt: 10
            },
            name: {
                $or: [
                    {ne: 'Bob'},
                    {ne: 'Alice'}
                ]
            }
        },
        include: {
            model: models.Books,
            where: {
                title: {$notLike: 'sad'},
                $or: [
                    {year: {$lt: 1920}},
                    {year: {$gt: 1930}}
                ]
            },
            attributes: ['year', 'title'],
            include: [
                models.Reviews,
                {
                    model: models.PublishersBooks,
                    where: {
                        $or: [
                            {
                                $and: [
                                    {mistakes: {$ne: ''}},
                                    {pressrun: {$gte: 5000}}
                                ]
                            },
                            {year: 1855}
                        ]
                    },
                    attributes: ['year', 'pressrun', 'mistakes']
                }
            ]
        }
    };
    models.Authors.findOne(params);
    toPhpParams(params);
}

function testFindOneEagerNestedDeep() {
    var params = {
        where: {
            id: {
                $gte: 0,
                $lt: 10
            },
            name: {
                $or: [
                    {ne: 'Bob'},
                    {ne: 'Alice'}
                ]
            }
        },
        include: {
            model: models.Books,
            where: {
                title: {$notLike: 'sad'},
                $or: [
                    {year: {$lt: 1920}},
                    {year: {$gt: 1930}}
                ]
            },
            attributes: ['year', 'title'],
            include: [
                models.Reviews,
                {
                    model: models.PublishersBooks,
                    where: {
                        $or: [
                            {
                                $and: [
                                    {mistakes: {$ne: ''}},
                                    {pressrun: {$gte: 5000}}
                                ]
                            },
                            {year: 1855}
                        ]
                    },
                    attributes: ['year', 'pressrun', 'mistakes'],
                    include: [
                        {
                            model: models.Shipped,
                            where: {
                                quantity: {$gt: 300}
                            }
                        },
                        {
                            model: models.Preorders,
                            where: {
                                quantity: {$lt: 155000}
                            },
                            required: false
                        }
                    ]
                }
            ]
        }
    };
    models.Authors.findOne(params);
    toPhpParams(params);
}

function testFindAll() {
    models.Authors.findAll();
}
