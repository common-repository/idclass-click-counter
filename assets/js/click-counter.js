/* click-counter.js */
jQuery(document).ready(function ($) {
    var elements = clickCounterAjax.elements;
    $.each(elements, function (index, element) {
        $(element).on('click', function () {
            $.post(clickCounterAjax.ajaxurl, {
                action: 'click_counter_increment',
                element: element,
                nonce: clickCounterAjax.nonce
            }, function (response) {
                if (response.success) {
                    console.log(element + ' clicks: ' + response.data.clicks);
                }
            });
        });
    });
});
