<div class="wrapper">
<h1>Connections</h1>
<h3>Here you can view all the users who have enabled Mailz Connection.</h3>
</div>
<?php
//query users
$connected_users = get_users(
    [
        'role__in'      =>['business_owner', 'seller', 'influencer', 'administrator'],
        'meta_key'      => 'mailz_connect',
        'meta_value'    => 'enabled',
    ]
);

    ?>
    <table class="table wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Public Key</th>
                <th>Private Key</th>
            </tr>
        </thead>
        <tbody>
    <?php
foreach( $connected_users as $user ){

    $user_id    = $user->ID;
    $name       = $user->display_name;
    $email      = $user->user_email;
    $username   = $user->user_login;

    $publicKey = get_user_meta( $user_id,'mailz_public_api', true );
    $privateKey  = get_user_meta( $user_id,'mailz_private_api', true );
    $id         = get_user_meta($user_id, 'mailz_id', true );

    ?>


        <tr>
        <td><?php echo $id; ?></td>
        <td><a href="<?php echo get_edit_profile_url( $user->ID )?>"><?php echo $name; ?></a></td>
        <td><?php echo $email; ?></td>
        <td><?php echo $publicKey; ?></td>
        <td><?php echo $privateKey; ?></td>
        </tr>
    <?php  }
    echo '</tbody></table>';
?>
