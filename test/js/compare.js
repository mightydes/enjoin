var debug = require('debug')('compare');
var fs = require('fs');
var _ = require('underscore');

module.exports = {
    save: save
};

function save(name, model, method, params) {
    switch (method) {
        case 'findById':
            return saveFindById(name, model, params);
        case 'create':
            return saveCreate(name, model, params);
        case 'update':
            return saveUpdate(name, model, params, arguments[4]);
        case 'destroy':
            return saveDestroy(name, model, params);
        default:
            var phpParams = toPhpParams(params);
            params || (params = {});
            params.logging = createLogging(name, {
                params: phpParams
            });
            return model[method](params);
    }
}

function saveFindById(name, model, id) {
    var options = {};
    options.logging = createLogging(name);
    return model.findById(id, options);
}

function saveCreate(name, model, collection) {
    var options = {};
    options.logging = createLogging(name, {
        collection: toPhpParams(collection)
    });
    return model.create(collection, options);
}

function saveUpdate(name, model, collection, params) {
    var phpParams = toPhpParams(params);
    var phpCollection = toPhpParams(collection);
    params.logging = createLogging(name, {
        params: phpParams,
        collection: phpCollection
    });
    return model.update(collection, params);
}

function saveDestroy(name, model, options) {
    var phpParams = toPhpParams(options);
    options.logging = createLogging(name, {
        params: phpParams
    });
    return model.destroy(options);
}

function createLogging(name, data) {
    data || (data = {});
    return function (sql) {
        debug(sql);
        var filename = __dirname + '/../php/compare/' + name + '.json';
        var re = /Executing \(default\):\s*([^;]+)/g;
        if (fs.existsSync(filename)) {
            data = _.extend(JSON.parse(fs.readFileSync(filename, 'utf8')), data);
        }
        data[process.env.ENJ_DIALECT] = re.exec(sql)[1];
        fs.writeFile(filename, JSON.stringify(data, null, 2));
    };
}

function toPhpParams(params) {
    var out = handle(params);
    debug('Php params: ' + out);
    return out;

    function handle(params) {
        var r = '[';
        var length = _.isArray(params) ? params.length : _.allKeys(params).length;
        var i = 0;
        _.forEach(params, function (v, k) {
            if (_.isString(k)) {
                if (k.substr(0, 1) === '$') {
                    k = k.substr(1);
                }
                r += "'" + k + "'=>";
            }
            // TODO: if `null`...
            if (_.isObject(v) && _.has(v, 'sequelize')) {
                r += "Enjoin::get('" + camelize(v.getTableName()) + "')";
            } else if (_.isObject(v) || _.isArray(v)) {
                r += handle(v);
            } else if (_.isBoolean(v)) {
                r += v ? 'true' : 'false';
            } else if (_.isNull(v)) {
                r += 'null';
            } else {
                r += _.isNumber(v) ? v : "'" + v + "'";
            }
            i++;
            if (i < length) {
                r += ',';
            }
        });
        r += ']';
        return r;
    }
}

function camelize(str) {
    return str.replace(/(?:^|[-_])(\w)/g, function (_, c) {
        return c ? c.toUpperCase() : '';
    });
}
