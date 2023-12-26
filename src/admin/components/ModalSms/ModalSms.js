import React from 'react';
import styles from './ModalSms.module.scss';
import {hideAllModals, sendSms} from '../../utils/utils';
import {connect} from 'trim-redux';
import {Alert, Button, Input, Popconfirm} from 'antd';
import {MessageOutlined} from '@ant-design/icons';
import {ModalSizes} from '../../../shared/scripts/constants';
import {showSuccessMessage} from '../../../shared/scripts/utils';
import AntModal from '../AntModal/AntModal';
import {translate} from '../../../shared/scripts/utils';
import SmsInput from '../SmsInput/SmsInput';

const {TextArea} = Input;

class ModalSms extends AntModal {

  state = {
    text   : '',
    error  : false,
    loading: false,
  };

  /**
   * @since 1.0.0
   */
  title = () => {
    return translate('send-sms');
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
    const {phone} = this.props;
    const {text} = this.state;
    this.setState({error: false, loading: true}, () => {
      sendSms(phone, text).then(response => {
        showSuccessMessage(translate('sms-sent'));
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
    const {phone} = this.props;
    const {text, loading, error} = this.state;
    return (
        <div className={styles.wrapper}>
          <Alert
              type={'info'}
              showIcon={true}
              message={`${translate('mobile')}: ${phone}`}
              className={styles.alert}
          />
          <SmsInput
              value={text}
              onChange={(e) => {
                const {value} = e.target;
                this.setState({text: value});
              }}
          />
          <Popconfirm
              title={translate('are-you-sure-sms')}
              trigger={'click'}
              cancelText={translate('cancel')}
              okText={`${translate('yes')}, ${translate('send-sms')}`}
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
              {translate('send-sms')}
            </Button>
          </Popconfirm>
        </div>
    );
  };

}

const mstp = (state) => ({
  phone : state.modals.sms.phone,
  isOpen: state.modals.sms.show,
});

export default connect(mstp)(ModalSms);
