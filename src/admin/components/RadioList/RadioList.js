import React from 'react';
import PropTypes from 'prop-types';

const RadioList = props => {
  const {name, value, disabled, items, onChange, wrapperProps} = props;
  return (
      <div {...wrapperProps}>
        {items.map((item, index) => {
          return <label key={index} form={`radio-${item.value}`} className={`${item.disabled ? 'disabled' : ''} me-2`}>
            <input
                id={`radio-${item.value}`}
                name={name}
                type={'radio'}
                value={item.value}
                checked={item.value === value}
                onChange={onChange}
                className={'me-1'}
            />
            {item.title}
          </label>;
        })}
      </div>
  );
};

RadioList.propTypes = {
  name: PropTypes.string,
  value: PropTypes.string,
  onChange: PropTypes.func,
  items: PropTypes.array,
  wrapperProps: PropTypes.object,
};

RadioList.defaultProps = {
  name: '',
  value: '',
  onChange: PropTypes.func,
  items: [],
  wrapperProps: {},
};

export default RadioList;
