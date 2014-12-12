<?php 
/*
Plugin Name: Google Docs Guestlist
Version: 1.2
Plugin URI: http://www.weedeedee.com/wordpress/google-docs-rsvp-guestlist-plugin-for-wordpress/
Description: A wedding guestlist that uses Google Docs for its backend. Instructions: Create a google docs spreadsheet with the following 7 headers: Guest Name, Code, Custom Message for Guest, Ceremony, Banquet, Message from Guest, Hotel. Go to "Settings->Google Docs RSVP" to configure. Add the text: wpgc-googledocsguestlist in the content of your RSVP page.
Author: Gifford Cheung, Brian Watanabe

    Copyright (C) 2008 Gifford Cheung, Brian Watanabe

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/* TODO
1. reception vs. banquet?
2. Fully customizable messages: "Thank you", "No match", "Oops"
*/

$path = dirname(__FILE__);
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'vendor/autoload.php';

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

ini_set('display_errors', 'On');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

function register_settings_fields() {
    add_settings_section(
        'gdrsvp_google_section',
        'Google Settings',
        'show_google_section',
        __FILE__
    );

    add_settings_field(
        'wpgc_guestlist_google_client_id',
        'Google Client ID',
        'show_settings_text_field',
        __FILE__,
        'gdrsvp_google_section',
        array(
            'field_name' => 'wpgc_guestlist_google_client_id',
            'description' => 'Your Google project client ID (e.g. 123456789012-j28e0e5rpbk4lh91avgaa55jobep90ec.apps.googleusercontent.com)'
        )
    );
    register_setting('gdrsvp_google_section', 'wpgc_guestlist_google_client_id');
    add_settings_field(
        'wpgc_guestlist_google_client_secret',
        'Google Client Secret',
        'show_settings_text_field',
        __FILE__,
        'gdrsvp_google_section',
        array(
            'field_name' => 'wpgc_guestlist_google_client_secret',
            'description' => 'Your Google project client secret (e.g. nSFlUIRGuWG36xBRp578FKaV)'
        )
    );
    register_setting('gdrsvp_google_section', 'wpgc_guestlist_google_client_secret');
    // If redirect URI is not set, set it to full URL of plugin page
    $schema = (@$_SERVER['HTTPS'] == "on") ? "https://" : "http://";
    $current_url = $schema.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    add_settings_field(
        'wpgc_guestlist_google_redirect_uri',
        'Google Redirect URI',
        'show_settings_label_field',
        __FILE__,
        'gdrsvp_google_section',
        array(
            'field_name' => 'wpgc_guestlist_google_redirect_uri',
            'description' => 'Your Google project redirect URI (paste this into your project in the Google Developers Console)',
            'default_value' => $current_url
        )
    );
    register_setting('gdrsvp_google_section', 'wpgc_guestlist_google_redirect_uri');

    add_settings_section(
        'gdrsvp_other_section',
        'Other Settings',
        false,
        __FILE__
    );

    add_settings_field(
        'wpgc_guestlist_google_spreadsheet_name',
        'Google Spreadsheet Name',
        'show_settings_text_field',
        __FILE__,
        'gdrsvp_other_section',
        array(
            'field_name' => 'wpgc_guestlist_google_spreadsheet_name',
            'description' => 'The name of your spreadsheet'
        )
    );
    register_setting('gdrsvp_other_section', 'wpgc_guestlist_google_spreadsheet_name');
    add_settings_field(
        'wpgc_guestlist_google_worksheet_name',
        'Google Worksheet Name',
        'show_settings_text_field',
        __FILE__,
        'gdrsvp_other_section',
        array(
            'field_name' => 'wpgc_guestlist_google_worksheet_name',
            'description' => 'The name of your worksheet (e.g. Sheet1)'
        )
    );
    register_setting('gdrsvp_other_section', 'wpgc_guestlist_google_worksheet_name');
    add_settings_field(
        'wpgc_guestlist_wedding_planner_name',
        'Wedding Planner Name',
        'show_settings_text_field',
        __FILE__,
        'gdrsvp_other_section',
        array(
            'field_name' => 'wpgc_guestlist_wedding_planner_name',
            'description' => 'The name of the person to contact directly for questions, confirmations, or problems... (e.g. Bernard and Alice)'
        )
    );
    register_setting('gdrsvp_other_section', 'wpgc_guestlist_wedding_planner_name');
    add_settings_field(
        'wpgc_guestlist_wedding_planner_email_address',
        'Wedding Planner Email Address',
        'show_settings_text_field',
        __FILE__,
        'gdrsvp_other_section',
        array(
            'field_name' => 'wpgc_guestlist_wedding_planner_email_address',
            'description' => 'This account will be emailed every time there is a new RSVP (assuming your WordPress site is configured to send email)'
        )
    );
    register_setting('gdrsvp_other_section', 'wpgc_guestlist_wedding_planner_email_address');
    add_settings_field(
        'wpgc_guestlist_hotel_reservation_name',
        'Hotel Reservation Name',
        'show_settings_text_field',
        __FILE__,
        'gdrsvp_other_section',
        array(
            'field_name' => 'wpgc_guestlist_hotel_reservation_name',
            'description' => 'Name or party under which the hotels are reserved'
        )
    );
    register_setting('gdrsvp_other_section', 'wpgc_guestlist_hotel_reservation_name');
    add_settings_field(
        'wpgc_guestlist_toggle_hotel',
        'Ask Guests for Hotel Information',
        'show_settings_radio_field',
        __FILE__,
        'gdrsvp_other_section',
        array(
            'field_name' => 'wpgc_guestlist_toggle_hotel'
        )
    );
    register_setting('gdrsvp_other_section', 'wpgc_guestlist_toggle_hotel');
    add_settings_field(
        'wpgc_guestlist_num_hotels',
        'Number of Hotels',
        'show_settings_hotels_field',
        __FILE__,
        'gdrsvp_other_section',
        array(
            'field_name' => 'wpgc_guestlist_num_hotels',
            'sub_div_name' => 'wpgc_guestlist_hotel_forms',
            'sub_field_name' => 'wpgc_guestlist_hotel',
            'default_value' => 2
        )
    );
    register_setting('gdrsvp_other_section', 'wpgc_guestlist_num_hotels');
}

