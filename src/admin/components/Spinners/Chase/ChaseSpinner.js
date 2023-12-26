import React from 'react';
import PropTypes from 'prop-types';
import './ChaseSpinner.scss';

const ChaseSpinner = props => {
  return (
      <div className="sk-chase">
        <div className="sk-chase-dot" />
        <div className="sk-chase-dot" />
        <div className="sk-chase-dot" />
        <div className="sk-chase-dot" />
        <div className="sk-chase-dot" />
        <div className="sk-chase-dot" />
      </div>
  );
};

ChaseSpinner.propTypes = {

};

export default ChaseSpinner;
