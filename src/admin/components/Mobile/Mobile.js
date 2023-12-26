import React from 'react';
import PropTypes from 'prop-types';
import styles from './Mobile.module.scss';
import {Space} from 'antd';

const Mobile = props => {
  const {className, wrapperProps, children, onClick} = props;
  if (!children) {
    return '-';
  }
  return (
      <a
          className={`${styles.mobile} ${className}`}
          href={onClick ? undefined : `tel: ${children}`}
          onClick={onClick ? onClick : undefined}
          {...wrapperProps}
      >
        {children}
      </a>
  );
};

Mobile.propTypes = {
  className   : PropTypes.string,
  wrapperProps: PropTypes.object,
  onClick     : PropTypes.func,
};

Mobile.defaultProps = {
  className   : '',
  wrapperProps: {},
  onClick     : false,
};

export default React.memo(Mobile, (oldProps, newProps) => {
    const {children: oldMobile} = oldProps;
    const {children: newMobile} = newProps;
    return oldMobile === newMobile;
});
