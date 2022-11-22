<?php namespace MailzFrontend;
use MailWizzApi_Base;

class SetupApi {

    public function setApi( $publicKey, $privateKey ){

        $apiUrl = 'https://mailz.koopo.app/api';

        $oldSdk = MailWizzApi_Base::getConfig();

        MailWizzApi_Base::setConfig( MailzConnect()->mwznb_build_sdk_config( $apiUrl, $publicKey, $privateKey ) );

    }

}