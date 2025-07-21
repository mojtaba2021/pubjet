import React, {useEffect} from 'react';
import {Select, Spin} from 'antd';
import debounce from 'lodash/debounce';
import {useMemo, useRef, useState} from "react";
import styles from './DebounceSelect.module.scss';

function DebounceSelect({fetchOptions, debounceTimeout = 800, externalOptions = null, ...props}) {
    const [fetching, setFetching] = useState(false);
    const [options, setOptions] = useState([]);
    const [lastKeyword, setLastKeyword] = useState('');
    const fetchRef = useRef(0);

    const debounceFetcher = useMemo(() => {
        const loadOptions = (value) => {
            fetchRef.current += 1;
            const fetchId = fetchRef.current;
            setOptions([]);
            setFetching(true);
            setLastKeyword(value);

            fetchOptions(value).then((newOptions) => {
                if (fetchId !== fetchRef.current) {
                    // for fetch callback order
                    return;
                }
                setOptions(newOptions);
                setFetching(false);
            });
        };
        return debounce(loadOptions, debounceTimeout);
    }, [fetchOptions, debounceTimeout]);

    useEffect(() => {
        if (externalOptions && Array.isArray(externalOptions)) {
            setOptions(externalOptions);
        }
    }, [externalOptions]);

    useEffect(() => {
        fetchOptions('').then(options => {
            setOptions(options);
            setLastKeyword('');
        });
    }, [fetchOptions]);

    const handleSearch = (value) => {
        if (!value) {
            setLastKeyword('');
            fetchOptions('').then(options => setOptions(options));
        } else {
            debounceFetcher(value);
        }
    };

    const handleChange = (value) => {
        if (props.onChange) {
            const processedValue = value === undefined ||
            value === null ||
            (Array.isArray(value) && value.length === 0)
                ? (props.mode === 'multiple' ? [] : null)
                : value;

            requestAnimationFrame(() => props.onChange(processedValue));
        }
    };

    const displayValue = useMemo(() => {
        const currentValue = props.value;
        if (currentValue === null || currentValue === undefined) {
            return undefined;
        }
        if (Array.isArray(currentValue) && currentValue.length === 0) {
            return undefined;
        }
        return currentValue;
    }, [props.value]);

    return (
        <Select
            {...props}
            value={displayValue}
            loading={fetching}
            filterOption={false}
            onSearch={handleSearch}
            onChange={handleChange}
            notFoundContent={fetching ? <Spin size="default" className={styles.spin} /> : null}
            options={options}
        />
    );
}

export default DebounceSelect;