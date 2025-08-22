'use strict';

(function ($) {
    $(function () {
        // ready
        if (wpclv_vars.tooltip_library === 'tippy') {
            tippy('.wpclv-tippy-tooltip', {
                allowHTML: true, interactive: true,
            });
        }

        // ajax single
        if (wpclv_vars.ajax_single) {
            $('.wpclv-attributes-single').each(function () {
                let $this = $(this);
                let id = $this.data('id');
                let data = {
                    action: 'wpclv_load_content', id: id, nonce: wpclv_vars.nonce,
                };

                $.post(wpclv_vars.wc_ajax_url.toString().replace('%%endpoint%%', 'wpclv_load_content'), data,
                    function (response) {
                        $this.html(response);
                    });
            });
        }

        if (wpclv_vars.ajax_shortcode) {
            $('.wpclv-attributes-shortcode').each(function () {
                let $this = $(this);
                let id = $this.data('id');
                let data = {
                    action: 'wpclv_load_content', id: id, nonce: wpclv_vars.nonce,
                };

                $.post(wpclv_vars.wc_ajax_url.toString().replace('%%endpoint%%', 'wpclv_load_content'), data,
                    function (response) {
                        $this.html(response);
                    });
            });
        }
    });

    $(document).on('change', '.wpclv-terms-select', function () {
        window.location = $(this).val();
    });
})(jQuery);