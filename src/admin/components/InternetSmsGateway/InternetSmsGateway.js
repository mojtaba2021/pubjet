import React from 'react';
import PropTypes from 'prop-types';
import './InternetSmsGateway.scss';
import Select from '../Select/Select';
import RadioList from '../RadioList/RadioList';

const InternetSmsGateway = props => {
  const items = [
    {
      title: pubjet_params.i18n['gateway-primary'],
      value: 'primary',
      disabled: false,
    },
    {
      title: pubjet_params.i18n['gateway-alt'],
      value: 'secondary',
      disabled: true,
    }
  ];
  const {
    name,
    disabled,
    value,
    onChange,
    className,
    renderAs
  } = props;

  if (renderAs === 'radio') {
    return <RadioList
        value={value}
        items={items}
        name={name}
        onChange={onChange}
        wrapperProps={{className: className}}
    />;
  }

  return <Select
      items={items}
      value={value}
      name={name}
      onChange={onChange}
      className={`form-control ${className}`}
  />

};

InternetSmsGateway.propTypes = {
  name: PropTypes.string,
  disabled: PropTypes.bool,
  value: PropTypes.string,
  onChange: PropTypes.func,
  className: PropTypes.string,
  renderAs: PropTypes.string,
};

InternetSmsGateway.defaultProps = {
  name: '',
  disabled: false,
  value: '',
  onChange: PropTypes.func,
  className: '',
  renderAs: 'radio'
};

export default InternetSmsGateway;