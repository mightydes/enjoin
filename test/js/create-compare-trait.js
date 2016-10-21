var path = require('path');
var fs = require('fs');
var async = require('async');
var _ = require('underscore');

module.exports = createCompareTrait;

function createCompareTrait() {
    var dir = path.normalize(__dirname + '/../php/compare');
    var keys = [];
    var data = {};
    var save = [];
    fs.readdir(dir, function (err, list) {
        async.each(list, function (filename, done) {
            var key = filename.substr(0, filename.lastIndexOf('.'));
            keys.push(key);
            fs.readFile(dir + '/' + filename, 'utf8', function (err, content) {
                data[key] = JSON.parse(content);
                done();
            });
        }, function () {
            keys.sort().forEach(function (key) {
                _.keys(data[key]).forEach(function (prop) {
                    var r = data[key][prop];
                    if (r.substr(0, 1) !== '[') {
                        r = '"' + r + '"';
                    }
                    save.push(phpFunction('private', prop + '_' + key, 'return ' + r + ';'));
                });
            });
            fs.writeFile(__dirname + '/../php/CompareTrait.php', '<?php\nuse Enjoin\\Enjoin;\ntrait CompareTrait\n{\n' + save.join('\n') + '\n}\n');
        });
    });
}

function phpFunction(privacy, name, body) {
    return '    ' + privacy + ' function ' + name + '(){' + body + '}';
}
