import NProgress from 'nprogress';
import copy from 'copy-text-to-clipboard';
import axios from './axios';
import {message as AntMessage} from 'antd';

AntMessage.config({
    top: 50,
});

const $ = jQuery;

// Jump to top
export function jumpToTop() {
    window.scrollTo(0, 0);
}

/**
 * @since 1.0.0
 */
export function uniqueKey(length) {
    let text = '';
    const possible = 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-=';

    for (var i = 0; i < length; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }

    return text;
}

/**
 * @since 1.0.0
 */
export const getInvisibleSpamProtectionKey = () => {
    return $('.triboon-form [name=isp]').first().val();
};

/**
 * @since 1.0
 * @param str
 * @param find
 * @param replace
 * @returns {*}
 */
export function replaceAll(str, find, replace) {
    return str.replace(new RegExp(find, 'g'), replace);
}

/**
 * @since 1.0
 */
export const showLoadingOnBtn = (objBtn, loadingText) => {
    if (!(objBtn instanceof jQuery)) {
        objBtn = $(objBtn);
    }
    if (typeof loadingText === 'undefined' || !loadingText) {
        loadingText = pubjet_params.i18n['please-wait'];
    }
    objBtn.text(loadingText).attr('disabled', 'disabled');
};

/**
 * @since 1.0
 * @param objBtn
 */
export const hideLoadingOnBtn = (objBtn, text) => {
    if (!(objBtn instanceof jQuery)) {
        objBtn = $(objBtn);
    }
    objBtn.html(text).removeAttr('disabled');
};

/**
 * @since 1.0
 * @return boolean
 */
export const isRTL = () => {
    return pubjet_params.is_rtl === 'yes';
};

/**
 * @since 1.0
 * @param message
 */
export const showErrorMessage = (message) => {
    let result = '';
    if ($.isArray(message)) {
        for (let i = 0; i < message.length; i++) {
            result += message[i] + '<br />';
        }
    } else {
        result = message;
    }
    AntMessage.error(result, 3);
};

/**
 * @since 1.0
 * @param text
 * @param options
 */
export const showSuccessMessage = (text) => {
    AntMessage.success(text, 3);
};

/**
 * @since 1.0
 * @author Pishook
 * @return void
 */
export const showLoadingBar = (text = '') => {
    const toastOptions = {
        position          : 'bottom-center',
        textAlign         : 'center',
        bgColor           : '#000',
        textColor         : '#fff',
        text              : text ? text : pubjet_params.i18n['please-wait'],
        loader            : false,
        hideAfter         : false,
        allowToastClose   : false,
        stack             : false,
        showHideTransition: 'slide',
    };
    $.toast(toastOptions);
};

/**
 * @since 1.0
 * @author Pishook
 * @return void
 */
export const hideLoadingBar = () => {
    $.toast().reset('all');
};

/**
 * @since 1.0
 * @author Pishook
 * @return void
 */
export const copyText = (text) => {
    copy(text);
};

/**
 * @since 1.0
 * @author Pishook
 * @return boolean
 */
export const isUserLoggedIn = () => {
    return 'yes' === pubjet_params.user.loggedin;
};

/**
 * @since 1.0
 * @param email
 * @returns {*}
 */
export const validateEmail = (email) => {
    return email.match(
        /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
    );
};

/**
 * @param value
 * @returns {boolean}
 */
export const isNumeric = (value) => {
    return /^-?\d+$/.test(value);
};

/**
 * @since 1.0
 * @return string|undefined
 */
