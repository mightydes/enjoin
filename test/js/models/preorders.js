module.exports = function (seq, dt) {
    return seq.define('preorders', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        publishers_books_id: {type: dt.INTEGER},
        person: {type: dt.STRING},
        quantity: {type: dt.INTEGER}
    });
};
