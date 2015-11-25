$(document).ready(function () {
    STUDIP.Podium.init();
});

// Quickfile loader
STUDIP.Podium = {

    // Edit this value if to many requests are fired
    keyTimeout: 300,
    fadeTime: 300,

    // Internal variables
    timeout: null,
    cache: [],
    current: false,
    active: false,
    ajax: $.ajax(),
    requestFinished: false,
    open: function () {
        STUDIP.Podium.active = true;
        $('#podiumicon').addClass('visible');
        // Podiumwindow
        $('#podiumwrapper').stop(true, true).fadeIn(STUDIP.Podium.fadeTime);
        $('#podiumwrapper input').focus();
        STUDIP.Podium.load();
    },
    close: function () {
        STUDIP.Podium.active = false;
        $('#podiumicon').removeClass('visible');
        $('#podiumwrapper').stop(true, true).fadeOut(STUDIP.Podium.fadeTime);
    },
    toggle: function() {
        if (STUDIP.Podium.active) {
            STUDIP.Podium.close();
        } else {
            STUDIP.Podium.open();
        }
    },
    load: function () {

        // Get typed value
        var val = $('#podiuminput input').val();
        if (STUDIP.Podium.current !== val) {
            if (STUDIP.Podium.cache[val] != undefined) {

                // Load from cache
                STUDIP.Podium.display(STUDIP.Podium.cache[val]);
                $('#podiuminput input').removeClass('podium_ajax');
            } else {
                $('#podiuminput input').addClass('podium_ajax');
                STUDIP.Podium.ajax = $.ajax({
                    method: "POST",
                    url: STUDIP.URLHelper.getURL('plugins.php/Podium/find'),
                    data: {search: val},
                    dataType: "json"
                }).done(function (data) {
                    STUDIP.Podium.current = val;
                    // Cache result
                    STUDIP.Podium.cache[val] = data;

                    // Display
                    STUDIP.Podium.display(data);
                    $('#podiuminput input').removeClass('podium_ajax');
                });
            }
        }
        STUDIP.Podium.requestFinished = true;
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
                    if (STUDIP.Podium.getSelectedItem().attr('data-expand')) {
                        window.location.href = STUDIP.Podium.getSelectedItem().data().expand;
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
            STUDIP.Podium.close();
        });

        // Move podiumicon
        $('form#quicksearch').after($('#podiumicon').click(function (e) {
            STUDIP.Podium.toggle();
        }).show()).hide();

        // Keymapping
        $('#podiumwrapper').keydown(function (e) {
            var list = $('#podium #podiumlist');
            var resultList = list.find('a:visible');
            var selectedItem = list.find('.selected');
            var currentIndex = resultList.index(selectedItem);
            switch (e.which) {
                case 27: // escape
                    e.preventDefault();
                    STUDIP.Podium.close();
                    break;
                case 13: // enter
                    if (STUDIP.Podium.requestFinished) {
                        var elem = list.find('a.selected');
                        if (elem.length > 0) {
                            window.location.href = elem.first().attr('href');
                        }
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
                    STUDIP.Podium.ajax.abort();
                    STUDIP.Podium.requestFinished = false;
                    clearTimeout(STUDIP.Podium.timeout);
                    STUDIP.Podium.timeout = setTimeout(function () {
                        STUDIP.Podium.load();
                    }, STUDIP.Podium.keyTimeout);
            }
        });

        // Set close and open
        $(window).keydown(function (e) {
            
            /* ctrl + shift + f */
            //if (e.which === 70 && e.ctrlKey && e.shiftKey) {
            /* ctrl + space */
            if (e.which === 32 && e.ctrlKey) {
                e.preventDefault();
                STUDIP.Podium.toggle();
            }
        });

    }
};
