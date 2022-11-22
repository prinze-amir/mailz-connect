<?php
namespace MailzFrontend;
use MailWizzApi_Endpoint_ListSubscribers;
use MailWizzApi_Endpoint_Lists;
use MailzFrontend\MailzStats;
class NewSubscriber{

    private $setup;
    private $is_koopo;
    private $koopo_privateKey;
    private $koopo_publicKey;
    private $new_user_list;

    public function __construct() {

        $this->setup = new SetupApi();
        $this->is_koopo = 'tf756gjztl4fe';
        $this->koopo_privateKey = '4797897f796ff53882a483aff3b67ef61ece3d27';
        $this->koopo_publicKey = 'f68a0fab0b7dc911290b752d9b874c8e2ec18475';
        $this->new_user_list = 'fc178zazqc1ba';
        add_action( 'user_register', [$this,'add_new_user_to_mailz']);

        add_action( 'biz_fav_subscribe', [ $this, 'create_new_subscriber' ],10,2 );//runs everytime a user follows a business
        add_action( 'biz_fav_unsubscribe', [ $this, 'unsubscribe_user' ],10,2 );
        add_action( 'woocommerce_thankyou', [$this, 'create_new_subscriber_woo'], 10,1);
        add_action( 'dokan_follow_store_toggle_status', [$this,'create_new_subscriber_follow_Seller'], 10,3);

    }

    public function add_new_user_to_mailz($user_id){

        $mailzUser = $this->is_koopo;
        $public = $this->koopo_publicKey;
        $private = $this->koopo_privateKey;
        $list = $this->new_user_list;
        $user = get_userdata( $user_id );
        $email = $user->user_email;
        $fname = $user->first_name;
        $lname = $user->last_name;

        $api = new MailzStats($public, $private, $mailzUser);

        $endpoint = new MailWizzApi_Endpoint_ListSubscribers();

        $data = array(
            'EMAIL' => $email,
            'FNAME' => $fname,
            'LNAME' => $lname,
        );

        $response = $endpoint->create( $list,  $data);

        $response = $response->body->toArray();
        if (isset($response['status']) && $response['status'] == 'success') {

            $user_lists = get_user_meta($user_id, 'mailz_subscriber_info', true);
            if(!array($user_lists)){
                $user_lists = [];
            }
            $new = [
                'list' =>$list,
                'mailz_user' =>$mailzUser,
                'subscriber_id' => $response['data']['record']['subscriber_uid'],
                'ip' => $response['data']['record']['ip_address']

            ];
            $user_lists[]= $new;

            update_user_meta($user_id, 'mailz_subscriber_info', $user_lists);
            update_user_meta($user_id, 'mailz_subscribed', 'yes');

        }
    }

    public function businessOwner($b_id = false){
        if ( ! $b_id )
			global $post;
		else
			$business = get_post( $b_id );

		if ( null == $business || ! isset( $business ) ) {
			return 0;
		}
		return $business->post_author;
    }

    public function getSeller(){

        $store_user   = dokan()->vendor->get( get_query_var( 'author' ) );
        $author = $store_user->get_id();

        if (empty($author)){
            $seller =  get_the_author_meta('ID');
            $author = $seller;
        }
        return $author;
    }

