import React from 'react';
import PropTypes from 'prop-types';
import styles from './BulkActions.module.scss';
import {DownOutlined} from '@ant-design/icons';
import {Button, Dropdown, Space} from 'antd';
import {translate} from '../../../shared/scripts/utils';

const BulkActions = props => {
  const {items, onClick, text, dropdownProps, buttonProps} = props;
  const menuProps = {
    items  : items,
    onClick: onClick,
  };
  return (
      <Dropdown menu={menuProps} {...dropdownProps}>
        <Button size={'large'} type={'default'} {...buttonProps}>
          <Space>{text}<DownOutlined/></Space>
        </Button>
      </Dropdown>
  );
};

BulkActions.propTypes = {
  text         : PropTypes.string,
  items        : PropTypes.array.isRequired,
  onClick      : PropTypes.func,
  dropdownProps: PropTypes.object,
  buttonProps  : PropTypes.object,
};

BulkActions.defaultProps = {
  items        : [],
  text         : translate('bulk-actions'),
  dropdownProps: {},
  buttonProps  : {},
  onClick      : () => {
  },
};

export default BulkActions;