function show_google_section() {
    $schema = (@$_SERVER['HTTPS'] == "on") ? "https://" : "http://";
    $current_host = $schema.$_SERVER['HTTP_HOST']; 
    $current_url = $schema.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    echo '<h4>Instructions on how to configure Google settings</h4>';
    echo '<ol>';
    echo '<li>Go to <a href="https://console.developers.google.com/" target="_blank">https://console.developers.google.com/</a> to get to the Google Developers Console.</li>';
    echo '<li>Select <strong>Create Project</strong>.</li>';
    echo '<li>Enter a name for the project (and ID if you wish) and select <strong>Create</strong>.</li>';
    echo '<li>Once Google finishes creating the project, go to your project and select <strong>APIs</strong> under <strong>APIs & auth</strong>.</li>';
    echo '<li>In the list of APIs, turn <strong>ON</strong> the one called <strong>Drive API</strong>.</li>';
    echo '<li>Then, select <strong>Credentials</strong> under <strong>APIs & auth</strong>.</li>';
    echo '<li>Under OAuth, select <strong>Create new Client ID</strong>.</li>';
    echo '<li>For <strong>application type</strong>, choose <strong>Web Application</strong>.</li>';
    echo '<li>For <strong>authorized JavaScript origins</strong>, enter your website name <code>'.$current_host.'</code>.</li>';
    echo '<li>For <strong>authorized redirect URIs</strong>, enter <code>'.$current_url.'</code>; this is the Google Redirect URI (which is also displayed below).</li>';
    echo '<li>Select <strong>Create new Client ID</strong> to create.</li>';
    echo '<li>Copy the <strong>Client ID</strong> and <strong>Client Secret</strong> into the plugin settings page.</li>';
    echo '<li><strong>Save</strong> your settings. When the plugin page refreshes, you should see a link to authorize this plugin to access your Google account.</li>';
    echo '<li>Click on the link and go through the steps to authorize.</li>';
    echo '</ol>';
    echo '<p>When you have finished successfully, this page will indicate the authorization was successful.</p>';
}

function show_settings_text_field($args) {
    $saved_value = get_option( $args['field_name'], $args['default_value'] );
    echo '<input type="text" name="' . $args['field_name'] . '" value="'.$saved_value.'" class="regular-text" />';
    if (!empty($args['description'])) {
        echo '<p class="description">' . $args['description'] . '</p>';
    } 
}

function show_settings_label_field($args) {
    $saved_value = get_option( $args['field_name'], $args['default_value'] );
    echo '<code>' . $saved_value . '</code>';
    echo '<input type="hidden" name="' . $args['field_name'] . '" value="'.$saved_value.'" />';
    if (!empty($args['description'])) {
        echo '<p class="description">' . $args['description'] . '</p>';
    } 
}

