var models = require('./models');
var compare = require('./compare');

module.exports = {
    testModelCreate: testModelCreate,
    testModelCreateEmpty: testModelCreateEmpty,
    testModelCreateWithDateField: testModelCreateWithDateField,

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
    testFindAllEmptyList: testFindAllEmptyList,
    testFindAllEagerOneThenMany: testFindAllEagerOneThenMany,
    testFindAllEagerOneThenManyMean: testFindAllEagerOneThenManyMean,
    testFindAllEagerOneThenManyMeanOrdered: testFindAllEagerOneThenManyMeanOrdered,
    testFindAllEagerOneThenManyMeanGrouped: testFindAllEagerOneThenManyMeanGrouped,
    testFindAllEagerNestedDeep: testFindAllEagerNestedDeep,
    testFindAllEagerNestedDeepLimited: testFindAllEagerNestedDeepLimited,

    testCount: testCount,
    testCountConditional: testCountConditional,
    testCountEagerOneThenMany: testCountEagerOneThenMany,
    testCountEagerOneThenManyMean: testCountEagerOneThenManyMean,
    testCountEagerRequired: testCountEagerRequired,
    testCountEagerRequiredLimited: testCountEagerRequiredLimited,

    testFindAndCountAll: testFindAndCountAll,
    testFindAndCountAllConditional: testFindAndCountAllConditional,
    testFindAndCountAllEagerOneThenMany: testFindAndCountAllEagerOneThenMany,
    testFindAndCountAllEagerOneThenManyMean: testFindAndCountAllEagerOneThenManyMean,
    testFindAndCountAllEagerRequired: testFindAndCountAllEagerRequired,
    testFindAndCountAllEagerRequiredLimited: testFindAndCountAllEagerRequiredLimited,

    testModelDestroy: testModelDestroy,
    testModelUpdate: testModelUpdate
};

function testModelCreate(callback) {
    compare.save('testModelCreate', models.Publishers, 'create', {
        name: 'Good Books!'
    }).then(callback);
}

function testModelCreateEmpty(callback) {
    compare.save('testModelCreateEmpty', models.Languages, 'create').then(callback);
    // Same result for `create({})`...
}

function testModelCreateWithDateField(callback) {
    compare.save('testModelCreateWithDateField', models.Pile, 'create', {
        date_till: new Date
    }).then(callback);
}

function testFindById(callback) {
    compare.save('testFindById', models.Authors, 'findById', 1).then(callback);
}

function testFindOneEager(callback) {
    compare.save('testFindOneEager', models.Authors, 'findOne', {
        include: models.Books
    }).then(callback);
}

function testFindOneEagerRequired(callback) {
    compare.save('testFindOneEagerRequired', models.Authors, 'findOne', {
        include: {
            model: models.Books,
            required: true
        }
    }).then(callback);
}

function testFindOneEagerById(callback) {
    compare.save('testFindOneEagerById', models.Authors, 'findOne', {
        where: {id: 1},
        include: models.Books
    }).then(callback);
}

function testFindOneEagerByIdRequired(callback) {
    compare.save('testFindOneEagerByIdRequired', models.Authors, 'findOne', {
        where: {id: 1},
        include: {
            model: models.Books,
            required: true
        }
    }).then(callback);
}

function testFindOneEagerByIdMean(callback) {
    compare.save('testFindOneEagerByIdMean', models.Authors, 'findOne', {
        where: {
            id: 1,
            name: ['Alice', 'Bob']
        },
        include: models.Books
    }).then(callback);
}

function testFindOneEagerMean(callback) {
    compare.save('testFindOneEagerMean', models.Authors, 'findOne', {
        where: {
            id: [1, 2, 3],
            name: ['Alice', 'Bob']
        },
        include: models.Books
    }).then(callback);
}

function testFindOneEagerMeanRequired(callback) {
    compare.save('testFindOneEagerMeanRequired', models.Authors, 'findOne', {
        where: {
            id: [1, 2, 3],
            name: ['Alice', 'Bob']
        },
        include: {
            model: models.Books,
            required: true
        }
    }).then(callback);
}

function testFindOneEagerReversed(callback) {
    compare.save('testFindOneEagerReversed', models.Books, 'findOne', {
        include: models.Authors
    }).then(callback);
}

