import React from 'react';
import PropTypes from 'prop-types';
import "./WPNotice.scss";

const WPNotice = props => {
  const {type, className, wrapperProps, children, dismissable, onDismiss} = props;
  return (
      <div className={`notice ${type ? `notice-${type}` : ''} ${dismissable ? 'is-dismissible' : ''}`}>
        <div style={{padding: '10px 0'}}>{children}</div>
        {dismissable && <button type="button" className="notice-dismiss" onClick={onDismiss}>
          <span className="screen-reader-text">Dismiss this notice.</span>
        </button>}
      </div>
  );
};

WPNotice.propTypes = {
  type: PropTypes.string,
  className: PropTypes.string,
  wrapperProps: PropTypes.object,
  dismissable: PropTypes.bool,
  onDismiss: PropTypes.func,
};

WPNotice.defaultProps = {
  type: '',
  className: '',
  wrapperProps: {},
  dismissable: true,
  onDismiss: () => {},
};

export default WPNotice;
