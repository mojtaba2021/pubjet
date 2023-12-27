import {setStore} from "trim-redux";


/**
 * @since 1.0.0
 * @param modalKey
 */
export const openGlobalModal = (modalName) => {
    setStore('modal', modalName);
};

/**
 * @since 1.0.0
 */
export const closeGlobalModal = () => {
    setStore('modal', false);
};