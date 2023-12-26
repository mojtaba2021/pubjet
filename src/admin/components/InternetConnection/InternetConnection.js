import React, {Component} from 'react';
import Alert from 'antd/lib/alert';
import {WifiOutlined} from '@ant-design/icons';
import {getAdminAjaxUrl} from '../../../shared/scripts/utils';
import styles from './InternetConnection.module.scss';
import {translate} from '../../../shared/scripts/utils';

class InternetConnection extends Component {

  state = {
    online : true,
    error  : false,
    loading: false,
  };

  /**
   * @since 1.0.0
   */
  componentDidMount() {
    this.handleCheck();
    this.timer = setInterval(this.handleCheck, 10000);
  }

  /**
   * @since 1.0.0
   */
  componentWillUnmount() {
    clearInterval(this.timer);
  }

  /**
   * @since 1.0.0
   */
  handleCheck = () => {
    jQuery.ajax({
      url       : getAdminAjaxUrl(),
      type      : 'POST',
      data      : {
        action: 'pubjet-heartbeat',
      },
      timeout   : 8000,
      beforeSend: () => {
        this.setState({loading: true});
      },
      success   : () => {
        this.setState({online: true});
      },
      error     : () => {
        this.setState({online: false});
      },
      complete  : () => {
        this.setState({loading: false});
      },
    });
  };

  /**
   * @since 1.0.0
   */
  getContent = () => {
    const {online, loading} = this.state;
    if (online) {
      return null;
    }
    return (
        <Alert
            showIcon={true}
            closable={true}
            type={'error'}
            banner={true}
            className={styles.alert}
            description={translate('internet-disconnect')}
            icon={<WifiOutlined/>}
        />
    );
  };

  /**
   * @since 1.0.0
   * @returns {JSX.Element}
   */
  render() {
    return this.getContent();
  }
}

InternetConnection.propTypes = {};

export default InternetConnection;