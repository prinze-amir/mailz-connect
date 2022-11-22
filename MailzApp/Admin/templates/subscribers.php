<div class="wrapper">
<h1>Mailz Subscribers</h1>
<h3>See the overview of the Mailz Connections and Analytics</h3>
</div>

    <table class="table wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Mailz User</th>
                <th>List ID</th>
                <th>IP Address</th>

            </tr>
        </thead>
        <tbody>
<?php
//write this to only show users subscribed to koopo lists and/or allow list to be chosen and refresh via ajax with subscribers.  dropdown of connected lists and check agains usermeta 'mailz_subscriber' for list id
$subscribed_users = get_users(
    [
        'meta_key'      => 'mailz_subscribed',
        'meta_value'    => 'yes',//check if this is empty should contain array of response
    ]
);

foreach( $subscribed_users as $user ){

    $user_id    = $user->ID;
    $name       = $user->display_name;
    $email      = $user->user_email;
    $username   = $user->user_login;

    $data = get_user_meta($user->ID, 'mailz_subscriber_info', true );

    foreach($data as $key =>$value){

        $lists      = $value['list'];
        $mailzUser  = $value['mailz_user'];
        $id         = $value['subscriber_id'];
        $ip         = $value['ip'];

    } ?>

    <tr>
        <td><a href="<?php echo get_edit_user_link( $user_id )?>"><?php echo $name; ?> / <?php echo $username; ?></a></td>
        <td><?php echo $email; ?></td>
        <td><a href="<?php echo get_edit_user_link( $mailzUser )?>"><?php echo $mailzUser; ?></a></td>
        <td><?php echo $id; ?></td>
        <td><?php echo $ip; ?></td>
        </tr>
    <?php  }
    echo '</tbody></table>';