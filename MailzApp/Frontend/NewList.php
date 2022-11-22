<?php namespace MailzFrontend;

use MailzFrontend\SetupApi;
use MailWizzApi_Endpoint_Lists;

class NewList{

    private $api;
    private $followers;
    private $custoemrs;
    public function __construct(){

        $this->api = new SetupApi();

        add_action( 'frm_after_create_entry', [$this,'get_new_list_params'], 30, 2);
        add_action( 'frm_after_update_entry', [$this, 'get_new_list_params'], 30, 2);

    }


    public function create_new_list($publicKey, $privateKey, $data, $user){

        $this->api->setApi($publicKey, $privateKey);

        $lists = new MailWizzApi_Endpoint_Lists();

        $new_list = $lists->create($data);
        $response = $new_list->body->toArray();
        if (isset($response['status']) && $response['status'] == 'success' ) {

            $new_list_id  = $response['list_uid'];
            update_user_meta($user, 'mailz_default_list', $new_list_id);
        }
      /* echo '<pre>';
        print_r($response);
        echo '</pre>';*/
       /* echo '<pre>';
        print_r($new_list->body->toArray());
        echo '</pre>';*/

    }

    public function get_new_list_params($entry, $form){

        if ($form === 54){

            $user = $_POST['item_meta'][755];
            $listID          = $_POST['item_meta'][811];
            $listName        = $_POST['item_meta'][813];
            $listDescription = $_POST['item_meta'][814];
            $listFromName    = $_POST['item_meta'][815];
            $listFromEmail   = $_POST['item_meta'][818];
            $listReplyTo     = $_POST['item_meta'][819];
            $listSubject     = $_POST['item_meta'][820];
            $listNotifyEmail = $_POST['item_meta'][821];
            $listCountry     = $_POST['item_meta'][822];

            $bus_ids    = get_user_meta($user,'wyz_user_businesses', true);
            if(!empty($bus_ids)){
            $bus_id     = array_values($bus_ids['published'])[0];
            }


            if(!empty($bus_id)){
                $busObject = get_post($bus_id);
                $business   = $busObject->post_title;
                $country    = get_post_meta($bus_id, 'wyz_biz_country', true);
                $state      = get_post_meta($bus_id, 'wyz_biz_state', true);
                $city       = get_post_meta($bus_id, 'wyz_business_city', true);
                $zip        = get_post_meta($bus_id, 'wyz_business_zipcode', true);
                $street     = get_post_meta($bus_id, 'wyz_business_street', true);
                $street2    = get_post_meta($bus_id, 'wyz_biz_street2', true);
            }

            $params =[
                'general' => array(
                    'name'          => $listName, // required
                    'description'   => $listDescription, // required
                ),
                // required
                'defaults' => array(
                    'from_name' => $listFromName, // required
                    'from_email'=> $listFromEmail, // required
                    'reply_to'  => $listReplyTo, // required
                    'subject'   => $listSubject,
                ),
                // optional
                'notifications' => array(
                    // notification when new subscriber added
                    'subscribe'         => 'yes', // yes|no
                    // notification when subscriber unsubscribes
                    'unsubscribe'       => 'yes', // yes|no
                    // where to send the notifications.
                    'subscribe_to'      => $listNotifyEmail,
                    'unsubscribe_to'    => $listNotifyEmail,
                ),
                // optional, if not set customer company data will be used
                'company' => array(
                    'name'      => $business,
                    'country_id'=> $listCountry,// see the countries endpoint for available countries and their zones
                    'country' => $country,
                    'zone'      => $state, // see the countries endpoint for available countries and their zones
                    'city'      => $city,
                    'zip_code'  => $zip,
                    'address_1' => !empty($street)?$street:'NA',
                    'address_2' => $street2,
                    'zone_name' => '', // when country doesn't have required zone.
                ),
            ];

            $public = get_user_meta( $user, 'mailz_public_api', true );
            $private = get_user_meta( $user, 'mailz_private_api', true );

            $this->create_new_list($public, $private, $params, $user);
        }
    }
}