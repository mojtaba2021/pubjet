import React from 'react';

const ConditionalWrap = ({ condition, wrap, children }) => {
  return condition ? wrap(children) : children;
};

ConditionalWrap.propTypes = {};

export default ConditionalWrap;
