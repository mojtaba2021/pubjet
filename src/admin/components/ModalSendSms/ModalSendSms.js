import React from 'react';
import PropTypes from 'prop-types';
import BaseComponent from '../BaseComponent/BaseComponent';
import axios from '../../../shared/scripts/axios';
import {showSuccessMessage, pubjet__} from '../../../shared/scripts/utils';
import Modal from '../Modal/Modal';
import Button from '../Button/Button';
import TelInput from '../TelInput/TelInput';
import InputLabel from '../InputLabel/InputLabel';
import './ModalSendSms.scss';

class ModalSendSms extends BaseComponent {

  state = {
    phone  : this.props.phoneNumber ? this.props.phoneNumber : {},
    text   : '',
    gateway: 'primary',
    error  : false,
    sending: false,
  };

  /**
   * @since 1.0
   * @author Triboon
   * @return void
   */
  clearForm = () => {
    this.setState({message: '', error: false, sending: false, phone: {}});
  };

  /**
   * @since 1.0
   * @author Triboon
   * @return void
   */
  handleSend = () => {
    this.setState({sending: true, error: false}, () => {
      const fd = new FormData();
      fd.append('action', 'pubjet-send-sms');
      fd.append('text', this.state.text);
      fd.append('mobile', this.props.phoneNumber);
      axios.post(pubjet_params.ajaxurl, fd).then(response => {
        if (response.success) {
          showSuccessMessage(pubjet__('sent'));
        } else {
          this.setState({error: true});
        }
      }).catch(err => {
        this.setState({error: true});
      }).finally(() => {
        this.setState({sending: false});
      });
    });
  };

  /**
   * @since 1.0
   * @returns {JSX.Element}
   */
  render() {
    const {text, gateway, sending, error, phone} = this.state;
    const {title, closeOnOuterClick, onClose, mobileInputReadonly, beforeContent, afterContent} = this.props;
    const {phoneNumber} = this.props;

    return (
        <Modal
            show={true}
            title={title}
            onClose={onClose}
            closeOnOuterClick={closeOnOuterClick}>
          {beforeContent()}
          {
            phoneNumber
            ?
            <InputLabel
                label={pubjet__('mobile-number')}
                value={phoneNumber}
                inputProps={{
                  className: mobileInputReadonly
                             ? 'readonly'
                             : '',
                }}
            />
            :
            <TelInput
                value={this.state.phone.number}
                onChange={this.handleChangePhone('phone')}
            />
          }

          <div className={'pubjet-mt-2'}>
            <label className={'pubjet-d-block pubjet-mb-1'}>
              {pubjet__('sms-text')}
            </label>
            <textarea
                rows={6}
                name={'text'}
                className={'pubjet-form-control'}
                onChange={this.handleInputChange}
                value={text}/>
          </div>

          <Button onClick={this.handleSend} block={true} large={true}
                  className={'pubjet-mt-2'} loading={sending}>
            {pubjet__('send-sms')}
          </Button>

          {afterContent()}
        </Modal>
    );
  }
}

ModalSendSms.propTypes = {
  title              : PropTypes.string,
  onClose            : PropTypes.func,
  closeOnOuterClick  : PropTypes.bool,
  mobileInputReadonly: PropTypes.bool,
  phoneNumber        : PropTypes.oneOfType([PropTypes.string, PropTypes.bool]),
  beforeContent      : PropTypes.func,
  afterContent       : PropTypes.func,
};

ModalSendSms.defaultProps = {
  onClose            : () => {
  },
  title              : pubjet_params.i18n['send-sms'],
  closeOnOuterClick  : true,
  mobileInputReadonly: false,
  phoneNumber        : false,
  beforeContent      : () => {
  },
  afterContent       : () => {
  },
};

export default ModalSendSms;