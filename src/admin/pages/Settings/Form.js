import React from 'react';
import styles from "./Form.module.scss";
import {changeInput, doCheckToken} from "./Actions";
import {Form as AntForm, Input, Tooltip} from "antd";
import {connect} from "trim-redux";
import CheckTokenResult from "./CheckTokenResult";
import {pubjet__} from "../../../shared/scripts/utils";
import {ReloadOutlined} from "@ant-design/icons";
import PricingPlans from "./PricingPlans";
import SelectTerms from "../../components/SelectTerms/SelectTerms";

const {TextArea} = Input;
const Form = props => {
    const {
              token,
              defaultCategory,
              alignCenterImages,
              pricingPlans,
          } = props.options;

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
                autoFocus: true,
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
            <SelectTerms taxonomy={'category'} value={defaultCategory} onChange={selected => {
                changeInput('defaultCategory', selected);
            }}/>
        </AntForm.Item>
        {/*<AntForm.Item label={pubjet__('align-center-images')} tooltip={pubjet__('align-center-images-help')}>*/}
        {/*    <Switch value={1} checked={alignCenterImages} onChange={checked => {*/}
        {/*        changeInput('alignCenterImages', checked);*/}
        {/*    }}/>*/}
        {/*</AntForm.Item>*/}
        {(pricingPlans && pricingPlans.length > 0) && <AntForm.Item label={pubjet__('plans-categories')}>
            <PricingPlans/>
        </AntForm.Item>}
    </AntForm>;
};

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(Form);