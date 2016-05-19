module.exports = function (seq, dt) {
    return seq.define('authors', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        name: {type: dt.STRING}
    });
};
