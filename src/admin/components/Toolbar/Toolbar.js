import React from 'react';
import PropTypes from 'prop-types';
import ButtonsList from '../ButtonsList/ButtonsList';

const Toolbar = props => {
  const {buttons, wrapperClass, wrapperProps} = props;
  return (
      <div className={wrapperClass} {...wrapperProps}>
        <ButtonsList buttons={buttons} />
      </div>
  );
};

Toolbar.propTypes = {
  buttons: PropTypes.oneOfType([PropTypes.bool, PropTypes.array]),
  wrapperClass: PropTypes.string,
  wrapperProps: PropTypes.object,
};

Toolbar.defaultProps = {
  buttons: false,
  wrapperClass: 'pubjet-mt-2 pubjet-mb-2',
  wrapperProps: {},
};

export default Toolbar;