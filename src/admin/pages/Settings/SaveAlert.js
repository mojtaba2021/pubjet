import React from 'react';
import PropTypes from 'prop-types';
import {Alert} from "antd";
import styles from "./SaveAlert.module.scss";

const SaveAlert = props => {
    const {saved} = props;
    if (!saved) {
        return undefined;
    }
    return <Alert
        showIcon={true}
        type={'success'}
        message={'تنظیمات با موفقیت ذخیره شد'}
        className={styles.alert}
    />;
};

SaveAlert.propTypes = {
    saved: PropTypes.bool,
};

SaveAlert.defaultProps = {
    saved: false,
};

export default SaveAlert;