    public function create_new_subscriber_follow_seller($vendor_id, $follower_id, $status){

       if($status !== 'following'){
            return $status;
       }

        $customer_id = $follower_id;

        $mailzUser = $vendor_id;
        $mailz_enabled = get_user_meta($mailzUser, 'mailz_connect', true);
        if ($mailz_enabled !== 'enabled'){
            return $status;
        }

        $public = get_user_meta( $mailzUser, 'mailz_public_api', true );
        $private = get_user_meta( $mailzUser, 'mailz_private_api', true );
        $mailzUserInfo = get_userdata($mailzUser);
        $mailzUserEmail = $mailzUserInfo->user_email;
        $newUserId = $customer_id;
        $newUser = get_userdata( $newUserId );
        $newEmail = $newUser->user_email;
        $newFname = get_user_meta($newUserId, 'first_name', true);
        $newLname = get_user_meta($newUserId, 'last_name', true);

        $endpoint = new MailWizzApi_Endpoint_ListSubscribers();

        //$this->setup->setApi($public, $private );
        $stats          = new MailzStats($public, $private, $mailzUser );

        $list_id    = get_user_meta($mailzUser, 'mailz_default_list', true);// you'll take this from your customers area, in list overview from the address bar.
        $theLists = new MailWizzApi_Endpoint_Lists();

        $checkList = $theLists->getList($list_id);
        $checkList = $checkList->body->toArray();
        $listGood = false;

        if (isset($checkList['status']) && $checkList['status'] == 'success') {
            $listGood = true;
        }

        if (empty($list_id) || !$listGood) {
            $lists    = $stats->get_lists();

            if (!empty($lists)) {

                $list_id = $lists['records'][0]['general']['list_uid'];

                update_user_meta($mailzUser, 'mailz_default_list', $list_id);
            //create form to update default lists

            } else {

                $store      = get_user_meta($vendor_id,'dokan_store_name', true);
                $state      = get_post_meta($bus_id, 'wyz_biz_state', true);
                $city       = get_post_meta($bus_id, 'wyz_business_city', true);
                $zip        = get_post_meta($bus_id, 'wyz_business_zipcode', true);
                $street     = get_post_meta($bus_id, 'wyz_business_street', true);
                $street2    = get_post_meta($bus_id, 'wyz_biz_street2', true);

                $new_list = $theLists->create([
                    'general' => array(
                        'name'          => 'Koopo Store Followers', // required
                        'description'   => 'This is a list of followers of your Koopo Marketplace Store', // required
                        'opt_in'        => 'single' //optional
                    ),
                    // required
                    'defaults' => array(
                        'from_name' => $mailzUserEmail, // required
                        'from_email'=> $mailzUserEmail, // required
                        'reply_to'  => $mailzUserEmail, // required
                        'subject'   => '',
                    ),
                    // optional
                    'notifications' => array(
                        // notification when new subscriber added
                        'subscribe'         => 'yes', // yes|no
                        // notification when subscriber unsubscribes
                        'unsubscribe'       => 'yes', // yes|no
                        // where to send the notifications.
                        'subscribe_to'      => $mailzUserEmail,
                        'unsubscribe_to'    => $mailzUserEmail,
                    ),
                    'company' => array(//this section only is necessary if company info is not already set
                        'name'      => $store,
                        'country_id'=> 223,// default is US
                        //'country' => $country,
                        'zone'      => $state, //this might not save
                        'city'      => $city,
                        'zip_code'  => $zip,
                        'address_1' => $street,
                        'address_2' => $street2,
                        'zone_name' => '', // when country doesn't have required zone.
                    ),

                ]);

                $responseList = $new_list->body->toArray();
                if (isset($responseList['status']) && $responseList['status'] == 'success' ) {

                    $new_list_id  = $responseList['list_uid'];
                    update_user_meta($user, 'mailz_default_list', $new_list_id);
                    $list_id = $new_list_id;
                }
            }

        }

        $data = array(
            'EMAIL' => $newEmail,
            'FNAME' => $newFname,
            'LNAME' => $newLname,
        );

        $response = $endpoint->create( $list_id,  $data);

        $response = $response->body->toArray();
        if (isset($response['status']) && $response['status'] == 'success') {

            $user_lists = get_user_meta($customer_id, 'mailz_subscriber_info', true);
            if(!array($user_lists)){
                $user_lists = [];
            }
            $new = [
                'list' =>$list_id,
                'mailz_user' =>$mailzUser,
                'subscriber_id' => $response['data']['record']['subscriber_uid'],
                'ip' => $response['data']['record']['ip_address']

            ];
            $user_lists[]= $new;

            update_user_meta($customer_id, 'mailz_subscriber_info', $user_lists);
            update_user_meta($customer_id, 'mailz_subscribed', 'yes');
        }
    }

