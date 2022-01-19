export default function ($selects) {
  // somehow this doesn't work...
  // @see https://select2.org/configuration/data-attributes
  // $selects.select2({
  //   theme: 'bootstrap4',
  //   width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
  //   placeholder: $(this).data('placeholder'),
  //   allowClear: Boolean($(this).data('allow-clear')) || false,
  // });
  $selects.each(function () {
    $(this).select2({
      theme: 'bootstrap4',
      width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
      placeholder: $(this).data('placeholder'),
      allowClear: Boolean($(this).data('allow-clear')) || false,
      closeOnSelect: !$(this).attr('multiple'),
    });
  });
}
