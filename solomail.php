<?php
/*
Plugin Name: SoloMail
Plugin URI: http://birdhouse.org/software
Description: Send single posts via HTML email to registered users.
Author: Scot Hacker
Version: 1.2.0
Author URI: http://about.me/shacker
*/

/*  Copyright 2011  Scot Hacker  (email : shacker@birdhouse.org)

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

//////////


# Functions in pluggable.php will be needed for permissions checking
require_once(ABSPATH . "/wp-includes/pluggable.php");	


// Add a SoloMail section to Post view in the Dashboard
if ( current_user_can('manage_options') ) {
	add_action('add_meta_boxes', 'solomail_add_metabox');
}

function solomail_add_metabox() {
    add_meta_box( 'solomail_sectionid', __( 'Send via SoloMail', 'solomail_textdomain' ), 
                'solomail_inner_custom_box', 'post', 'side', 'high' );
}

/* Prints the box content */
function solomail_inner_custom_box() {
  // global $post_ID;	
  // echo "<a class=\"button button-highlighted\" href=\"solomail.php?id=$post_ID\"  id=\"solomail-preview\" tabindex=\"4\">Preview and post email</a>";
  echo '
	<p>When checked, this post will be sent via email when post is updated.
	See sending options in SoloMail settings.</p>
	<label for="solomail_send_active" style="padding:2px 0;"><input type="checkbox" name="solomail_send_active" value="1" /> Send mail when post is updated. </label>	
	';
}


