import React, { useEffect } from 'react';
import styles from "./Form.module.scss";
import { changeInput, doCheckToken } from "./Actions";
import { Alert, Button, Form as AntForm, Input, Space, Tooltip } from "antd";
import { connect } from "trim-redux";
import CheckTokenResult from "./CheckTokenResult";
import { pubjet__ } from "../../../shared/scripts/utils";
import { CheckCircleFilled, CloseCircleFilled, ReloadOutlined, SaveOutlined } from "@ant-design/icons";
import PricingPlans from "./PricingPlans";


const { TextArea } = Input;

const MAX_COUNT = 10;
const Form = props => {
    const { token, pricingPlans, checkToken } = props.options;

    useEffect(() => {
        if (token) {
            doCheckToken();
        }
    }, [token]);
    
    /**
     * @since 1.0
     */
    const renderInput = (args) => {
        const { textarea = false } = args;
        if (textarea) {
            return <TextArea {...args} />;
        }
        return <Input size={'large'}{...args} />;
    };

    return <AntForm layout={`vertical`} autoComplete="off">
        <AntForm.Item label={pubjet__('triboon-token')}>
            {renderInput({
                name: 'token',
                value: token,
                className: `${styles.input} ${token ? (checkToken.valid ? (styles.borderSuccess) : (styles.borderError)) : ''}`,
                onChange: (e) => {
                    changeInput('token', e.target.value);
                },
                suffix: token ? (
                    checkToken.valid ? (
                        <CheckCircleFilled style={{ color: '#52c41a', fontSize: '24px' }} />
                    ) : (
                        <CloseCircleFilled style={{ color: '#ff4d4f', fontSize: '24px' }} />
                    )
                ) : null,
            })}
        </AntForm.Item>
        <CheckTokenResult />
        {
            token && checkToken.valid ? (
                pricingPlans && pricingPlans.length > 0 ? (
                    <AntForm.Item label={pubjet__('plans-categories')}>
                        <PricingPlans />
                    </AntForm.Item>
                ) : (
                    <div className={styles.alertWrapper}>
                        <Alert
                            type="error"
                            showIcon={true}
                            description={pubjet__('no-pricing-plans-available')}
                        />
                    </div>

                )
            ) : null
        }
        {(!checkToken.valid || (pricingPlans?.length == 0)) && (
            <AntForm.Item>
                <Button
                    type={'primary'}
                    block={true}
                    size={'large'}
                    onClick={doCheckToken}
                    icon={<ReloadOutlined />}
                    shape={'square'}
                >
                    {pubjet__('check-token')}
                </Button>
            </AntForm.Item>)}

    </AntForm>;
};

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(Form);