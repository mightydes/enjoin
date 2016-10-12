module.exports = function (seq, dt) {
    return seq.define('pile', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        date_till: {type: dt.DATE}
    }, {
        tableName: 'pile'
    });
};
