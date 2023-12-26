import React from 'react';
import PropTypes from 'prop-types';
import styles from './ReloadSpan.module.scss';
import {translate} from '../../../shared/scripts/utils';
import {ReloadOutlined} from '@ant-design/icons';
import {Tooltip} from 'antd';

const ReloadSpan = props => {
  const {title, onClick, spin, show} = props;
  if (!show) {
    return null;
  }
  return (
      <Tooltip title={title}>
          <span onClick={onClick} className={styles.span}>
           <ReloadOutlined spin={spin}/>
          </span>
      </Tooltip>
  );
};

ReloadSpan.propTypes = {
  title  : PropTypes.string,
  onClick: PropTypes.func,
  spin   : PropTypes.bool,
  show   : PropTypes.bool,
};

ReloadSpan.defaultProps = {
  title  : translate('refresh'),
  onClick: '',
  spin   : false,
  show   : true,
};

export default ReloadSpan;
