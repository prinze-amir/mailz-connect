<?php namespace MailzFrontend;

use MailWizzApi_Endpoint_Campaigns;
use MailWizzApi_Endpoint_ListSubscribers;
use MailWizzApi_Endpoint_Countries;
use MailWizzApi_Base;
use MailWizzApi_Endpoint_Lists;
use MailzFrontend\SetupApi;

class MailzStats{

    private $public;
    private $private;
    private $MailzUser;

    public function __construct( $public, $private, $mailzUser ){

        $this->public   = $public;
        $this->private  = $private;
        $this->user     = $mailzUser;
        $setup          = new SetupApi();
        $setup->setApi($this->public, $this->private);
    }

    public function count_campaigns(){

        $cams = new MailWizzApi_Endpoint_Campaigns();
        $count = 0;

        $getCams = $cams->getCampaigns( $page = 1, $perPage = 10 );

        $response = $getCams->body->toArray();

        if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {

            $count = $response['data']['count'];

        }


        return $count;
    }

    public function campaign_stats(){

        $cams = new MailWizzApi_Endpoint_Campaigns();

        $getCams = $cams->getCampaigns( $page = 1, $perPage = 10 );

        $response = $getCams->body->toArray();
        /*if (empty($response)){
            return;
        }*/
       //the array of campaigns is inside the [records] array
       if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {

            $id =  $response['data']['records'][0]['campaign_uid'];//this gets the first campaign
            $getStats = $cams->getStats($id);

            return $getStats->body;
       }
        /*[data] => Array
                (
                    [campaign_status] => paused
                    [subscribers_count] => 6
                    [processed_count] => 0
                    [delivery_success_count] => 0
                    [delivery_success_rate] => 0
                    [delivery_error_count] => 0
                    [delivery_error_rate] => 0
                    [opens_count] => 0
                    [opens_rate] => 0
                    [unique_opens_count] => 0
                    [unique_opens_rate] => 0
                    [clicks_count] => 0
                    [clicks_rate] => 0
                    [unique_clicks_count] => 0
                    [unique_clicks_rate] => 0
                    [unsubscribes_count] => 0
                    [unsubscribes_rate] => 0
                    [complaints_count] => 0
                    [complaints_rate] => 0
                    [bounces_count] => 0
                    [bounces_rate] => 0
                    [hard_bounces_count] => 0
                    [hard_bounces_rate] => 0
                    [soft_bounces_count] => 0
                    [soft_bounces_rate] => 0
                    [internal_bounces_count] => 0
                    [internal_bounces_rate] => 0
                )*/


    }

    public function get_lists(){

        $lists = new MailWizzApi_Endpoint_Lists();
        $count= 0;

        $getLists = $lists->getLists($page = 1, $perPage = 10);
        $response = $getLists->body->toArray();

        if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
            $count = $response['data']['count'];
            return $response['data'];
        }
        return $count;

    }

    public function get_mailz_countries(){

        $endpoint = new MailWizzApi_Endpoint_Countries();
        $countries = $endpoint->getCountries($page = 1, $perPage = 239);

        $response = $countries->body->toArray();
        if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
            $count = $response['data']['count'];
            return $response['data'];
        }

    }

    public function get_mailz_zones($country){

        $endpoint = new MailWizzApi_Endpoint_Countries();
        $zones = $endpoint->getZones($country, $page = 1, $perPage = 10);

        $response = $zones->body->toArray();

        if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
            $count = $response['data']['count'];
            return $response['data'];
        }

    }
    public function count_subscribers(){

        $Subs = new MailWizzApi_Endpoint_ListSubscribers();
        $count = 0;
        $lists = $this->get_lists();
        if (empty($lists)){
            return $count;
        }

        $list_id = get_user_meta( $this->user, 'mailz_default_list', true );

         //  echo '<pre>'.$list_id .'</pre>';
        //echo '<pre>'.$this->user.'</pre>';

        if(empty($list_id)){
            $list_id = $lists['records'][0]['general']['list_uid'];
        }
        $getSubs = $Subs->getSubscribers($list_id, $page = 1, $perPage = 10);
        $response = $getSubs->body->toArray();

                //echo '<pre>'.print_r($response['data']['records']).'</pre>';//this is the array of subscribers with the key=>value structure. notible keys {subscriber_uid, EMAIL, FNAME, LNAME, status, source, ip_address, date_added}

        if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
            $count = $response['data']['count'];
        }

        return $count;
    }

}