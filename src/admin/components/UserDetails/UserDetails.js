import React from 'react';
import {pubjet__} from '../../../shared/scripts/utils';
import Mobile from '../Mobile/Mobile';
import DateTimeSpan from '../DateTimeSpan/DateTimeSpan';
import styles from './UserDetails.module.scss';
import ListItems from '../ListItems/ListItems';
import Online from '../Online/Online';

const UserDetails = props => {
  const items = [
    {value: props.id, label: pubjet__('user-id')},
    {value: props.user_login, label: pubjet__('user-login')},
    {value: props.display_name, label: pubjet__('display-name')},
    {value: <Mobile>{props.mobile}</Mobile>, label: pubjet__('mobile')},
    {value: <DateTimeSpan
          date={props.user_registered.date}
          time={props.user_registered.time}
      />, label: pubjet__('register-date'),},
    {value: <Online isOnline={props.is_online} />, label: pubjet__('status'),},
  ];
  return (
      <div className={styles.wrapper}>
        <ListItems items={items}/>
      </div>
  );
};

UserDetails.propTypes = {};

export default UserDetails;