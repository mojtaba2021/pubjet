import React from 'react';
import PropTypes from 'prop-types';
import {Badge, Tooltip} from 'antd';

const ActiveDeactive = props => {
  const {
        activeKey,
        deactiveKey,
        status,
        activeText,
        deactiveText,
        onClick,
        tooltip,
      } = props;
  return (
      <Tooltip title={tooltip}>
        {status === activeKey && <Badge color={'green'} className={`${onClick ? 'pubjet-cursor-pointer' : ''}`} onClick={onClick} text={activeText}/>}
        {status === deactiveKey &&
        <Badge color={'red'} className={`${onClick ? 'pubjet-cursor-pointer' : ''}`} onClick={onClick} text={deactiveText}/>}
      </Tooltip>
  );
};

ActiveDeactive.propTypes = {
  status      : PropTypes.string.isRequired,
  activeKey   : PropTypes.string,
  deactiveKey : PropTypes.string,
  activeText  : PropTypes.string,
  deactiveText: PropTypes.string,
  tooltip     : PropTypes.string,
  onClick     : PropTypes.func,
};

ActiveDeactive.defaultProps = {
  activeKey   : 'active',
  deactiveKey : 'deactive',
  tooltip     : '',
  activeText  : pubjet_params.i18n.active,
  deactiveText: pubjet_params.i18n.deactive,
};

export default ActiveDeactive;