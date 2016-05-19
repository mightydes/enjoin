var Sequelize = require('sequelize');
var path = require('path');
var debug = require('debug')('sequelize');

const DATABASE = process.env.ENJ_DATABASE;
const USERNAME = process.env.ENJ_USERNAME;
const PASSWORD = process.env.ENJ_PASSWORD;

var sequelize = new Sequelize(DATABASE, USERNAME, PASSWORD, {
    host: 'localhost',
    port: 3306,
    logging: debug,
    define: {
        underscored: true,
        freezeTableName: false,
        syncOnAssociation: true,
        charset: 'utf8',
        collate: 'utf8_general_ci',
        timestamps: true
    }
});

module.exports = describeRelations({
    sequelize: sequelize,
    Authors: sequelize.import('./authors'),
    Languages: sequelize.import('./languages'),
    Books: sequelize.import('./books'),
    Reviews: sequelize.import('./reviews'),
    Publishers: sequelize.import('./publishers'),
    PublishersBooks: sequelize.import('./publishers-books'),
    Preorders: sequelize.import('./preorders'),
    Shipped: sequelize.import('./shipped')
});

function describeRelations(ctx) {
    ctx.Authors.hasMany(ctx.Books, {foreignKey: 'authors_id'});
    ctx.Books.belongsTo(ctx.Authors, {foreignKey: 'authors_id'});

    ctx.Languages.hasMany(ctx.Books, {foreignKey: 'languages_id'});
    ctx.Books.belongsTo(ctx.Languages, {foreignKey: 'languages_id'});

    ctx.Books.hasMany(ctx.PublishersBooks, {foreignKey: 'books_id'});
    ctx.PublishersBooks.belongsTo(ctx.Books, {foreignKey: 'books_id'});

    ctx.Books.hasMany(ctx.Reviews, {foreignKey: 'books_id'});
    ctx.Reviews.belongsTo(ctx.Books, {foreignKey: 'books_id'});

    ctx.Publishers.hasMany(ctx.PublishersBooks, {foreignKey: 'publishers_id'});
    ctx.PublishersBooks.belongsTo(ctx.Publishers, {foreignKey: 'publishers_id'});

    ctx.PublishersBooks.hasMany(ctx.Preorders, {foreignKey: 'publishers_books_id'});
    ctx.Preorders.belongsTo(ctx.PublishersBooks, {foreignKey: 'publishers_books_id'});

    ctx.PublishersBooks.hasMany(ctx.Shipped, {foreignKey: 'publishers_books_id'});
    ctx.Shipped.belongsTo(ctx.PublishersBooks, {foreignKey: 'publishers_books_id'});

    return ctx;
}
