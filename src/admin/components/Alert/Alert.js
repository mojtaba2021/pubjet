import React from 'react';
import PropTypes from 'prop-types';
import './Alert.scss';

const Alert = props => {
  const {type, className, children, wrapperProps} = props;
  return (
      <div className={`pubjet-alert pubjet-alert-${type} ${className}`} {...wrapperProps}>
        {children}
      </div>
  );
};

Alert.propTypes = {
  type: PropTypes.string,
  className: PropTypes.string,
  wrapperProps: PropTypes.object,
};

Alert.defaultProps = {
  type: 'primary',
  className: '',
  wrapperProps: {},
};

export default Alert;