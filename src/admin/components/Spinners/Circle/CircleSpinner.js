import React from 'react';
import PropTypes from 'prop-types';

const CircleSpinner = props => {
  const {width, height} = props;
  return (
      <div className={'pubjet-circle-spinner'}>
        <svg xmlns="http://www.w3.org/2000/svg"
             style={{
               margin: 'auto',
               display: 'inline !important',
               shapeRendering: 'auto'
             }}
             width={width} height={height} viewBox="0 0 100 100"
             preserveAspectRatio="xMidYMid">
          <circle cx="50" cy="50" fill="none" stroke="#0099e5" strokeWidth="10"
                  r="35" strokeDasharray="164.93361431346415 56.97787143782138">
            <animateTransform attributeName="transform" type="rotate"
                              repeatCount="indefinite" dur="0.3958579881656805s"
                              values="0 50 50;360 50 50"
                              keyTimes="0;1"></animateTransform>
          </circle>
        </svg>
      </div>
  );
};

CircleSpinner.propTypes = {
  width: PropTypes.string,
  height: PropTypes.string,
  wrapperClassName: PropTypes.string,
};

CircleSpinner.defaultProps = {
  width: '25px',
  height: '25px',
  wrapperClassName: '',
};

export default CircleSpinner;