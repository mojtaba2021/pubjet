import React from 'react';
import PropTypes from 'prop-types';
import './InputText.scss';

const InputText = props => {
  const {id, name, inputProps, className, onChange, value ,placeholder} = props;
  return (
      <input
          id={id}
          name={name}
          className={`pubjet-input-text ${className}`}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          {...inputProps}
      />
  );
};

InputText.propTypes = {
  id: PropTypes.string,
  name: PropTypes.string,
  inputProps: PropTypes.object,
  className: PropTypes.string,
  value: PropTypes.string,
  placeholder: PropTypes.string,
  onChange: PropTypes.func,
};

InputText.defaultProps = {
  id: '',
  name: '',
  inputProps: {},
  className: '',
  value: '',
  onChange: () => {},
};

export default InputText;