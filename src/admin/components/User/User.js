import React from 'react';
import PropTypes from 'prop-types';
import "./User.scss";

const User = props => {
  const {avatar, name, avatarProps, nameProps, linkProps, onClick, url} = props;

  const element = <React.Fragment>
    <img src={avatar} width={'20px'} height={'20px'} {...avatarProps} />
    <span {...nameProps}>{name}</span>
  </React.Fragment>;

  if (!url) {
    return <div className={`pubjet-d-flex ${onClick ? 'pubjet-cursor-pointer' : ''}`} onClick={onClick ? onClick : () => {}}>
      {element}
    </div>;
  }

  return <a href={url} {...linkProps}>{element}</a>
};

User.propTypes = {
  avatar: PropTypes.string,
  name: PropTypes.string,
  url: PropTypes.oneOfType([PropTypes.string, PropTypes.bool,]),
  onClick: PropTypes.oneOfType([PropTypes.func, PropTypes.bool,]),
  avatarProps: PropTypes.object,
  nameProps: PropTypes.object,
  linkProps: PropTypes.object,
};

User.defaultProps = {
  avatar: '',
  name: '',
  url: false,
  onClick: false,
  avatarProps: {},
  nameProps: {},
  linkProps: {},
};

export default User;