///////
// Handles actual sending of post
function solomail_send() {
	
	// Before sending mail, make sure the current user is an admin AND the Send checkbox is enabled.
	if( (isset($_POST['solomail_send_active'])) && ( current_user_can('manage_options') ) ) {

		// This function is called when page is updated, using the WP post_save function. 
		// But post_save actually gets called twice! http://www.technokinetics.com/double-saving-with-save-post-hook/
		// To prevent it from sending out duplicate emails, test against a global $flag variable.

		global $flag;
		if ($flag == 0) {

			// $post_ID will be passed in from global env
			global $post_ID;	

			// Get SoloMail settings from db
		    $solomail_send_type = get_option('solomail_send_type');
		    $solomail_excerpt_full = get_option('solomail_excerpt_full');		
		    $solomail_post_addr = get_option('solomail_post_addr');	
		    $solomail_subj_prefix = get_option('solomail_subj_prefix');	


			// Send to all subscribers or to single addr? Either way 
			// we'll store it in an array so we can loop through $recips when sending message.
			$recips = array();

			if ($solomail_send_type == 1) {
				// Query the database for all registered users
				global $wpdb;
				$emails = $wpdb->get_results("SELECT user_email FROM $wpdb->users");
				foreach($emails as $useremail) {
					$recips[] = $useremail->user_email;
				}

			} else {
				// Send to the single listed address
				$recips[] = $solomail_post_addr;			
			}


			// Uncomment for debugging - see who's on the recipient list
			// print_r($recips);
			

			// Meta-data for the post and its author
			$the_post = get_post($post_ID); // Object representing the post
			$post_author_id = $the_post->post_author; // Post's author ID
			$author_info = get_userdata($post_author_id); // Object representing the author
			$author_name = $author_info->first_name .  " " . $author_info->last_name; // String representing author's first and last name


			// Send message with PHPMailer, which should exist in all modern WP installs. 
			// PHPMailer lets us send multipart (text+html) email and to optionally use SMTP server settings.
			// Build its path starting from ABSPATH as defined in wp-config, and instantiate from it as $mailer.
			require_once(ABSPATH . "/wp-includes/class-phpmailer.php");	
			if (class_exists('PHPMailer')) {
			    $mailer = new PHPMailer();
				$mailer->Subject = "[" . $solomail_subj_prefix . "] " . $the_post->post_title;
				$mailer->From = get_bloginfo('admin_email');
				$mailer->FromName = $author_name;
	
				// Set up HTML and plain text alternatives
				// Establish vars with WP built-ins, filters, etc.
				// Run post_content through wpautop to add paragraphs etc., just like a web post.
				
				$stylesheet_url = get_bloginfo('template_url') . "/solomail-styles.css";
				$site_url = get_bloginfo('url');
				$site_title = get_bloginfo('name');						
				$post_body = wpautop($the_post->post_content);

				// For text-only: Keep the original line breaks but remove all HTML tags from the text version.
				$text_content = strip_tags($the_post->post_content);

				// If user has chosen to show excerpts only, remove everything after the 'More' tag.
				// No, it's not possible to do this with the WP API (get_the_content() doesn't work for single post views),
				// So we bypass it with a manual ereg_replace. This is not working for the plain text version because
				// the More tag is already stripped out at this point. Not sure what the solution to that is.
				if ($solomail_excerpt_full == 2) {
					$post_body = ereg_replace('<!--more-->.*', '', $post_body);
					$text_content = ereg_replace('<!--more-->.*', '', $text_content);					
				}
				
		
				$post_title = $the_post->post_title;
				$post_permalink = get_permalink($post_ID);
				$post_date = get_the_time('F jS, Y', $post_ID);
				$post_author_id = $the_post->post_author;
				$post_author = get_the_author_meta('first_name',$post_author_id) . " " . get_the_author_meta('last_name',$post_author_id);
		
				// Read the template files into vars
				$htmlTemplate = file_get_contents(get_bloginfo('template_url') . "/solomail.html");
				$textTemplate = file_get_contents(get_bloginfo('template_url') . "/solomail.txt");			
		
				// Search/replace vars in the templates with context data
				$htmlBody = str_replace("{{stylesheet_url}}", $stylesheet_url, $htmlTemplate);
				$htmlBody = str_replace("{{site_url}}", $site_url, $htmlBody);
				$htmlBody = str_replace("{{site_title}}", $site_title, $htmlBody);			
				$htmlBody = str_replace("{{newsletter_title}}", $solomail_subj_prefix, $htmlBody);
				$htmlBody = str_replace("{{post_body}}", $post_body, $htmlBody);
				$htmlBody = str_replace("{{post_title}}", $post_title, $htmlBody);
				$htmlBody = str_replace("{{post_permalink}}", $post_permalink, $htmlBody);			
				$htmlBody = str_replace("{{post_date}}", $post_date, $htmlBody);
				$htmlBody = str_replace("{{post_author}}", $post_author, $htmlBody);
		
				// Stupid to duplicate this block with one above but copping out for now
				$textBody = str_replace("{{site_url}}", $site_url, $textTemplate);
				$textBody = str_replace("{{site_title}}", $site_title, $textBody);			
				$textBody = str_replace("{{newsletter_title}}", $solomail_subj_prefix, $textBody);
				$textBody = str_replace("{{post_body}}", $text_content, $textBody);
				$textBody = str_replace("{{post_title}}", $post_title, $textBody);
				$textBody = str_replace("{{post_permalink}}", $post_permalink, $textBody);			
				$textBody = str_replace("{{post_date}}", $post_date, $textBody);
				$textBody = str_replace("{{post_author}}", $post_author, $textBody);
				$textBody = html_entity_decode($textBody, ENT_QUOTES); // Replace HTML entities with characters									
		

				$mailer->Body = $htmlBody;
				$mailer->isHTML(true);
				$mailer->AltBody = $textBody;
	
				// Add recipients
				if ($solomail_send_type == 1) {
					// If sending to registered users, To: is the blog admin and we bcc: everyone else.	
					$mailer->AddAddress(get_bloginfo('admin_email'));					
					foreach ($recips as $recip) {
						$mailer->AddBCC($recip);
					}

				} else {
					// Otherwise we send a single message to the recipient address.
					foreach ($recips as $recip) {
						$mailer->AddAddress($recip);
					}			
				}

				// Send it!
				$mailer->Send();
			}	
		}
		$flag = 1;			
	}

}

