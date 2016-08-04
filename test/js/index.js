var models = require('./models');
var debug = require('debug')('phpunit');
var _ = require('underscore');
var fs = require('fs');

module.exports = {
    createTables: require('./create-tables'),
    createCompareTrait: require('./create-compare-trait'),
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
    testFindOneEagerSelfNestedNoSubQuery: testFindOneEagerSelfNestedNoSubQuery,

    testFindAll: testFindAll,
    testFindAllEagerOneThenMany: testFindAllEagerOneThenMany,
    testFindAllEagerOneThenManyMean: testFindAllEagerOneThenManyMean,
    testFindAllEagerOneThenManyMeanOrdered: testFindAllEagerOneThenManyMeanOrdered,
    testFindAllEagerNestedDeep: testFindAllEagerNestedDeep,
    testFindAllEagerNestedDeepLimited: testFindAllEagerNestedDeepLimited,

    testFindAndCountAll: testFindAndCountAll,
    testFindAndCountAllConditional: testFindAndCountAllConditional,
    testFindAndCountAllEagerOneThenMany: testFindAndCountAllEagerOneThenMany,
    testFindAndCountAllEagerOneThenManyMean: testFindAndCountAllEagerOneThenManyMean,
    testFindAndCountAllEagerRequired: testFindAndCountAllEagerRequired,

    testDestroy: testDestroy
};

function camelize(str) {
    return str.replace(/(?:^|[-_])(\w)/g, function (_, c) {
        return c ? c.toUpperCase() : '';
    });
}

function toPhpParams(params) {
    var out = handle(params);
    debug('Php params: ' + out);
    return out;

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

function saveCompare(name, model, method, params) {
    var phpParams = toPhpParams(params);
    params || (params = {});
    params.logging = function (sql) {
        debug(sql);
        var filename = __dirname + '/../php/compare/' + name + '.json';
        var data = {
            params: phpParams,
            sql: sql.replace(/Executing \(default\):\s*(.+);$/, '$1')
        };
        fs.writeFile(filename, JSON.stringify(data));
    };
    model[method](params);
}

function testFindById() {
    models.Authors.findById(1);
}

function testFindOneEager() {
    saveCompare('testFindOneEager', models.Authors, 'findOne', {
        include: models.Books
    });
}

function testFindOneEagerRequired() {
    saveCompare('testFindOneEagerRequired', models.Authors, 'findOne', {
        include: {
            model: models.Books,
            required: true
        }
    });
}

function testFindOneEagerById() {
    saveCompare('testFindOneEagerById', models.Authors, 'findOne', {
        where: {id: 1},
        include: models.Books
    });
}

function testFindOneEagerByIdRequired() {
    saveCompare('testFindOneEagerByIdRequired', models.Authors, 'findOne', {
        where: {id: 1},
        include: {
            model: models.Books,
            required: true
        }
    });
}

function testFindOneEagerByIdMean() {
    saveCompare('testFindOneEagerByIdMean', models.Authors, 'findOne', {
        where: {
            id: 1,
            name: ['Alice', 'Bob']
        },
        include: models.Books
    });
}

function testFindOneEagerMean() {
    saveCompare('testFindOneEagerMean', models.Authors, 'findOne', {
        where: {
            id: [1, 2, 3],
            name: ['Alice', 'Bob']
        },
        include: models.Books
    });
}

function testFindOneEagerMeanRequired() {
    saveCompare('testFindOneEagerMeanRequired', models.Authors, 'findOne', {
        where: {
            id: [1, 2, 3],
            name: ['Alice', 'Bob']
        },
        include: {
            model: models.Books,
            required: true
        }
    });
}

function testFindOneEagerReversed() {
    saveCompare('testFindOneEagerReversed', models.Books, 'findOne', {
        include: models.Authors
    });
}

function testFindOneEagerReversedRequired() {
    saveCompare('testFindOneEagerReversedRequired', models.Books, 'findOne', {
        include: {
            model: models.Authors,
            required: true
        }
    });
}

function testFindOneEagerReversedById() {
    saveCompare('testFindOneEagerReversedById', models.Books, 'findOne', {
        where: {id: 1},
        include: models.Authors
    });
}

function testFindOneEagerReversedByIdRequired() {
    saveCompare('testFindOneEagerReversedByIdRequired', models.Books, 'findOne', {
        where: {id: 1},
        include: {
            model: models.Authors,
            required: true
        }
    });
}

function testFindOneEagerReversedByIdMean() {
    saveCompare('testFindOneEagerReversedByIdMean', models.Books, 'findOne', {
        where: {
            id: [1, 2, 3],
            title: ['Alice', 'Bob']
        },
        include: models.Authors
    });
}

function testFindOneEagerReversedMean() {
    saveCompare('testFindOneEagerReversedMean', models.Books, 'findOne', {
        where: {
            id: {
                $in: [1, 2, 3],
                $gte: 2,
                $lt: 10
            },
            title: ['Alice', 'Bob']
        },
        include: models.Authors
    });
}

function testFindOneEagerReversedMeanRequired() {
    saveCompare('testFindOneEagerReversedMeanRequired', models.Books, 'findOne', {
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
    });
}

function testFindOneComplex() {
    saveCompare('testFindOneComplex', models.Authors, 'findOne', {
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
    });
}

function testFindOneAndOr() {
    saveCompare('testFindOneAndOr', models.Authors, 'findOne', {
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
    });
}

function testFindOneEagerMulti() {
    saveCompare('testFindOneEagerMulti', models.Books, 'findOne', {
        include: [
            models.Authors,
            models.Reviews,
            models.PublishersBooks
        ]
    });
}

function testFindOneEagerMultiRequired() {
    saveCompare('testFindOneEagerMultiRequired', models.Books, 'findOne', {
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
    });
}

/**
 * Note: when an eager loaded model is filtered using include.where then include.required is implicitly set to true.
 */
function testFindOneEagerMultiWhere() {
    saveCompare('testFindOneEagerMultiWhere', models.Books, 'findOne', {
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
    });
}

function testFindOneEagerNested() {
    saveCompare('testFindOneEagerNested', models.Authors, 'findOne', {
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
    });
}

function testFindOneEagerNestedById() {
    saveCompare('testFindOneEagerNestedById', models.Authors, 'findOne', {
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
    });
}

function testFindOneEagerNestedMean() {
    saveCompare('testFindOneEagerNestedMean', models.Authors, 'findOne', {
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
    });
}

function testFindOneEagerNestedDeep() {
    saveCompare('testFindOneEagerNestedDeep', models.Authors, 'findOne', {
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
    });
}

/**
 * Note: https://github.com/sequelize/sequelize/issues/3917
 */
function testFindOneEagerSelfNestedNoSubQuery() {
    saveCompare('testFindOneEagerSelfNestedNoSubQuery', models.Books, 'findOne', {
        where: {
            year: {$notIn: [1900, 1950, 2000]},
            $or: [
                {
                    $and: [
                        {id: {$lte: 5000}},
                        {year: {$gte: 500}}
                    ]
                },
                {
                    $and: [
                        {id: null},
                        {year: null}
                    ]
                }
            ]
        },
        subQuery: false,
        attributes: ['id'],
        include: {
            model: models.PublishersBooks,
            where: {
                books_id: 77
            },
            attributes: ['id'],
            include: {
                model: models.Publishers,
                where: {
                    pid: 99
                },
                attributes: ['id'],
                include: {
                    model: models.Publishers,
                    as: 'parent',
                    where: {
                        pid: 505
                    },
                    attributes: ['id'],
                    include: {
                        model: models.Publishers,
                        as: 'parent',
                        where: {
                            pid: 3443
                        },
                        attributes: ['id']
                    }
                }
            }
        }
    });
}

function testFindAll() {
    models.Authors.findAll();
}

function testFindAllEagerOneThenMany() {
    saveCompare('testFindAllEagerOneThenMany', models.Books, 'findAll', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    });
}

