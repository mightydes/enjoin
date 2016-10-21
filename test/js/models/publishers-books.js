module.exports = function (seq, dt) {
    return seq.define('publishers_books', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        publishers_id: {type: dt.INTEGER},
        books_id: {type: dt.INTEGER},
        year: {type: dt.INTEGER},
        pressrun: {type: dt.INTEGER},
        mistakes: {type: dt.TEXT}
    }, {
        timestamps: false
    });
};