function testFindOneEagerReversedRequired(callback) {
    compare.save('testFindOneEagerReversedRequired', models.Books, 'findOne', {
        include: {
            model: models.Authors,
            required: true
        }
    }).then(callback);
}

function testFindOneEagerReversedById(callback) {
    compare.save('testFindOneEagerReversedById', models.Books, 'findOne', {
        where: {id: 1},
        include: models.Authors
    }).then(callback);
}

function testFindOneEagerReversedByIdRequired(callback) {
    compare.save('testFindOneEagerReversedByIdRequired', models.Books, 'findOne', {
        where: {id: 1},
        include: {
            model: models.Authors,
            required: true
        }
    }).then(callback);
}

function testFindOneEagerReversedByIdMean(callback) {
    compare.save('testFindOneEagerReversedByIdMean', models.Books, 'findOne', {
        where: {
            id: [1, 2, 3],
            title: ['Alice', 'Bob']
        },
        include: models.Authors
    }).then(callback);
}

function testFindOneEagerReversedMean(callback) {
    compare.save('testFindOneEagerReversedMean', models.Books, 'findOne', {
        where: {
            id: {
                $in: [1, 2, 3],
                $gte: 2,
                $lt: 10
            },
            title: ['Alice', 'Bob']
        },
        include: models.Authors
    }).then(callback);
}

function testFindOneEagerReversedMeanRequired(callback) {
    compare.save('testFindOneEagerReversedMeanRequired', models.Books, 'findOne', {
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
    }).then(callback);
}

function testFindOneComplex(callback) {
    compare.save('testFindOneComplex', models.Authors, 'findOne', {
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
    }).then(callback);
}

function testFindOneAndOr(callback) {
    compare.save('testFindOneAndOr', models.Authors, 'findOne', {
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
    }).then(callback);
}

function testFindOneEagerMulti(callback) {
    compare.save('testFindOneEagerMulti', models.Books, 'findOne', {
        include: [
            models.Authors,
            models.Reviews,
            models.PublishersBooks
        ]
    }).then(callback);
}

function testFindOneEagerMultiRequired(callback) {
    compare.save('testFindOneEagerMultiRequired', models.Books, 'findOne', {
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
    }).then(callback);
}

/**
 * Note: when an eager loaded model is filtered using include.where then include.required is implicitly set to true.
 */
function testFindOneEagerMultiWhere(callback) {
    compare.save('testFindOneEagerMultiWhere', models.Books, 'findOne', {
        include: [
            {
                model: models.Authors,
                where: {
                    name: {$like: '%Tol%'}
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
    }).then(callback);
}

function testFindOneEagerNested(callback) {
    compare.save('testFindOneEagerNested', models.Authors, 'findOne', {
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
    }).then(callback);
}

function testFindOneEagerNestedById(callback) {
    compare.save('testFindOneEagerNestedById', models.Authors, 'findOne', {
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
    }).then(callback);
}

function testFindOneEagerNestedMean(callback) {
    compare.save('testFindOneEagerNestedMean', models.Authors, 'findOne', {
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
    }).then(callback);
}

function testFindOneEagerNestedDeep(callback) {
    compare.save('testFindOneEagerNestedDeep', models.Authors, 'findOne', {
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
    }).then(callback);
}

/**
 * Note: https://github.com/sequelize/sequelize/issues/3917
 */
function testFindOneEagerSelfNestedNoSubQuery(callback) {
    compare.save('testFindOneEagerSelfNestedNoSubQuery', models.Books, 'findOne', {
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
    }).then(callback);
}

function testFindAll(callback) {
    compare.save('testFindAll', models.Authors, 'findAll').then(callback);
}

function testFindAllEmptyList(callback) {
    compare.save('testFindAllEmptyList', models.Books, 'findAll', {
        where: {
            id: [],
            title: {$like: '%cloud%'},
            year: {$ne: null}
        }
    }).then(callback);
}

function testFindAllEagerOneThenMany(callback) {
    compare.save('testFindAllEagerOneThenMany', models.Books, 'findAll', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    }).then(callback);
}

function testFindAllEagerOneThenManyMean(callback) {
    compare.save('testFindAllEagerOneThenManyMean', models.Books, 'findAll', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$gte: 2}},
            include: {
                model: models.Articles,
                where: {year: {$gte: 1900}}
            }
        }
    }).then(callback);
}

