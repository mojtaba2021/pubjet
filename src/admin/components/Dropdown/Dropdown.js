import React from 'react';
import PropTypes from 'prop-types';
import { v4 as uuid } from 'uuid';
import "./Dropdown.scss";

const Dropdown = props => {
  const {items, wrapperClassName, wrapperProps} = props;
  return (
      <div className={`pubjet-dropdown ${wrapperClassName}`} {...wrapperProps}>
        <span className={'pubjet-text-black-50 pubjet-cursor-pointer'}>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
                 className="feather feather-more-horizontal">
              <circle cx="12" cy="12" r="1" />
              <circle cx="19" cy="12" r="1" />
              <circle cx="5" cy="12" r="1" />
            </svg>
        </span>
        <div className="pubjet-dropdown-content">
          {items.map(item => {
            return <a key={uuid()} className="pubjet-cursor-pointer pubjet-text-muted pubjet-dropdown-item" onClick={item.onClick}>
              {item.title}
            </a>;
          })}
        </div>
      </div>
  );
};

Dropdown.propTypes = {
  items: PropTypes.array,
  wrapperClassName: PropTypes.string,
  wrapperProps: PropTypes.object,
};

Dropdown.defaultProps = {
  items: [],
  wrapperClassName: '',
  wrapperProps: {},
};

export default Dropdown;