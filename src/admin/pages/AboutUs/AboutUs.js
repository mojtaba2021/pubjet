import React from 'react';
import './AboutUs.scss';
import Card from '../../components/Card/Card';

const PageAboutUs = props => {
    return (
        <div className={'row'}>
            <div className={'col-12 column'}>
                <Card className={'card'}>
                    <div className={'text-center'}>
                        <img className={'image'} src={`${pubjet_params.images_url}/team.png`} width={'90px'}
                             height={'90px'}/>
                    </div>
                    <h2 className={'team text-center my-3'}>
                        {pubjet_params.i18n['page']['aboutus']['team']}
                    </h2>
                    <p className={'info'}>
                        {pubjet_params.i18n['page']['aboutus']['about']}
                    </p>
                    <div className={'socials'}>
                        <a href={`https://www.youtube.com/channel/UC-XdD46667eViOBZEWbqcSQ`} target={'_blank'}><img
                            src={`${pubjet_params.images_url}/icon-youtube.png`}/></a>
                        <a href={`https://www.t.me/soozeh`} target={'_blank'}><img
                            src={`${pubjet_params.images_url}/icon-telegram.png`}/></a>
                        <a href={`https://www.instagram.com/soozeh`} target={'_blank'}><img
                            src={`${pubjet_params.images_url}/icon-instagram.png`}/></a>
                        <a href={`mailto:soozeh@gmail.com`} target={'_blank'}><img
                            src={`${pubjet_params.images_url}/icon-email.png`}/></a>
                    </div>
                </Card>
            </div>
        </div>
    );
};

PageAboutUs.propTypes = {};

export default PageAboutUs;