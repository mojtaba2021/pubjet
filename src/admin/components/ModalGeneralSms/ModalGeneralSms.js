import React from 'react';
import styles from './ModalGeneralSms.module.scss';
import {hideAllModals, sendSms} from '../../utils/utils';
import {connect} from 'trim-redux';
import {Alert, Button, Form, Input, Popconfirm} from 'antd';
import {MessageOutlined} from '@ant-design/icons';
import {ModalSizes} from '../../../shared/scripts/constants';
import {showSuccessMessage} from '../../../shared/scripts/utils';
import AntModal from '../AntModal/AntModal';
import {pubjet__} from '../../../shared/scripts/utils';
import SmsInput from '../SmsInput/SmsInput';
import InputLabel from '../InputLabel/InputLabel';

const {TextArea} = Input;

class ModalGeneralSms extends AntModal {

  state = {
    phone  : '',
    text   : '',
    error  : false,
    loading: false,
  };

  /**
   * @since 1.0.0
   */
  title = () => {
    return pubjet__('send-sms');
  };

  /**
   * @since 1.0
   * @returns {boolean}
   */
  isOpen = () => {
    return this.props.isOpen;
  };

  /**
   * @since 1.0.0
   */
  handleCancel = () => {
    hideAllModals();
  };

  /**
   * @since 1.0.0
   */
  modalProps = () => {
    return {
      footer: null,
      width : ModalSizes.SMALL,
    };
  };

  /**
   * @since 1.0.0
   */
  handleSend = () => {
    const {text, phone} = this.state;
    this.setState({error: false, loading: true}, () => {
      sendSms(phone, text).then(response => {
        showSuccessMessage(pubjet__('sms-sent'));
      }).catch(error => {
        this.setState({error});
      }).finally(() => {
        this.setState({loading: false});
      });
    });
  };

  /**
   * @since 1.0.0
   * @returns {JSX.Element}
   */
  content = () => {
    const {text, loading, phone} = this.state;
    return (
        <div className={styles.wrapper}>
          <Form layout={'vertical'} className={styles.form}>
            <Form.Item label={pubjet__('mobile-number')}>
              {this.renderInput({name: 'phone'})}
            </Form.Item>
          </Form>
          <SmsInput
              value={text}
              onChange={(e) => {
                const {value} = e.target;
                this.setState({text: value});
              }}
          />
          <Popconfirm
              title={pubjet__('are-you-sure-sms')}
              trigger={'click'}
              cancelText={pubjet__('cancel')}
              okText={`${pubjet__('yes')}, ${pubjet__('send-sms')}`}
              onConfirm={this.handleSend}
          >
            <Button
                type={'primary'}
                block={true}
                size={'large'}
                className={styles.button}
                icon={<MessageOutlined/>}
                loading={loading}
            >
              {pubjet__('send-sms')}
            </Button>
          </Popconfirm>
        </div>
    );
  };

}

const mstp = (state) => ({
  isOpen: state.modals.generalSms,
});

export default connect(mstp)(ModalGeneralSms);