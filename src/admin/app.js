import React from 'react';
import ReactDOM from 'react-dom';
import {getStore, Provider, setStore} from 'trim-redux';
import store from './store/store';
import {pubjet__} from "../shared/scripts/utils";
import {getStoreKey} from "./pages/Settings/Actions";

const PageSettings = React.lazy(() => import('./pages/Settings/Settings'));
const ReportagePanelData = React.lazy(() => import('./components/ReportageData/ReportageData'));
const ReportageOptions = React.lazy(() => import('./components/ReportageOptions/ReportageOptions'));

const renderElement = (element, containerId) => {
    const container = document.getElementById(containerId);
    if (!container || container.length === 0) {
        return;
    }
    ReactDOM.render(<Provider store={store}>
        <React.Suspense fallback={<div>{pubjet__('pwait')}</div>}>
            {element}
        </React.Suspense>
    </Provider>, container);
};

const elements = [
    {selector: 'pubjet-page-settings-content', element: <PageSettings/>},
    {selector: 'pubjet-reportage-panel-data', element: <ReportagePanelData/>},
    {selector: 'pubjet-reportage-post-options', element: <ReportageOptions/>},
];

elements.map(item => {
    renderElement(item.element, item.selector);
});


/**
 * @since 1.0.0
 */
const initOptions = () => {
    const {options = {}} = pubjet_params;
    if (Object.keys(options).length === 0) {
        return;
    }
    const {token = '', debug = false, category, categories = [], uninstall = false} = options;
    setStore(getStoreKey(), {
        ...options,
    });
    if (category) {
        const found = categories.find(item => item.value == category);
        if (found) {
            setStore(getStoreKey(), {
                ...getStore(getStoreKey()),
                category: found,
            });
        }
    }
};
initOptions();