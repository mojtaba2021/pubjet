import {getStore, setStore} from "trim-redux";
import {findEndpointUrl, getAdminAjaxUrl, getAxios, getSecurityNonce} from "../../../shared/scripts/utils";

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
        axios.get(findEndpointUrl('get-options'), {
            params: {
                security: getSecurityNonce(),
            },
        }).then(response => {
            if (response.success) {
                const {token, debug, category, categories, uninstall} = response.payload;
                setStore(getStoreKey(), {
                    ...options,
                    ...response.payload
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
        axios.post(findEndpointUrl('save-options'), {
            token            : options.token,
            debug            : options.debug,
            uninstall        : options.uninstall,
            nofollow         : options.nofollow,
            category         : options.category ? options.category.value : '',
            alignCenterImages: options.alignCenterImages,
            security         : pubjet_params.nonce,
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
export const doCheckToken = () => {
    const curstate = getStore(getStoreKey());

    if (!curstate.token) {
        setStore(getStoreKey(), {
            ...curstate,
            checkToken: {
                checking: false,
                checked : false,
                valid   : false,
                error   : false,
                payload : {},
            },
        });
        return;
    }

    return new Promise((resolve, reject) => {
        setStore(getStoreKey(), {
            ...curstate,
            checkToken: {
                checking: true,
                checked : false,
                valid   : false,
                error   : false,
                payload : {},
            },
        });

        axios.get(findEndpointUrl('check-token'), {
            params   : {
                token   : curstate.token,
                security: getSecurityNonce(),
            },
            hideError: true,
        }).then(response => {
            if (response.success) {
                setStore(getStoreKey(), {
                    ...curstate,
                    checkToken: {
                        checked: true,
                        valid  : true,
                        error  : false,
                        payload: response.payload,
                    },
                });
                resolve(response);
            } else {
                setStore(getStoreKey(), {
                    ...curstate,
                    checkToken: {
                        checked: true,
                        valid  : false,
                        error  : response.error,
                        payload: {},
                    },
                });
                reject(response);
            }
        }).catch((err) => {
            reject(err);
        }).finally(() => {
            const newstate = getStore(getStoreKey());
            setStore(getStoreKey(), {
                ...newstate,
                checkToken: {
                    ...newstate.checkToken,
                    checking: false,
                },
            });
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