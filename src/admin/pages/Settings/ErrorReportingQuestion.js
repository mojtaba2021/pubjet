import React, {useState} from 'react';
import {Alert, Button, Space} from "antd";
import {connect} from "trim-redux";
import {pubjet__} from "../../../shared/scripts/utils";
import {setErrorReporting} from "./Actions";
import styles from "./ErrorReportingQuestion.module.scss";

/**
 * Asks the administrator, until they answer, whether Pubjet error reports may be sent to Triboon.
 * Both answers have the same weight and nothing is preselected.
 *
 * @since 5.4.9
 */
const ErrorReportingQuestion = ({errorReporting = {}}) => {
    const [saving, setSaving] = useState(false);
    const forced = errorReporting.forced === true || errorReporting.forced === false;

    if (!errorReporting.ask || forced) {
        return null;
    }

    const choose = (choice) => {
        setSaving(true);
        setErrorReporting(choice).catch(() => {}).finally(() => setSaving(false));
    };

    return <div className={styles.wrapper}>
        <Alert
            type="info"
            showIcon={false}
            message={<strong>{pubjet__('error-reporting-question')}</strong>}
            description={<div>
                <div>{pubjet__('error-reporting-data-title')}</div>
                <ul className={styles.list}>
                    <li>{pubjet__('error-reporting-data-site')}</li>
                    <li>{pubjet__('error-reporting-data-versions')}</li>
                    <li>{pubjet__('error-reporting-data-error')}</li>
                </ul>
                <p>{pubjet__('error-reporting-change-later')}</p>
                <Space>
                    <Button size={'large'} disabled={saving} onClick={() => choose('yes')}>
                        {pubjet__('error-reporting-yes')}
                    </Button>
                    <Button size={'large'} disabled={saving} onClick={() => choose('no')}>
                        {pubjet__('error-reporting-no')}
                    </Button>
                </Space>
            </div>}
        />
    </div>;
};

const mstp = (state) => ({
    errorReporting: state.errorReporting,
});

export default connect(mstp)(ErrorReportingQuestion);
