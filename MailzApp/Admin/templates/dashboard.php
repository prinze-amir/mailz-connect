<div class="wrapper">
<h1>Mailz Dashboard</h1>
<h3>See the overview of the Mailz Connections and Analytics</h3>
</div>

<?php

$subscribed_users = get_users(
    [
        'meta_key'      => 'mailz_subscribed',
        'meta_value'    => 'yes',//check if this is empty should contain array of response
    ]
);

$connected_users = get_users(
    [
        'role__in'      =>['business_owner', 'seller', 'influencer', 'administrator'],
        'meta_key'      => 'mailz_connect',
        'meta_value'    => 'enabled',
    ]
);

$subCount = count($subscribed_users);
$conCount = count($connected_users);
?>

<h2>Subscribers: <?php echo $subCount; ?></h2>
<h2>Connections: <?php echo $conCount; ?></h2>
