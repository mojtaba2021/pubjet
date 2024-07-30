import React from 'react';
import styles from './CheckTokenResult.module.scss';
import {Alert, Space, Tooltip} from "antd";
import {pubjet__} from "../../../shared/scripts/utils";
import {LoadingOutlined, QuestionCircleOutlined} from "@ant-design/icons";
import {connect} from "trim-redux";
import {getStoreKey} from "./Actions";

const CheckTokenResult = props => {
    const {checkToken} = props;
    if (!checkToken || 'undefined' === typeof checkToken || Object.keys(checkToken).length === 0) {
        return;
    }
    const {checked, checking, valid} = props.checkToken;
    const {website_id, website_url} = props.checkToken.payload;
    if (!checked && !checking) {
        return null;
    }
    if (checking) {
        return <div className={styles.spinWrapper}>
            <LoadingOutlined
                spin={true}
                style={{fontSize: '18px', marginBottom: '15px'}}
            />
        </div>;
    }
    const description = () => {
        if (!valid) {
            return pubjet__('invalid-token');
        }
        return <Space>
            <span>{pubjet__('valid-token')}</span>
            {website_id && <Tooltip title={`${website_id} - ${website_url}`}>
                <QuestionCircleOutlined className={styles.cursorPointer}/>
            </Tooltip>}
        </Space>;
    };
    return <div className={styles.wrapper}>
        {
            <Alert
                type={checking ? 'info' : (valid ? 'success' : 'error')}
                showIcon={true}
                description={description()}
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