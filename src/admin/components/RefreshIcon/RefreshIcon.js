import React from 'react';
import styles from './RefreshIcon.module.scss';
import {Tooltip} from "antd";
import {ReloadOutlined} from "@ant-design/icons";
import PropTypes from "prop-types";

const RefreshIcon = (props) => {
    const {onClick, tooltip} = props;
    return <Tooltip title={tooltip}>
        <ReloadOutlined style={{fontSize: '15px'}} className={styles.refresh} onClick={onClick}/>
    </Tooltip>
};

RefreshIcon.propTypes = {
    tooltip: PropTypes.string,
    onClick: PropTypes.func,
};

RefreshIcon.defaultProps = {
    tooltip: 'بارگذاری مجدد',
    onClick: () => {
    },
};

export default RefreshIcon;