import React, {Component} from 'react';
import PropTypes from 'prop-types';
import AntModal from '../AntModal/AntModal';
import {pubjet__} from '../../../shared/scripts/utils';
import TelegramActivationForm from '../TelegramActivationForm/TelegramActivationForm';

class ModalActivation extends AntModal {

  state = {
    error  : false,
    loading: false,
  };

  /**
   * @since 1.0.0
   */
  title = () => {
    return pubjet__('activation');
  };

  /**
   * @since 1.0.0
   */
  modalProps = () => {
    return {
      footer: null,
      closable: false,
    };
  };

  /**
   * @since 1.0.0
   */
  isOpen = () => {
    return true;
  };

  /**
   * @since 1.0.0
   * @returns {JSX.Element}
   */
  content = () => {
    return (
        <TelegramActivationForm />
    );
  };
}

export default ModalActivation;