import React from 'react';
import PropTypes from 'prop-types';
import {Alert, Button, Typography} from 'antd';
import {ReloadOutlined} from '@ant-design/icons';
import styles from './ErrorLoading.module.scss';
import {translate} from '../../../shared/scripts/utils';

const {Text} = Typography;

const ErrorLoading = (props) => {
  const {message, reloadText, onClick, buttonProps, alertProps, textProps} = props;
  return (
      <Alert
          type={'error'}
          showIcon={false}
          banner={true}
          className={'pubjet-text-center'}
          description={<React.Fragment>
            <Text type={'danger'} className={styles.text} {...textProps}>
              {message}
            </Text>
            <Button
                type={'primary'}
                size={'medium'}
                onClick={onClick}
                className={styles.button}
                {...buttonProps}
            >
              <ReloadOutlined/>
              {reloadText}
            </Button>
          </React.Fragment>}
          {...alertProps}
      />
  );
};

ErrorLoading.propTypes = {
  message    : PropTypes.string,
  reloadText : PropTypes.string,
  onClick    : PropTypes.func,
  buttonProps: PropTypes.object,
  alertProps : PropTypes.object,
  textProps  : PropTypes.object,
};

ErrorLoading.defaultProps = {
  message    : translate('error'),
  reloadText : translate('refresh'),
  buttonProps: {},
  alertProps : {},
  textProps  : {},
  onClick    : () => {
  },
};

export default ErrorLoading;