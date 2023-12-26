import {getStore, setStore} from 'trim-redux';
import {formDataFromObj, getAdminAjaxUrl, getAxios} from '../../shared/scripts/utils';
import {message} from 'antd';

message.config({
  top     : 100,
  duration: 100,
  rtl     : true,
});

/**
 * @since 1.0.0
 */
export const hideAllModals = () => {
  setStore('modals', initModalsStoreData());
};

/**
 * @since 1.0.0
 * @param phone
 */
export const showModalSms = (phone) => {
  const state = getStore('modals');
  setStore('modals', {
    ...state,
    sms: {
      ...state.sms,
      phone: phone,
      show : true,
    },
  });
};

/**
 * @since 1.0.0
 */
export const showModalGeneralSms = () => {
  const state = getStore('modals');
  setStore('modals', {
    ...state,
    generalSms: true,
  });
};