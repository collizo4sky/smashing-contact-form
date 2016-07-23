(function ($) {
    $(window).load(function () {
        var error, fadeOut;

        $.fn.fallOut = function () {
            var _this = this;
            setTimeout(function () {
                _this.fadeOut("slow");
            }, 5000);

            return this;
        };

        $('#smashing-contact-form').submit(function (e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append("action", "smashing_cf_submission");

            $('#cf-loader').show();

            $.post({
                url: smashing_cf_ajax_form.ajaxurl,
                data: formData,
                cache: false,
                contentType: false,
                enctype: 'multipart/form-data',
                processData: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#cf-loader').hide();
                        $('#cf-sent').show().fallOut();
                    }
                    else {
                        if (response.data != '') {
                            error = response.data;
                        }
                        else {
                            error = 'Unexpected error. Please try again.';
                        }

                        $('#cf-loader').hide();
                        $('#cf-notice').text(error).show().fallOut("slow");
                    }
                }
            });
        });

    })
})(jQuery);