export const serializeForm = (form) => {
    var i, j, len, jLen, formElement, q = [];

    function urlencode(str) {
        // http://kevin.vanzonneveld.net
        // Tilde should be allowed unescaped in future versions of PHP (as
        // reflected below), but if you want to reflect current PHP behavior, you
        // would need to add ".replace(/~/g, '%7E');" to the following.
        return encodeURIComponent(str)
            .replace(/!/g, '%21')
            .replace(/'/g, '%27')
            .replace(/\(/g, '%28')
            .replace(/\)/g, '%29')
            .replace(/\*/g, '%2A')
            .replace(/%20/g, '+');
    }

    function addNameValue(name, value) {
        q.push(urlencode(name) + '=' + urlencode(value));
    }

    if (!form || !form.nodeName || form.nodeName.toLowerCase() !== 'form') {
        throw 'You must supply a form element';
    }
    for (i = 0, len = form.elements.length; i < len; i++) {
        formElement = form.elements[i];
        if (formElement.name === '' || formElement.disabled) {
            continue;
        }
        switch (formElement.nodeName.toLowerCase()) {
            case 'input':
                switch (formElement.type) {
                    case 'text':
                    case 'hidden':
                    case 'tel':
                    case 'email':
                    case 'number':
                    case 'password':
                    case 'button': // Not submitted when submitting form manually, though
                                   // jQuery does serialize this and it can be an HTML4
                                   // successful control
                    case 'submit':
                        addNameValue(formElement.name, formElement.value);
                        break;
                    case 'checkbox':
                    case 'radio':
                        if (formElement.checked) {
                            addNameValue(formElement.name, formElement.value);
                        }
                        break;
                    case 'file':
                        // addNameValue(formElement.name, formElement.value); // Will work
                        // and part of HTML4 "successful controls", but not used in jQuery
                        break;
                    case 'reset':
                        break;
                }
                break;
            case 'textarea':
                addNameValue(formElement.name, formElement.value);
                break;
            case 'select':
                switch (formElement.type) {
                    case 'select-one':
                        addNameValue(formElement.name, formElement.value);
                        break;
                    case 'select-multiple':
                        for (j = 0, jLen = formElement.options.length; j < jLen; j++) {
                            if (formElement.options[j].selected) {
                                addNameValue(formElement.name, formElement.options[j].value);
                            }
                        }
                        break;
                }
                break;
            case 'button': // jQuery does not submit these, though it is an HTML4
                // successful control
                switch (formElement.type) {
                    case 'reset':
                    case 'submit':
                    case 'button':
                        addNameValue(formElement.name, formElement.value);
                        break;
                }
                break;
        }
    }
    return q.join('&');
};

/**
 * @since 1.0.0
 */
export const getAdminAjaxUrl = () => {
    return pubjet_params.ajaxurl;
};

/**
 * @since 1.0.0
 * @param endpoint
 */
export const findEndpointUrl = (endpoint) => {
    return `${findSiteUrl()}/pubjet-api/${endpoint}`;
};

/**
 * @since 1.0.0
 */
export const findSiteUrl = () => {
    return pubjet_params.siteurl;
};

/**
 * @since 1.0
 * @author Pishook
 * @return void
 */
export const showLoadingModal = () => {
    $('.modal').modal({
        showClose   : false,
        clickClose  : false,
        escapeClose : false,
        fadeDuration: 100,
    });
};

/**
 * @since 1.0
 * @author Pishook
 * @return void
 */
export const showNProgress = () => {
    NProgress.configure({trickleRate: 0.02, trickleSpeed: 50});
    NProgress.start();
};

/**
 * Hide loading
 *
 * @since 1.0
 * @return void
 */
export const showLoading = () => {
    showNProgress();
};

/**
 * Show loading (nprogress, modal ...)
 */
export const hideLoading = () => {
    NProgress.done();
};

/**
 * @since 1.0.0
 */
export const initTableStoreData = () => {
    return {
        data      : [],
        filter    : {
            search: '',
        },
        pagination: initPaginationStoreData(),
    };
};

/**
 * @since 1.0.0
 */
export const initPaginationStoreData = () => {
    return {
        perPage    : 25,
        currentPage: 1,
        totalItems : 0,
    };
};

/**
 *
 * @returns {string}
 */
export const getBrowserWidth = () => {
    if (window.innerWidth < 768) {
        // Extra Small Device
        return 'xs';
    } else if (window.innerWidth < 991) {
        // Small Device
        return 'sm';
    } else if (window.innerWidth < 1199) {
        // Medium Device
        return 'md';
    } else {
        // Large Device
        return 'lg';
    }
};

/**
 * Get Words Count
 *
 * @since 1.0
 * @param value
 * @returns {*|number}
 */
export const getWordsCount = (value) => {
    return jQuery.trim(value).length ? value.match(/\S+/g).length : 0;
};

/**
 * Check if current page is default wordpress login/signup page
 *
 * @since 1.0
 * @return boolean
 */
export const isWPLoginPage = () => {
    return $('body').hasClass('login-action-login');
};

/**
 * Check if current page is default wordpress login/signup page
 *
 * @since 1.0
 * @return boolean
 */
export const isWPRegisterPage = () => {
    return $('body').hasClass('login-action-register');
};

/**
 * Start OTP timer
 *
 * @since 1.0
 * @param jqObj
 * @param expiredCallback
 * @returns {number}
 */
export const startOtpTimer = (jqObj, expiredCallback = () => {
}) => {
    const duration = pubjet_params.otp['resend'];
    let timer = duration, minutes, seconds;
    let timerId = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;

        jqObj.text(minutes + ':' + seconds);

        if (--timer < 0) {
            if (typeof expiredCallback === 'function') {
                expiredCallback();
            }
            // Clear timeout
            clearInterval(timerId);
        }
    }, 1000);
    return timerId;
};

