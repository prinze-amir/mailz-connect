<?php namespace MailzWidgets;

use WP_Widget;

use MailWizzApi_Base;
use MailWizzApi_Endpoint_Lists;
use MailWizzApi_Endpoint_ListFields;

class MailzBox extends WP_Widget {
/**
     * Register widget with WordPress.
     */
    public function __construct(){

        $opts = [
            "classname"=>"MailzBox",
            "description"=>"Mailz Newsletter Box",
        ];

        parent::__construct( 'MailzBox', 'Mailz Box', $opts );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

		$nonce      = wp_create_nonce('mailz-box');
		$nonceField = '<input type="hidden" name="mwznb_form_nonce" value="'.$nonce.'" />';
		$form       = $instance['generated_form'];
		$form       = str_replace('</form>', "\n" . $nonceField . "\n</form>", $form);
        ?>
        <div class="mwznb-widget" data-ajaxurl="<?php echo admin_url('admin-ajax.php'); ?>">
            <div class="message"></div>
            <?php echo $form;?>
        </div>
        <?php
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance)
    {
        $title              = isset($instance['title'])                 ? $instance['title']                : null;
        $apiUrl             = isset($instance['api_url'])               ? $instance['api_url']              : null;
        $publicKey          = isset($instance['public_key'])            ? $instance['public_key']           : null;
        $privateKey         = isset($instance['private_key'])           ? $instance['private_key']          : null;
        $listUid            = isset($instance['list_uid'])              ? $instance['list_uid']             : null;
        $listSelectedFields = isset($instance['selected_fields'])       ? $instance['selected_fields']      : array();
        $generatedForm      = isset($instance['generated_form'])        ? $instance['generated_form']       : '';

        $freshLists = array(
            array('list_uid' => null, 'name' => __('Please select', 'mwznb'))
        );
        $freshFields = array();

        if (!empty($apiUrl) && !empty($publicKey) && !empty($privateKey)) {

            $oldSdkConfig = MailWizzApi_Base::getConfig();
            MailWizzApi_Base::setConfig( MailzConnect()->mwznb_build_sdk_config( $apiUrl, $publicKey, $privateKey ) );

            $endpoint = new MailWizzApi_Endpoint_Lists();
            $response = $endpoint->getLists(1, 50);
            $response = $response->body->toArray();

            if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
                foreach ($response['data']['records'] as $list) {
                    $freshLists[] = array(
                        'list_uid'  => $list['general']['list_uid'],
                        'name'      => $list['general']['name']
                    );
                }
            }

            if (!empty($listUid)) {
                $endpoint = new MailWizzApi_Endpoint_ListFields();
                $response = $endpoint->getFields($listUid);
                $response = $response->body->toArray();

                if (isset($response['status']) && $response['status'] == 'success' && !empty($response['data']['records'])) {
                    foreach ($response['data']['records'] as $field) {
                        $freshFields[] = $field;
                    }
                }
            }

            MailzConnect()->mwznb_restore_sdk_config($oldSdkConfig);

            unset($oldSdkConfig);
        }

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><strong><?php _e('Title:'); ?></strong></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('api_url'); ?>"><strong><?php _e('Api url:'); ?></strong></label>
            <input class="widefat mwz-api-url" id="<?php echo $this->get_field_id('api_url'); ?>" name="<?php echo $this->get_field_name('api_url'); ?>" type="text" value="<?php echo esc_attr($apiUrl); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('public_key'); ?>"><strong><?php _e('Public api key:'); ?></strong></label>
            <input class="widefat mwz-public-key" id="<?php echo $this->get_field_id('public_key'); ?>" name="<?php echo $this->get_field_name('public_key'); ?>" type="text" value="<?php echo esc_attr($publicKey); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('private_key'); ?>"><strong><?php _e('Private api key:'); ?></strong></label>
            <input class="widefat mwz-private-key" id="<?php echo $this->get_field_id('private_key'); ?>" name="<?php echo $this->get_field_name('private_key'); ?>" type="text" value="<?php echo esc_attr($privateKey); ?>" />
        </p>

        <div class="widget-control-actions">
            <div class="alignleft"></div>
            <div class="alignright">
                 <input type="submit" class="button button-primary right mwz-fetch-available-lists" value="Fetch available lists">
               <span class="spinner mwz-spinner" style="display: none;"></span>
            </div>
            <br class="clear">
        </div>

        <div class="lists-container" style="<?php echo !empty($freshFields) ? 'display:block':'display:none';?>; margin:0; float:left; width:100%">
            <label for="<?php echo $this->get_field_id('list_uid'); ?>"><strong><?php _e('Select a list:'); ?></strong></label>
            <select data-listuid="<?php echo esc_attr($listUid); ?>" data-fieldname="<?php echo $this->get_field_name('selected_fields');?>" class="widefat mwz-mail-lists-dropdown" id="<?php echo $this->get_field_id('list_uid'); ?>" name="<?php echo $this->get_field_name('list_uid'); ?>">
            <?php foreach ($freshLists as $list) { ?>
            <option value="<?php echo $list['list_uid'];?>"<?php if ($listUid == $list['list_uid']) { echo ' selected="selected"';}?>><?php echo $list['name'];?></option>
            <?php } ?>
            </select>
            <br class="clear"/>
            <br class="clear"/>
        </div>

