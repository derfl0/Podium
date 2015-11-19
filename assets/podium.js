// Set close and open
$(document).keydown(function (e) {

    /* ctrl + shift + f */
    //if (e.which === 70 && e.ctrlKey && e.shiftKey) {
    /* ctrl + space */
    if (e.which === 32 && e.ctrlKey) {
        e.preventDefault();
        STUDIP.quickfile.open();
    }

    if (e.which === 27) {

        // Prevent mac fullscreen
        e.preventDefault();
        $('#quickfilewrapper').fadeOut(400);
    }
});

$('html').click(function (e) {
    if (e.target.id == "quickfile" || e.target.id == "podiumicon")
        return;
    //For descendants of menu_content being clicked, remove this check if you do not want to put constraint on descendants.
    if ($(e.target).closest('#quickfile').length)
        return;
    $('#quickfilewrapper').fadeOut(400);
});

// Quickfile loader
STUDIP.quickfile = {
    timeout: null,
    cache: [],
    current: "",
    open: function () {
        $('#quickfilewrapper').fadeToggle(400);
        $('#quickfilewrapper input').focus();
        STUDIP.quickfile.load();
    },
    load: function () {

        // Get typed value
        var val = $('#quickfileinput input').val();
        if (STUDIP.quickfile.current !== val) {
            if (STUDIP.quickfile.cache[val] != undefined) {

                // Load from cache
                STUDIP.quickfile.display(STUDIP.quickfile.cache[val]);
            } else {
                $('#quickfileinput input').addClass('quickfile_ajax');
                $.ajax({
                    method: "POST",
                    url: STUDIP.URLHelper.getURL('plugins.php/Podium/find'),
                    data: {search: val},
                    dataType: "json"
                }).done(function (data) {
                    STUDIP.quickfile.current = val;
                    // Cache result
                    STUDIP.quickfile.cache[val] = data;

                    // Display
                    STUDIP.quickfile.display(data);
                    $('#quickfileinput input').removeClass('quickfile_ajax');
                });
            }
        }
    },
    display: function (items) {
        var list = $('#quickfile #quickfilelist');
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
                        .append($('<div>', {class: 'quickfiledate', text: hit.date})))
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
    }
};

//Up and down keys
$(document).ready(function () {
    $('form#quicksearch').after($('#podiumicon').click(function (e) {
        STUDIP.quickfile.open();
    }).show()).hide().parent().css('vertical-align', 'middle');

    $('#quickfilewrapper').keydown(function (e) {
        var list = $('#quickfile #quickfilelist');
        var resultList = list.find('a:visible');
        var selectedItem = list.find('.selected');
        var currentIndex = resultList.index(selectedItem);
        switch (e.which) {
            case 27:
                $('#quickfilewrapper').fadeOut(400);
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
                clearTimeout(STUDIP.quickfile.timeout);
                STUDIP.quickfile.timeout = setTimeout(function () {
                    STUDIP.quickfile.load();
                }, 600);
        }
    });
});
