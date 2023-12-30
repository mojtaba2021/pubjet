import {applyMiddleware, compose} from 'redux';
import {createStore} from 'trim-redux';
import thunk from 'redux-thunk';

const composeEnhancers =
          (process.env.NODE_ENV !== 'production' &&
              typeof window !== 'undefined' &&
              window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__) ||
          compose;

export const state = {
    options: {
        token     : '',
        debug     : false,
        category  : '',
        categories: [],
        modal     : false,
        uninstall : false,
        nofollow  : false,
    },
    modal  : false,
};

export default createStore(state, composeEnhancers(applyMiddleware(thunk)));