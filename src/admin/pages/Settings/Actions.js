import {getStore, setStore} from "trim-redux";
import {findEndpointUrl, getAdminAjaxUrl, getAxios, getSecurityNonce} from "../../../shared/scripts/utils";
import {v4 as uuid} from 'uuid';

const axios = getAxios();

/**
 * @since 1.0.0
 */
export const saveOptions = () => {
    return new Promise((resolve, reject) => {
        const options = getStore(getStoreKey());
        axios.post(getAdminAjaxUrl(), {
            action  : 'pubjet-save-options',
            settings: JSON.stringify({...options, modal: false}),
            security: getSecurityNonce(),
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
        axios.get(getAdminAjaxUrl(), {
            params   : {
                action  : 'pubjet-check-token',
                token   : curstate.token,
                security: getSecurityNonce(),
            },
            hideError: true,
        }).then(response => {
            if (response.success) {
                const {pricing_plans} = response.payload;
                setStore(getStoreKey(), {
                    ...curstate,
                    pricingPlans: pricing_plans.map(item => {
                        const old = getStore(getStoreKey()).pricingPlans.find(item2 => item2.id == item.id);
                        if (old) {
                            return {...old, ...item};
                        }
                        return item;
                    }),
                    checkToken  : {
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
    const state = getStore(getStoreKey());
    setStore(getStoreKey(), {
        ...state,
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

/**
 * @since 1.0.0
 */
export const toggleModal = (modalKey) => {
    const state = getStore(getStoreKey());
    setStore(getStoreKey(), {
        ...state,
        modal: modalKey,
    });
};

/**
 * @since 1.0.0
 */
export const handleChangePlanCategory = (planId, category) => {
    const state = getStore(getStoreKey());
    setStore(getStoreKey(), {
        ...state,
        pricingPlans: state.pricingPlans.map(item => {
            if (item.id == planId) {
                return {
                    ...item,
                    category,
                };
            }
            return item;
        })
    });
}

/**
 * @since 1.0.0
 * @returns {string}
 */
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
export const addMetakey = () => {
    const options = getStore(getStoreKey());
    setStore(getStoreKey(), {
        ...options,
        metakeys: {
            ...options.metakeys,
            items: [
                ...options.metakeys.items,
                {_id: uuid(), name: '', value: ''},
            ],
        },
    });
};

/**
 * @since 1.0.0
 */
export const deleteMetakey = (itemId) => {
    const options = getStore(getStoreKey());
    setStore(getStoreKey(), {
        ...options,
        metakeys: {
            ...options.metakeys,
            items: options.metakeys.items.filter(item => item._id !== itemId),
        },
    });
};

/**
 * @since 1.0.0
 * @param itemId
 * @param propName
 * @param propValue
 */
export const changeMetakey = (itemId, propName, propValue) => {
    const state = getStore(getStoreKey());
    setStore(getStoreKey(), {
        ...state,
        metakeys: {
            ...state.metakeys,
            items: state.metakeys.items.map(item => {
                if (item._id === itemId) {
                    return {...item, [propName]: propValue};
                }
                return item;
            })
        },
    });
};