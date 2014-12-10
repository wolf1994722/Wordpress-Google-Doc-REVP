<?php 
/*
Plugin Name: Google Docs Guestlist
Version: 1.2
Plugin URI: http://www.weedeedee.com/wordpress/google-docs-rsvp-guestlist-plugin-for-wordpress/
Description: A wedding guestlist that uses Google Docs for its backend. Instructions: Create a google docs spreadsheet with the following 7 headers: Guest Name, Code, Custom Message for Guest, Ceremony, Banquet, Message from Guest, Hotel. Go to "Settings->Google Docs Guestlist" to configure. Add the text: wpgc-googledocsguestlist in the content of your RSVP page.
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

function admin_wpgc_guestlist_options(){
	?><div class="wrap"><h2>GoogleDocs Guestlist</h2><?php
        if ($_REQUEST['submit']){
        	update_wpgc_guestlist_options();
        }
        print_wpgc_guestlist_form();

        ?></div><?php
}

function update_wpgc_guestlist_options() {
	$ok = false;

        if($_REQUEST['wpgc_guestlist_google_client_id']) {
        	update_option('wpgc_guestlist_google_client_id',$_REQUEST['wpgc_guestlist_google_client_id']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_google_client_secret']) {
        	update_option('wpgc_guestlist_google_client_secret',$_REQUEST['wpgc_guestlist_google_client_secret']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_google_redirect_uri']) {
            update_option('wpgc_guestlist_google_redirect_uri',$_REQUEST['wpgc_guestlist_google_redirect_uri']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_google_spreadsheet_name']) {
        	update_option('wpgc_guestlist_google_spreadsheet_name',$_REQUEST['wpgc_guestlist_google_spreadsheet_name']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_google_worksheet_name']) {
        	update_option('wpgc_guestlist_google_worksheet_name',$_REQUEST['wpgc_guestlist_google_worksheet_name']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_wedding_planner_email_address']) {
        	update_option('wpgc_guestlist_wedding_planner_email_address',$_REQUEST['wpgc_guestlist_wedding_planner_email_address']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_hotel_reservation_name']) {
        	update_option('wpgc_guestlist_hotel_reservation_name',$_REQUEST['wpgc_guestlist_hotel_reservation_name']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_wedding_planner']) {
        	update_option('wpgc_guestlist_wedding_planner',$_REQUEST['wpgc_guestlist_wedding_planner']);
                $ok = true;
        }
        if($_REQUEST['wpgc_guestlist_toggle_hotel']) {
        	update_option('wpgc_guestlist_toggle_hotel',$_REQUEST['wpgc_guestlist_toggle_hotel']);
                $ok = true;
        }
		
		if ($_REQUEST['wpgc_guestlist_num_hotels']) {
			update_option('wpgc_guestlist_num_hotels', $_REQUEST['wpgc_guestlist_num_hotels']);
	        $ok = true;    
		}
		
		// we assume that num_hotels is a number
		$num_hotels = get_option('wpgc_guestlist_num_hotels');
		for ($i = 0; $i < $num_hotels; $i+=1) {
			if ($_REQUEST['wpgc_guestlist_hotel'.$i]) {
				update_option('wpgc_guestlist_hotel'.$i, $_REQUEST['wpgc_guestlist_hotel'.$i]);
				$ok = true; // this is not very meaningful right now because $num_hotels will have already set ok to true.
			}
		}
			
	
		
        /* VERSION 2.0
        if($_REQUEST['wpgc_guestlist_toggle_ceremony']) {
        	update_option('wpgc_guestlist_toggle_ceremony',$_REQUEST['wpgc_guestlist_toggle_ceremony']);
                $ok = true;
        }
        */
        if($ok) {
        	?><div id="message" class="updated fade">
        	<p>Options saved.</p>
                </div><?php
        }
        else {
        	?><div id="message" class="error fade">
        	<p>Failed to save options.</p>
                </div><?php
        }
}

