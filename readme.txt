=== SoloMail ===
Contributors: shacker
Donate link: http://birdhouse.org/software
Tags: email
Requires at least: 3.0
Tested up to: 3.0.4
Stable tag: 1.3

Emails an HTML-formatted copy of a single post (not batch/digests) to site subscribers.

== Description ==

SoloMail is a WordPress plugin designed to email an HTML-formatted copy of a single post to site subscribers or a mailing list. There are quite a few solutions out there designed to send batch digests of recent posts to subscribers on a scheduled basis. SoloMail solves a different problem - you may want to cherry pick just certain posts to go out via email, and you may want the ability to "send now."

The subscriber list can EITHER be the group of all registered users on a site OR a single address, such as a mailing list. Though you can use SoloMail either way, I recommend sending through a proper mailing list, which provides advantages such as handling and unsubscribing bounced/dead addresses, and metered sending for large lists.

Please send bug reports / requests via 
http://hosting.birdhouse.org/contact/

== Installation ==

1) Upload to wp-content/plugins and activate.

2) Move the two provided templates and one provided stylesheet into your current theme directory:
	solomail.html
	solomail.txt
	solomail-styles.css

	SoloMail sends multi-part email, with HTML format primary and text as a fallback. The templates are stored in your theme directory rather than in the plugin directory so that A) Your customizations won't be overridden by a plugin update and B) you can edit them the same way you edit theme files.
	
	Feel free to use the provided templates as-is or create your own. Note that SoloMail intentionally does not attempt to use the standard WP template system. That's because HTML email has unique needs, and shouldn't have all the header crap and extras that appear on your site. If you've never sent HTML email before, see a tutorial such as http://articles.sitepoint.com/article/code-html-email-newsletters
	
3) Visit the Settings | SoloMail section in your Dashboard. During testing, set it to send to a single address (your own). After testing, change that address to that of a mailing list to which your site administrator's address has permission to post (SoloMail emails will be sent FROM the site administrator's email address). Or select Registered Users for the Subscription Group Type.

4) Edit the provided templates and stylesheet as needed. Don't forget the .txt version - you can test it by selecting the "text only" view in your mail client (if you use Apple Mail, use Cmd-[  and Cmd-] to cycle through the available multipart formats). SquirrelMail is another good way to test the text version. The following set of template variables are available to both versions:

	{{stylesheet_url}}
	{{site_url}}
	{{site_title}}
	{{post_title}}
	{{post_author}}
	{{post_date}}
	{{post_body}}
	{{post_permalink}}
	{{newsletter_title}}

Yes, these are Django-style template variables :)

Note that if you modify a post and send it via SoloMail *in the same step*, the modifications will not be in the email. I'm not sure why this is, but you must save your changes first, *then* send it.

== Frequently Asked Questions ==

= How do I use SoloMail? =

Select a post that's *already been edited and saved*, and that you consider finished. At the top right of the sidebar, you'll see a SoloMail checkbox. Select the checkbox and click the usual Update button on the post page. The email will be sent immediately to the group chosen in SoloMail options. 

The checkbox will not remain checked - it's safe to edit the post again without risk of sending out mail again accidentally. 

Only site administrators can send email with SoloMail. On multi-author sites, administrators can send other authors' posts out, as well as their own.


== Changelog ==


= 1.3 =
* Added option to send either full posts or excerpts.

= 1.2 =
* Fixed bug that caused emails to be sent twice. This was because the WordPress post_save action actually runs twice, so needed to wrap our function in a test for presence of a global var.

= 1.1 =
* Initial release.



