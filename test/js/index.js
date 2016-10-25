var models = require('./models');
var compare = require('./compare');

module.exports = {
    testModelCreate: testModelCreate,
    testModelCreateEmpty: testModelCreateEmpty,
    testModelCreateWithDateField: testModelCreateWithDateField,

    testModelFindById: testModelFindById,

    testModelFindOneEager: testModelFindOneEager,
    testModelFindOneEagerRequired: testModelFindOneEagerRequired,
    testModelFindOneEagerById: testModelFindOneEagerById,
    testModelFindOneEagerByIdRequired: testModelFindOneEagerByIdRequired,
    testModelFindOneEagerByIdMean: testModelFindOneEagerByIdMean,
    testModelFindOneEagerMean: testModelFindOneEagerMean,
    testModelFindOneEagerMeanRequired: testModelFindOneEagerMeanRequired,

    testModelFindOneEagerReversed: testModelFindOneEagerReversed,
    testModelFindOneEagerReversedRequired: testModelFindOneEagerReversedRequired,
    testModelFindOneEagerReversedById: testModelFindOneEagerReversedById,
    testModelFindOneEagerReversedByIdRequired: testModelFindOneEagerReversedByIdRequired,
    testModelFindOneEagerReversedByIdMean: testModelFindOneEagerReversedByIdMean,
    testModelFindOneEagerReversedMean: testModelFindOneEagerReversedMean,
    testModelFindOneEagerReversedMeanRequired: testModelFindOneEagerReversedMeanRequired,

    testModelFindOneComplex: testModelFindOneComplex,
    testModelFindOneAndOr: testModelFindOneAndOr,

    testModelFindOneEagerMulti: testModelFindOneEagerMulti,
    testModelFindOneEagerMultiRequired: testModelFindOneEagerMultiRequired,
    testModelFindOneEagerMultiWhere: testModelFindOneEagerMultiWhere,

    testModelFindOneEagerNested: testModelFindOneEagerNested,
    testModelFindOneEagerNestedById: testModelFindOneEagerNestedById,
    testModelFindOneEagerNestedMean: testModelFindOneEagerNestedMean,
    testModelFindOneEagerNestedDeep: testModelFindOneEagerNestedDeep,
    testModelFindOneEagerSelfNestedNoSubQuery: testModelFindOneEagerSelfNestedNoSubQuery,

    testModelFindAll: testModelFindAll,
    testModelFindAllEmptyList: testModelFindAllEmptyList,
    testModelFindAllEagerOneThenMany: testModelFindAllEagerOneThenMany,
    testModelFindAllEagerOneThenManyMean: testModelFindAllEagerOneThenManyMean,
    testModelFindAllEagerOneThenManyMeanOrdered: testModelFindAllEagerOneThenManyMeanOrdered,
    testModelFindAllEagerOneThenManyMeanGrouped: testModelFindAllEagerOneThenManyMeanGrouped,
    testModelFindAllEagerNestedDeep: testModelFindAllEagerNestedDeep,
    testModelFindAllEagerNestedDeepLimited: testModelFindAllEagerNestedDeepLimited,

    testModelCount: testModelCount,
    testModelCountConditional: testModelCountConditional,
    testModelCountEagerOneThenMany: testModelCountEagerOneThenMany,
    testModelCountEagerOneThenManyMean: testModelCountEagerOneThenManyMean,
    testModelCountEagerRequired: testModelCountEagerRequired,
    testModelCountEagerRequiredLimited: testModelCountEagerRequiredLimited,

    testModelFindAndCountAll: testModelFindAndCountAll,
    testModelFindAndCountAllConditional: testModelFindAndCountAllConditional,
    testModelFindAndCountAllEagerOneThenMany: testModelFindAndCountAllEagerOneThenMany,
    testModelFindAndCountAllEagerOneThenManyMean: testModelFindAndCountAllEagerOneThenManyMean,
    testModelFindAndCountAllEagerRequired: testModelFindAndCountAllEagerRequired,
    testModelFindAndCountAllEagerRequiredLimited: testModelFindAndCountAllEagerRequiredLimited,

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

function testModelFindById(callback) {
    compare.save('testModelFindById', models.Authors, 'findById', 1).then(callback);
}

function testModelFindOneEager(callback) {
    compare.save('testModelFindOneEager', models.Authors, 'findOne', {
        include: models.Books
    }).then(callback);
}

function testModelFindOneEagerRequired(callback) {
    compare.save('testModelFindOneEagerRequired', models.Authors, 'findOne', {
        include: {
            model: models.Books,
            required: true
        }
    }).then(callback);
}

function testModelFindOneEagerById(callback) {
    compare.save('testModelFindOneEagerById', models.Authors, 'findOne', {
        where: {id: 1},
        include: models.Books
    }).then(callback);
}

function testModelFindOneEagerByIdRequired(callback) {
    compare.save('testModelFindOneEagerByIdRequired', models.Authors, 'findOne', {
        where: {id: 1},
        include: {
            model: models.Books,
            required: true
        }
    }).then(callback);
}

function testModelFindOneEagerByIdMean(callback) {
    compare.save('testModelFindOneEagerByIdMean', models.Authors, 'findOne', {
        where: {
            id: 1,
            name: ['Alice', 'Bob']
        },
        include: models.Books
    }).then(callback);
}

function testModelFindOneEagerMean(callback) {
    compare.save('testModelFindOneEagerMean', models.Authors, 'findOne', {
        where: {
            id: [1, 2, 3],
            name: ['Alice', 'Bob']
        },
        include: models.Books
    }).then(callback);
}

function testModelFindOneEagerMeanRequired(callback) {
    compare.save('testModelFindOneEagerMeanRequired', models.Authors, 'findOne', {
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

function testModelFindOneEagerReversed(callback) {
    compare.save('testModelFindOneEagerReversed', models.Books, 'findOne', {
        include: models.Authors
    }).then(callback);
}

function testModelFindOneEagerReversedRequired(callback) {
    compare.save('testModelFindOneEagerReversedRequired', models.Books, 'findOne', {
        include: {
            model: models.Authors,
            required: true
        }
    }).then(callback);
}

function testModelFindOneEagerReversedById(callback) {
    compare.save('testModelFindOneEagerReversedById', models.Books, 'findOne', {
        where: {id: 1},
        include: models.Authors
    }).then(callback);
}

function testModelFindOneEagerReversedByIdRequired(callback) {
    compare.save('testModelFindOneEagerReversedByIdRequired', models.Books, 'findOne', {
        where: {id: 1},
        include: {
            model: models.Authors,
            required: true
        }
    }).then(callback);
}

function testModelFindOneEagerReversedByIdMean(callback) {
    compare.save('testModelFindOneEagerReversedByIdMean', models.Books, 'findOne', {
        where: {
            id: [1, 2, 3],
            title: ['Alice', 'Bob']
        },
        include: models.Authors
    }).then(callback);
}

function testModelFindOneEagerReversedMean(callback) {
    compare.save('testModelFindOneEagerReversedMean', models.Books, 'findOne', {
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

function testModelFindOneEagerReversedMeanRequired(callback) {
    compare.save('testModelFindOneEagerReversedMeanRequired', models.Books, 'findOne', {
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

function testModelFindOneComplex(callback) {
    compare.save('testModelFindOneComplex', models.Authors, 'findOne', {
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

function testModelFindOneAndOr(callback) {
    compare.save('testModelFindOneAndOr', models.Authors, 'findOne', {
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

function testModelFindOneEagerMulti(callback) {
    compare.save('testModelFindOneEagerMulti', models.Books, 'findOne', {
        include: [
            models.Authors,
            models.Reviews,
            models.PublishersBooks
        ]
    }).then(callback);
}

function testModelFindOneEagerMultiRequired(callback) {
    compare.save('testModelFindOneEagerMultiRequired', models.Books, 'findOne', {
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
function testModelFindOneEagerMultiWhere(callback) {
    compare.save('testModelFindOneEagerMultiWhere', models.Books, 'findOne', {
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

function testModelFindOneEagerNested(callback) {
    compare.save('testModelFindOneEagerNested', models.Authors, 'findOne', {
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

function testModelFindOneEagerNestedById(callback) {
    compare.save('testModelFindOneEagerNestedById', models.Authors, 'findOne', {
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

function testModelFindOneEagerNestedMean(callback) {
    compare.save('testModelFindOneEagerNestedMean', models.Authors, 'findOne', {
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

function testModelFindOneEagerNestedDeep(callback) {
    compare.save('testModelFindOneEagerNestedDeep', models.Authors, 'findOne', {
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
function testModelFindOneEagerSelfNestedNoSubQuery(callback) {
    compare.save('testModelFindOneEagerSelfNestedNoSubQuery', models.Books, 'findOne', {
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

function testModelFindAll(callback) {
    compare.save('testModelFindAll', models.Authors, 'findAll').then(callback);
}

function testModelFindAllEmptyList(callback) {
    compare.save('testModelFindAllEmptyList', models.Books, 'findAll', {
        where: {
            id: [],
            title: {$like: '%cloud%'},
            year: {$ne: null}
        }
    }).then(callback);
}

function testModelFindAllEagerOneThenMany(callback) {
    compare.save('testModelFindAllEagerOneThenMany', models.Books, 'findAll', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    }).then(callback);
}

function testModelFindAllEagerOneThenManyMean(callback) {
    compare.save('testModelFindAllEagerOneThenManyMean', models.Books, 'findAll', {
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

function testModelFindAllEagerOneThenManyMeanOrdered(callback) {
    compare.save('testModelFindAllEagerOneThenManyMeanOrdered', models.Books, 'findAll', {
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

function testModelFindAllEagerOneThenManyMeanGrouped(callback) {
    compare.save('testModelFindAllEagerOneThenManyMeanGrouped', models.Books, 'findAll', {
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

function testModelFindAllEagerNestedDeep(callback) {
    compare.save('testModelFindAllEagerNestedDeep', models.Authors, 'findAll', {
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

function testModelFindAllEagerNestedDeepLimited(callback) {
    compare.save('testModelFindAllEagerNestedDeepLimited', models.Authors, 'findAll', {
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

function testModelCount(callback) {
    compare.save('testModelCount', models.Authors, 'count').then(callback);
}

function testModelCountConditional(callback) {
    compare.save('testModelCountConditional', models.Books, 'count', {
        where: {
            id: {$lt: 5},
            title: {$like: 'My%'}
        }
    }).then(callback);
}

function testModelCountEagerOneThenMany(callback) {
    compare.save('testModelCountEagerOneThenMany', models.Books, 'count', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    }).then(callback);
}

function testModelCountEagerOneThenManyMean(callback) {
    compare.save('testModelCountEagerOneThenManyMean', models.Books, 'count', {
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

function testModelCountEagerRequired(callback) {
    compare.save('testModelCountEagerRequired', models.Authors, 'count', {
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

function testModelCountEagerRequiredLimited(callback) {
    compare.save('testModelCountEagerRequiredLimited', models.Authors, 'count', {
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

function testModelFindAndCountAll(callback) {
    compare.save('testModelFindAndCountAll', models.Authors, 'findAndCountAll').then(callback);
}

function testModelFindAndCountAllConditional(callback) {
    compare.save('testModelFindAndCountAllConditional', models.Books, 'findAndCountAll', {
        where: {
            id: {$lt: 5},
            title: {$like: 'My%'}
        }
    }).then(callback);
}

function testModelFindAndCountAllEagerOneThenMany(callback) {
    compare.save('testModelFindAndCountAllEagerOneThenMany', models.Books, 'findAndCountAll', {
        include: {
            model: models.Authors,
            include: models.Articles
        }
    }).then(callback);
}

function testModelFindAndCountAllEagerOneThenManyMean(callback) {
    compare.save('testModelFindAndCountAllEagerOneThenManyMean', models.Books, 'findAndCountAll', {
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

function testModelFindAndCountAllEagerRequired(callback) {
    compare.save('testModelFindAndCountAllEagerRequired', models.Authors, 'findAndCountAll', {
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

function testModelFindAndCountAllEagerRequiredLimited(callback) {
    compare.save('testModelFindAndCountAllEagerRequiredLimited', models.Authors, 'findAndCountAll', {
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
