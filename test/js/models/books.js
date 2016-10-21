module.exports = function (seq, dt) {
    return seq.define('books', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        authors_id: {type: dt.INTEGER},
        languages_id: {type: dt.INTEGER},
        title: {type: dt.STRING},
        year: {type: dt.INTEGER}
    });
};
