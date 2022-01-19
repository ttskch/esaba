/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../scss/app.scss';

// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
// import $ from 'jquery';

import 'select2';
import bsCustomFileInput from 'bs-custom-file-input';
import applySelect2 from './lib/applySelect2';

// to use jquery from html event listener like onclick
// @see https://symfony.com/doc/current/frontend/encore/legacy-applications.html
global.$ = global.jQuery = $;

applySelect2($('select'));
$('select[autofocus]').select2('focus');

$('[data-toggle="tooltip"]').tooltip();

bsCustomFileInput.init();