/**
 * @return boolean
 * @param $wrapper
 */
export const validateDynamicFields = ($wrapper) => {
    const errors = [];
};

/**
 * @type {{}}
 */
export const OTPActionTypes = {
    Subscribed        : 'subscribe_newsletter',
    Unsubscribed      : 'unsubscribe_newsletter',
    Signup            : 'signup_otp',
    Login             : 'login_otp',
    ResetPassword     : 'rpass_otp',
    DownloadFile      : 'download_file',
    ForceVerify       : 'force_verify_otp',
    EditMobile        : 'edit_mobile',
    VerifyBillingPhone: 'verify_billing_phone',
};

/**
 * @since 1.0
 * @return void
 */
export const showLoginModal = () => {
    $('#triboon-modal-login').modal({
        fadeDuration: 90,
        clickClose  : pubjet_params.login_signup_modal.outside_close,
        escapeClose : false,
    });
};

/**
 * @since 1.0
 * @author Pishook
 * @return void
 */
export const showLoadingSpinner = () => {
    jQuery('.triboon-loading').removeClass('triboon-hide');
};

/**
 * Hide Loading Spinner
 *
 * @since 1.0
 * @author Pishook
 * @return void
 */
export const hideLoadingSpinner = () => {
    jQuery('.triboon-loading').addClass('triboon-hide');
};

/**
 *
 * @param name
 * @param url
 * @returns {string|null}
 */
export function getQueryParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    const regex   = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
          results = regex.exec(url);
    if (!results) {
        return null;
    }
    if (!results[2]) {
        return '';
    }
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

/**
 * @since 1.0.0
 */
export const getImagesUrl = () => {
    return pubjet_params.images_url;
};

/**
 * @since 1.0.0
 */
export const getPluginsUrl = () => {
    return pubjet_params.plugins_url;
};

/**
 * @since 1.0
 * @param url
 */
export const redirectTo = (url) => {
    const redirectQV = getQueryParameterByName('backto');
    if (redirectQV && redirectQV.length > 0) {
        url = redirectQV;
    }
    window.location.href = url;
};

/**
 * Reload current page
 *
 * @return void
 */
export const reloadCurrentPage = () => {
    window.location.reload();
};

/**
 * @since 1.0.0
 * @param array
 * @returns {any[]}
 */
export const removeDuplicates = (array) => {
    return Array.from(new Set(array));
};

/**
 * Check if username is valid
 *
 * @since 1.0
 * @return boolean
 */
export const isUsernameValid = (username) => {
    return /^[0-9a-zA-Z_.-]+$/.test(username);
};

/**
 * @param object
 * @returns {FormData}
 */
export const formDataFromObj = object => {
    const fd = new FormData();
    Object.keys(object).forEach(function (key) {
        fd.append(key, object[key]);
    });
    return fd;
};

/**
 * @since 1.0
 * @return void
 */
export const closeModal = () => {
    $.modal.close();
};

/**
 * @returns {*}
 */
export const isRefererBackEnable = () => {
    return pubjet_params.misc.referer_enable;
};

/**
 * @since 1.0.0
 * @returns {AxiosInstance}
 */
export const getAxios = () => {
    return axios;
};

/**
 * @since 1.0.0
 */
export const getSecurityNonce = () => {
    return pubjet_params.nonce;
};

/**
 * @since 1.0.0
 * @param key
 */
export const pubjet__ = (key) => {
    return pubjet_params.i18n[key];
};

/**
 * @param redirect
 */
export const redirectWithRefererPriority = (redirect) => {

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('backto')) {
        redirectTo(urlParams.get('backto'));
        return;
    }

    // Back to referer url
    if (isRefererBackEnable()) {
        const refererUrl = $('.triboon-form-oneclick').first().find('[name=http_referer]').val();
        if (refererUrl.length > 0) {
            window.location.href = refererUrl;
            return;
        }
    }
    redirectTo(redirect);
};