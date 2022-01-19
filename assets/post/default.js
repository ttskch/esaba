const stickybits = require('stickybits');

$('#esa-content-body .code-block pre.plaintext code').addClass('nohighlight');

$('#esa-content-body table')
  .addClass('table table-bordered table-striped')
  .each(function() {
    // decrease font-size when the number of column is 5 or more
    let tr = $(this).find('tr').get(0);
    if ($(tr).find('td, th').length >= 5) {
      $(this).css('font-size', '0.9em');
    }
  })
;

stickybits('#toc-wrapper');
