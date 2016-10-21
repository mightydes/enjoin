module.exports = function (seq, dt) {
    return seq.define('publishers', {
        id: {type: dt.INTEGER, primaryKey: true, autoIncrement: true},
        pid: {type: dt.INTEGER},
        name: {type: dt.STRING}
    });
};
