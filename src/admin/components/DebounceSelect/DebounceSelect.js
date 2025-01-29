import React, {useEffect} from 'react';
import {Select, Spin} from 'antd';
import debounce from 'lodash/debounce';
import {useMemo, useRef, useState} from "react";
import styles from './DebounceSelect.module.scss';

function DebounceSelect({fetchOptions, debounceTimeout = 800, ...props}) {
    const [fetching, setFetching] = useState(false);
    const [options, setOptions] = useState([]);
    const fetchRef = useRef(0);
    const debounceFetcher = useMemo(() => {
        const loadOptions = (value) => {
            fetchRef.current += 1;
            const fetchId = fetchRef.current;
            setOptions([]);
            setFetching(true);
            fetchOptions(value).then((newOptions) => {
                console.log(newOptions);
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
        fetchOptions('').then(options => setOptions(options));
    }, [fetchOptions]);

    const handleSearch = (value) => {
        if (!value) {
            fetchOptions('').then(options => setOptions(options));
        } else {
            debounceFetcher(value);
        }
    };

    const handleChange = (value) => {
        fetchOptions('').then(options => setOptions(options));
    };


    return (
        <Select
            loading={fetching}
            filterOption={false}
            onSearch={handleSearch}
            onChange={handleChange}
            notFoundContent={fetching ? <Spin size="default" className={styles.spin} /> : null}
            {...props}
            options={options}
        />
    );
}

export default DebounceSelect;