function testFindAllEagerOneThenManyMean() {
    saveCompare('testFindAllEagerOneThenManyMean', models.Books, 'findAll', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$like: '2%'}},
            include: {
                model: models.Articles,
                where: {year: {$like: '19%'}}
            }
        }
    });
}

function testFindAllEagerOneThenManyMeanOrdered() {
    saveCompare('testFindAllEagerOneThenManyMeanOrdered', models.Books, 'findAll', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$like: '2%'}},
            include: {
                model: models.Articles,
                where: {year: {$like: '19%'}}
            }
        },
        order: [
            'year',
            [models.Authors, models.Articles, 'year', 'DESC']
        ]
    });
}

function testFindAllEagerNestedDeep() {
    saveCompare('testFindAllEagerNestedDeep', models.Authors, 'findAll', {
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
    });
}

function testFindAllEagerNestedDeepLimited() {
    saveCompare('testFindAllEagerNestedDeepLimited', models.Authors, 'findAll', {
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
        },
        offset: 0,
        limit: 50
    });
}

function testFindAndCountAll() {
    models.Authors.findAndCountAll();
}

function testFindAndCountAllConditional() {
    saveCompare('testFindAndCountAllConditional', models.Books, 'findAndCountAll', {
        where: {
            id: {$lt: 5},
            title: {$like: 'My%'}
        }
    });
}

function testFindAndCountAllEagerOneThenMany() {
    saveCompare('testFindAndCountAllEagerOneThenMany', models.Books, 'findAndCountAll', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    });
}

function testFindAndCountAllEagerOneThenManyMean() {
    saveCompare('testFindAndCountAllEagerOneThenManyMean', models.Books, 'findAndCountAll', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$like: '2%'}},
            include: {
                model: models.Articles,
                where: {year: {$like: '19%'}}
            }
        }
    });
}

function testFindAndCountAllEagerRequired() {
    saveCompare('testFindAndCountAllEagerRequired', models.Authors, 'findAndCountAll', {
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
            include: [
                models.Reviews,
                {
                    model: models.PublishersBooks,
                    required: true
                }
            ]
        }
    });
}

// TODO: limit, order

function testDestroy() {
    models.Languages.destroy({
        where: {id: {$gt: 5}}
    }).then(function () {
        debug(arguments);
    });
}
