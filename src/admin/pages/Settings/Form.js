import React from 'react';
import styles from "./Form.module.scss";
import {changeInput, doCheckToken} from "./Actions";
import {Form as AntForm, Input, Select, Tooltip} from "antd";
import {connect} from "trim-redux";
import CheckTokenResult from "./CheckTokenResult";
import {pubjet__} from "../../../shared/scripts/utils";
import {CheckOutlined, ReloadOutlined} from "@ant-design/icons";

const {TextArea} = Input;

const Form = props => {
    const {token, category, categories, checkToken} = props.options;

    /**
     * @since 1.0
     */
    const renderInput = (args) => {
        const {textarea = false} = args;
        if (textarea) {
            return <TextArea {...args}/>;
        }
        return <Input size={'large'}{...args}/>;
    };

    return <AntForm layout={`vertical`} autoComplete="off">
        <AntForm.Item label={pubjet__('triboon-token')}>
            {renderInput({
                name     : 'token',
                value    : token,
                className: styles.input,
                onChange : (e) => {
                    changeInput('token', e.target.value);
                },
                suffix   : token ? <Tooltip title={pubjet__('check-token')}>
                    <ReloadOutlined className={styles.spinner} onClick={doCheckToken}/>
                </Tooltip> : null,
            })}
        </AntForm.Item>
        <CheckTokenResult/>
        <AntForm.Item label={pubjet__('default-category')}>
            <Select
                className={`${styles.input} ${styles.select}`}
                options={categories}
                value={category}
                size={'large'}
                labelInValue={true}
                onChange={value => {
                    changeInput('category', value);
                }}
            />
        </AntForm.Item>
    </AntForm>;
};

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(Form);