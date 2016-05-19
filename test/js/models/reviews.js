module.exports = function (seq, dt) {
    return seq.define('reviews', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        books_id: {type: dt.INTEGER},
        resource: {type: dt.STRING},
        content: {type: dt.TEXT}
    });
};
