import React from 'react';
import PropTypes from 'prop-types';
import {Alert} from "antd";
import styles from "./SaveAlert.module.scss";
import {pubjet__} from "../../../shared/scripts/utils";

const SaveAlert = props => {
    const {saved} = props;
    if (!saved) {
        return undefined;
    }
    return <Alert
        showIcon={true}
        type={'success'}
        message={pubjet__('settings-saved')}
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