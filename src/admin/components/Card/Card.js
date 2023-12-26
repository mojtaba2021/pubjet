import React from 'react';
import PropTypes from 'prop-types';
import './Card.scss';
import Loading from '../Loading/Loading';

const Card = props => {
  const {title, children, loading, wrapperClassName, wrapperProps} = props;
  return (
      <div className={`pubjet-card ${wrapperClassName}`} {...wrapperProps}>
        {title && <h2 className="title pubjet-card-header">{title}</h2>}
        <div className={`pubjet-card-body`}>{children}</div>
        <Loading show={loading} />
      </div>
  );
};

Card.propTypes = {
  title: PropTypes.string,
  wrapperClassName: PropTypes.string,
  wrapperProps: PropTypes.object,
  loading: PropTypes.bool,
};

Card.defaultProps = {
  title: '',
  loading: false,
  wrapperClassName: '',
  wrapperProps: {},
};

export default Card;