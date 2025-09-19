jQuery(document).ready(function ($) {

    /**
     * Dismiss Notice
     */
    $(document).on('click', '.notice.is-dismissible .notice-dismiss', function (e) {
        e.preventDefault();

        var $notice = $(this).closest('.notice');
        var nonce   = $notice.data('nonce');
        var notice  = $notice.data('notice');

        if (!nonce || !notice) {
            return;
        }

        $.post(ajaxurl, {
            action: 'epta_dismiss_notice',
            nonce: nonce,
            notice: notice
        }, function (response) {
            if (response.success) {
                $notice.fadeOut();
            }
        });
    });

});
