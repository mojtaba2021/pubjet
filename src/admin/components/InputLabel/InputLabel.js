import React from 'react';
import PropTypes from 'prop-types';
import "./InputLabel.scss";

const InputLabel = props => {
  const {value, onChange, showLabel, label, wrapperClassName, inputType, wrapperProps, inputName, inputProps} = props;
  return (
      <div className={`pubjet-input-label ${wrapperClassName} pubjet-mb-2`} {...wrapperProps}>
        {showLabel && <label className={'pubjet-mb-1'}>{label}</label>}
        {('text' === inputType || 'tel' === inputType) && <input name={inputName} type={inputType} value={value} onChange={onChange} {...inputProps} />}
        {'textarea' === inputType && <textarea name={inputName} onChange={onChange} className={`pubjet-textarea`} rows={6} value={value} {...inputProps} />}
      </div>
  );
};

InputLabel.propTypes = {
  value: PropTypes.string,
  label: PropTypes.string,
  showLabel: PropTypes.bool,
  onChange: PropTypes.func,
  wrapperClassName: PropTypes.string,
  wrapperProps: PropTypes.object,
  inputType: PropTypes.string,
  inputProps: PropTypes.object,
  inputName: PropTypes.string,
};

InputLabel.defaultProps = {
  value: '',
  label: '',
  wrapperClassName: '',
  showLabel: true,
  inputType: 'text',
  onChange: () => {},
  wrapperProps: {},
  inputProps: {},
  inputName: '',
};

export default InputLabel;