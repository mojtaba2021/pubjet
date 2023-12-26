import React from 'react';
import PropTypes from 'prop-types';
import {Button, Popconfirm} from 'antd';
import {translate} from '../../../shared/scripts/utils';

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
  title      : translate('delete'),
  description: translate('confirm-delete-data'),
  okText     : translate('delete'),
  cancelText : translate('cancel'),
  content    : <Button type={'text'} danger={true}>{translate('delete')}</Button>,
  onConfirm  : () => {
  },
  onCancel   : () => {
  },
};

export default ConfirmDelete;
