import React from 'react';
import PropTypes from 'prop-types';

const Loading = props => {
  if (!props.show) {
    return false;
  }
  return (
      <div className={`pubjet-loading`}>
        <div className={`pubjet-loading-icon`} />
      </div>
  );
};

Loading.propTypes = {
  show: PropTypes.bool,
};

Loading.defaultProps = {
  show: false
};

export default Loading;