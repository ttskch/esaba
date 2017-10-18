$(function () {
    $('#esa-content-body .code-block pre.plaintext code').addClass('nohighlight');
    hljs.initHighlightingOnLoad();

    $('#esa-content-body table')
        .addClass('table table-bordered table-striped')
        .each(function () {
            // decrease font-size when the number of column is 5 or more
            var tr = $(this).find('tr').get(0);
            if ($(tr).find('td, th').length >= 5) {
                $(this).css('font-size', '0.9em');
            }
        })
    ;
});
