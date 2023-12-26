import React from 'react';
import PropTypes from 'prop-types';
import './Button.scss';

const Button = props => {
  const {
    id,
    children,
    onClick,
    type,
    large,
    className,
    block,
    disabled,
    loading,
    style,
  } = props;
  return (
      <button
          id={id ? id : ''}
          className={`
            button button-${type}
            ${large ? 'pubjet-btn-lg' : ''}
            ${className} 
            ${disabled ? 'pubjet-disabled' : ''} 
            ${block ? 'pubjet-btn-block' : ''}
            ${loading ? 'pubjet-disabled' : ''}
          `}
          onClick={onClick}
          style={style}
      >
        {loading ? pubjet_params.i18n['please-wait'] : children}
      </button>
  );
};

Button.propTypes = {
  id: PropTypes.string,
  type: PropTypes.string,
  block: PropTypes.bool,
  disabled: PropTypes.bool,
  onClick: PropTypes.func,
  large: PropTypes.bool,
  loading: PropTypes.bool,
  className: PropTypes.string,
  style: PropTypes.oneOfType([PropTypes.object, PropTypes.bool,]),
};

Button.defaultProps = {
  id: '',
  type: 'primary',
  block: false,
  disabled: false,
  large: false,
  className: '',
  loading: false,
  style: {},
};

export default Button;