    public function create_new_subscriber( $user_id, $bus_id ){

        $mailzUser      = $this->businessOwner($bus_id);
        $mailz_enabled = get_user_meta($mailzUser, 'mailz_connect', true);
        if ($mailz_enabled !== 'enabled'){
            return;
        }
        $public         = get_user_meta( $mailzUser, 'mailz_public_api', true );
        $private        = get_user_meta( $mailzUser, 'mailz_private_api', true );
        $mailzUserInfo  = get_userdata($mailzUser);
        $mailzUserEmail = $mailzUserInfo->user_email;
        $newUserId      = $user_id;
        $newUser        = get_userdata( $newUserId );
        $newEmail       = $newUser->user_email;
        $newFname       = get_user_meta($newUserId, 'first_name', true);
        $newLname       = get_user_meta($newUserId, 'last_name', true);

        $endpoint       = new MailWizzApi_Endpoint_ListSubscribers();

        //$this->setup->setApi($public, $private );
        $stats          = new MailzStats($public, $private, $mailzUser);

        $list_id        = get_user_meta($mailzUser, 'mailz_default_list', true);// you'll take this from your customers area, in list overview from the address bar.
        $theLists = new MailWizzApi_Endpoint_Lists();

        $checkList = $theLists->getList($list_id);
        $checkList = $checkList->body->toArray();
        $listGood = false;

        if (isset($checkList['status']) && $checkList['status'] == 'success') {
            $listGood = true;
        }

        if (empty($list_id) || !$listGood) {

            $lists    = $stats->get_lists();

            if (!empty($lists)) {

                $list_id = $lists['records'][0]['general']['list_uid'];

                update_user_meta($mailzUser, 'mailz_default_list', $list_id);
            //create form to update default lists
            } else {

                    $busObject = get_post($bus_id);
                    $business   = $busObject->post_title;
                    $state      = get_post_meta($bus_id, 'wyz_biz_state', true);
                    $city       = get_post_meta($bus_id, 'wyz_business_city', true);
                    $zip        = get_post_meta($bus_id, 'wyz_business_zipcode', true);
                    $street     = get_post_meta($bus_id, 'wyz_business_street', true);
                    $street2    = get_post_meta($bus_id, 'wyz_biz_street2', true);


                $new_list = $theLists->create([
                        'general' => array(
                        'name'          => 'Koopo Business Page', // required
                        'description'   => 'Koopo User who follow your business page', // required
                        'opt_in'        => 'single' //optional

                    ),
                    // required
                    'defaults' => array(
                        'from_name' => $mailzUserEmail, // required
                        'from_email'=> $mailzUserEmail, // required
                        'reply_to'  => $mailzUserEmail, // required
                        'subject'   => '',
                    ),
                    // optional
                    'notifications' => array(
                        // notification when new subscriber added
                        'subscribe'         => 'yes', // yes|no
                        // notification when subscriber unsubscribes
                        'unsubscribe'       => 'yes', // yes|no
                        // where to send the notifications.
                        'subscribe_to'      => $mailzUserEmail,
                        'unsubscribe_to'    => $mailzUserEmail,
                    ),
                    'company' => array(//this section only is necessary if company info is not already set
                        'name'      => $business,
                        'country_id'=> 223,// default is US
                        //'country' => $country,
                        'zone'      => $state, //this might not save
                        'city'      => $city,
                        'zip_code'  => $zip,
                        'address_1' => $street,
                        'address_2' => $street2,
                        'zone_name' => '', // when country doesn't have required zone.
                    ),

                ]);

                $responseList = $new_list->body->toArray();
                if (isset($responseList['status']) && $responseList['status'] == 'success' ) {

                    $new_list_id  = $responseList['list_uid'];
                    update_user_meta($user, 'mailz_default_list', $new_list_id);
                    $list_id = $new_list_id;
                }
            }

        }

        $data = array(
            'EMAIL' => $newEmail,
            'FNAME' => $newFname,
            'LNAME' => $newLname,
        );

        $response = $endpoint->create( $list_id,  $data);

        $response = $response->body->toArray();
        // if the returned status is success, we are done.
        if (isset($response['status']) && $response['status'] == 'success') {

            $user_lists = get_user_meta($user_id, 'mailz_subscriber_info', true);
            if(!is_array($user_lists)){
                $user_lists =[];
            }
            $new = [
                'list' => $list_id,
                'mailz_user' => $mailzUser,
                'subscriber_id' => $response['data']['record']['subscriber_uid'],
                'ip' => $response['data']['record']['ip_address'],
                'subscribed' =>'yes'
            ];
            $user_lists[] = $new;

            update_user_meta($user_id, 'mailz_subscriber_info', $user_lists);
            update_user_meta($user_id, 'mailz_subscribed', 'yes');

        } else {
            $to = 'aamarketsonline@gmail.com';
            $subject = 'MailZ Subscriber Error';
            $message = '
                <html>
                <head>
                <title>Error</title>
                </head>
                <body>
                <p>Something happended a subscriber was not created</p>
                <pre>'.$response['error'].'</pre>
                </body>
                </html>
                ';

                // Always set content-type when sending HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

                // More headers
                $headers .= 'From: Koopo <mailer@koopo.app>' . "\r\n";
                $headers .= 'Cc: aamarketsonline@gmail.com' . "\r\n";

        return wp_mail( $to, $subject, $message, $headers );

        }

    }

