import React, {Component} from 'react';
import styles from './ReportageOptions.module.scss';
import {Form, Spin, Switch} from "antd";
import {getAdminAjaxUrl, getAxios, getSecurityNonce, pubjet__, showErrorMessage} from "../../../shared/scripts/utils";

const axios = getAxios();

class ReportageOptions extends Component {

    state = {
        nofollow: false,
        error   : false,
        loading : false,
    };

    /**
     * @since 1.0.0
     */
    componentDidMount() {
        this.fetch();
    }

    /**
     * @since 1.0.0
     */
    getPostId = () => {
        return jQuery('#pubjet-reportage-post-options').data('postid');
    };

    /**
     * @since 1.0.0
     */
    fetch = () => {
        this.setState({error: false, loading: true,}, () => {
            axios.get(getAdminAjaxUrl(), {
                params: {
                    action  : 'pubjet-find-reportage-options',
                    postId  : this.getPostId(),
                    security: getSecurityNonce(),
                },
            }).then(response => {
                if (response.success) {
                    this.setState({
                        ...response.payload,
                    });
                } else {
                    this.setState({error: false,});
                }
            }).catch(err => {
                this.setState({error: true,});
            }).finally(() => {
                this.setState({loading: false,});
            });
        });
    };

    /**
     * @since 1.0.0
     */
    save = () => {
        axios.post(getAdminAjaxUrl(), {
            action  : 'pubjet-save-reportage-options',
            postId  : this.getPostId(),
            nofollow: this.state.nofollow,
            security: getSecurityNonce(),
        }).then(response => {
            if (!response.success) {
                showErrorMessage(response.error);
            }
        }).catch(err => {
            showErrorMessage(pubjet__('error-occured'));
        });
    };

    /**
     * @since 1.0.0
     */
    content = () => {
        const {nofollow} = this.state;
        return <Form layout={'horizontal'} colon={false}>
            <Form.Item label={pubjet__('enable-nofollow')}>
                <Switch checked={nofollow} onChange={(checked) => {
                    this.setState({nofollow: checked,}, this.save);
                }} size={'default'}/>
            </Form.Item>
        </Form>
    };

    /**
     * @since 1.0.0
     * @returns {Element}
     */
    render() {
        return (
            <div className={styles.wrapper}>
                <Spin spinning={this.state.loading}>
                    {this.content()}
                </Spin>
            </div>
        );
    }
}

export default ReportageOptions;