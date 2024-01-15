import React from 'react';
import {Form, Switch} from "antd";
import {changeInput, saveOptions} from "./Actions";
import {connect} from "trim-redux";
import {pubjet__, showSuccessMessage} from "../../../shared/scripts/utils";

import styles from './Misc.module.scss';

const Misc = props => {
    const {uninstall, nofollow} = props.options;
    return (
        <Form className={styles.wrapper} layout={'vertical'} colon={false}>
            {/*<Form.Item className={'pubjet-nofollow-wrapper'} label={pubjet__('enable-nofollow')}>*/}
            {/*    <Switch checked={nofollow} onChange={(checked) => {*/}
            {/*        changeInput('nofollow', checked);*/}
            {/*        saveOptions();*/}
            {/*        showSuccessMessage(pubjet__('saved'));*/}
            {/*    }} size={'default'}/>*/}
            {/*</Form.Item>*/}
            <Form.Item className={'pubjet-uninstall-switch-wrapper'} label={pubjet__('uninstall')}>
                <Switch checked={uninstall} onChange={(checked) => {
                    changeInput('uninstall', checked);
                    saveOptions();
                    showSuccessMessage(pubjet__('saved'));
                }} size={'default'}/>
            </Form.Item>
        </Form>
    );
};

const mstp = (state) => ({
    options: state.options
});

export default connect(mstp)(Misc);