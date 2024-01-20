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
    $('.pubjet-regthumb').click(function (e) {
        const $self = $(this);
        const postId = $self.data('post-id');
        $self.addClass('pubjet-disabled');
        axios.post(findEndpointUrl('reg-thumb'), {
            postId  : postId,
            security: getSecurityNonce(),
        }).then(response => {
            if (!response.success) {
                alert(response.error);
            }
        }).finally(() => {
            $self.removeClass('pubjet-disabled');
        });
        e.preventDefault();
        return false;
    });
    $('.pubjet-notice-remind-me').click(function (e) {
        const $self    = $(this),
              $wrapper = $(this).closest('.pubjet-notice');
        $wrapper.slideUp('fast');
        axios.post(findEndpointUrl('remind-admin-notice'), formDataFromObj({
            noticeId: $wrapper.attr('id'),
            security: $self.attr('data-security'),
        }));
        e.preventDefault();
        return false;
    });
    $('.pubjet-notice__permanenthide').click(function (e) {
        const $self    = $(this),
              $wrapper = $(this).closest('.pubjet-notice');
        $wrapper.slideUp('fast');
        axios.post(findEndpointUrl('permanent-hide-admin-notice'), formDataFromObj({
            noticeId: $wrapper.attr('id'),
            security: $self.attr('data-security'),
        }));
        e.preventDefault();
        return false;
    });
});