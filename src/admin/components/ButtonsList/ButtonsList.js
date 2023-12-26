import React from 'react';
import PropTypes from 'prop-types';
import { v4 as uuid } from 'uuid';
import "./ButtonsList.scss";

const ButtonsList = props => {
  const {buttons, wrapperId, wrapperClassName} = props;
  return (
      <div id={wrapperId} className={`buttons-list ${wrapperClassName}`}>
        {buttons.map(button => {
          const {id, title, type, className, loading, onClick} = button;
          return <button key={uuid()} id={id} title={title} className={`button ${type ? 'button-' + type : ''} ${loading ? 'disabled' : ''} ${className} pubjet-me-1`} onClick={onClick}>
            {loading ? pubjet_params.i18n['please-wait'] : title}
          </button>;
        })}
      </div>
  );
};

ButtonsList.propTypes = {
  buttons: PropTypes.array.isRequired,
  loading: PropTypes.bool,
  wrapperId: PropTypes.string,
  wrapperClassName: PropTypes.string,
};

ButtonsList.defaultProps = {
  buttons: [],
  loading: false,
  wrapperId: '',
  wrapperClassName: '',
};

export default ButtonsList;