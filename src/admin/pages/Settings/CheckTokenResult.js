import React from 'react';
import styles from './CheckTokenResult.module.scss';
import { Alert, Space, Tooltip } from "antd";
import { pubjet__ } from "../../../shared/scripts/utils";
import { LoadingOutlined, QuestionCircleOutlined } from "@ant-design/icons";
import { connect } from "trim-redux";
import { getStoreKey } from "./Actions";

class CheckTokenResult extends React.Component {
    state = {
        visible: true, // control visibility of alert
    };

    componentDidUpdate(prevProps) {
        const { checkToken } = this.props;

        if (checkToken.checked && !checkToken.checking && checkToken !== prevProps.checkToken) {
            this.setState({ visible: true });
            setTimeout(() => {
                this.setState({ visible: false });
            }, 10000);
        }
    }

    render() {
        const { checkToken } = this.props;
        const { visible } = this.state;

        if (!checkToken || typeof checkToken === 'undefined' || Object.keys(checkToken).length === 0) {
            return null;
        }

        const { checked, checking, valid } = checkToken;
        const { website_id, website_url } = checkToken.payload || {};

        if (!checked && !checking) {
            return null;
        }

        if (checking) {
            return (
                <div className={styles.spinWrapper}>
                    <LoadingOutlined
                        spin={true}
                        style={{ fontSize: '18px', marginBottom: '15px' }}
                    />
                </div>
            );
        }

        const message = () => {
        
            if(valid){
                return (
                    <Space>
                        <span>{pubjet__('valid-token')}</span>
                        {website_id && (
                            <Tooltip title={`${website_id} - ${website_url}`}>
                                <QuestionCircleOutlined className={styles.cursorPointer} />
                            </Tooltip>
                        )}
                    </Space>
                );
            }
           
        };

        const description = () => {
            if (valid) {
                return pubjet__('pubjet-triboon-connection-desc');
            }else{
                return pubjet__('invalid-token');
            }
        };

        return (
            <div className={styles.wrapper}>
                {visible && (
                    <Alert
                        type={valid ? 'success' : 'error'}
                        showIcon={true}
                        description={description()}
                        message={message()}
                        style={{textAlign:'center'}}
                    />
                )}
            </div>
        );
    }
}

CheckTokenResult.defaultProps = {
    checkToken: {},
};

const mstp = (state) => ({
    checkToken: state[getStoreKey()].checkToken,
});

export default connect(mstp)(CheckTokenResult);