    public function create_new_subscriber_woo($order_id){

        $order = wc_get_order( $order_id );
        //get customer data
        $customer_id = $order->get_customer_id();
        $newUserId = $customer_id;
        $newUser = get_userdata( $newUserId );
        $newEmail = $newUser->user_email;
        $newFname = get_user_meta($newUserId, 'first_name', true);
        $newLname = get_user_meta($newUserId, 'last_name', true);
        //loop order items to get sellers
        $sellers = [];
        $items = $order->get_items();

        foreach($items as $item_id => $item){

            $product_id = $item->get_product_id();
            $product = get_post($item->get_product_id());
            $sellers[] =  $product->post_author;
        }

        foreach( $sellers as $seller ){
            $mailz_enabled = get_user_meta($seller, 'mailz_connect', true);

            if ($mailz_enabled == 'enabled'){

                //get seller info to setup api calls
                $public = get_user_meta( $seller, 'mailz_public_api', true );
                $private = get_user_meta( $seller, 'mailz_private_api', true );
                $list_id    = get_user_meta($seller, 'mailz_default_list', true);
                $mailzUserInfo = get_userdata($seller);
                $mailzUserEmail = $mailzUserInfo->user_email;

                $stats          = new MailzStats($public, $private, $seller);
                $theLists = new MailWizzApi_Endpoint_Lists();

                $checkList = $theLists->getList($list_id);
                $checkList = $checkList->body->toArray();
                $listGood = false;

                if (isset($checkList['status']) && $checkList['status'] == 'success') {
                    $listGood = true;
                }


                if (empty($list_id) || !$listGood) {

                    $lists = $stats->get_lists();//get lists if no default list is set

                    if (!empty($lists)) {

                        $list_id = $lists['records'][0]['general']['list_uid'];//get the first lists

                        update_user_meta($seller, 'mailz_default_list', $list_id);

                    } else {
                        //if there are no lists create a default list for seller
                        $store      = get_user_meta($seller,'dokan_store_name', true);
                        $state      = get_post_meta($bus_id, 'wyz_biz_state', true);
                        $city       = get_post_meta($bus_id, 'wyz_business_city', true);
                        $zip        = get_post_meta($bus_id, 'wyz_business_zipcode', true);
                        $street     = get_post_meta($bus_id, 'wyz_business_street', true);
                        $street2    = get_post_meta($bus_id, 'wyz_biz_street2', true);

                        $new_list = $theLists->create([
                            'general' => array(
                                'name'          => 'Koopo Customer List', // required
                                'description'   => 'Auto generated list for your Koopo customers', // required
                                'opt_in'        => 'single' //optional
                            ),
                            // required
                            'defaults' => array(
                                'from_name' => $mailzUserEmail, // required
                                'from_email'=> $mailzUserEmail, // required
                                'reply_to'  => $mailzUserEmail, // required
                                'subject'   => '',
                            ),
                            // optional
                            'notifications' => array(
                                // notification when new subscriber added
                                'subscribe'         => 'yes', // yes|no
                                // notification when subscriber unsubscribes
                                'unsubscribe'       => 'yes', // yes|no
                                // where to send the notifications.
                                'subscribe_to'      => $mailzUserEmail,
                                'unsubscribe_to'    => $mailzUserEmail,
                            ),
                            'company' => array(//this section only is necessary if company info is not already set
                                'name'      => $store,
                                'country_id'=> 223,// default is US
                                //'country' => $country,
                                'zone'      => $state, //this might not save
                                'city'      => $city,
                                'zip_code'  => $zip,
                                'address_1' => $street,
                                'address_2' => $street2,
                                'zone_name' => '', // when country doesn't have required zone.
                            ),
                        ]);
                        $responseList = $new_list->body->toArray();
                if (isset($responseList['status']) && $responseList['status'] == 'success' ) {

                    $new_list_id  = $responseList['list_uid'];
                    update_user_meta($user, 'mailz_default_list', $new_list_id);
                    $list_id = $new_list_id;
                }
                    }

                }
                //setup data for new subscriber
                $data = array(
                    'EMAIL' => $newEmail,
                    'FNAME' => $newFname,
                    'LNAME' => $newLname
                );
                $endpoint = new MailWizzApi_Endpoint_ListSubscribers();

                $response = $endpoint->create( $list_id,  $data);//this creates the new subscriber
                //this will return an array of the api requests response
                $response = $response->body->toArray();


                // if the returned status is success, we are done.
                if (isset($response['status']) && $response['status'] == 'success') {

                    $new_sub_arg = [
                        'list' => $list_id,
                        'subscriber_id' => $response['data']['record']['subscriber_uid'],
                        'mailzUser' => $seller,
                        'ip' => $response['data']['record']['ip_address']

                    ];

                    update_user_meta( $newUserId, 'mailz_woo_sub', $new_sub_arg );

                    $to = $mailzUserEmail;
                    $subject = 'New MailZ Subscriber';
                    $message = '
                        <html>
                        <head>
                        <title>New MailZ Subscriber</title>
                        </head>
                        <body>
                        <p>Congrats '.$mailzUserInfo->display_name.'!</p>
                        <p>You have a new subscriber to your MailZ Koopo Customer List.</p>
                        <table>
                        <tr>
                        <th>Email</th>
                        <th>Firstname</th>
                        <th>Lastname</th>
                        </tr>
                        <tr>
                        <td>'.$newEmail.'</td>
                        <td>'.$newFname.'</td>
                        <td>'.$newLname.'</td>
                        </tr>
                        </table>
                        </body>
                        </html>
                        ';

                        // Always set content-type when sending HTML email
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

                        // More headers
                        $headers .= 'From: Koopo <mailer@koopo.app>' . "\r\n";
                        $headers .= 'Cc: aamarketsonline@gmail.com' . "\r\n";

                     wp_mail( $to, $subject, $message, $headers );

                } else{

                $to = 'aamarketsonline@gmail.com';
                $subject = 'MailZ Subscriber Error';
                $message = '
                    <html>
                    <head>
                    <title>Error</title>
                    </head>
                    <body>
                    <p>Something happended a subscriber was not created</p>
                    <p>'.$response['error'].'</p>
                    </body>
                    </html>
                    ';

                    // Always set content-type when sending HTML email
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

                    // More headers
                    $headers .= 'From: Koopo <mailer@koopo.app>' . "\r\n";
                    $headers .= 'Cc: aamarketsonline@gmail.com' . "\r\n";

                 wp_mail( $to, $subject, $message, $headers );
                }
            }
        }

    }

