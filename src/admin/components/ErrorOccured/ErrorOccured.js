import React from 'react';
import PropTypes from 'prop-types';
import Button from '../Button/Button';

const ErrorOccured = props => {
  const {
    showIcon,
    showButton,
    showMessage,
    message,
    onButtonClick,
    buttonText,
    iconProps,
    wrapperProps,
    messageProps,
    buttonProps,
  } = props;
  return (
      <div className={'pubjet-text-center'} {...wrapperProps}>
        {showIcon && <img className={'pubjet-mb-1'} src={`${pubjet_params.images_url}/warning.png`} width={'55px'} height={'55px'} {...iconProps} />}
        {showMessage && <div className={'pubjet-mb-2'} style={{color: 'red', fontWeight: 'bold'}} {...messageProps}>{message}</div>}
        {showButton && <Button className={'pubjet-mt-1'} onClick={onButtonClick} large={true} {...buttonProps}>{buttonText}</Button>}
      </div>
  );
};

ErrorOccured.propTypes = {
  showIcon: PropTypes.bool,
  showMessage: PropTypes.bool,
  showButton: PropTypes.bool,
  message: PropTypes.string,
  buttonText: PropTypes.string,
  onButtonClick: PropTypes.func,
  wrapperProps: PropTypes.object,
  iconProps: PropTypes.object,
  messageProps: PropTypes.object,
  buttonProps: PropTypes.object,
};

ErrorOccured.defaultProps = {
  showIcon: true,
  showMessage: true,
  showButton: true,
  onButtonClick: () => {},
  message: pubjet_params.i18n['error-occured'],
  buttonText: pubjet_params.i18n['refresh-data'],
  wrapperProps: {},
  iconProps: {},
  messageProps: {},
  buttonProps: {},
};

export default ErrorOccured;