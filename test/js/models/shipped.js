module.exports = function (seq, dt) {
    return seq.define('shipped', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        publishers_books_id: {type: dt.INTEGER},
        destination: {type: dt.TEXT},
        quantity: {type: dt.INTEGER},
        sent_at: {type: dt.DATE}
    }, {
        tableName: 'shipped'
    });
};
