import React from 'react';
import PropTypes from 'prop-types';
import './Select.scss';

const Select = props => {
  const {
    name,
    label,
    showLabel,
    items,
    value,
    large,
    block,
    onChange,
    className,
    selectProps,
    wrapperClassName,
    wrapperProps,
    labelProps,
  } = props;
  return (
      <div className={wrapperClassName} {...wrapperProps}>
        {(showLabel && label) && <label className={'pubjet-d-block pubjet-mb-1'} {...labelProps}>{label}</label>}
        <select name={name} value={value} onChange={onChange} className={`${large ? 'pubjet-select-large' : ''} ${block ? 'pubjet-w-100' : ''} ${className}`} {...selectProps}>
          {items.map((item, index) => {
            if (item.hidden) {
              return false;
            }
            return <option key={index + 1} value={item.value}>
              {item.title}
            </option>;
          })}
        </select>
      </div>
  );
};

Select.propTypes = {
  label: PropTypes.string,
  showLabel: PropTypes.bool,
  name: PropTypes.string,
  items: PropTypes.oneOfType([PropTypes.bool, PropTypes.array]),
  value: PropTypes.string,
  block: PropTypes.bool,
  className: PropTypes.string,
  onChange: PropTypes.func,
  selectProps: PropTypes.object,
  wrapperClassName: PropTypes.string,
  wrapperProps: PropTypes.object,
  labelProps: PropTypes.object,
  large: PropTypes.bool,
};

Select.defaultProps = {
  items: [],
  label: '',
  showLabel: true,
  labelProps: {},
  value: '',
  block: true,
  className: '',
  large: false,
  selectProps: {},
  wrapperClassName: '',
  wrapperProps: {},
  onChange: (item) => {},
};

export default Select;