function show_settings_radio_field($args) {
    $saved_value = get_option( $args['field_name'], $args['default_value'] );
    if (strcmp($saved_value,"true") == 0) {
        $true_on = 'checked="checked"';
    } else {
        $false_on = 'checked="checked"';
    }
    echo '<input type="radio" name="' . $args['field_name'] . '" value="true" ' . $true_on . ' />On ';
    echo '<input type="radio" name="' . $args['field_name'] . '" value="false" ' . $false_on . ' />Off';
}

function show_settings_hotels_field($args) {
    $saved_value = get_option( $args['field_name'], $args['default_value'] );
    echo '<input type="text" name="' . $args['field_name'] . '" value="'.$saved_value.'" /> ';
    echo '<input type="submit" name="submit" class="button button-primary" value="Update" /><br />';
    echo '<div id="' . $args['sub_div_name'] . '">';
    $hotels = array();
    for ($i = 0; $i < $saved_value; $i+=1) {
        $hotels[$i] = htmlspecialchars(get_option($args['sub_field_name'].$i),ENT_QUOTES);
        echo 'Hotel #' . ($i+1);
        echo '<input type="text" name="' . $args['sub_field_name'] . $i . '" value="' . $hotels[$i] . '" /><br />';
    }
    echo '</div>';
    if (!empty($args['description'])) {
        echo '<p class="description">' . $args['description'] . '</p>';
    } 
}

function admin_wpgc_guestlist_options() {
    echo '<div class="wrap"><h2>Google Docs RSVP</h2>';
    if ($_REQUEST['submit']) {
        update_wpgc_guestlist_options();
    }
    print_wpgc_guestlist_form();
    echo '</div>';
}

