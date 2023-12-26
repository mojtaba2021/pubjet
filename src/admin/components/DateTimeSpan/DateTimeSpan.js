import React from 'react';
import PropTypes from 'prop-types';
import styles from './DateTimeSpan.module.scss';
import {Tooltip} from 'antd';

const DateTimeSpan = props => {
  const {date, time, className} = props;
  return (
      <Tooltip title={time}>
        <span className={`${className} ${styles.wrapper}`}>{date}</span>
      </Tooltip>
  );
};

DateTimeSpan.propTypes = {
  date     : PropTypes.string,
  time     : PropTypes.string,
  className: PropTypes.string,
};

export default DateTimeSpan;
