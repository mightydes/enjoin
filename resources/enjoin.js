(function () {
    var levels = ['h2', 'h3', 'h4'];
    var hie = {};
    var selector = getSelector(levels, hie);
    var HIDE_LEVEL = 2;

    $('.toc').html(getTableOfContents(selector, levels, hie));
    $('a[href="http://mightydes.github.io/enjoin/"]').hide();

    function getSelector(levels, hie) {
        var out = [];
        _.forEach(levels, function (it, idx) {
            out.push(it + '[id]');
            hie[it] = idx;
        });
        return out.join(',');
    }

    function getTableOfContents(selector, levels, hie) {
        var $root = createList();
        var level = 999;
        var $ul, $li, $a, $child, $parent;

        $(selector).each(handleItem);
        return $root;

        function handleItem() {
            var itLevel = getLevel($(this));
            if (itLevel === 0) {
                $ul = $root;
            } else if (itLevel > level) {
                itLevel === level + 1 || (itLevel = level + 1);

                $li = $ul.find('li:last');
                if (itLevel >= HIDE_LEVEL) {
                    $li.addClass('toc--toggle');
                    $a = $li.find('a:first');
                    $a.prepend('<i class="glyphicon glyphicon-chevron-right"></i> ');
                    bindToggle($a);
                }

                $child = createList(itLevel);
                $li.append($child);
                $ul = $child;
            } else if (itLevel < level) {
                $parent = $ul.closest('.level-' + itLevel);
                $ul = $parent;
            }
            appendItem($ul, $(this));
            level = itLevel;
        }

        function getLevel($it) {
            var out = 0;
            _.forEach(levels, function (it) {
                if ($it.is(it)) {
                    return out = hie[it];
                }
            });
            return out;
        }

        function bindToggle($el) {
            $el.click(function () {
                $(this).next().toggleClass('hidden');
                $el.find('.glyphicon:first')
                    .toggleClass('glyphicon-chevron-right')
                    .toggleClass('glyphicon-chevron-down');
            });
        }
    }

    function createList(level) {
        var $ul = $('<ul>');
        if (level) {
            $ul.addClass('level-' + level);
        }
        if (level >= HIDE_LEVEL) {
            $ul.addClass('hidden');
        }
        return $ul;
    }

    function appendItem($list, $it) {
        var $li = $('<li>');
        var $a = $('<a>')
            .text($it.text())
            .attr('href', '#' + $it.attr('id'));
        return $list.append($li.html($a));
    }
})();