    public function unsubscribe_user($user, $bus_id){

        $mailzUser      = $this->businessOwner($bus_id);
        $mailz_enabled = get_user_meta($mailzUser, 'mailz_connect', true);
        if ($mailz_enabled !== 'enabled'){
            return;
        }
        $subscribed  = get_user_meta($user, 'mailz_subscribed', true);
        if( $subscribed !== 'yes' ){
            return;
        }
        $public = get_user_meta( $mailzUser, 'mailz_public_api', true );
        $private = get_user_meta( $mailzUser, 'mailz_private_api', true );
        $api = new MailzStats($public, $private, $mailzUser);

        $subscriber_info = get_user_meta($user, 'mailz_subscriber_info', true);

        $thelist = get_user_meta($mailzUser, 'mailz_default_list', true);// you'll take this from your customers area, in list overview from the address bar.
        if(!empty($subscriber_info) && is_array($subscriber_info)){

            foreach( $subscriber_info as $r=>$list){

                if($list['list'] == $thelist){
                    $subscriber   = $list['subscriber_id'];
                }
            }

            $endpoint = new MailWizzApi_Endpoint_ListSubscribers();
            $unsubscribe = $endpoint->unsubscribe($thelist,$subscriber);
            $unsubscribe = $unsubscribe->body->toArray();
        }

                // if the returned status is success, we are done.
                if (isset($unsubscribe['status']) && $unsubscribe['status'] == 'success') {

                    return;

                } else{

                $to = 'aamarketsonline@gmail.com';
                $subject = 'MailZ UnSubscriber Error';
                $message = '
                    <html>
                    <head>
                    <title>Error</title>
                    </head>
                    <body>
                    <p>Something happended a subscriber was not created</p>
                    <p>'.$unsubscribe['error'].'</p>
                    </body>
                    </html>
                    ';

                    // Always set content-type when sending HTML email
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

                    // More headers
                    $headers .= 'From: Koopo <mailer@koopo.app>' . "\r\n";
                    $headers .= 'Cc: aamarketsonline@gmail.com' . "\r\n";

                 wp_mail( $to, $subject, $message, $headers );
                }


    }
}
