# Google Docs RSVP, WordPress Plugin
* Contributors: Gifford Cheung, Brian Watanabe, Chongsun Ahn
* Tags: RSVP, guestlist, wedding, Google Docs, spreadsheet
* Requires at least: 2.5
* Tested up to: 4.0.1
* Stable tag: 2.0

This plugin allows you to add RSVP and guestlist functionality. Guests can leave custom messages for the planners. Uses Google Docs spreadsheets.

# Known issue
Guest codes are not case sensitive.

# Installation

1. Create a google docs spreadsheet with the following 7 headers: Guest Name, Code, Custom Message for Guest, Ceremony, Banquet, Message from Guest, Hotel. 
2. Go to "Settings->Google Docs RSVP" to configure. 
3. Add the text: gdrsvp-googledocsrsvp in the content of your RSVP page.

# Description
This plugin allows you to add RSVP and guestlist functionality to your Wordpress site. It tracks RSVPs for ceremony and banquet. Additionally, guests can leave custom messages for the planners. The guestlist is maintained with a Google Docs spreadsheet which is very easy to use.

It was originally designed to be a wedding guestlist that I made for a friend (congratulations to Mike & Di!).

Features Bulletlist:

* Customizable RSVP page
* Connects to Google Docs Spreadsheet for guestlist
* Planners can write custom messages to guests
* Guests can send custom message to planners
* Email updates are sent to the planner
* Wedding features: Records responses for Ceremony, Banquet, or Hotel Reservations

# Instructions
   1. Using a Google account, create a new Spreadsheet at docs.google.com
   2. The spreadsheet must have the following 7 headers: Guest Name, Code, Custom Message for Guest, Ceremony, Banquet, Message from Guest, Hotel
   3. Fill in the guestlist with names, codes, and an optional custom message. Make sure the code is not guessable, for example: short numeric codes are probably a bad idea. A nosy guest might punch in random numbers and see guest information.
   4. Download, unzip, upload, and activate your plugin. 
   5. In your Wordpress site, go to "Settings->Google Docs RSVP" and follow the step-by-step instructions on the page, and fill in the other information (Google Docs title and sheet, etc.).
   6. Create a new wordpress Page and put the text: gdrsvp-googledocsrsvp in the content box. The plugin will replace it with the RSVP code.
   7. Now, guests can type in a code and fill out the reservation form, which will send an email to you and update the spreadsheet. Note: Once guests have filled out the form, their RSVP code is no longer usable.


Thank you! Good luck with your planning efforts. Remember to allow guests to contact you in other ways in case of digital emergencies.

We look forward to any comments. If there is a good response, we may incorporate your suggestions into the next version.

This code is released under GPLv3. If you create a new version of this plugin, let us know and we may link to it.

Thanks!

# Frequently Asked Questions

= My plugin isn't working =

Check the homepage for a lot of comments and responses about how to fiddle
with this plugin, we have had a bit of discussion and help from other users.

Note that you are required to have PHP version 5. Sorry, the only solution right now is to use that version of PHP.

# How do I change some of the text?

If you cannot change the text in the options page, you can change it in the source code (by editting wp-gdrsvp-plugin.php). This is not a very safe thing to do, but you could search the code for the words you want to change and fiddle around with it. This take familiarity with HTML and a little PHP. 
