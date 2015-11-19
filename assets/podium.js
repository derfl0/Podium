$(document).ready(function () {
    STUDIP.podium.init();
});

// Quickfile loader
STUDIP.podium = {
    timeout: null,
    cache: [],
    current: false,
    open: function () {

        // icon
        if ($('#podiumwrapper').is(':visible')) {
            $('#podiumicon').removeClass('visible');
        } else {
            $('#podiumicon').addClass('visible');
        }

        // Podiumwindow
        $('#podiumwrapper').fadeToggle(400);
        $('#podiumwrapper input').focus();
        STUDIP.podium.load();
    },
    load: function () {

        // Get typed value
        var val = $('#podiuminput input').val();
        if (STUDIP.podium.current !== val) {
            if (STUDIP.podium.cache[val] != undefined) {

                // Load from cache
                STUDIP.podium.display(STUDIP.podium.cache[val]);
            } else {
                $('#podiuminput input').addClass('podium_ajax');
                $.ajax({
                    method: "POST",
                    url: STUDIP.URLHelper.getURL('plugins.php/Podium/find'),
                    data: {search: val},
                    dataType: "json"
                }).done(function (data) {
                    STUDIP.podium.current = val;
                    // Cache result
                    STUDIP.podium.cache[val] = data;

                    // Display
                    STUDIP.podium.display(data);
                    $('#podiuminput input').removeClass('podium_ajax');
                });
            }
        }
    },
    display: function (items) {
        var list = $('#podium #podiumlist');
        list.children().remove();

        // calculate max size
        var maxSize = 6;
        var length = Object.keys(items).length;
        if (length > 1) {
            if (length > 2) {
                maxSize = 2;
            } else {
                maxSize = 3;
            }
        }

        // Append all result groups
        $.each(items, function (key, val) {
            var result = $('<li>');
            list.append(result);
            result.append($('<p>', {text: val.name})).click(function (e) {
                $(this).addClass('expand');
                list.children('li:not(.expand)').addClass('collapse');
            });
            var resultlist = $('<ul>', {'data-maxsize': maxSize});
            result.append(resultlist);

            $.each(val.content, function (mykey, hit) {

                var newItem = $('<a>', {href: hit.url})
                    .append($('<div>')
                        .append($('<p>', {html: hit.name}))
                        .append($('<p>', {html: hit.additional}))
                        .append($('<div>', {class: 'podiumdate', text: hit.date})))
                    .mouseenter(function (e) {
                        list.find('.selected').removeClass('selected');
                        $(e.target).closest('a').addClass('selected');
                    });

                // Add image if sent
                if (hit.img) {
                    newItem.prepend($('<img>', {src: hit.img}));
                }

                resultlist.append($('<li>')
                    .append(newItem));
            });
        });
        list.find('a').first().addClass('selected');
    },
    init: function() {

        // Bind click outside podium
        $('html').click(function (e) {
            if (e.target.id == "podium" || e.target.id == "podiumicon")
                return;
            //For descendants of menu_content being clicked, remove this check if you do not want to put constraint on descendants.
            if ($(e.target).closest('#podium').length)
                return;
            STUDIP.podium.open();
        });

        // Move podiumicon
        $('form#quicksearch').after($('#podiumicon').click(function (e) {
            STUDIP.podium.open();
        }).show()).hide();

        // Keymapping
        $('#podiumwrapper').keydown(function (e) {
            var list = $('#podium #podiumlist');
            var resultList = list.find('a:visible');
            var selectedItem = list.find('.selected');
            var currentIndex = resultList.index(selectedItem);
            switch (e.which) {
                case 27:
                    $('#podiumwrapper').fadeOut(400);
                    break;
                case 13: // enter
                    var elem = list.find('a.selected');
                    console.log(elem);
                    if (elem.length > 0) {
                        window.location.href = elem.first().attr('href');
                    }
                    break;

                case 38: // up
                    e.preventDefault();
                    if (currentIndex > 0) {
                        $(resultList).removeClass('selected');
                        $(resultList[currentIndex - 1]).addClass('selected');
                    }
                    break;

                case 40: // down
                    e.preventDefault();
                    if (resultList.size() - 1 > currentIndex) {
                        $(resultList).removeClass('selected');
                        $(resultList[currentIndex + 1]).addClass('selected');
                    }
                    break;
                case 18: // alt
                    e.preventDefault();
                    selectedItem.parents('li').addClass('expand');
                    list.children('li:not(.expand)').addClass('collapse');
                    break;
                case 8: // backspace
                    if (list.find('.expand').length > 0) {
                        e.preventDefault();
                        list.find('.expand').removeClass('expand');
                        list.find('.collapse').removeClass('collapse');
                    }
                default:
                    clearTimeout(STUDIP.podium.timeout);
                    STUDIP.podium.timeout = setTimeout(function () {
                        STUDIP.podium.load();
                    }, 600);
            }
        });

        // Set close and open
        $(document).keydown(function (e) {

            /* ctrl + shift + f */
            //if (e.which === 70 && e.ctrlKey && e.shiftKey) {
            /* ctrl + space */
            if (e.which === 32 && e.ctrlKey) {
                e.preventDefault();
                STUDIP.podium.open();
            }

            if (e.which === 27) {

                // Prevent mac fullscreen
                e.preventDefault();
                $('#podiumwrapper').fadeOut(400);
            }
        });

    }
};
