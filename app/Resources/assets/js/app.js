/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import '../css/requiredInJsFile.css';
//import '../css/requiredInJsFile.less';

console.log('JS loading!');

import './imported-js';
//import './imported-js2';
require.ensure([], function(require) {
    require('./split-chunk');
});

require('jquery');
require('bootstrap-sass/assets/javascripts/bootstrap/modal.js');
require('bootstrap-sass/assets/javascripts/bootstrap/collapse.js');
require('bootstrap-sass/assets/javascripts/bootstrap/dropdown.js');
require('moment');
require('eonasdan-bootstrap-datetimepicker');

// don't replace this by 'require('highlight.js')' to avoid loading all the
// programming languages, which result in a huge JavaScript file
var hljs = require('highlight.js/lib/highlight.js');
hljs.registerLanguage('php', require('highlight.js/lib/languages/php.js'));
hljs.registerLanguage('twig', require('highlight.js/lib/languages/twig.js'));

// Needed to fix a Bootstrap DateTimePicker problem. It may be an ugly solution,
// but it's the only one that worked for us.
// See https://github.com/Eonasdan/bootstrap-datetimepicker/issues/1662
global.$ = global.jQuery = require('jquery');
$.fn.datetimepicker = require('eonasdan-bootstrap-datetimepicker');

$(function () {
    hljs.initHighlightingOnLoad();

    $('[data-toggle="datetimepicker"]').datetimepicker({
        icons: {
            time: 'fa fa-clock-o',
            date: 'fa fa-calendar',
            up: 'fa fa-chevron-up',
            down: 'fa fa-chevron-down',
            previous: 'fa fa-chevron-left',
            next: 'fa fa-chevron-right',
            today: 'fa fa-check-circle-o',
            clear: 'fa fa-trash',
            close: 'fa fa-remove'
        }
    });

    $(document).on('submit', 'form[data-confirmation]', function (event) {
        var $form = $(this),
            $confirm = $('#confirmationModal');

        if ($confirm.data('result') !== 'yes') {
            //cancel submit event
            event.preventDefault();

            $confirm
                .off('click', '#btnYes')
                .on('click', '#btnYes', function () {
                    $confirm.data('result', 'yes');
                    $form.find('input[type="submit"]').attr('disabled', 'disabled');
                    $form.submit();
                })
                .modal('show');
        }
    });
});
