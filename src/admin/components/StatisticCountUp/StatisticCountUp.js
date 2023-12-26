import React from 'react';
import PropTypes from 'prop-types';
import styles from './StatisticCountUp.module.scss';
import {translate} from '../../../shared/scripts/utils';
import {Statistic} from 'antd';
import CountUp from 'react-countup';

const StatisticCountUp = props => {
  const {title, value, loading, valueStyle} = props;
  const formatter = value => <CountUp end={value} separator=","/>;
  return (
      <Statistic
          title={title}
          value={value}
          loading={loading}
          formatter={formatter}
          valueStyle={valueStyle}
      />
  );
};

StatisticCountUp.propTypes = {
  title     : PropTypes.string,
  value     : PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  loading   : PropTypes.bool,
  valueStyle: PropTypes.object,
};

StatisticCountUp.defaultProps = {
  title     : '',
  value     : '',
  loading   : false,
  valueStyle: {
    fontSize  : '40px',
    fontWeight: 'bold',
  },
};

export default StatisticCountUp;
