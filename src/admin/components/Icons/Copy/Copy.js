import React from 'react';
import PropTypes from 'prop-types';

const IconCopy = props => {
  const {width, height, onClick} = props;
  return (
      <div className={`icon-copy ${onClick ? 'cursor-pointer' : ''}`} onClick={onClick ? onClick : () => {}}>
        <svg xmlns="http://www.w3.org/2000/svg" width={width} height={height}
             viewBox="0 0 24 24" fill="none" stroke="currentColor"
             strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
             className="feather feather-copy">
          <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
        </svg>
      </div>
  );
};

IconCopy.propTypes = {
  width: PropTypes.string,
  height: PropTypes.string,
  onClick: PropTypes.oneOfType([PropTypes.func, PropTypes.bool,]),
};

IconCopy.defaultProps = {
  width: '24px',
  height: '24px',
  onClick: false,
};

export default IconCopy;
