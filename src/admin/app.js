import React from 'react';
import ReactDOM from 'react-dom';
import {Provider} from 'trim-redux';
import store from './store/store';
import {pubjet__} from "../shared/scripts/utils";

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