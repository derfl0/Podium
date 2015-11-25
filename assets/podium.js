$(document).ready(function () {
    STUDIP.podium.init();
});

// Quickfile loader
STUDIP.podium = {

    // Edit this value if to many requests are fired
    keyTimeout: 300,
    fadeTime: 300,

    // Internal variables
    timeout: null,
    cache: [],
    current: false,
    active: false,
    open: function () {
        STUDIP.podium.active = true;
        $('#podiumicon').addClass('visible');
        // Podiumwindow
        $('#podiumwrapper').stop(true, true).fadeIn(STUDIP.podium.fadeTime);
        $('#podiumwrapper input').focus();
        STUDIP.podium.load();
    },
    close: function () {
        STUDIP.podium.active = false;
        $('#podiumicon').removeClass('visible');
        $('#podiumwrapper').stop(true, true).fadeOut(STUDIP.podium.fadeTime);
    },
    toggle: function() {
        if (STUDIP.podium.active) {
            STUDIP.podium.close();
        } else {
            STUDIP.podium.open();
        }
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
    getSelectedItem: function() {
      return $('#podium #podiumlist').find('.selected');
    },
    display: function (items) {
        var list = $('#podium #podiumlist');
        list.children().remove();

        // calculate max size
        var maxSize = 6;
        var length = Object.keys(items).length;
        if (length > 1) {
            if (length > 2) {
                if (length > 4) {
                    maxSize = 1;
                } else {
                    maxSize = 2;
                }
            } else {
                maxSize = 3;
            }
        }

        // Append all result groups
        $.each(items, function (key, val) {
            var result = $('<li>');
            list.append(result);
            result.append($('<p>', {text: val.name})).click(function (e) {
                if (list.find('ul:visible').length === 1 ) {
                    if (STUDIP.podium.getSelectedItem().attr('data-expand')) {
                        window.location.href = STUDIP.podium.getSelectedItem().data().expand;
                    }
                } else {
                    $(this).addClass('expand');
                    list.children('li:not(.expand)').addClass('collapse');
                }
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

                // Add expand if sent
                if (hit.expand) {
                    newItem.attr('data-expand', hit.expand);
                }

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
    init: function () {

        // Bind click outside podium
        $('html').click(function (e) {
            if (e.target.id == "podium" || e.target.id == "podiumicon")
                return;
            //For descendants of menu_content being clicked, remove this check if you do not want to put constraint on descendants.
            if ($(e.target).closest('#podium').length)
                return;
            STUDIP.podium.close();
        });

        // Move podiumicon
        $('form#quicksearch').after($('#podiumicon').click(function (e) {
            STUDIP.podium.toggle();
        }).show()).hide();

        // Keymapping
        $('#podiumwrapper').keydown(function (e) {
            var list = $('#podium #podiumlist');
            var resultList = list.find('a:visible');
            var selectedItem = list.find('.selected');
            var currentIndex = resultList.index(selectedItem);
            switch (e.which) {
                case 27: // escape
                    STUDIP.podium.close();
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
                    if (list.find('ul:visible').length === 1 ) {
                        if (selectedItem.attr('data-expand')) {
                            window.location.href = selectedItem.data().expand;
                        }
                    } else {
                        selectedItem.parents('li').addClass('expand');
                        list.children('li:not(.expand)').addClass('collapse');
                    }
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
                    }, STUDIP.podium.keyTimeout);
            }
        });

        // Set close and open
        $(document).keydown(function (e) {

            /* ctrl + shift + f */
            //if (e.which === 70 && e.ctrlKey && e.shiftKey) {
            /* ctrl + space */
            if (e.which === 32 && e.ctrlKey) {
                e.preventDefault();
                STUDIP.podium.toggle();
            }

            if (e.which === 27) {

                // Prevent mac fullscreen
                e.preventDefault();
                $('#podiumwrapper').fadeOut(400);
            }
        });

    }
};
