import {
    findEndpointUrl,
    formDataFromObj,
    getAdminAjaxUrl,
    getAxios,
    getSecurityNonce
} from "../../shared/scripts/utils";

const axios = getAxios();

jQuery(document).ready(function ($) {
    // Regenerate Post Thumbnail
    $('.pubjet-notice-remind-me').click(function (e) {
        const $self = $(this),
            $wrapper = $(this).closest('.pubjet-notice');
        $wrapper.slideUp('fast');
        axios.post(getAdminAjaxUrl(), formDataFromObj({
            action: 'pubjet-remind-admin-notice',
            noticeId: $wrapper.attr('id'),
            security: $self.attr('data-security'),
        }));
        e.preventDefault();
        return false;
    });
    $('.pubjet-notice__permanenthide').click(function (e) {
        const $self = $(this),
            $wrapper = $(this).closest('.pubjet-notice');
        $wrapper.slideUp('fast');
        axios.post(getAdminAjaxUrl(), formDataFromObj({
            action: 'pubjet-permanent-hide-admin-notice',
            noticeId: $wrapper.attr('id'),
            security: $self.attr('data-security'),
        }));
        e.preventDefault();
        return false;
    });


    // Regenerate Thumbnail
    $('.pubjet-regthumb').click(function (e) {
        e.preventDefault();

        const $self = $(this);
        const postId = $self.data('post-id');

        // Disable button and show loading state
        $self.addClass('pubjet-disabled').text('در حال پردازش...');

        axios.post(getAdminAjaxUrl(), {
            action  : 'pubjet-reg-images',
            type    : 'thumbnail',
            postId  : postId,
            security: getSecurityNonce(),
        }).then(response => {
            // axios puts the actual response in response.data
            console.log(response)
            const data = response.payload;

            if (response.success) {
                // Success - you can show success message or update UI
                if (data && data.thumbnail) {
                    console.log('Thumbnail updated:', data.thumbnail);
                    // Optional: Update thumbnail preview in UI
                    updateThumbnailPreview(postId, data.thumbnail.url);
                }

                // Show success message (optional)
                // showNotification('تصویر شاخص با موفقیت بازتولید شد', 'success');

            } else {
                // Error from server
                alert(data || 'خطا در بازتولید تصویر شاخص');
            }
        }).catch(error => {
            // Network or other errors
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        }).finally(() => {
            // Always restore button state
            $self.removeClass('pubjet-disabled').text('بازتولید تصویر شاخص');
            location.reload(true);

        });

        return false;
    });

// Regenerate Content Images
    $('.pubjet-regcontent').click(function (e) {
        e.preventDefault();

        const $self = $(this);
        const postId = $self.data('post-id');

        // Show confirmation for content images (might take longer)
        if (!confirm('آیا از بازتولید تمام تصاویر محتوا اطمینان دارید؟ این عملیات ممکن است چند دقیقه طول بکشد.')) {
            return false;
        }

        $self.addClass('pubjet-disabled').text('در حال پردازش تصاویر...');

        axios.post(getAdminAjaxUrl(), {
            action  : 'pubjet-reg-images',
            type    : 'content',
            postId  : postId,
            security: getSecurityNonce(),
        }).then(response => {
            console.log(response);
            const data = response.payload.content;

            if (response.success) {
                if (data && data.images_processed != null) {
                    console.log(`${data.images_processed} تصویر پردازش شد`);

                    // Show detailed results
                    let message = `تصاویر محتوا با موفقیت بازتولید شدند.\n`;
                    message += `تعداد تصاویر پردازش شده: ${data.images_processed}`;

                    if (data.failed_images && data.failed_images.length > 0) {
                        message += `\nتعداد تصاویر ناموفق: ${data.failed_images.length}`;
                    }

                    alert(message);

                }else {
                    alert('هیچ تصویری پردازش نشد');
                    console.log('هیچ تصویری پردازش نشد');
                }
            } else {
                alert(data || 'خطا در بازتولید تصاویر محتوا');
                console.log(data || 'خطا در بازتولید تصاویر محتوا');
            }
        }).catch(error => {
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        }).finally(() => {
            $self.removeClass('pubjet-disabled').text('بازتولید تصاویر محتوا');
            location.reload(true);
        });

        return false;
    });

// Regenerate Both (Thumbnail + Content)
    $('.pubjet-regall').click(function (e) {
        e.preventDefault();

        const $self = $(this);
        const postId = $self.data('post-id');

        if (!confirm('آیا از بازتولید تصویر شاخص و تمام تصاویر محتوا اطمینان دارید؟')) {
            return false;
        }

        $self.addClass('pubjet-disabled').text('در حال پردازش همه تصاویر...');

        axios.post(getAdminAjaxUrl(), {
            action  : 'pubjet-reg-images',
            type    : 'both', // or omit for default
            postId  : postId,
            security: getSecurityNonce(),
        }).then(response => {
            const data = response.payload;
            console.log(response);
            if (response.success) {
                let message = 'تمام تصاویر با موفقیت بازتولید شدند.\n';

                // Thumbnail result
                if (data.thumbnail && data.thumbnail.success) {
                    message += 'تصویر شاخص: ✓\n';
                    updateThumbnailPreview(postId, data.thumbnail.url);
                }

                // Content result
                if (data.content && data.content.success) {
                    message += `تصاویر محتوا: ${data.content.images_processed} تصویر ✓`;
                }
                alert(message);
            } else {
                alert(data.data || 'خطا در بازتولید تصاویر');
            }
        }).catch(error => {
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        }).finally(() => {
            $self.removeClass('pubjet-disabled').text('بازتولید همه تصاویر');
            location.reload(true);
        });

        return false;
    });

    function updateThumbnailPreview(postId, newThumbnailUrl) {

        const $editPageThumb = $('#set-post-thumbnail');
        if ($editPageThumb.length && $('input[name="post_ID"]').val() == postId) {
            if ($editPageThumb.find('img').length === 0) {
                // Show the new thumbnail in edit page
                $editPageThumb.html(`
                <img src="${newThumbnailUrl}" alt="" style="max-width: 246px; height: auto;">
                <p class="hide-if-no-js howto">برای حذف تصویر شاخص، روی لینک حذف کلیک کنید.</p>
                <p class="hide-if-no-js">
                    <a href="#" id="remove-post-thumbnail">حذف تصویر شاخص</a>
                </p>
            `);


                $('#set-post-thumbnail-link').text('تغییر تصویر شاخص');

            } else {
                $editPageThumb.find('img').attr('src', newThumbnailUrl);
            }
        }


        $(`.wp-post-image[data-post-id="${postId}"]`).attr('src', newThumbnailUrl);
    }


    function showNotification(message, type = 'info') {
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
});