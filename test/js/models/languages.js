module.exports = function (seq, dt) {
    return seq.define('languages', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        name: {type: dt.STRING}
    }, {
        timestamps: false
    });
};
