import React from 'react';
import PropTypes from 'prop-types';

const ClickToCall = props => {
  const {children, className, wrapperProps} = props;
  return (
      <a className={className} href={`tel: ${children}`} {...wrapperProps}>
        {children}
      </a>
  );
};

ClickToCall.propTypes = {
  className   : PropTypes.string,
  wrapperProps: PropTypes.object,
};

ClickToCall.defaultProps = {
  className   : '',
  wrapperProps: {},
};

export default ClickToCall;
