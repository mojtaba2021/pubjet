import React from 'react';
import PropTypes from 'prop-types';
import "./Span.scss";

const Span = props => {
  const {type, onClick, children} = props;
  return (
      <span className={`pubjet-span pubjet-span-${type} ${onClick ? 'cursor-pointer' : ''}`} onClick={onClick}>
        {children}
      </span>
  );
};

Span.propTypes = {
    type: PropTypes.string,
    onClick: PropTypes.func,
};

Span.defaultValue = {
  type: 'green',
};

export default Span;