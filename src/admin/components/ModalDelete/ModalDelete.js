import React from 'react';
import styles from './ModalDelete.module.scss';
import AntModal from '../AntModal/AntModal';
import {Alert, Button, Form} from 'antd';
import {pubjet__} from '../../../shared/scripts/utils';
import {DeleteOutlined} from '@ant-design/icons';
import {ModalSizes} from '../../../shared/scripts/constants';

class ModalDelete extends AntModal {

  state = {
    error   : false,
    loading : false,
    password: '',
  };

  /**
   * @since 1.0.0
   */
  title = () => {
    return '';
  };

  /**
   * @since 1.0.0
   */
  handleDelete = () => {
  };

  /**
   * @since 1.0.0
   */
  showPassword = () => {
    return false;
  };

  /**
   * @since 1.0.0
   */
  passwordInput = () => {
    if (!this.showPassword()) {
      return null;
    }
    return <Form layout={'vertical'} className={styles.form}>
      <Form.Item label={pubjet__('enter-password')}>
        {this.renderInput({name: 'password', type: 'password', autoFocus: true})}
      </Form.Item>
    </Form>
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
  buttonText = () => {
    return pubjet__('delete-data');
  };

  /**
   * @since 1.0.0
   */
  beforeAlert = () => {};

  /**
   * @since 1.0.0
   */
  afterAlert = () => {};

  /**
   * @since 1.0.0
   */
  beforeButton = () => {};

  /**
   * @since 1.0.0
   */
  afterButton = () => {};

  /**
   * @since 1.0.0
   * @type {undefined}
   */
  content = () => {
    const {loading} = this.state;
    return (
        <div className={styles.wrapper}>
          <div className={styles.imageWrapper}>
            <img
                src={`${pubjet_params.images_url}/delete-single.png`}
                width={170}
                height={170}
            />
          </div>
          {this.beforeAlert()}
          <Alert
              description={pubjet__('confirm-delete-data')}
              banner={true}
              showIcon={true}
              className={styles.alert}
              type={'error'}
          />
          {this.afterAlert()}
          {this.passwordInput()}
          {this.beforeButton()}
          <Button
              type={'primary'}
              danger={true}
              block={true}
              icon={<DeleteOutlined/>}
              onClick={this.handleDelete}
              className={styles.button}
              size={'large'}
              loading={loading}
              disabled={this.showPassword() && this.state.password.length === 0}
          >
            {this.buttonText()}
          </Button>
          {this.afterButton()}
        </div>
    );
  };
}

ModalDelete.propTypes = {};

export default ModalDelete;