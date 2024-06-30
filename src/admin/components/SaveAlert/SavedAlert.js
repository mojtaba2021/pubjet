import React from 'react';
import PropTypes from 'prop-types';
import styles from './SavedAlert.module.scss';
import {Alert} from "antd";
import {pubjet__} from "../../../shared/scripts/utils";

const SavedAlert = props => {
    const {className} = props;
    return (
        <Alert
            type={'success'}
            showIcon={true}
            message={pubjet__('settings-saved')}
            {...props}
            className={`${className} ${styles.alert}`}
        />
    );
};

SavedAlert.propTypes = {
    className: PropTypes.string,
};

SavedAlert.defaultProps = {
    className: '',
};

export default SavedAlert;