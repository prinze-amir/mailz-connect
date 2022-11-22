<?php
/*
 * @wordpress-plugin
 * Plugin Name:       Mailz Connect
 * Plugin URI:        www.mailz.koopo.app
 * Description:       Connect your account with Mailz Marketing Automation Service
 * Version:           1.0.0
 * Author:            Prinze Amir
 * Author URI:        www.koopoonline.com/we/prinze-amir
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       www.mailz.koopo.app
 * Domain Path:       /languages
 */
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MailzConnect {

    /**
     * Instance of self
     *
     * @var MailzConnect
     */
    private static $instance = null;

    private function __construct() {

        if (!class_exists('MailWizzApi_Autoloader', false)) {
            require plugin_dir_path(__FILE__) . '/vendor/autoload.php';
        }

        $this->init_plugin();
        $this->theDefined();//good

     //   register_activation_hook( __FILE__, [ $this, 'activate' ] );
     //   register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

    }

    public function theDefined(){

        define( 'MAILZ_PATH',  __DIR__ );
        define( 'MAILZ_URL', plugins_url( '', __FILE__) );

    }

    /**
     * Initializes the WeDevs_Dokan() class
     *
     * Checks for an existing WeDevs_WeDevs_Dokan() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {

        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

     /**
     * Load the plugin after WP User Frontend is loaded
     *
     * @return void
     */
    public function init_plugin() {

        //$this->includes();
        $this->init_hooks();

        do_action( 'mailz_loaded' );
    }


    /**
     * Initialize the actions
     *
     * @return void
     */
    public function init_hooks() {

        // Localize our plugin
        add_action( 'init', [ $this, 'mailz_load_textdomain' ] );

        // initialize the classes
        add_action( 'init', [ $this, 'init_classes' ], 4 );
        add_action( 'admin_enqueue_scripts', [ $this, 'mwznb_load_admin_assets' ]);
        add_action( 'wp_enqueue_scripts', [ $this, 'mwznb_load_frontend_assets' ]);
        //mailz calls
        add_action( 'wp_ajax_mwznb_fetch_lists', [ $this, 'mwznb_fetch_lists_callback' ] );
        // fetch list fields
        add_action( 'wp_ajax_mwznb_fetch_list_fields', [ $this, 'mwznb_fetch_list_fields_callback' ] );

        // subscribe a user in given list
        add_action('wp_ajax_mwznb_subscribe', [ $this, 'mwznb_subscribe_callback' ] );
        add_action('wp_ajax_nopriv_mwznb_subscribe', [ $this, 'mwznb_subscribe_callback' ] );
        add_action( 'widgets_init', [ $this, 'register_widgets' ] );
        //styles

    }

    public function register_widgets(){

        return register_widget( 'MailzWidgets\MailzBox' );
    }

    public function mwznb_fetch_lists_callback() {

        $apiUrl     = isset($_POST['api_url'])      ? sanitize_text_field($_POST['api_url'])        : null;
        $publicKey  = isset($_POST['public_key'])   ? sanitize_text_field($_POST['public_key'])     : null;
        $privateKey = isset($_POST['private_key'])  ? sanitize_text_field($_POST['private_key'])    : null;

        $errors = array();
        if (empty($apiUrl) || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            $errors['api_url'] = __('Please type a valid API url!', 'mwznb');
        }
        if (empty($publicKey) || strlen($publicKey) != 40) {
            $errors['public_key'] = __('Please type a public API key!', 'mwznb');
        }
        if (empty($privateKey) || strlen($privateKey) != 40) {
            $errors['private_key'] = __('Please type a private API key!', 'mwznb');
        }
        if (!empty($errors)) {
            exit(MailWizzApi_Json::encode(array(
                'result' => 'error',
                'errors' => $errors,
            )));
        }

        $oldSdkConfig = MailWizzApi_Base::getConfig();
        MailWizzApi_Base::setConfig($this->mwznb_build_sdk_config($apiUrl, $publicKey, $privateKey));

        $endpoint = new MailWizzApi_Endpoint_Lists();
        $response = $endpoint->getLists(1, 50);
        $response = $response->body->toArray();

        $this->mwznb_restore_sdk_config($oldSdkConfig);
        unset($oldSdkConfig);

        if (!isset($response['status']) || $response['status'] != 'success') {
            exit(MailWizzApi_Json::encode(array(
                'result' => 'error',
                'errors' => array(
                    'general'   => isset($response['error']) ? $response['error'] : __('Invalid request!', 'mwznb'),
                ),
            )));
        }

        if (empty($response['data']['records']) || count($response['data']['records']) == 0) {
            exit(MailWizzApi_Json::encode(array(
                'result' => 'error',
                'errors' => array(
                    'general'   => __('We couldn\'t find any mail list, are you sure you have created one?', 'mwznb'),
                ),
            )));
        }

        $lists = array(
            array(
                'list_uid'  => null,
                'name'      => __('Please select', 'mwznb')
            )
        );

        foreach ($response['data']['records'] as $list) {
            $lists[] = array(
                'list_uid'  => $list['general']['list_uid'],
                'name'      => $list['general']['name']
            );
        }

        exit(MailWizzApi_Json::encode(array(
            'result' => 'success',
            'lists' => $lists,
        )));
    }

    /**
     * Fetch list fields
     */
    public function mwznb_fetch_list_fields_callback() {

        $apiUrl     = isset($_POST['api_url'])      ? sanitize_text_field($_POST['api_url'])        : null;
        $publicKey  = isset($_POST['public_key'])   ? sanitize_text_field($_POST['public_key'])     : null;
        $privateKey = isset($_POST['private_key'])  ? sanitize_text_field($_POST['private_key'])    : null;
        $listUid    = isset($_POST['list_uid'])     ? sanitize_text_field($_POST['list_uid'])       : null;
        $fieldName  = isset($_POST['field_name'])   ? sanitize_text_field($_POST['field_name'])     : null;

        if (
            empty($apiUrl)      || !filter_var($apiUrl, FILTER_VALIDATE_URL) ||
            empty($publicKey)   || strlen($publicKey)   != 40 ||
            empty($privateKey)  || strlen($privateKey)  != 40 ||
            empty($listUid)     || empty($fieldName)
        ) {
            die();
        }

        $oldSdkConfig = MailWizzApi_Base::getConfig();
        MailWizzApi_Base::setConfig($this->mwznb_build_sdk_config($apiUrl, $publicKey, $privateKey));

        $endpoint = new MailWizzApi_Endpoint_ListFields();
        $response = $endpoint->getFields($listUid);
        $response = $response->body->toArray();

        $this->mwznb_restore_sdk_config($oldSdkConfig);
        unset($oldSdkConfig);

        if (!isset($response['status']) || $response['status'] != 'success' || empty($response['data']['records']) || count($response['data']['records']) == 0) {
            die();
        }
        $this->mwznb_generate_fields_table((array)$response['data']['records'], $fieldName, array());
        die();
    }

    /**
     * Subscribe a user in given list
     */
    public function mwznb_subscribe_callback() {
        if (!isset($_POST['mwznb_form_nonce']) || !wp_verify_nonce($_POST['mwznb_form_nonce'], 'mailz-box')) {
            exit(MailWizzApi_Json::encode(array(
                'result'    => 'error',
                'message'   => __('Invalid nonce!', 'mwznb')
            )));
        }

        $uid = isset($_POST['uid']) ? sanitize_text_field($_POST['uid']) : null;
        if ($uid) {
            unset($_POST['uid']);
        }
        unset($_POST['action'], $_POST['mwznb_form_nonce']);

        if (empty($uid) || !($uidData = get_option('mwznb_widget_instance_' . $uid))) {
            exit(MailWizzApi_Json::encode(array(
                'result'    => 'error',
                'message'   => __('Please try again later!', 'mwznb')
            )));
        }

        $keys = array('api_url', 'public_key', 'private_key', 'list_uid');
        foreach ($keys as $key) {
            if (!isset($uidData[$key])) {
                exit(MailWizzApi_Json::encode(array(
                    'result'    => 'error',
                    'message'   => __('Please try again later!', 'mwznb')
                )));
            }
        }

        $oldSdkConfig = MailWizzApi_Base::getConfig();
        MailWizzApi_Base::setConfig($this->mwznb_build_sdk_config($uidData['api_url'], $uidData['public_key'], $uidData['private_key']));

        $endpoint = new MailWizzApi_Endpoint_ListSubscribers();
        $response = $endpoint->create($uidData['list_uid'], $_POST);
        $response = $response->body->toArray();

        $this->mwznb_restore_sdk_config($oldSdkConfig);
        unset($oldSdkConfig);

        if (isset($response['status']) && $response['status'] == 'error' && isset($response['error'])) {
            $errorMessage = $response['error'];
            if (is_array($errorMessage)) {
                $errorMessage = implode("\n", array_values($errorMessage));
            }
            exit(MailWizzApi_Json::encode(array(
                'result'    => 'error',
                'message'   => $errorMessage
            )));
        }

        if (isset($response['status']) && $response['status'] == 'success') {
            exit(MailWizzApi_Json::encode(array(
                'result'    => 'success',
                'message'   => __('Please check your email to confirm the subscription!', 'mwznb')
            )));
        }

        exit(MailWizzApi_Json::encode(array(
            'result'    => 'success',
            'message'   => __('Unknown error!', 'mwznb')
        )));
    }

    /**
     * @param $apiUrl
     * @param $publicKey
     * @param $privateKey
     *
     * @return MailWizzApi_Config
     */
    public function mwznb_build_sdk_config($apiUrl, $publicKey, $privateKey) {
        return new MailWizzApi_Config(array(
            'apiUrl'        => $apiUrl,
            'publicKey'     => $publicKey,
            'privateKey'    => $privateKey,
        ));
    }


    /**
     * Restore the original config
     *
     * @param $oldConfig
     */
    public function mwznb_restore_sdk_config($oldConfig) {
        if (!empty($oldConfig) && $oldConfig instanceof MailWizzApi_Config) {
            MailWizzApi_Base::setConfig($oldConfig);
        }
    }

    /**
     * @param array $freshFields
     * @param $fieldName
     * @param array $listSelectedFields
     */
    public function mwznb_generate_fields_table(array $freshFields, $fieldName, array $listSelectedFields = array()) {
        ?>
        <table cellpadding="0" cellspacing="0">
            <thead>
                <th width="40" align="left"><?php echo  __('Show', 'mwznb');?></th>
                <th width="60" align="left"><?php echo  __('Required', 'mwznb');?></th>
                <th align="left"><?php echo  __('Label', 'mwznb');?></th>
            </thead>
            <tbody>
                <?php foreach ($freshFields as $field) { ?>
                <tr>
                    <td width="40" align="left"><input name="<?php echo $fieldName; ?>[]" value="<?php echo $field['tag']?>" type="checkbox"<?php echo empty($listSelectedFields) || in_array($field['tag'], $listSelectedFields) ? ' checked="checked"':''?>/></td>
                    <td width="60" align="left"><?php echo $field['required'];?></td>
                    <td align="left"><?php echo $field['label'];?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    }
    public function init_classes(){

        new \MailzFrontend\NewSubscriber();
        new \MailzFrontend\NewAccount();
        new \MailzFrontend\NewList();
        if( is_admin() ){

            new \MailzAdmin\Admin();

        }

    }

    // register admin assets
    public function mwznb_load_admin_assets() {

        wp_register_script('mwznb-admin', plugins_url('/Assets/js/admin.js', __FILE__), array('jquery'), '1.0', true);
        wp_enqueue_script('mwznb-admin');
    }

    // register frontend assets
    public function mwznb_load_frontend_assets() {
        wp_register_style('mwznb-front', plugins_url('/Assets/css/front.css', __FILE__), array(), '1.0');
        wp_register_script('mwznb-front', plugins_url('/Assets/js/front.js', __FILE__), array('jquery'), '1.0', true);

        wp_enqueue_style('mwznb-front');
        wp_enqueue_script('mwznb-front');
    }


    public function mailz_load_textdomain() {

        load_plugin_textdomain( 'mailz-connect', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

}

/**
 * Load Mailz Plugin when all plugins loaded
 *
 * @return MailzConnect
 */
function MailzConnect() {
    return MailzConnect::init();
}

MailzConnect();



