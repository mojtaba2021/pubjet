import React from 'react';
import PropTypes from 'prop-types';
import styles from './Online.module.scss';
import {Badge} from 'antd';
import {translate} from '../../../shared/scripts/utils';

const Online = props => {
  const {isOnline} = props;
  const color = isOnline ? 'green' : 'red';
  const text  = isOnline ? translate('online') : translate('offline');
  return <Badge text={text} color={color} />;
};

Online.propTypes = {
  isOnline: PropTypes.bool.isRequired,
};

export default Online;
