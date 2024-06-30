import React, {useState} from 'react';
import {Form, Space, Switch} from "antd";
import {changeInput, saveOptions} from "./Actions";
import {connect} from "trim-redux";
import {pubjet__, showSuccessMessage} from "../../../shared/scripts/utils";

import styles from './Misc.module.scss';
import SavedAlert from "../../components/SaveAlert/SavedAlert";

const Misc = props => {
    const [showAlert, setShowAlert] = useState(false);
    const {uninstallCleanup, deleteFirstImage} = props.options;
    const toggleSavedAlert = () => {
        setShowAlert(true);
        setTimeout(() => {
            setShowAlert(false);
        }, 2500);
    };
    return (
        <React.Fragment>
            {showAlert && <SavedAlert/>}
            <Form className={styles.wrapper} layout={'vertical'} colon={false}>
                <Form.Item className={styles.hideInput} label={<Space>
                    <Switch
                        size={'default'}
                        checked={deleteFirstImage}
                        onChange={(checked) => {
                            changeInput('deleteFirstImage', checked);
                            saveOptions();
                            toggleSavedAlert();
                        }}
                    />
                    <span>{pubjet__('delete-first-image')}</span>
                </Space>}
                />
                <Form.Item className={styles.hideInput} label={<Space>
                    <Switch checked={uninstallCleanup} onChange={(checked) => {
                        changeInput('uninstallCleanup', checked);
                        saveOptions();
                        toggleSavedAlert();
                    }} size={'default'}/>
                    <span>{pubjet__('uninstall')}</span>
                </Space>}
                />
            </Form>
        </React.Fragment>
    );
};

const mstp = (state) => ({
    options: state.options
});

export default connect(mstp)(Misc);