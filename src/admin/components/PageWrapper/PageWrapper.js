import React from 'react';
import PropTypes from 'prop-types';
import styles from './PageWrapper.module.scss';

const PageWrapper = props => {
  const {title} = props;
  return (
      <div className={styles.wrapper}>
        {
          title
          &&
          <div className={styles.wrapper__header}>
            <div className={styles.wrapper__title}>
              <h3>{title}</h3>
            </div>
          </div>
        }
        <div className={styles.wrapper__content}>
          {props.children}
        </div>
      </div>
  );
};

PageWrapper.propTypes = {
  title: PropTypes.string,
};

export default PageWrapper;
