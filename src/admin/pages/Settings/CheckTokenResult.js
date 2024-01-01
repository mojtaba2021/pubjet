import React from 'react';
import styles from './CheckTokenResult.module.scss';
import {Alert} from "antd";
import {pubjet__} from "../../../shared/scripts/utils";
import {LoadingOutlined} from "@ant-design/icons";
import {connect} from "trim-redux";
import {getStoreKey} from "./Actions";

const CheckTokenResult = props => {
    const {checked, checking, valid} = props.checkToken;
    if (!checked && !checking) {
        return null;
    }
    if (checking) {
        return <div className={styles.spinWrapper}>
            <LoadingOutlined
                spin={true}
                style={{fontSize: '18px'}}
            />
        </div>;
    }
    return <div className={styles.wrapper}>
        {
            <Alert
                type={checking ? 'info' : (valid ? 'success' : 'error')}
                showIcon={true}
                description={pubjet__(valid ? 'valid-token' : 'invalid-token')}
            />
        }
    </div>
};

CheckTokenResult.defaultProps = {
    checkToken: {},
};

const mstp = (state) => ({
    checkToken: state[getStoreKey()].checkToken,
});

export default connect(mstp)(CheckTokenResult);