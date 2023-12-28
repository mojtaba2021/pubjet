import React from 'react';
import styles from "./Form.module.scss";
import {changeInput} from "./Actions";
import {Form as AntForm, Input, Select} from "antd";
import {connect} from "trim-redux";

const {TextArea} = Input;

const Form = props => {
    const {token, debug, category, categories} = props.options;

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
        <AntForm.Item label={'کلید دسترسی تریبون'}>
            {renderInput({
                name     : 'token',
                value    : token,
                className: styles.input,
                onChange : (e) => {
                    changeInput('token', e.target.value);
                },
            })}
        </AntForm.Item>
        <AntForm.Item label={'دسته بندی پیشفرض انتشار'}>
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