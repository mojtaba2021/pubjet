import {setStore, getStore} from "trim-redux";
import {getAdminAjaxUrl, getAxios} from "../../../shared/scripts/utils";

const axios = getAxios();

export const getStoreKey = () => {
    return 'options';
};


/**
 * @since 1.0.0
 */
export const changeInput = (name, value) => {
    const options = getStore(getStoreKey());
    setStore(getStoreKey(), {
        ...options,
        [name]: value,
    });
};

/**
 * @since 1.0.0
 */
export const loadOptions = () => {
    return new Promise((resolve, reject) => {
        const options = getStore(getStoreKey());
        axios.get(getAdminAjaxUrl(), {
            params: {
                action: 'pubjet-get-options',
                security: pubjet_params.nonce,
            },
        }).then(response => {
            if (response.success) {
                const {token, debug, category, categories} = response.payload;
                setStore(getStoreKey(), {
                    ...options,
                    token,
                    debug,
                    category,
                    categories
                });

                if (category) {
                    const found = categories.find(item => item.value == category);
                    if (found) {
                        setStore(getStoreKey(), {
                            ...getStore(getStoreKey()),
                            category: found,
                        });
                    }
                }

                resolve(response);

            } else {
                reject(response.error);
            }
        }).catch(err => {
            reject(err);
        });
    });
};

/**
 * @since 1.0.0
 */
export const saveOptions = () => {
    return new Promise((resolve, reject) => {
        const options = getStore(getStoreKey());
        axios.post(getAdminAjaxUrl(), {
            action: 'pubjet-save-options',
            token: options.token,
            debug: options.debug,
            category: options.category ? options.category.value : '',
            security: pubjet_params.nonce,
        }).then(response => {
            if (response.success) {
                resolve(response);
            } else {
                reject(response.error);
            }
        }).catch(err => {
            reject(err);
        });
    });
};


/**
 * @since 1.0.0
 */
export const closeModals = () => {
    setStore(getStoreKey(), {
        modal: false,
    });
};

/**
 * @since 1.0.0
 */
export const openModal = (modalKey) => {
    setStore(getStoreKey(), {
        [modalKey]: true,
    });
};