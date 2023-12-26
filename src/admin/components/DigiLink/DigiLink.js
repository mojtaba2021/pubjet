import React from 'react';
import PropTypes from 'prop-types';

const DigiLink = props => {
    const {onClick, children} = props;
    return (
        <span
            className={`${onClick ? 'pubjet-cursor-pointer' : false} pubjet-digi-link`}
            onClick={onClick ? onClick : undefined}
        >
       {children}
     </span>
    );
};

DigiLink.propTypes = {
    onClick: PropTypes.oneOfType([PropTypes.func, PropTypes.bool,]),
};

DigiLink.defaultProps = {
    onClick: false,
};

export default DigiLink;