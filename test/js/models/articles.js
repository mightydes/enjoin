module.exports = function (seq, dt) {
    return seq.define('articles', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        authors_id: {type: dt.INTEGER},
        year: {type: dt.INTEGER},
        title: {type: dt.STRING},
        content: {type: dt.TEXT}
    });
};
