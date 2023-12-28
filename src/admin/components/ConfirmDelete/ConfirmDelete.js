import React from 'react';
import PropTypes from 'prop-types';
import {Button, Popconfirm} from 'antd';
import {pubjet__} from '../../../shared/scripts/utils';

const ConfirmDelete = props => {
  const {
    title,
    description,
    onConfirm,
    onCancel,
    okText,
    cancelText,
    children,
  } = props;
  return (
      <Popconfirm
          title={title}
          description={description}
          onConfirm={onConfirm}
          onCancel={onCancel}
          okText={okText}
          cancelText={cancelText}
          okButtonProps={{danger: true,}}
      >{children}</Popconfirm>
  );
};

ConfirmDelete.propTypes = {
  title      : PropTypes.string,
  description: PropTypes.string,
  okText     : PropTypes.string,
  cancelText : PropTypes.string,
  content    : PropTypes.element,
  onConfirm  : PropTypes.func,
  onCancel   : PropTypes.func,
};

ConfirmDelete.defaultProps = {
  title      : pubjet__('delete'),
  description: pubjet__('confirm-delete-data'),
  okText     : pubjet__('delete'),
  cancelText : pubjet__('cancel'),
  content    : <Button type={'text'} danger={true}>{pubjet__('delete')}</Button>,
  onConfirm  : () => {
  },
  onCancel   : () => {
  },
};

export default ConfirmDelete;