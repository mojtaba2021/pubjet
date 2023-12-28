import React from 'react';
import ReactDOM from 'react-dom';
import {Provider} from 'trim-redux';
import store from './store/store';

const PageSettings = React.lazy(() => import('./pages/Settings/Settings'));

const renderElement = (element, containerId) => {
    const container = document.getElementById(containerId);
    if (!container || container.length === 0) {
        return;
    }
    ReactDOM.render(<Provider store={store}>
        <React.Suspense fallback={<div>منتظر بمانید ...</div>}>
            {element}
        </React.Suspense>
    </Provider>, container);
};

const elements = [
    {selector: 'pubjet-page-settings-content', element: <PageSettings/>},
];

elements.map(item => {
    renderElement(item.element, item.selector);
});