function print_wpgc_guestlist_form() {
	$default_client_id = get_option('wpgc_guestlist_google_client_id');
        $default_client_secret = get_option('wpgc_guestlist_google_client_secret');
        $default_redirect_uri = get_option('wpgc_guestlist_google_redirect_uri');
        $default_spreadsheet = get_option('wpgc_guestlist_google_spreadsheet_name');
        $default_worksheet = get_option('wpgc_guestlist_google_worksheet_name');
        $default_email_address = get_option('wpgc_guestlist_wedding_planner_email_address');

        $default_hotel_reservation_name = get_option('wpgc_guestlist_hotel_reservation_name');
        $default_wedding_planner = get_option('wpgc_guestlist_wedding_planner');

        $default_toggle_hotel = get_option('wpgc_guestlist_toggle_hotel');
        $hotelon = "";
        $hoteloff = "";
        if( strcmp($default_toggle_hotel,"true")==0 )
			$hotelon = 'checked="checked"';
        else
        	$hoteloff = 'checked="checked"';

		$default_num_hotels = get_option('wpgc_guestlist_num_hotels');
		if (!$default_num_hotels) $default_num_hotels = 2;
		$hotels = array();
		for ($i = 0; $i < $default_num_hotels; $i+=1) {
			$hotels[$i] = htmlspecialchars(get_option('wpgc_guestlist_hotel'.$i),ENT_QUOTES);
			if (!$hotels[$i])
				$hotels[$i] = "Hotel Name";
		}

        if (isset($_GET['deauthorize'])) {
          delete_option('google_api_access_token');
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
              update_option('google_api_access_token',$client->getAccessToken());
              unset($_GET['code']);
              // Redirecting to same page without authorization code in query string
              // Better done with HTTP header, but headers already sent by this point
              $query_string = urldecode(http_build_query($_GET));
              $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?" . $query_string;
              echo "<script>window.location.replace('" . $redirect . "');</script>";
            }

            $perm_access_token = get_option('google_api_access_token');
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
        if( $default_toggle_ceremony )
		$ceremonyon = 'checked="checked"';
        else
        	$ceremonyoff = 'checked="checked"';
        */

        ?>
		<script type="text/Javascript">
			var numHotels = <?=$default_num_hotels?>;
			function generate_hotel_form() {			
				document.getElementById('wpgc_guestlist_hotel_forms').innerHTML="We have hotels numbering " + numHotels;
			}
		</script>
		
        <form method="post">
        	<label for="wpgc_guestlist_google_client_id">Google client ID:
              	<input type="text" name="wpgc_guestlist_google_client_id" value="<?=$default_client_id?>" />
                </label><i>Your Google project client ID</i>
                <br />
                <br />
                <label for="wpgc_guestlist_google_client_secret">Google client secret:
              	<input type="text" name="wpgc_guestlist_google_client_secret" value="<?=$default_client_secret?>" />
                </label><i>Your Google project client password</i>
                <br />
                <br />
                <label for="wpgc_guestlist_google_redirect_uri">Google redirect URI:
                <input type="text" name="wpgc_guestlist_google_redirect_uri" value="<?=$default_redirect_uri?>" />
                </label><i>Your Google project redirect URI</i>
                <div class="box">
                    <div class="request">
                <?php
                if ($client && isset($authUrl)) {
                    echo "<a class='login' href='" . $authUrl . "'>Authorize this plugin to Google</a>";
                } elseif ($client) {
                    echo "<span>You have authorized the plugin!</span>";
                    echo "<a class='logout' href='".$_SERVER['REQUEST_URI']."&deauthorize'>Deauthorize this plugin</a>";
                } else {
                    echo "<span>Fill in the values above to set up access to your Google spreadsheet</span>";
                }
                ?>
                    </div>
                </div>
                <br />
                <br />
                <label for="wpgc_guestlist_google_spreadsheet_name">Google Spreadsheet Name:
              	<input type="text" name="wpgc_guestlist_google_spreadsheet_name" value="<?=$default_spreadsheet?>" />
                </label><i>The name of your spreadsheet</i>
                <br />
                <br />
                <label for="wpgc_guestlist_google_worksheet_name">Google Worksheet Name:
              	<input type="text" name="wpgc_guestlist_google_worksheet_name" value="<?=$default_worksheet?>" />
                </label><i>The name of your worksheet(e.g. Sheet1)</i>
                <br />
                <br />
                <label for="wpgc_guestlist_wedding_planner_email_address">Wedding Planner Email Address:
              	<input type="text" name="wpgc_guestlist_wedding_planner_email_address" value="<?=$default_email_address?>" />
                </label><i>This account will be emailed every time there is a new RSVP</i>
                <br />
                <br />
                 <label for="wpgc_guestlist_hotel_reservation_name">Hotel Reservation Name:
              	<input type="text" name="wpgc_guestlist_hotel_reservation_name" value="<?=$default_hotel_reservation_name?>" />
                </label><i>Name or party the hotel(s) is(are) reserved under</i>
                <br />
                <br />
                 <label for="wpgc_guestlist_wedding_planner">Wedding Planner Name:
              	<input type="text" name="wpgc_guestlist_wedding_planner" value="<?=$default_wedding_planner?>" />
                </label><i>Name of person to contact directly for questions, confirmations, or problems... (e.g. Bernard and Alice)</i>
                <br />
                <br />
                <br />
                <h3>RSVP Options:</h3>
                <label for="wpgc_guestlist_toggle_hotel">Ask Guests for Hotel Information:<br />
                <input type="radio" name="wpgc_guestlist_toggle_hotel" value="true" <?=$hotelon?> />On
                <input type="radio" name="wpgc_guestlist_toggle_hotel" value="false" <?=$hoteloff?> />Off
                <br />
				
				<label for="wp_guestlist_num_hotels">Number of Hotels:
				<input type="text" name="wpgc_guestlist_num_hotels" value="<?=$default_num_hotels?>" />
				</label> <input type="submit" name="submit" value="Update" />

				<br />
				
				<div id="wpgc_guestlist_hotel_forms">
				<?php
				for ($i = 0; $i < $default_num_hotels; $i+=1) {
					?>
	                <label for="wpgc_guestlist_hotel<?=$i?>">Hotel #<?=$i+1?>:
					<input type="text" name="wpgc_guestlist_hotel<?=$i?>" value="<?=$hotels[$i]?>" />
					</label>
					<br />
				<?php
				}
				?>
				</div>
				
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

                <input type="submit" name="submit" value="Submit" />
	</form>
        <?php
}

