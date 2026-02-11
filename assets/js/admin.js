/**
 * Anik Smart TOC - Admin JavaScript
 *
 * @package Anik_Smart_TOC
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Review notice - button actions.
        $('.smart-toc-review-btn').on('click', function(e) {
            var action = $(this).data('action');
            var nonce  = $(this).closest('.smart-toc-review-notice').data('nonce');

            if (action !== 'reviewed') {
                e.preventDefault();
            }

            $.post(ajaxurl, {
                action: 'aniksmta_dismiss_review',
                review_action: action,
                nonce: nonce
            });

            $(this).closest('.smart-toc-review-notice').fadeOut();
        });

        // Review notice - X dismiss button.
        $(document).on('click', '.smart-toc-review-notice .notice-dismiss', function() {
            var nonce = $(this).closest('.smart-toc-review-notice').data('nonce');
            $.post(ajaxurl, {
                action: 'aniksmta_dismiss_review',
                review_action: 'later',
                nonce: nonce
            });
        });

        // Copy system info to clipboard.
        $('.smart-toc-copy-info').on('click', function() {
            var info = '';
            var rows = document.querySelectorAll('.smart-toc-system-table tr');

            rows.forEach(function(row) {
                var cells = row.querySelectorAll('td');
                if (cells.length === 2) {
                    info += cells[0].innerText + ': ' + cells[1].innerText + '\n';
                }
            });

            if (navigator.clipboard) {
                navigator.clipboard.writeText(info).then(function() {
                    if (typeof aniksmtaAdmin !== 'undefined') {
                        alert(aniksmtaAdmin.copiedMsg);
                    }
                });
            }
        });
    });

})(jQuery);
