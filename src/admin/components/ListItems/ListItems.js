import React from 'react';
import PropTypes from 'prop-types';
import {v4 as uuid} from 'uuid';
import styles from './ListItems.module.scss';

const ListItems = props => {
  const {items, className} = props;
  return (
      <div className={`${styles.wrapper} ${className}`}>
        {items.map(item => {
          return <React.Fragment key={uuid()}>
            <span className={`label`}>{item.label}</span>
            <span className={`value ${item.className ? item.className : ''}`}>{item.value}</span>
          </React.Fragment>
        })}
      </div>
  );
};

ListItems.propTypes = {
  items: PropTypes.array.isRequired,
  className: PropTypes.string,
};

ListItems.defaultProps = {
  items: [],
  className: '',
};

export default ListItems;