function modify_menu() {
	add_options_page(
        		'GoogleDocs Guestlist',	//page title
                        'GoogleDocs Guestlist',	//subpage title
                        'manage_options',	//access
                        __FILE__,		//current file
                        'admin_wpgc_guestlist_options'	//options function above
                        );
}

/*
Function: wpgc_clean
Meant to clean up user input.... watching out for google docs injection attacks???? shrug
*/
function wpgc_clean( $value , $strip = true) {
	return $value;
        //if ($strip) return strip_tags($value);
        //else return $value;
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
        update_option('google_api_access_token', $client->getAccessToken());
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

/*
Name: wpgc_parse_entry

Extract the values we care about from a listfeedentry

TODO: Currently unused, not necessary?

*/
function wpgc_parse_entry($listFeedEntry) {
	$onerow_data = $listFeedEntry->getCustom();
	foreach($onerow_data as $column) {
		switch ($column->getColumnName()) {
			case ("guestname"): 
				$entry["guestname"] = $column->getText();
			break;
			case ("ceremony"): 
				$entry["ceremony"] = $column->getText();
			break;
			case ("banquet"): 
				$entry["banquet"] = $column->getText();
			break;
			case ("custommessageforguest"): 
				$entry["custommessageforguest"] = $column->getText();
			break;
			case ("messagefromguest"): 
				$entry["messagefromguest"] = $column->getText();
			break;
			case ("hotel"): 
				$entry["hotel"] = $column->getText();
			break;

		}
	}
return $entry;
}

function wpgc_my_googledocsguestlist ($text) {
	//QUIT if the replacement string doesn't exist
	if (!strstr($text,'wpgc-googledocsguestlist')) return $text;


        // Key variables
        $client_id = get_option('wpgc_guestlist_google_client_id');
        $client_secret = get_option('wpgc_guestlist_google_client_secret');
        $access_token = get_option('google_api_access_token');

        $spreadsheet_name = get_option('wpgc_guestlist_google_spreadsheet_name');
        $worksheet_name = get_option('wpgc_guestlist_google_worksheet_name');
        $wedding_planner_email_address = get_option('wpgc_guestlist_wedding_planner_email_address');

        $hotel_reservation_name = get_option('wpgc_guestlist_hotel_reservation_name');
        $wedding_planner = get_option('wpgc_guestlist_wedding_planner');

        $toggle_hotel = get_option('wpgc_guestlist_toggle_hotel');
        $hotelon = !(boolean)strcmp($toggle_hotel,"true");
	$num_hotels = get_option('wpgc_guestlist_num_hotels');
	$hotel_list = array();
	for ($i = 0; $i < $num_hotels; $i+=1) {
		$hotel_list[$i] = get_option('wpgc_guestlist_hotel'.$i);
	}


        //Configuration check
        if (!$client_id || !$client_secret || !$access_token || !$spreadsheet_name || !$worksheet_name || !$wedding_planner_email_address) {
        	return "This plugin has not been fully configured.  Please fill out all the entries under Settings->GoogleDocs Guestlist.";
        }

        $spreadsheet_key = '';
	$worksheet_key = '';
	$guest_code = $_POST['guest_code'] ? $_POST['guest_code'] : '';
	$outputtext = '';
	$abort_and_reprint_form = false;

	// Login
	// if you have a  code and NO answers, pass
	// if you have a  code, but already answered, no pass
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
				if ($abort_and_reprint_form) break;
				
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
				$outputtext .= '<b>Thank you for your response!</b><br/>';
				$outputtext .= "<br/>\n";
				$plural = '';
				if (sizeof($ceremony_attendees) != 1) $plural = "s"; 
				$outputtext .= '<b>'.sizeof($ceremony_attendees).'</b> guest'.$plural.' will be attending the ceremony';
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
				if (sizeof($banquet_attendees) != 1) $plural = "s"; 
				$outputtext .= '<b>'.sizeof($banquet_attendees).'</b> guest'.$plural.' will be attending the banquet';
				$emailreport .= sizeof($banquet_attendees).' guest'.$plural.' will be attending the banquet';

				if (sizeof($banquet_attendees)) {
					$outputtext .= ":<br/>";
					$emailreport .= ":\n";
				} else {
					$outputtext .=".<br/>";
					$emailreport .=".\n";
				}
				
				for ($i=0; $i<sizeof($banquet_attendees); $i+=1) {
					if (empty($ceremony_attendees[$i])) $ceremony_attendees[$i] = "Guest";
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
                $outputtext .= "You will be staying at the <b>" . $your_hotel . ".</b><br/><br/>\n";
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

				$outputtext .= "<i>Thank you! Your response has been saved and your invitation code has been used. If you need to change your reply or have any questions, please contact " . $wedding_planner . " at <a href='". $wedding_planner_email_address ."'>". $wedding_planner_email_address ."</a>.</i>";

                $subject = "Wedding RSVP";
                $headers = 'From: '.$wedding_planner_email_address;
                mail($wedding_planner_email_address, $subject, $emailreport, $headers);

			} catch (Exception $e) {
				$outputtext .= "<b>Oops, there was a small glitch.</b> Please try again, or contact ". $wedding_planner ." directly.";
				$outputtext .= "<pre>" . $e->getMessage() . " " . $e->getTraceAsString() . "</pre>";
			}
		break;	
		
		case('edit'):
		try {
		// Connect and retrieve the listFeed for your guestcode
            if (strcmp($guest_code,'') == 0) {
                $abort_and_reprint_form = true;
                break;
            }
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
				if (count($entries) != 1) $plural = "s"; 
				$outputtext .= "Thank you for replying online. We look forward to celebrating with you all! Please indicate whether each member of your party will be attending our wedding day events.<br/><br/><b>We have reserved ".count($entries)." seat".$plural." in your honor.</b><br/>";
				// Initializing the form. And yes, it is one massive form for the whole page. We differentiate among the attendees by adding an index number after each input 'name', e.g. guestname3
				$outputtext .= '<form style="text-align: left" action="'.get_permalink().'" method="post">';

				// We use a for loop here because the index i is important to keep as a row identifier when we update the row information later.
				for ($i = 0; $i < count($entries); $i +=1) {
					$entry = $entries[$i]->getValues();

					// Populating the sub-parts of the form for each guest
					$outputtext .= '<b>'.($i+1).'. Name:</b> <input name="guestname'.$i.'" type="text" value="'. htmlspecialchars($entry["guestname"],ENT_QUOTES).'"/>';
					$outputtext .= "<br/>";
					$outputtext .= '<i>Ceremony:</i> <input name="ceremony'.$i.'" type="radio" value="Attending" ';
					if (strcmp($entry["ceremony"],"Attending")==0)
						$outputtext .= "checked='checked' ";
					$outputtext .= "/>Attending   ";
					$outputtext .= '<input name="ceremony'.$i.'" type="radio" value="Not Attending" ';
					if (strcmp($entry["ceremony"],"Not Attending")==0)
						$outputtext .= 'checked="checked" ';
					$outputtext .= "/>Not Attending";
					$outputtext .= "<br/>";
					$outputtext .= '<i>Banquet:</i> <input name="banquet'.$i.'" type="radio" value="Attending" ';
					if (strcmp($entry["banquet"],"Attending")==0)
						$outputtext .= "checked='checked' ";
					$outputtext .= "/>Attending   ";
					$outputtext .= '<input name="banquet'.$i.'" type="radio" value="Not Attending" ';
					if (strcmp($entry["banquet"],"Not Attending")==0)
						$outputtext .= "checked='checked' ";
					$outputtext .= "/>Not Attending";
					$outputtext .= "<br/>\n";
					// Message for guest
					if (!empty($entry["custommessageforguest"])) {
						$outputtext .= '<blockquote>'.htmlspecialchars($entry["custommessageforguest"],ENT_QUOTES).'</blockquote>';
					}
					$outputtext .= '<input type="hidden" name="custommessageforguest'.$i.'" value="'.htmlspecialchars($entry["custommessageforguest"]).'"/>';

					$outputtext .= "<br/>\n";				
					$outputtext .= "<br/>\n";
				}	
				// Some questions for the whole party -- needs some tweaking
				// Blocked Rooms, this doesn't record past information.
				if ( $hotelon ) {
					$outputtext .= "<i>If you are planning to stay at one of the hotels where we have reserved rooms under \"" . $hotel_reservation_name . ",\" please let us know where you will be staying:</i>";
					$outputtext .= "<br/>\n";
	                                for($i=0; $i < $num_hotels; $i++) {
	                                        $outputtext .= "<input name='hotel' type='radio' value='hotel".$i."'/>".$hotel_list[$i];
						$outputtext .= "<br/>\n";
	                                }
	                                $outputtext .= "<input name='hotel' type='radio' value='none'/>Not planning to stay at any of these hotels";
					$outputtext .= "<br/>\n";
					$outputtext .= "<br/>\n";
				}

				// Message from the Guests
				$outputtext .= "<b>Let us know how you're doing. Leave us a note!</b><br/>\n";
				$outputtext .= "<textarea name='messagefromguest'></textarea>"; // Note that old comments are not retrieved. We assume that you will only submit an RSVP once.
				$outputtext .= "<br/>\n";

				// Closing the form
				$outputtext .= "\n";
				$outputtext .= "<input type='hidden' name='motion' value='update'/>";
				$outputtext .= "<input type='hidden' name='guest_code' value='". htmlspecialchars($guest_code,ENT_QUOTES)."'/>";
				$outputtext .= "<br/>\n";
				$outputtext .= "<button type='Submit'>Send my RSVP</button>";
				$outputtext .= " <a href='".get_permalink()."'>Cancel, I will reply later.</a>";
				$outputtext .= "</form>";
			} else {
				$outputtext .= "<b>No match.</b> Please try again. <i>If you have already RSVP'd, your code will no longer work. If you would like to change your response, or if you were not able to respond in the first place, please contact " . $wedding_planner . " at <a href='". $wedding_planner_email_address ."'>". $wedding_planner_email_address ."</a>.</i><br />";
				$outputtext .= "<br/>\n";
				$outputtext .= 'Please type in the code from your wedding invitation below:<br/>';
				$outputtext .= '<form style="text-align: left" action="'.get_permalink().'" method="post">';
				$outputtext .= '<input type="hidden" name="motion" value="edit"/>';
				$outputtext .= '<input type="text" name="guest_code" /> ';
				$outputtext .= '<button type="Submit">Submit</button>';
				$outputtext .= '</form>';
			}

		} catch (Exception $e) {
			$outputtext .= "Oops. You found an error. Please try again or contact " . $wedding_planner . " at <a href='". $wedding_planner_email_address ."'>". $wedding_planner_email_address ."</a> to confirm your response.";
			//$outputtext .= "[There was a error. Please consult the source code or an experienced programmer. :( ]";
			$outputtext .= "<pre>" . $e->getMessage() . " " . $e->getTraceAsString() . "</pre>";
		}
		break;
	} //endswitch on post motion  
	} //endif (isset)
	else {
		
		$outputtext .= "Please type in the code from your wedding invitation below:<br/>";
		$outputtext .= "<form style='text-align: left' action='".get_permalink()."' method='post'>";
		$outputtext .= "<input type='hidden' name='motion' value='edit'/>";
		$outputtext .= "<input type='text' name='guest_code' /> ";
		$outputtext .= "<button type='Submit'>Submit</button>";
		$outputtext .= "</form>";
		
	}
	
	// this is covers cases where someone re-posted an update ... it defends against attacks where someone is spamming the update and trying to overwrite entries
	// TODO This currently covers the case where someone has naively click back and resubmitted a form
	// TODO I haven't checked whether an attacker can use the feedback to determine which codes are valid.
	if ($abort_and_reprint_form) {
		$outputtext .= "<b>Sorry, our records were not changed.</b>";
		$outputtext .= "Please type in the code from your wedding invitation below:<br/>";
		$outputtext .= "<form style='text-align: left' action='".get_permalink()."' method='post'>";
		$outputtext .= "<input type='hidden' name='motion' value='edit'/>";
		$outputtext .= "<input type='text' name='guest_code' /> ";
		$outputtext .= "<button type='Submit'>Submit</button>";
		$outputtext .= "</form>";
	}
	
	$text = str_replace('wpgc-googledocsguestlist', $outputtext, $text);
	return $text;
}
add_action('admin_menu', 'modify_menu');
add_filter('the_content', 'wpgc_my_googledocsguestlist');
?>
