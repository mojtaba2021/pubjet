import React from 'react';
import styles from './Header.module.scss';
import {getImagesUrl} from "../../../shared/scripts/utils";

const Header = props => {
    return <div className={styles.header}>
        <div className={styles.logo}>
            <img className={styles.image} src={`${getImagesUrl()}logo.png`}/>
            <a href={`https://triboon.net`} target={'_blank'}>
                <img className={styles.triboon} src={`${getImagesUrl()}triboon.png`}/>
            </a>
        </div>
    </div>;
};

export default Header;