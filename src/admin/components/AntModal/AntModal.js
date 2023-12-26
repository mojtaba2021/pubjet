import React from 'react';
import PropTypes from 'prop-types';
import {Modal} from 'antd';
import BaseComponent from '../BaseComponent/BaseComponent';

class AntModal extends BaseComponent {

  /**
   * @since 1.0.0
   */
  title = () => {
  };

  /**
   * @since 1.0.0
   */
  okText = () => {
  };

  /**
   * @since 1.0.0
   */
  cancelText = () => {
    return pubjet_params.i18n['cancel'];
  };

  /**
   * @since 1.0
   */
  content = () => {
  };

  /**
   * @since 1.0
   * @returns {boolean}
   */
  isOpen = () => {
    return false;
  };

  /**
   * @since 1.0.0
   */
  handleOk = () => {
    this.props.onOk();
  };

  /**
   * @since 1.0.0
   */
  handleCancel = () => {
    this.props.onCancel();
  };

  /**
   * @since 1.0.0
   * @returns {{}}
   */
  modalProps = () => {
    return {};
  };

  /**
   * @since 1.0.0
   */
  getOkProps = () => {
    return {};
  };

  /**
   * @since 1.0.0
   */
  getCancelProps = () => {
    return {};
  };

  /**
   * @since 1.0.0
   * @param step
   */
  handleGoToStep = (step) => {
    return () => {
      this.setState({step});
    };
  };

  /**
   * @since 1.0.0
   * @returns {JSX.Element}
   */
  render() {
    const {loading, error} = this.state;
    return (
        <Modal
            title={this.title()}
            open={this.isOpen()}
            okText={this.okText()}
            cancelText={this.cancelText()}
            onOk={this.handleOk}
            onCancel={this.handleCancel}
            okButtonProps={{size: 'large', loading: loading, ...this.getOkProps()}}
            cancelButtonProps={{size: 'large', type: 'text', ...this.getCancelProps()}}
            centered={true}
            {...this.modalProps()}
        >
          {this.content()}
        </Modal>
    );
  }

}

AntModal.propTypes = {
  onOk    : PropTypes.func,
  onCancel: PropTypes.func,
};

AntModal.defaultProps = {
  onOk    : () => {},
  onCancel: () => {},
};

export default AntModal;