/////////////////

// Set up actions and Options menu item

// Trigger main sending function when the post is updated.
// (but the solomail_send function only fires if the checkbox is ON)
add_action('save_post', 'solomail_send');

// Add settings page to menu
add_action('admin_menu', 'solomail_menu');

function solomail_menu() {
  add_options_page('SoloMail Options', 'SoloMail', 'manage_options', 'solomail', 'solomail_options');
}



//////////////////
// Handle settings
function solomail_options() {

    // Permissions check
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

	// Required to trigger POST submit
    $hidden_field_name = 'solo_submit_hidden';

    // Read in existing option values from database
    $solomail_send_type = get_option('solomail_send_type');
    $solomail_excerpt_full = get_option('solomail_excerpt_full');
    $solomail_post_addr = get_option('solomail_post_addr');
    $solomail_subj_prefix = get_option('solomail_subj_prefix');

    // See if the user has posted - if so, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
	
        // Read the posted value
        $solomail_send_type = $_POST[ 'solomail_send_type' ];
        $solomail_excerpt_full = $_POST[ 'solomail_excerpt_full' ];
        $solomail_post_addr = $_POST[ 'solomail_post_addr' ];
        $solomail_subj_prefix = $_POST[ 'solomail_subj_prefix' ];

        // Save the posted value in the database
        update_option( 'solomail_send_type', $solomail_send_type );
        update_option( 'solomail_excerpt_full', $solomail_excerpt_full );
        update_option( 'solomail_post_addr', $solomail_post_addr );
        update_option( 'solomail_subj_prefix', $solomail_subj_prefix );

        // Report success:
?>
<div class="updated"><p><strong><?php _e('Settings saved.', 'solomail-menu' ); ?></strong></p></div>
<?php

    }
    ?>

<div class="wrap">
<h2>SoloMail Settings</h2>

<p>SoloMail lets you email individual posts either to all registered users of this site or to a single address (such as a mailing list the site administrator has permission to post to).</p>


<form name="solomail-settings" method="post" action="">

<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<p>
<strong>Subscription group type:</strong><br />
<input type="radio" name="solomail_send_type" value="1" <?php if ($solomail_send_type == "1"): ?>checked="checked"<?php endif ?>> Registered users<br />
<input type="radio" name="solomail_send_type" value="2" <?php if ($solomail_send_type == "2" || $solomail_send_type == ""): ?>checked="checked"<?php endif ?>> Single address<br />
<em>Do you want to send posts to all registered users of this site, or to a single address such as a mailing list?</em>
</p>

<p>
<strong>Send excerpts or full posts?</strong><br />
<input type="radio" name="solomail_excerpt_full" value="1" <?php if ($solomail_excerpt_full == "1"): ?>checked="checked"<?php endif ?>> Send full posts<br />
<input type="radio" name="solomail_excerpt_full" value="2" <?php if ($solomail_excerpt_full == "2" || $solomail_excerpt_full == ""): ?>checked="checked"<?php endif ?>> Break posts at the "More" tag if present<br />
<em>Should emails include the full post, or an excerpt? (Excerpts break at the "More" tag)</em>
</p>

<p><strong>Post address:</strong>
<input type="text" name="solomail_post_addr" value="<?php echo $solomail_post_addr; ?>" size="40"><br />
<em>If Post Type is set to Single Address, emails will be sent to this address.</em>
</p>

<p><strong>Subject prefix:</strong>
<input type="text" name="solomail_subj_prefix" value="<?php echo $solomail_subj_prefix; ?>" size="40"><br />
<em>Text to prepend to subject line, e.g. [Joe's Infoblog Update]:</em>
</p>

<hr />

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
</p>

</form>
</div>

<?php
 
}


?>
