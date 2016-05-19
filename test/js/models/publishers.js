module.exports = function (seq, dt) {
    return seq.define('publishers', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        name: {type: dt.STRING}
    });
};
