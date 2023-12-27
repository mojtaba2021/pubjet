import React from 'react';
import PropTypes from 'prop-types';
import {Button as AntButton} from 'antd';
import styles from './Button.module.scss';

const Button = props => {
    const {
        children,
        onClick,
        type,
        className,
        block,
        disabled,
        loading,
        size,
        icon,
        buttonProps
    } = props;
    return (
        <AntButton
            className={`${className} ${styles.button}`}
            type={type}
            disabled={disabled}
            onClick={onClick}
            loading={loading}
            block={block}
            size={size}
            icon={icon}
            {...buttonProps}
        >
            {loading ? pubjet_params.i18n['pwait'] : children}
        </AntButton>
    );
};

Button.propTypes = {
    type: PropTypes.string,
    size: PropTypes.string,
    block: PropTypes.bool,
    disabled: PropTypes.bool,
    onClick: PropTypes.func,
    large: PropTypes.bool,
    icon: PropTypes.oneOfType([PropTypes.object, PropTypes.element, PropTypes.bool]),
    loading: PropTypes.bool,
    className: PropTypes.string,
    style: PropTypes.oneOfType([PropTypes.object, PropTypes.bool,]),
    buttonProps: PropTypes.object,
};

Button.defaultProps = {
    type: 'primary',
    block: true,
    disabled: false,
    large: false,
    className: '',
    loading: false,
    size: 'large',
    icon: false,
    buttonProps: {},
};

export default Button;