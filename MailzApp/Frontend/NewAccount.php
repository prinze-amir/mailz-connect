<?php
namespace MailzFrontend;
use MailWizzApi_Base;
use MailWizzApi_Endpoint_Customers;
use MailWizzApi_Endpoint_Countries;
use MailzFrontend\Setup;
use MailzFrontend\MailzStats;
use FrmAppHelper;
class NewAccount {

    /**
     * Class constructor
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function __construct() {

        add_action( 'frm_after_update_entry', [ $this, 'create_new_customer' ], 10,2 );
        add_action( 'frm_after_create_entry', [ $this, 'create_new_customer' ], 10,2 );
        add_filter( 'frm_setup_new_fields_vars', [$this,'mailz_lists_options'], 10, 2);
        add_filter( 'frm_setup_edit_fields_vars', [$this, 'mailz_lists_options'],10,2);
    }
    /**
     * for Group id to work we must change app/common/models/customer.php line 144 from
     * "array('group_id, status', 'unsafe', 'on' => 'update-profile, register')"
     * to array('status', 'unsafe', 'on' => 'update-profile, register')
     *
     * @Mailwizz code Make Sure this is changed
     */
    public function create_new_customer( $entryID, $formID ){

        if( $formID == 54 ){

            $user       = $_POST['item_meta'][755];
            $fname      = $_POST['item_meta'][790];
            $lname      = $_POST['item_meta'][791];
            $phone      = $_POST['item_meta'][795];
            $email      = $_POST['item_meta'][794];
            $password   = $_POST['item_meta'][797];
            $account    = $_POST['item_meta'][796];
            $status     = $_POST['item_meta'][787];
            $public     = $_POST['item_meta'][751];
            $private    = $_POST['item_meta'][749];
            $list_id    = $_POST['item_meta'][811];

            if ($status === 'disabled'){
             return update_user_meta( $user, 'mailz_connect', $status );
            }

          //  if( !empty($list_id) ){
                update_user_meta($user, 'mailz_default_list', $list_id);// you'll take this from your customers area, in list overview from the address bar.
        //    }
            if ( !empty($public) && !empty($private) && $status == 'enabled'){
                update_user_meta( $user, 'mailz_connect', $status );
            }
            if (!empty($public)){
                update_user_meta($user,'mailz_public_api', $public);
            }
            if (!empty($private)){
                update_user_meta($user,'mailz_private_api', $private);
            }

            if ($account == 'New Account'):

                $business   = '';
                $country    = '';
                $state     = '';
                $city       = '';
                $zip        = '';
                $street     = '';
                $street2    = '';

                    $bus_ids    = get_user_meta($user,'wyz_user_businesses', true);
                if(!empty($bus_ids)){
                $bus_id     = array_values($bus_ids['published'])[0];
                }

                if(!empty($bus_id)){
                    $busObject = get_post($bus_id);
                    $business   = $busObject->post_title;
                    $country    = get_post_meta($bus_id, 'wyz_biz_country', true);
                    $state     = get_post_meta($bus_id, 'wyz_biz_state', true);
                    $city       = get_post_meta($bus_id, 'wyz_business_city', true);
                    $zip        = get_post_meta($bus_id, 'wyz_business_zipcode', true);
                    $street     = get_post_meta($bus_id, 'wyz_business_street', true);
                    $street2    = get_post_meta($bus_id, 'wyz_biz_street2', true);
                }

                if(empty($phone)){

                    $phone  = get_user_meta($user, 'billing_phone', true);

                }

                $avatar = get_avatar_url($user);
                $data = [];
                $data   = [
                    'customer' => [
                        'first_name' => $fname,
                        'last_name'  => $lname,
                        'email'      => $email,
                        'password'   => $password,
                        'timezone'   => 'UTC',
                        'group_id'   => 1,
                        'birthDate'  => 16,
                        'avatar'     => $avatar,
                        'phone'      => $phone
                    ],
                    'company'  => [
                        'name'     => $business,
                        'country'  => $country, // see the countries endpoint for available countries and their zones
                        'zone'     => $state, // see the countries endpoint for available countries and their zones
                        'city'     => $city,
                        'zip_code' => $zip,
                        'address_1'=> $street,
                        'address_2'=> $street2
                    ]
                ];

                $setup = new SetupApi();
                $setup->setApi($public, $private );

                $customer = new MailWizzApi_Endpoint_Customers();

                $newCustomer = $customer->create($data);

                $response = $newCustomer->body;

                if ($response->itemAt('status') == 'success') {
                    update_user_meta( $user, 'mailz_connect', $status );
                    update_user_meta( $user, 'mailz_id', $response->itemAt('customer_uid') );
                }

            endif;

        }

    }

    public function mailz_lists_options($values,$field) {

        if($field->id == 811 || $field->id == 822 /*|| $field->id == 823*/){
            $user = get_current_user_id();
            $public = get_user_meta($user,'mailz_public_api', true);
            $private = get_user_meta($user,'mailz_private_api', true);
            $endpoint = new MailzStats($public, $private, $user);

        }


        if($field->id == 811 ){//list dropdown field

            $lists = $endpoint->get_lists();
            unset($values['options']);
            $values['options'] = array(''); //remove this line if you are using a checkbox or radio button field
            $values['options'][''] = ''; //remove this line if you are using a checkbox or radio button field

            if (is_array($lists) || is_object($lists)):

                foreach($lists['records'] as $list){
                    $id     = $list['general']['list_uid'];
                    $name   = $list['general']['name'];
                    if ( FrmAppHelper::is_admin() ){

                    } else{
                    $values['options'][$id] = $name;
                    }
                }
                $values['use_key'] = true; //this will set the field to save the post ID instead of post title
                unset($values['options'][0]);

            endif;

          }
        if($field->id == 822){//country dropdown field

            $countries = $endpoint->get_mailz_countries();
            unset($values['options']);
            $values['options'] = array(''); //remove this line if you are using a checkbox or radio button field
            $values['options'][''] = ''; //remove this line if you are using a checkbox or radio button field
            if (is_array($countries) || is_object($countries)):

                foreach($countries['records'] as $country){
                    $id     = $country['country_id'];
                    $name   = $country['name'];

                    if ( FrmAppHelper::is_admin() ){

                    } else{
                    $values['options'][$id] = $name;
                    }
                }
                $values['use_key'] = true; //this will set the field to save the post ID instead of post title
                unset($values['options'][0]);
            endif;

          }
        /*if($field->id == 823){//city dropdown field
        //this is for states
            $country = 223;
            $zones = $endpoint->get_mailz_zones($country);
            unset($values['options']);
            $values['options'] = array(''); //remove this line if you are using a checkbox or radio button field
            $values['options'][''] = ''; //remove this line if you are using a checkbox or radio button field
            if (is_array($zones) || is_object($zones)):

                foreach($zones['records'] as $zone){
                    $id     = $zone['zone_id'];
                    $name   = $zone['name'];
                $values['options'][$id] = $name;
                }
                $values['use_key'] = true; //this will set the field to save the post ID instead of post title
                unset($values['options'][0]);
            endif;

          }*/
          return $values;
    }


}