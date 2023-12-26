import React from 'react';
import PropTypes from 'prop-types';
import { v4 as uuid } from 'uuid';

const TableNav = props => {
  const {actions} = props;
  return (
      <div className={'tablenav top'}>
        <div className="alignleft actions bulkactions">
          {actions.map(action => {
            const {id, classes, title, onClick} = action;
            return <button id={id} className={`button ${classes}`} onClick={onClick} key={uuid()}>
              {title}
            </button>;
          })}
        </div>
        <div className={`tablenav-pages`}>
          <span className="displaying-num"></span>
        </div>
      </div>
  );
};

TableNav.propTypes = {
  actions: PropTypes.oneOfType([PropTypes.array, PropTypes.bool,])
};

export default TableNav;
