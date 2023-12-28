import React from 'react';
import styles from './Settings.module.scss';

import {connect} from "trim-redux";
import Header from "./Header";
import General from "./General";
import {Tabs} from "antd";
import Debug from "./Debug";
import {pubjet__} from "../../../shared/scripts/utils";
import Misc from "./Misc";

const Settings = props => {
    return <div className={styles.container}>
        <Header/>
        <Tabs
            defaultActiveKey="general"
            rootClassName={styles.tabs}
            items={[
                {
                    key     : 'general',
                    label   : pubjet__('general'),
                    children: <General/>,
                },
                {
                    key     : 'debug',
                    label   : pubjet__('debug'),
                    children: <Debug/>,
                },
                {
                    key     : 'misc',
                    label   : pubjet__('advanced'),
                    children: <Misc/>,
                },
            ]}
        />
    </div>
};

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(Settings);