function update_wpgc_guestlist_options() {
    $ok = false;

    if (isset($_REQUEST['wpgc_guestlist_google_client_id'])) {
        update_option('wpgc_guestlist_google_client_id',$_REQUEST['wpgc_guestlist_google_client_id']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_google_client_secret'])) {
        update_option('wpgc_guestlist_google_client_secret',$_REQUEST['wpgc_guestlist_google_client_secret']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_google_redirect_uri'])) {
        update_option('wpgc_guestlist_google_redirect_uri',$_REQUEST['wpgc_guestlist_google_redirect_uri']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_google_spreadsheet_name'])) {
        update_option('wpgc_guestlist_google_spreadsheet_name',$_REQUEST['wpgc_guestlist_google_spreadsheet_name']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_google_worksheet_name'])) {
        update_option('wpgc_guestlist_google_worksheet_name',$_REQUEST['wpgc_guestlist_google_worksheet_name']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_wedding_planner_email_address'])) {
        update_option('wpgc_guestlist_wedding_planner_email_address',$_REQUEST['wpgc_guestlist_wedding_planner_email_address']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_hotel_reservation_name'])) {
        update_option('wpgc_guestlist_hotel_reservation_name',$_REQUEST['wpgc_guestlist_hotel_reservation_name']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_wedding_planner_name'])) {
        update_option('wpgc_guestlist_wedding_planner_name',$_REQUEST['wpgc_guestlist_wedding_planner_name']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_toggle_hotel'])) {
        update_option('wpgc_guestlist_toggle_hotel',$_REQUEST['wpgc_guestlist_toggle_hotel']);
        $ok = true;
    }
    if (isset($_REQUEST['wpgc_guestlist_num_hotels'])) {
        update_option('wpgc_guestlist_num_hotels', $_REQUEST['wpgc_guestlist_num_hotels']);
        $ok = true;    
    }
    // we assume that num_hotels is a number
    $num_hotels = get_option('wpgc_guestlist_num_hotels');
    for ($i = 0; $i < $num_hotels; $i+=1) {
        if (isset($_REQUEST['wpgc_guestlist_hotel'.$i])) {
            update_option('wpgc_guestlist_hotel'.$i, $_REQUEST['wpgc_guestlist_hotel'.$i]);
            $ok = true; // this is not very meaningful right now because $num_hotels will have already set ok to true.
        }
    }
    /* VERSION 2.0
    if (isset($_REQUEST['wpgc_guestlist_toggle_ceremony'])) {
        update_option('wpgc_guestlist_toggle_ceremony',$_REQUEST['wpgc_guestlist_toggle_ceremony']);
        $ok = true;
    }
    */
    if ($ok) {
        echo '<div id="message" class="updated fade">';
        echo '<p>Options saved.</p>';
        echo '</div>';
    }
    else {
        echo '<div id="message" class="error fade">';
        echo '<p>Failed to save options.</p>';
        echo '</div>';
    }
}

function print_wpgc_guestlist_form() {
    $default_client_id = get_option('wpgc_guestlist_google_client_id');
    $default_client_secret = get_option('wpgc_guestlist_google_client_secret');
    $default_redirect_uri = get_option('wpgc_guestlist_google_redirect_uri');

    if (isset($_GET['deauthorize'])) {
      delete_option('gdrsvp_access_token');
    }

    $perm_access_token = "";
    if ($default_client_id && $default_client_secret && $default_redirect_uri) {
        $client = new Google_Client();
        $client->setClientId($default_client_id);
        $client->setClientSecret($default_client_secret);
        $client->setRedirectUri($default_redirect_uri);
        $client->setScopes(array('https://spreadsheets.google.com/feeds'));
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        if (isset($_GET['code'])) {
          $client->authenticate($_GET['code']);
          update_option('gdrsvp_access_token',$client->getAccessToken());
          // Redirecting to same page without authorization code in query string
          // Better done with HTTP header, but headers already sent by this point
          unset($_GET['code']);
          $query_string = urldecode(http_build_query($_GET));
          $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?" . $query_string;
          echo "<script>window.location.replace('" . $redirect . "');</script>";
        }

        $perm_access_token = get_option('gdrsvp_access_token');
        if (isset($perm_access_token) && $perm_access_token) {
          $client->setAccessToken($perm_access_token);
        } else {
          $authUrl = $client->createAuthUrl();
        }
    }
    
    /* VERSION 2.0
    $default_toggle_ceremony = get_option('wpgc_guestlist_toggle_ceremony');
    $ceremonyon = "";
    $ceremonyoff = "";
    if ($default_toggle_ceremony) {
        $ceremonyon = 'checked="checked"';
    }
    else {
        $ceremonyoff = 'checked="checked"';
    }
    */

    ?>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th>Google Authorization</th>
                        <td>
    <?php
    if ($client && isset($authUrl)) {
        echo "<a class='login' href='" . $authUrl . "' style='color:red'>Authorize this plugin to Google</a> (Last step!)";
    } elseif ($client) {
        echo "<p style='color:green'>You have authorized the plugin!</p>";
        echo "<a class='logout' href='".$_SERVER['REQUEST_URI']."&deauthorize'>Deauthorize the plugin</a>";
    } else {
        echo "<p>Fill in the values below to set up access to your Google spreadsheet.</p>";
    }
    ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php settings_fields(__FILE__); ?>
        <?php do_settings_sections(__FILE__); ?>

            
            
    <?php /* VERSION 3.0
    <a href="javascript:remove_hotel_form()">Remove Hotel</a>
    <a href="javascript:add_hotel_form()">Add Hotel" />
    */ ?>
    
    <?php
    /* VERSION 2.0
    <label for="wpgc_guestlist_toggle_ceremony">Ceremony:<br />
    <input type="radio" name="wpgc_guestlist_toggle_ceremony" value="true" <?=$ceremonyon?> />On
    <input type="radio" name="wpgc_guestlist_toggle_ceremony" value="false" <?=$ceremonyoff?> />Off
    */
    ?>

        <?php submit_button(); ?>
        </form>
    <?php
}

function modify_menu() {
    add_options_page(
        'Google Docs RSVP',        //page title
        'Google Docs RSVP',        //subpage title
        'manage_options',              //access
        __FILE__,                      //current file
        'admin_wpgc_guestlist_options' //options function above
    );
}

/*
Function: wpgc_clean
Meant to clean up user input.... watching out for google docs injection attacks???? shrug
*/
function wpgc_clean( $value , $strip = true) {
    return $value;
    /*
    if ($strip) {
        return strip_tags($value);
    } else {
        return $value;
    }
    */
}

/*
Function: wpgc_prepare_access_token
Helper function to ensure the access token is valid for the listFeed function
*/
function wpgc_prepare_access_token($client_id, $client_secret, $access_token) {
    $client = new Google_Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setAccessToken($access_token);
    if ($client->isAccessTokenExpired()) {
        $refresh_token = $client->getRefreshToken();
        $client->refreshToken($refresh_token);
        update_option('gdrsvp_access_token', $client->getAccessToken());
    }
}


/*
Function: wpgc_get_listFeed_for_guestcode
This is a helper function. It connects to the Google docs spreadsheet, finds the right worksheet, and finds only the rows that have $guest_code as its guest code.
TODO: Order the entries by some order
*/
function wpgc_get_listFeed_for_guestcode($access_token, $spreadsheet_name, $worksheet_name, $guest_code) {
    $accessTokenObj = json_decode($access_token, true);
    $serviceRequest = new DefaultServiceRequest($accessTokenObj['access_token']);
    ServiceRequestFactory::setInstance($serviceRequest);
    $spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
    $spreadsheetFeed = $spreadsheetService->getSpreadsheets();
    $spreadsheet = $spreadsheetFeed->getByTitle($spreadsheet_name);
    $worksheetFeed = $spreadsheet->getWorksheets();
    $worksheet = $worksheetFeed->getByTitle($worksheet_name);
    $listFeed = $worksheet->getListFeed(array("sq" => "code=".$guest_code));
    return $listFeed;
}

function add_guest_code_submission($outputtext) {
    $new_outputtext = $outputtext;
    $new_outputtext .= "<p>Please type in the code from your wedding invitation below:</p>";
    $new_outputtext .= "<form style='text-align: left' action='".get_permalink()."' method='post'>";
    $new_outputtext .= "<input type='hidden' name='motion' value='edit'/>";
    $new_outputtext .= "<input type='text' name='guest_code' /> ";
    $new_outputtext .= "<button type='Submit'>Submit</button>";
    $new_outputtext .= "</form>";
    return $new_outputtext;
}

function wpgc_my_googledocsguestlist ($text) {
    //QUIT if the replacement string doesn't exist
    if (!strstr($text,'wpgc-googledocsguestlist')) {
        return $text;
    }
    // Key variables
    $client_id = get_option('wpgc_guestlist_google_client_id');
    $client_secret = get_option('wpgc_guestlist_google_client_secret');
    $access_token = get_option('gdrsvp_access_token');

    $spreadsheet_name = get_option('wpgc_guestlist_google_spreadsheet_name');
    $worksheet_name = get_option('wpgc_guestlist_google_worksheet_name');
    $wedding_planner = get_option('wpgc_guestlist_wedding_planner_name');
    $wedding_planner_email_address = get_option('wpgc_guestlist_wedding_planner_email_address');
    $hotel_reservation_name = get_option('wpgc_guestlist_hotel_reservation_name');

    $toggle_hotel = get_option('wpgc_guestlist_toggle_hotel');
    $hotelon = !(boolean)strcmp($toggle_hotel,"true");
    $num_hotels = get_option('wpgc_guestlist_num_hotels');
    $hotel_list = array();
    for ($i = 0; $i < $num_hotels; $i+=1) {
        $hotel_list[$i] = get_option('wpgc_guestlist_hotel'.$i);
    }

    //Configuration check
    if (!$client_id || !$client_secret || !$access_token || !$spreadsheet_name || !$worksheet_name || !$wedding_planner_email_address) {
        return "This plugin has not been fully configured. Please complete all of the steps described under Settings->Google Docs RSVP.";
    }

    $guest_code = $_POST['guest_code'] ? $_POST['guest_code'] : '';
    $outputtext = '';
    $abort_and_reprint_form = false;

    // Login
    // if you have a code and NO answers, pass
    // if you have a code, but already answered, no pass
    // if you have bad code, no pass
    
    if (isset($_POST['motion'])) {
        switch ($_POST['motion']) {
            case ('update'): 
                // (A) Save to the database
                try {
                    // Prepare access token and retrieve the listFeed for your guestcode
                    wpgc_prepare_access_token($client_id, $client_secret, $access_token);
                    $listFeed = wpgc_get_listFeed_for_guestcode($access_token, $spreadsheet_name, $worksheet_name, $guest_code);
                    
                    // CHECK, did they already fill out the form?
                    $entries = $listFeed->getEntries();
                    foreach ($entries as $e) {
                        $entry = $e->getValues();
                        if ($entry['ceremony'] || $entry['banquet']) {
                            $abort_and_reprint_form = true;
                            break;
                        }
                    }
                    if ($abort_and_reprint_form) {
                        break;
                    }
                    
                    $hotel = '';
                    if (isset($_POST['hotel'])) {
                        $hotel = wpgc_clean($_POST['hotel']);
                    }
                    $messagefromguest = '';
                    if (isset($_POST['messagefromguest'])) {
                        $messagefromguest = wpgc_clean($_POST['messagefromguest']);
                    }
                    $ceremony_attendees = array();            
                    $banquet_attendees = array();
                    $your_hotel = '';
                    $your_message = '';
                    
                    if (count($entries) == 0) {
                        $abort_and_reprint_form = true;
                        break;
                    }
                    // Construct
                    for ($i=0; $i < count($entries); $i+=1) {
                        // Grab the data from the http post and construct the array
                        $listEntry = $entries[$i];
                        $newentry = $listEntry->getValues();
                        if (isset($_POST['guestname'.$i])) {
                            $newentry["guestname"] = stripslashes($_POST['guestname'.$i]);
                        }
                        if (isset($_POST['ceremony'.$i])) {
                            $newentry["ceremony"] = stripslashes($_POST['ceremony'.$i]);
                        }
                        if (isset($_POST['banquet'.$i])) {
                            $newentry["banquet"] = stripslashes($_POST['banquet'.$i]);
                        }
                        if (isset($_POST['custommessageforguest'.$i])) {
                            $newentry["custommessageforguest"] = stripslashes($_POST['custommessageforguest'.$i]);
                        }
                        // for the hotel information, we'll only add it to the entry if they are attending either the banquet or the ceremony
                        if ($hotelon) {
                            if (strcmp($newentry["ceremony"],"Attending") == 0 ||
                                strcmp($newentry["banquet"],"Attending") == 0) {
                                $hotel_index = str_replace("hotel", "", $hotel);
                                $hotel_name = $hotel_list[$hotel_index];
                                $newentry["hotel"] = $hotel_name;
                            }
                        }
                        // for the comments from the quest, we'll only plug it into the first entry
                        if ($i == 0) {
                            $newentry["messagefromguest"] = stripslashes($messagefromguest);
                        }
                        // guest_code
                        $newentry["code"] = $guest_code;
                        
                        // GO! Update the spreadsheet!
                        // What if it fails? I suppose an exception gets thrown...
                        $listEntry->update($newentry);
                        
                        $checked_entry = $newentry;
                        if ($i == 0) {
                            $your_message = $checked_entry["messagefromguest"];
                        }
                        if (strcmp($checked_entry["ceremony"],"Attending")==0) {
                            $ceremony_attendees[] = $checked_entry["guestname"];
                            // Also grab hotel information, yes, this is a bit redundant and will get rewritten a few times
                            $your_hotel = $checked_entry['hotel'];
                        }
                        if (strcmp($checked_entry["banquet"],"Attending")==0) {
                            $banquet_attendees[] = $checked_entry["guestname"];
                            $your_hotel = $checked_entry['hotel'];
                        }
                    }

                    // (B) Give user confirmation
                    $emailreport = "NEW RSVP! \n";
                    $outputtext .= '<strong>Thank you for your response!</strong><br/>';
                    $outputtext .= "<br/>\n";
                    $plural = '';
                    if (sizeof($ceremony_attendees) != 1) {
                        $plural = "s";
                    }
                    $outputtext .= '<strong>'.sizeof($ceremony_attendees).'</strong> guest'.$plural.' will be attending the ceremony';
                    $emailreport .= sizeof($ceremony_attendees).' guest'.$plural.' will be attending the ceremony';
                    
                    if (sizeof($ceremony_attendees)) { 
                        $outputtext .= ":<br/>";
                        $emailreport .= ":\n";
                    } else {
                        $outputtext .=".<br/>";
                        $emailreport .=".\n";
                    }
                    
                    for ($i=0; $i<sizeof($ceremony_attendees); $i+=1) {
                        if (empty($ceremony_attendees[$i])) $ceremony_attendees[$i] = "Guest";
                        $outputtext .= htmlspecialchars($ceremony_attendees[$i],ENT_QUOTES);
                        $emailreport .= htmlspecialchars($ceremony_attendees[$i],ENT_QUOTES);
                        
                        if ($i != sizeof($ceremony_attendees)-1) {
                            $outputtext .= ", ";
                            $emailreport .= ", ";
                        }
                    }
                    if (sizeof($ceremony_attendees)) {
                        $outputtext .= "<br/>\n";
                        $emailreport .= "\n";
                    }
                    $outputtext .= "<br/>\n";
                    $emailreport .= "\n";

                    $plural = '';
                    if (sizeof($banquet_attendees) != 1) {
                        $plural = "s";
                    }
                    $outputtext .= '<strong>'.sizeof($banquet_attendees).'</strong> guest'.$plural.' will be attending the banquet';
                    $emailreport .= sizeof($banquet_attendees).' guest'.$plural.' will be attending the banquet';

                    if (sizeof($banquet_attendees)) {
                        $outputtext .= ":<br/>";
                        $emailreport .= ":\n";
                    } else {
                        $outputtext .=".<br/>";
                        $emailreport .=".\n";
                    }
                    
                    for ($i=0; $i<sizeof($banquet_attendees); $i+=1) {
                        if (empty($ceremony_attendees[$i])) {
                            $ceremony_attendees[$i] = "Guest";
                        }
                        $outputtext .= htmlspecialchars($banquet_attendees[$i],ENT_QUOTES);
                        $emailreport .= htmlspecialchars($banquet_attendees[$i],ENT_QUOTES);
                        if ($i != sizeof($banquet_attendees)-1) {
                            $outputtext .= ", ";
                            $emailreport .= ", ";
                        }
                    }
                    
                    if (sizeof($banquet_attendees)) {
                        $outputtext .= "<br/>\n";
                        $emailreport .="\n";
                    }
                    $outputtext .= "<br/>\n";
                    $emailreport .= "\n";

                    if ($hotelon) {
                        $outputtext .= "You will be staying at the <strong>" . $your_hotel . ".</strong><br/><br/>\n";
                        $emailreport .= "They will be staying at the " . $your_hotel . ".\n";
                    }

                    if (!empty($your_message)) {
                        $outputtext .= "Your message will be delivered:<br/><blockquote>";
                        $outputtext .=  htmlspecialchars(stripslashes($your_message),ENT_QUOTES);
                        $outputtext .= "</blockquote><br/>";
                        $emailreport .= "They also wrote a message for you:\n";
                        $emailreport .=  stripslashes($your_message);
                        $emailreport .= "\n";
                    }

                    $outputtext .= "<em>Thank you! Your response has been saved and your invitation code has been used. If you need to change your reply or have any questions, please contact " . $wedding_planner . " at <a href='". $wedding_planner_email_address ."'>". $wedding_planner_email_address ."</a>.</em>";

                    $subject = "Wedding RSVP";
                    $headers = 'From: '.$wedding_planner_email_address;
                    mail($wedding_planner_email_address, $subject, $emailreport, $headers);

                } catch (Exception $e) {
                    $outputtext .= "<strong>Oops, there was a small glitch.</strong> Please try again, or contact ". $wedding_planner ." directly.";
                    //$outputtext .= "<pre>" . $e->getMessage() . " " . $e->getTraceAsString() . "</pre>";
                }
            break;    
            
            case('edit'):
                try {
                    // If no code was entered, just abort and break
                    if (strcmp($guest_code,'') == 0) {
                        $abort_and_reprint_form = true;
                        break;
                    }
                    // Prepare access token and retrieve the listFeed for your guestcode
                    wpgc_prepare_access_token($client_id, $client_secret, $access_token);
                    $listFeed = wpgc_get_listFeed_for_guestcode($access_token, $spreadsheet_name, $worksheet_name, $guest_code);

                    $already_replied = false;
                    // CHECK, did they already fill out the form?
                    $entries = $listFeed->getEntries();
                    foreach ($entries as $e) {
                        $entry = $e->getValues();
                        if ($entry['ceremony'] || $entry['banquet']) {
                            $already_replied = true;
                            break;
                        }
                    }

                    // Careful... entries might be an empty set
                    if (count($entries) > 0 && !$already_replied) {
                        // SUCCESS: We found a party 
                        $plural = '';
                        if (count($entries) != 1) {
                            $plural = "s";
                        }
                        $outputtext .= "Thank you for replying online. We look forward to celebrating with you all! Please indicate whether each member of your party will be attending our wedding day events.<br/><br/><strong>We have reserved ".count($entries)." seat".$plural." in your honor.</strong><br/>";
                        // Initializing the form. And yes, it is one massive form for the whole page. We differentiate among the attendees by adding an index number after each input 'name', e.g. guestname3
                        $outputtext .= '<form style="text-align: left" action="'.get_permalink().'" method="post">';

                        // We use a for loop here because the index i is important to keep as a row identifier when we update the row information later.
                        for ($i = 0; $i < count($entries); $i +=1) {
                            $entry = $entries[$i]->getValues();

                            // Populating the sub-parts of the form for each guest
                            $outputtext .= '<strong>'.($i+1).'. Name:</strong> <input name="guestname'.$i.'" type="text" value="'. htmlspecialchars($entry["guestname"],ENT_QUOTES).'"/>';
                            $outputtext .= "<br/>";
                            $outputtext .= '<em>Ceremony:</em> <input name="ceremony'.$i.'" type="radio" value="Attending" ';
                            if (strcmp($entry["ceremony"],"Attending") == 0) {
                                $outputtext .= "checked='checked' ";
                            }
                            $outputtext .= "/> Attending   ";
                            $outputtext .= '<input name="ceremony'.$i.'" type="radio" value="Not Attending" ';
                            if (strcmp($entry["ceremony"],"Not Attending") == 0) {
                                $outputtext .= 'checked="checked" ';
                            }
                            $outputtext .= "/> Not Attending";
                            $outputtext .= "<br/>";
                            $outputtext .= '<em>Banquet:</em> <input name="banquet'.$i.'" type="radio" value="Attending" ';
                            if (strcmp($entry["banquet"],"Attending") == 0) {
                                $outputtext .= "checked='checked' ";
                            }
                            $outputtext .= "/> Attending   ";
                            $outputtext .= '<input name="banquet'.$i.'" type="radio" value="Not Attending" ';
                            if (strcmp($entry["banquet"],"Not Attending") == 0) {
                                $outputtext .= "checked='checked' ";
                            }
                            $outputtext .= "/> Not Attending";
                            $outputtext .= "<br/>\n";
                            // Message for guest
                            if (!empty($entry["custommessageforguest"])) {
                                $outputtext .= '<blockquote>'.htmlspecialchars($entry["custommessageforguest"],ENT_QUOTES).'</blockquote>';
                            }
                            $outputtext .= '<input type="hidden" name="custommessageforguest'.$i.'" value="'.htmlspecialchars($entry["custommessageforguest"]).'"/>';

                            $outputtext .= "<p/>\n";                
                        }
                        // Some questions for the whole party -- needs some tweaking
                        // Blocked Rooms, this doesn't record past information.
                        if ($hotelon) {
                            $outputtext .= "<em>If you are planning to stay at one of the hotels where we have reserved rooms under \"" . $hotel_reservation_name . ",\" please let us know where you will be staying:</em>";
                            $outputtext .= "<br/>\n";
                            for ($i=0; $i < $num_hotels; $i++) {
                                $outputtext .= "<input name='hotel' type='radio' value='hotel".$i."'/> ".$hotel_list[$i];
                                $outputtext .= "<br/>\n";
                            }
                            $outputtext .= "<input name='hotel' type='radio' value='none'/> Not planning to stay at any of these hotels";
                            $outputtext .= "<p/>\n";
                        }

                        // Message from the Guests
                        $outputtext .= "<strong>Let us know how you're doing. Leave us a note!</strong><br/>\n";
                        $outputtext .= "<textarea name='messagefromguest'></textarea>"; // Note that old comments are not retrieved. We assume that you will only submit an RSVP once.
                        $outputtext .= "<br/>\n";

                        // Closing the form
                        $outputtext .= "\n";
                        $outputtext .= "<input type='hidden' name='motion' value='update'/>";
                        $outputtext .= "<input type='hidden' name='guest_code' value='". htmlspecialchars($guest_code,ENT_QUOTES)."'/>";
                        $outputtext .= "<br/>\n";
                        $outputtext .= "<button type='Submit'>Send my RSVP</button> ";
                        $outputtext .= "<input type='button' onClick='window.location.href=\"".get_permalink()."\"' value='Cancel, I will reply later' />";
                        $outputtext .= "</form>";
                    } else {
                        $outputtext .= "<h6>No match. Please try again.</h6>";
                        $outputtext .= "<p><em>If you have already RSVP'd, your code will no longer work. If you would like to change your response, or if you were not able to respond in the first place, please contact " . $wedding_planner . " at <a href='mailto:". $wedding_planner_email_address ."'>". $wedding_planner_email_address ."</a>.</em></p>";
                        $outputtext = add_guest_code_submission($outputtext);
                    }

                } catch (Exception $e) {
                    $outputtext .= "<h6>Oops. You found an error.</h6><p>Please try again or contact " . $wedding_planner . " at <a href='mailto:". $wedding_planner_email_address ."'>". $wedding_planner_email_address ."</a> to confirm your response.</p>";
                    //$outputtext .= "[There was a error. Please consult the source code or an experienced programmer. :( ]";
                    //$outputtext .= "<pre>" . $e->getMessage() . " " . $e->getTraceAsString() . "</pre>";
                    $outputtext = add_guest_code_submission($outputtext);
                }
            break;
        } // endswitch on post motion  
    } // endif (isset)
    else {
        $outputtext = add_guest_code_submission($outputtext);
    }
    
    // this is covers cases where someone re-posted an update ... it defends against attacks where someone is spamming the update and trying to overwrite entries
    // TODO This currently covers the case where someone has naively click back and resubmitted a form
    // TODO I haven't checked whether an attacker can use the feedback to determine which codes are valid.
    if ($abort_and_reprint_form) {
        $outputtext .= "<p><strong>Sorry, our records were not changed.</strong></p>";
        $outputtext = add_guest_code_submission($outputtext);
    }
    
    $text = str_replace('wpgc-googledocsguestlist', $outputtext, $text);
    return $text;
}

add_action('admin_init', 'register_settings_fields');
add_action('admin_menu', 'modify_menu');
add_filter('the_content', 'wpgc_my_googledocsguestlist');
?>