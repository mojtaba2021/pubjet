import axios from 'axios';
import {showErrorMessage, showSuccessMessage} from './utils';

/**
 * @since 1.0
 * @returns {string|*}
 */
export const getRestNonce = () => {
    if (typeof pubjet_params !== 'undefined') {
        if (typeof pubjet_params.rest_nonce !== "undefined") {
            return pubjet_params.rest_nonce;
        }
    }
    return '';
};

/**
 * @since 1.0
 * @returns {string|string}
 */
export const getBaseRestApiURL = () => {
    return typeof pubjet_params !== 'undefined' ? pubjet_params.rest_url : '';
};

/**
 * Create single customized instance of axios
 *
 * @since 1.0
 */
const instance = axios.create({
    baseURL: getBaseRestApiURL(),
    headers: {
        'X-WP-NONCE'                  : getRestNonce(),
        'Access-Control-Allow-Origin' : '*',
        'Access-Control-Allow-Methods': 'GET,PUT,POST,DELETE,PATCH,OPTIONS',
    },
});

// Request
instance.interceptors.request.use(request => {
    return request;
});

// Response
instance.interceptors.response.use(response => {
    const {hideError} = response.config;
    if (200 === response.status) {
        if (response.data.success) {
            return response.data;
        } else {
            if (!hideError) {
                if (typeof response.data.message !== 'undefined') {
                    showSuccessMessage(response.data.message);
                }
                if (typeof response.data.error !== 'undefined') {
                    showErrorMessage(response.data.error);
                }
            }
        }
    }
    return response.data;
});

instance.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';

export default instance;