<?php namespace MailzAdmin;

Class Admin {

    /**
     * Class constructor
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
    }

    public function add_admin_menu(){
        $cap = 'manage_options';

        add_menu_page( 'Mailz Connect', 'Mailz Dashboard', $cap, 'mailz-connect/dashboard.php', [$this, 'get_mailz_dashboard'], 'dashicons-email', 25 );
        add_submenu_page( 'mailz-connect/dashboard.php', 'Mailz Connections', 'Mailz Connections', $cap, 'mailz-connect/connections.php', [$this,'get_mailz_connections'], 1 );

        add_submenu_page( 'mailz-connect/dashboard.php', 'Mailz Subscribers', 'Subscribers', $cap, 'mailz-connect/subscribers.php', [$this,'get_mailz_subscribers'], 1 );

    }

    public function get_mailz_dashboard(){
        //return views
        require_once 'templates/dashboard.php';
    }

    public function get_mailz_connections(){
        //return views
        require_once 'templates/connections.php';
    }


    public function get_mailz_subscribers(){
        //return views
        require_once 'templates/subscribers.php';
    }
}
