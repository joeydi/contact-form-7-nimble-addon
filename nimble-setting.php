<?php

/*
Plugin Name:  Contact Form 7 - Nimble Addon
Plugin URI: http://okaypl.us/
Description: This plugin integrates Contact Form 7 with Nimble CRM to automatically import leads on form submission.
Author: Okay Plus
Author URI: http://okaypl.us/
Version: 0.6
License: GPL2 or Later

Copyright 2013 Viktorix Innovative (email: support@viktorixinnovative.com)
Copyright 2015 Okay Plus (email: joeydi@okaypl.us)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function cf7_plugin_check() {
    $plugin_name = 'contact-form-7/wp-contact-form-7.php';

    if ( !is_plugin_active($plugin_name) ) {
        echo '<div class="wrap"><div class="error"><p>Contact Form 7 plugin is required to configure this Nimble plugin. Please install and activate Contact Form 7 plugin and then return to this page.</p></div></div>';
        exit;
    }
}

function nimble_add_pages() {
    add_menu_page('Nimble', 'Nimble', 'edit_pages', 'nimble', 'nimble_main_admin', plugins_url('/images/nimble.png', __FILE__));
    add_submenu_page( 'nimble', 'Mapping Fields', 'Mapping Fields', 'edit_pages', 'mapping-fields', 'nimble_mapping_fields');
}
add_action('admin_menu', 'nimble_add_pages');

function nimble_mapping_fields() {
    include('nimble_map_fields.php');
}

function nimble_main_admin() {
    if(!isset($_GET['action'])) {
       include('nimble_admin_setting.php');
    }
}

function contact7_nimble( $cfdata ) {
    if ( $submission = WPCF7_Submission::get_instance() ) {
        $formdata = $submission->get_posted_data();
    }

    // Apply filters to the form data
    if ( has_filter( 'nimble_formdata' ) ) {
        $formdata = apply_filters( 'nimble_formdata', $cfdata->title, $formdata );
    }

    // Allow filters to cancel nimble_add_contact by returning null
    if ( !$formdata ) {
        return false;
    }

    require_once('api_nimble.php');
    $nimble = new NimbleAPI();

    try {
        $N_name         = get_option('N_name');
        $N_Fname        = get_option('N_Fname');
        $N_Lname        = stripslashes(get_option('N_Lname'));
        $N_email        = get_option('N_email');
        $N_phone_work   = get_option('N_phone_work');
        $N_phone_mobile = get_option('N_phone_mobile');
        $N_title        = get_option('N_title');
        $N_company      = get_option('N_company');

        if ( 'on' == get_option('CHKN_name' ) ) {
            $name = explode(' ', $formdata[$N_name], 2);
            $first_name = isset( $name[0] ) ? $name[0] : '';
            $last_name  = isset( $name[1] ) ? $name[1] : '';
        } else {
            $first_name = isset( $formdata[$N_Fname] ) ? $formdata[$N_Fname] : '';
            $last_name  = isset( $formdata[$N_Lname] ) ? $formdata[$N_Lname] : '';
        }

        $email          = isset( $formdata[$N_email] )          ? $formdata[$N_email]           : '';
        $phone_work     = isset( $formdata[$N_phone_work] )     ? $formdata[$N_phone_work]      : '';
        $phone_mobile   = isset( $formdata[$N_phone_mobile] )   ? $formdata[$N_phone_mobile]    : '';
        $title          = isset( $formdata[$N_title] )          ? $formdata[$N_title]           : '';
        $company        = isset( $formdata[$N_company] )        ? $formdata[$N_company]         : '';

        $access_token = $nimble->nimble_refreshtoken_get_access_token();
        update_option('nimble_access_token', $access_token);

        $counter = get_option('nimble_refresh_token_counter');
        $counter += 1;
        update_option('nimble_refresh_token_counter', $counter);

        $response = $nimble->nimble_add_contact($first_name, $last_name, $email, $phone_work, $phone_mobile, $title, $company);
    } catch(AWeberAPIException $exc) {

    }
}
add_action('wpcf7_mail_sent', 'contact7_nimble', 1);
