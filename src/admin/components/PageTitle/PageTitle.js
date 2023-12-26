import React from 'react';
import PropTypes from 'prop-types';
import "./PageTitle.scss";

const PageTitle = props => {
  const {title} = props;
  return (
      <React.Fragment>
        <h4 className="wp-heading-inline">{title}</h4>
        <hr className="wp-header-end" />
      </React.Fragment>
  );
};

PageTitle.propTypes = {
  title: PropTypes.string.isRequired,
};

export default PageTitle;
