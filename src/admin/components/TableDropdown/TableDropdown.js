import React from 'react';
import PropTypes from 'prop-types';
import styles from './TableDropdown.module.scss';
import {ArrowDownOutlined} from '@ant-design/icons';
import {pubjet__} from '../../../shared/scripts/utils';
import {Button, Dropdown} from 'antd';

const TableDropdown = props => {
  const {items, text, placement, dropdownProps, buttonProps} = props;
  return (
      <Dropdown
          menu={{items}}
          placement={placement}
          trigger={'click'}
          className={styles.wrapper}
          {...dropdownProps}
      >
        <Button
            type={'dashed'}
            className={styles.button}
            icon={<ArrowDownOutlined />}
            {...buttonProps}
        >
          {text}
        </Button>
      </Dropdown>
  );
};

TableDropdown.propTypes = {
  text         : PropTypes.string,
  placement    : PropTypes.string,
  dropdownProps: PropTypes.object,
  buttonProps  : PropTypes.object,
  items        : PropTypes.array.isRequired,
};

TableDropdown.defaultProps = {
  text         : pubjet__('actions'),
  placement    : 'bottomLeft',
  items        : [],
  dropdownProps: {},
  buttonProps  : {},
};

export default TableDropdown;