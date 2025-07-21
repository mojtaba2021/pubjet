import React, { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import PropTypes from 'prop-types';
import styles from './SelectTerms.module.scss';
import { findEndpointUrl, getAdminAjaxUrl, getAxios } from "../../../shared/scripts/utils";
import DebounceSelect from "../DebounceSelect/DebounceSelect";

const axios = getAxios();

const SelectTerms = props => {
    const { selectProps, taxonomy, placeholder, value, onChange, className, mode, maxCount, suffixIcon, required } = props;
    const [error, setError] = useState(false);
    const [cachedOptions, setCachedOptions] = useState([]);

    const fetchPosts = useCallback((keyword = '') => {
        return axios.get(getAdminAjaxUrl(), {
            params: {
                action: 'pubjet-find-terms',
                search: keyword,
                taxonomy: taxonomy,
            },
        }).then(response => {
            const rawOptions = response.payload;
            setCachedOptions(rawOptions);

            return applyDisableLogic(rawOptions, value, maxCount);
        });
    }, [taxonomy, value, maxCount]);

    const applyDisableLogic = useCallback((options, currentValue, currentMaxCount) => {
        return options.map(option => {
            const isSelected = currentValue && Array.isArray(currentValue) && currentValue.includes(option.value);
            const isMaxReached = currentValue && Array.isArray(currentValue) && currentValue.length >= currentMaxCount;
            return {
                ...option,
                disabled: !isSelected && isMaxReached,
            };
        });
    }, []);

    const processedOptions = useMemo(() => {
        if (cachedOptions.length > 0) {
            return applyDisableLogic(cachedOptions, value, maxCount);
        }
        return [];
    }, [cachedOptions, value, maxCount, applyDisableLogic]);

    const handleChange = useCallback((selected) => {
        const isEmpty = !selected ||
            (Array.isArray(selected) && selected.length === 0) ||
            selected === '' ||
            selected === null ||
            selected === undefined;

        if (isEmpty) {
            onChange(Array.isArray(value) ? [] : null);
        } else {
            onChange(selected);
        }

        if (required && isEmpty) {
            setError(true);
        } else {
            setError(false);
        }
    }, [onChange, required, value]);

    const displayValue = useMemo(() => {
        if (!value) return undefined;
        if (Array.isArray(value) && value.length === 0) return undefined;
        return value;
    }, [value]);

    return (
        <DebounceSelect
            mode={mode}
            showSearch={true}
            value={displayValue}
            placeholder={placeholder}
            onChange={handleChange}
            className={`${className} ${styles.select}`}
            fetchOptions={fetchPosts}
            externalOptions={processedOptions.length > 0 ? processedOptions : null}
            allowClear={selectProps.allowClear || false}
            suffixIcon={suffixIcon}
            status={error ? 'error' : undefined}
            {...selectProps}
        />
    );
};

SelectTerms.propTypes = {
    value: PropTypes.oneOfType([PropTypes.array, PropTypes.string, PropTypes.number]),
    onChange: PropTypes.func,
    selectProps: PropTypes.object,
    placeholder: PropTypes.string,
    className: PropTypes.string,
    taxonomy: PropTypes.oneOfType([PropTypes.string, PropTypes.array]),
    mode: PropTypes.string,
    maxCount: PropTypes.number,
    suffixIcon: PropTypes.node,
    required: PropTypes.bool,
};

SelectTerms.defaultProps = {
    value: null,
    placeholder: '',
    className: '',
    selectProps: {},
    taxonomy: '',
    onChange: () => {},
    mode: 'single',
    maxCount: 10,
    required: false,
};

export default SelectTerms;