function testFindAllEagerOneThenManyMeanOrdered(callback) {
    compare.save('testFindAllEagerOneThenManyMeanOrdered', models.Books, 'findAll', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$gte: 2}},
            include: {
                model: models.Articles,
                where: {year: {$gte: 1900}}
            }
        },
        order: [
            'year',
            [models.Authors, models.Articles, 'year', 'DESC']
        ]
    }).then(callback);
}

function testFindAllEagerOneThenManyMeanGrouped(callback) {
    compare.save('testFindAllEagerOneThenManyMeanGrouped', models.Books, 'findAll', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$gte: 2}},
            include: {
                model: models.Articles,
                where: {year: {$gte: 1900}}
            }
        },
        group: [
            [models.Authors, models.Articles, 'year'],
            [models.Authors, models.Articles, 'id'],
            [models.Authors, 'id'],
            'books.id'
        ]
    }).then(callback);
}

function testFindAllEagerNestedDeep(callback) {
    compare.save('testFindAllEagerNestedDeep', models.Authors, 'findAll', {
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
    }).then(callback);
}

function testFindAllEagerNestedDeepLimited(callback) {
    compare.save('testFindAllEagerNestedDeepLimited', models.Authors, 'findAll', {
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
    }).then(callback);
}

function testCount(callback) {
    compare.save('testCount', models.Authors, 'count').then(callback);
}

function testCountConditional(callback) {
    compare.save('testCountConditional', models.Books, 'count', {
        where: {
            id: {$lt: 5},
            title: {$like: 'My%'}
        }
    }).then(callback);
}

function testCountEagerOneThenMany(callback) {
    compare.save('testCountEagerOneThenMany', models.Books, 'count', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    }).then(callback);
}

function testCountEagerOneThenManyMean(callback) {
    compare.save('testCountEagerOneThenManyMean', models.Books, 'count', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$gte: 2}},
            include: {
                model: models.Articles,
                where: {year: {$gte: 1900}}
            }
        }
    }).then(callback);
}

function testCountEagerRequired(callback) {
    compare.save('testCountEagerRequired', models.Authors, 'count', {
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
    }).then(callback);
}

function testCountEagerRequiredLimited(callback) {
    compare.save('testCountEagerRequiredLimited', models.Authors, 'count', {
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
        },
        limit: 25,
        offset: 7
    }).then(callback);
}

function testFindAndCountAll(callback) {
    compare.save('testFindAndCountAll', models.Authors, 'findAndCountAll').then(callback);
}

function testFindAndCountAllConditional(callback) {
    compare.save('testFindAndCountAllConditional', models.Books, 'findAndCountAll', {
        where: {
            id: {$lt: 5},
            title: {$like: 'My%'}
        }
    }).then(callback);
}

function testFindAndCountAllEagerOneThenMany(callback) {
    compare.save('testFindAndCountAllEagerOneThenMany', models.Books, 'findAndCountAll', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    }).then(callback);
}

function testFindAndCountAllEagerOneThenManyMean(callback) {
    compare.save('testFindAndCountAllEagerOneThenManyMean', models.Books, 'findAndCountAll', {
        where: {id: {$lt: 5}},
        include: {
            model: models.Authors,
            where: {id: {$gte: 2}},
            include: {
                model: models.Articles,
                where: {year: {$gte: 1900}}
            }
        }
    }).then(callback);
}

function testFindAndCountAllEagerRequired(callback) {
    compare.save('testFindAndCountAllEagerRequired', models.Authors, 'findAndCountAll', {
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
    }).then(callback);
}

function testFindAndCountAllEagerRequiredLimited(callback) {
    compare.save('testFindAndCountAllEagerRequiredLimited', models.Authors, 'findAndCountAll', {
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
        },
        limit: 25,
        offset: 7
    }).then(callback);
}

function testModelDestroy(callback) {
    compare.save('testModelDestroy', models.Authors, 'destroy', {
        where: {name: {$like: 'Samuel%'}}
    }).then(callback);
}

function testModelUpdate(callback) {
    compare.save('testModelUpdate', models.Languages, 'update', {
        name: 'Korean'
    }, {
        where: {id: 200}
    }).then(callback);
}
