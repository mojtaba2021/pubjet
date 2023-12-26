import React from 'react';
import PropTypes from 'prop-types';
import "./TelInput.scss";

const TelInput = props => {
  const {
    value,
    onChange,
    label,
    inputClassName,
    wrapperProps,
    wrapperClassName,
    inputContainerClassName,
    inputProps,
    labelProps,
  } = props;

  const handlePhoneChange = (status, phoneNumber, country) => {
    onChange({
      status: status,
      number: phoneNumber,
      country: country,
    });
  };

  return (
      <div className={`tel-input-wrapper ${wrapperClassName}`} {...wrapperProps}>
        {label && <label className={'d-block mb-2'} {...labelProps}>{label}</label>}

      </div>
  );
};

TelInput.propTypes = {
  label: PropTypes.string,
  value: PropTypes.string,
  onChange: PropTypes.func,
  inputClassName: PropTypes.string,
  wrapperClassName: PropTypes.string,
  wrapperProps: PropTypes.object,
  inputContainerClassName: PropTypes.string,
  inputProps: PropTypes.object,
  labelProps: PropTypes.object,
};

TelInput.defaultProps = {
  label: '',
  wrapperClassName: '',
  inputClassName: 'form-control',
  inputContainerClassName: 'intl-tel-input',
  wrapperProps: {},
  inputProps: {},
  labelProps: {},
};

export default TelInput;
