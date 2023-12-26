import React from 'react';
import PropTypes from 'prop-types';
import SimpleReactModal from 'simple-react-modal';
import './Modal.scss';
import Loading from '../Loading/Loading';

const Modal = props => {
  const {
    title,
    show,
    children,
    onClose,
    loading,
    closeOnOuterClick,
    modalProps,
  } = props;
  return (
      <SimpleReactModal
          containerClassName={`pubjet-react-modal`}
          onClose={onClose}
          closeOnOuterClick={closeOnOuterClick}
          show={show}
          transitionSpeed={1200}
          {...modalProps}
      >
        <div className={`pubjet-modal-title`}>{title}</div>
        <div className={`pubjet-modal-content`}>
          <Loading show={loading} />
          {children}
        </div>
      </SimpleReactModal>
  );
};

Modal.propTypes = {
  title: PropTypes.string,
  show: PropTypes.bool,
  modalProps: PropTypes.object,
  onClose: PropTypes.func,
  loading: PropTypes.bool,
  closeOnOuterClick: PropTypes.bool,
};

Modal.defaultProps = {
  title: '',
  show: true,
  loading: false,
  closeOnOuterClick: true,
  modalProps: {},
  onClose: () => {},
};

export default Modal;