        <div class="fields-container" style="<?php echo !empty($listUid) ? 'display:block':'display:none';?>; margin:0; float:left; width:100%">
            <label for="<?php echo $this->get_field_id('selected_fields'); ?>"><strong><?php _e('Fields:'); ?></strong></label>
            <div class="table-container" style="width:100%;max-height:200px; overflow-y: scroll">
                <?php MailzConnect()->mwznb_generate_fields_table((array)$freshFields, $this->get_field_name('selected_fields'), $listSelectedFields);?>
            </div>
            <br class="clear">
            <div style="float: right;">
                Generate form again: <input name="<?php echo $this->get_field_name('generate_new_form'); ?>" value="1" type="checkbox" checked="checked"/>
            </div>
            <br class="clear">
        </div>

        <div class="generated-form-container" style="<?php echo !empty($listUid) ? 'display:block':'display:none';?>; margin:0; float:left; width:100%">
            <label for="<?php echo $this->get_field_id('generated_form'); ?>"><strong><?php _e('Generated form:'); ?></strong></label>
            <textarea name="<?php echo $this->get_field_name('generated_form'); ?>" id="<?php echo $this->get_field_id('generated_form'); ?>" style="width: 100%; height: 200px; resize:none; outline:none"><?php echo $generatedForm;?></textarea>
        </div>

        <hr />
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();

        $instance['title']          = !empty($new_instance['title'])        ? sanitize_text_field($new_instance['title'])       : '';
        $instance['api_url']        = !empty($new_instance['api_url'])      ? sanitize_text_field($new_instance['api_url'])     : '';
        $instance['public_key']     = !empty($new_instance['public_key'])   ? sanitize_text_field($new_instance['public_key'])  : '';
        $instance['private_key']    = !empty($new_instance['private_key'])  ? sanitize_text_field($new_instance['private_key']) : '';
        $instance['list_uid']       = !empty($new_instance['list_uid'])     ? sanitize_text_field($new_instance['list_uid'])    : '';
        $instance['uid']            = !isset($old_instance['uid'])          ? uniqid()                                          : $old_instance['uid'];

        $instance['selected_fields'] = !empty($new_instance['selected_fields']) && is_array($new_instance['selected_fields']) ? array_map('sanitize_text_field', $new_instance['selected_fields']) : array();

        update_option('mwznb_widget_instance_' . $instance['uid'], array(
            'api_url'       => $instance['api_url'],
            'public_key'    => $instance['public_key'],
            'private_key'   => $instance['private_key'],
            'list_uid'      => $instance['list_uid']
        ));

        if (!empty($new_instance['generate_new_form'])) {
            $instance['generated_form'] = $this->generateForm($instance);
        } else {
            $instance['generated_form'] = !empty($new_instance['generated_form']) ? $new_instance['generated_form'] : '';
        }

        return $instance;
    }

	/**
     * Helper method to generate the html form that will be pushed in the widgets area in frontend.
	 * It exists so that we don't have to generate the html at each page load.
     *
	 * @param array $instance
	 *
	 * @return string
	 */
    protected function generateForm(array $instance)
    {
        if (empty($instance['list_uid']) || empty($instance['public_key']) || empty($instance['private_key'])) {
            return '';
        }

        $oldSdkConfig = MailWizzApi_Base::getConfig();
        MailWizzApi_Base::setConfig( MailzConnect()->mwznb_build_sdk_config($instance['api_url'], $instance['public_key'], $instance['private_key']));

        $endpoint = new MailWizzApi_Endpoint_ListFields();
        $response = $endpoint->getFields($instance['list_uid']);
        $response = $response->body->toArray();

        MailzConnect()->mwznb_restore_sdk_config($oldSdkConfig);

        unset($oldSdkConfig);

        if (!isset($response['status']) || $response['status'] != 'success' || empty($response['data']['records'])) {
            return '';
        }

        $freshFields    = $response['data']['records'];
        $selectedFields = !empty($instance['selected_fields']) ? $instance['selected_fields'] : array();
        $rowTemplate    = '<div class="form-group"><label>[LABEL] [REQUIRED_SPAN]</label><input type="text" class="form-control" name="[TAG]" placeholder="[HELP_TEXT]" value="" [REQUIRED]/></div>';

        $output = array();
        foreach ($freshFields as $field) {
            $searchReplace = array(
                '[LABEL]'           => $field['label'],
                '[REQUIRED]'        => $field['required'] != 'yes' ? '' : 'required',
                '[REQUIRED_SPAN]'   => $field['required'] != 'yes' ? '' : '<span class="required">*</span>',
                '[TAG]'             => $field['tag'],
                '[HELP_TEXT]'       => $field['help_text'],

            );
            if (in_array($field['tag'], $selectedFields) || $field['required'] == 'yes') {
                $output[] = str_replace(array_keys($searchReplace), array_values($searchReplace), $rowTemplate);
            }
        }

        $out = '<form method="post" data-uid="'.$instance['uid'].'">' . "\n\n";
        $out .= implode("\n\n", $output);
        $out .= "\n\n";
        $out .= '<div class="clearfix"><!-- --></div><div class="actions pull-right"><button type="submit" class="btn btn-default btn-submit">Subscribe</button></div><div class="clearfix"><!-- --></div>';
        $out .= "\n\n" . '</form>';

        return $out;
    }

}
