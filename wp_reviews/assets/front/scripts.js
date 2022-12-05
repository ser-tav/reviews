jQuery(document).ready(function ($) {
    $('#add-review').on('click', function (e) {
        e.preventDefault();
        var review_heading = $('#review_heading').val();
        var review_name = $('#review_name').val();
        var review_content = $('#review_content').val();
        var review_social = $('#review_social').val();
        var status = 'publish';

        var data = {
            title: review_heading,
            content: review_content,
            review_name: review_name,
            review_social: review_social,
            status: status,
        };

        if (review_heading && review_name && review_social) {
            $.ajax({
                method: "POST",
                url: additionalData.root + 'wp/v2/review',
                data: data,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', additionalData.nonce);
                },
                success: function (response) {
                    $('#review_heading, #review_name, #review_content, #review_social').val('');

                    $('.message-container').text(additionalData.success)
                    setTimeout(function () {
                        $('.message-container').text('')
                    }, 5000)
                },
                fail: function (response) {
                    $('.message-container').text(additionalData.failure)
                }
            });
        } else {
            $('.message-container').text(additionalData.failure)
        }
    });
});