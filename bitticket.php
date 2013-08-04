<?php
/*
Plugin Name: BitTicket
Plugin URI: https://bips.me/
Description: Extends WordPress with a event ticket system
Version: 0.1
Author: BIPS
Author URI: https://bips.me

Copyright: © 2013 BIPS.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

add_action('init', 'BitTicket_init');

function BitTicket_init() {
    $labels = array(
        'menu_name' => _x('BitTicket', 'bitticket'),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Ticket System',
        'supports' => array('title', 'editor'),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    register_post_type('bitticket', $args);

	if (get_option('BitTicket_enabled') == 1 && isset($_GET['BitTicket']))
	{
		header('HTTP/1.1 200 OK');

		$hash = hash('sha512', $_POST['transaction']['hash'] . get_option('BitTicket_secret'));

		if ($_POST['hash'] == $hash && $_POST['status'] == 1)
		{
			$event = $_POST['custom']['event'];
			$date = $_POST['custom']['date'];
			$location = $_POST['custom']['location'];

			if (isset($_POST['custom']['custom']) && is_array($_POST['custom']['custom']))
			{
				$event = $_POST['custom']['custom']['event'];
				$date = $_POST['custom']['custom']['date'];
				$location = $_POST['custom']['custom']['location'];
			}

			// base64 encoded ticket image
			$stringim = generateTicket($_POST['invoice'], $event, $date, $location, $_POST['item'], $_POST['custom']['name']);

			sendTicketEmail($stringim, $_POST['custom']['email']);
		}

		exit;
	}
}

function generateTicket($invoice, $event, $date, $location, $item, $name)
{
	// Generate ticket
	$base64 = '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';

	$im = imagecreatefromstring(base64_decode($base64));
	
	$bg = imagecolorallocate($im, 255, 255, 255);
	$textcolor = imagecolorallocate($im, 0, 0, 0);

	imagestring($im, 5, 165, 130, (strlen($event) > 0 ? $event : get_option('BitTicket_event')), $textcolor);
	imagestring($im, 2, 165, 204, (strlen($date) > 0 ? $date : get_option('BitTicket_date')), $textcolor);
	imagestring($im, 2, 165, 248, $item, $textcolor);
	
	$location = explode("\r\n", (strlen($location) > 0 ? $location : get_option('BitTicket_location')));
	$i = 275;
	
	foreach ($location as $l)
	{
		imagestring($im, 2, 230, $i, $l, $textcolor);
		
		$i += 13;
	}
	
	imagestring($im, 2, 165, 353, 'Order #' . $invoice . '. ' . $name, $textcolor);

	$names = explode(' ', $name);
	$i = 100;
	
	foreach ($names as $n)
	{
		imagestring($im, 2, 630, $i, $n, $textcolor);
		
		$i += 13;
	}
	
	imagestring($im, 2, 630, 250, 'Completed', $textcolor);


	// Qr code
	$qr = file_get_contents('https://chart.googleapis.com/chart?chs=70x70&cht=qr&chl=https://bips.me/invoice/' . get_option('BitTicket_uniqid') . '/' . shortID($invoice) . '&chld=L|1&choe=UTF-8');
	$qr = imagecreatefromstring($qr);

	imagecopymerge($im, $qr, 645, 290, 0, 0, 70, 70, 100);
	
	// barcode
	$barcode = file_get_contents('http://www.barcodesinc.com/generator/image.php?code=' . get_option('BitTicket_uniqid'). '-' . $invoice . '&style=453&type=C128B&width=270&height=60&xres=1&font=3');
	$barcode = imagecreatefromstring($barcode);
	$rotated = imagerotate($barcode, -90, 0);

	imagecopymerge($im, $rotated, 87, 95, 1, 1, 58, 268, 100);
	imagecopymerge($im, $barcode, 530, 410, 1, 1, 268, 58, 100);
	

	ob_start();
	imagepng($im, null);
	$stringim = ob_get_contents();
	imagedestroy($im);
	ob_end_clean();

	return base64_encode($stringim);
}

function sendTicketEmail($stringim, $recipient)
{
	// Send Email
	$boundary1 = rand(0,9)."-" . rand(10000000000,9999999999)."-" . rand(10000000000,9999999999)."=:" . rand(10000,99999);
	$boundary2 = rand(0,9)."-" . rand(10000000000,9999999999)."-" . rand(10000000000,9999999999)."=:" . rand(10000,99999);

	$attachment = chunk_split($stringim);

	$domain = substr(get_option('BitTicket_email'), strpos(get_option('BitTicket_email'), '@') + 1);

	$replyto = get_option('BitTicket_email');

	$headers = <<<EOT
From: Ticket System <ticket@{$domain}>
Reply-To: {$replyto}
MIME-Version: 1.0
Content-Type: multipart/mixed;
	boundary="{$boundary1}"
EOT;

			$attachments.=<<<EOT
--{$boundary1}
Content-Type: image/png;
    name="ticket.png"
Content-Transfer-Encoding: base64
Content-Disposition: attachment;
    filename="ticket.png"

$attachment
EOT;

			$body=<<<EOT
This is a multi-part message in MIME format.

--{$boundary1}
Content-Type: multipart/alternative;
    boundary="{$boundary2}"

--{$boundary2}
Content-Type: text/plain;
    charset="windows-1256"
Content-Transfer-Encoding: quoted-printable

Your tickets are attached to this email. Please print and bring them to the event.

--{$boundary2}
Content-Type: text/html;
    charset="windows-1256"
Content-Transfer-Encoding: quoted-printable

 <html>
 <head>
 <title>Hello</title>
 </head>
 <body>
 	<p>Your tickets are attached to this email. Please print and bring them to the event.</p>
 </body>
 </html>

--{$boundary2}--

{$attachments}

--{$boundary1}--
EOT;
	// mail to customer
	if (mail($recipient, 'Order Confirmation', $body, $headers))
	{
		print '1';
	}

	// mail the same to merchant list of emails
	if (strlen(get_option('BitTicket_emails')) > 0)
	{
		$emails = preg_replace('/\s+/', '', get_option('BitTicket_emails'));

		if (mail($emails, 'Order Received', $body, $headers))
		{
			print '2';
		}
	}
}

/* Save BitTicket Options to database */
add_action('save_post', 'BitTicket_save_info');

function BitTicket_save_info($post_id) {


    // verify nonce
    if (!wp_verify_nonce($_POST['BitTicket_nonce'], basename(__FILE__))) {
        return $post_id;
    }

    // check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // check permissions
    if ('bitticket' == $_POST['post_type'] && current_user_can('edit_post', $post_id))
	{

       
    } else {
        return $post_id;
    }
}

add_action('admin_menu', 'BitTicket_plugin_settings');

function BitTicket_plugin_settings() {
    //creecho ate new top-level menu
    add_menu_page('BitTicket', 'BitTicket', 'administrator', 'BitTicket_settings', 'BitTicket_display_settings');
}

function BitTicket_display_settings()
{
	$enabled = (get_option('BitTicket_enabled') == 1 ? 'checked="checked"' : '');

	// Event details
	$EVENT = (get_option('BitTicket_event') != '') ? get_option('BitTicket_event') : '';
	$DATE = (get_option('BitTicket_date') != '') ? get_option('BitTicket_date') : '';
	$LOCATION = (get_option('BitTicket_location') != '') ? get_option('BitTicket_location') : '';

	// Merchant details
    $UNIQID = (get_option('BitTicket_uniqid') != '') ? get_option('BitTicket_uniqid') : '';
	$SECRET = (get_option('BitTicket_secret') != '') ? get_option('BitTicket_secret') : '';
	$EMAIL = (get_option('BitTicket_email') != '') ? get_option('BitTicket_email') : '';
	$EMAILS = (get_option('BitTicket_emails') != '') ? get_option('BitTicket_emails') : '';

    $html = '<div class="wrap">
		<form method="post" name="options" action="options.php">
			<h2>Select Your Settings</h2>' . wp_nonce_field('update-options') . '
			<table width="100%" cellpadding="10" class="form-table">
				<tr>
					<td align="left" scope="row">
						<label>Enabled</label> <input type="checkbox" name="BitTicket_enabled" value="1" ' . $enabled . ' />
					</td> 
				</tr>
				<tr>
					<td align="left" scope="row">
						<h1>Event Details</h1>
						<label>Event</label> <input type="text" name="BitTicket_event" value="' . $EVENT . '" size="70" />
					</td> 
				</tr>
				<tr>
					<td align="left" scope="row">
						<label>Date</label> <input type="text" name="BitTicket_date" value="' . $DATE . '" size="70" />
					</td> 
				</tr>
				<tr>
					<td align="left" scope="row" valign="top">
						<label>Location</label> <textarea name="BitTicket_location" cols="40">' . $LOCATION . '</textarea>
					</td>
				</tr>
				<tr>
					<td align="left" scope="row">
						<h1>Merchant Details</h1>
						<label>Uniqid</label> <input type="text" name="BitTicket_uniqid" value="' . $UNIQID . '" />
					</td> 
				</tr>
				<tr>
					<td align="left" scope="row">
						<label>Merchant Secret</label> <input type="text" name="BitTicket_secret" value="' . $SECRET . '" />
					</td> 
				</tr>
				<tr>
					<td align="left" scope="row">
						<label>Merchant Reply To Email</label> <input type="text" name="BitTicket_email" value="' . $EMAIL . '" />
					</td> 
				</tr>
				<tr>
					<td align="left" scope="row">
						<label>List of emails to forward payment information (Comma-separated)</label><br /><input type="text" name="BitTicket_emails" value="' . $EMAILS . '" size="70" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="hidden" name="action" value="update" />  
				<input type="hidden" name="page_options" value="BitTicket_enabled,BitTicket_event,BitTicket_date,BitTicket_location,BitTicket_uniqid,BitTicket_secret,BitTicket_email,BitTicket_emails" /> 
				<input type="submit" name="Submit" value="Update" />
			</p>
		</form>

		<h1>Ticket preview</h1>

		<img src="data:image/png;base64,' . generateTicket('00001001', '', '', '', 'Attendee', 'John Doe') . '" alt="" />

		<h1>Manually Send</h1>

		<form method="post" name="bitticketsend" action="#sent">
			<label>Invoice number (<small><a href="https://bips.me/merchant/logs" title="" target="_blank">https://bips.me/merchant/logs</a></small>)</label>
			<br />
			<input type="text" name="BitTicket_invoicenumber" size="29" />

			<br /><br />

			<label>Type</label>
			<br />
			<input type="text" name="BitTicket_item" size="29" />

			<br /><br />

			<label>Recipient Email</label>
			<br />
			<input type="text" name="BitTicket_recipient" size="29" />

			<br /><br />

			<label for="name">
				Name
			</label>
			<br />
			<input type="text" id="name" name="name" size="29" />

			<p class="submit">
				<input type="hidden" name="action" value="BitTicket_sendemail" />  
				<input type="submit" name="Submit" value="Send Email" />
			</p>
		</form>
	</div>';

    print $html;

	if ($_POST['action'] == 'BitTicket_sendemail')
	{
		// base64 encoded ticket image
		$stringim = generateTicket($_POST['BitTicket_invoicenumber'], '', '', '', $_POST['BitTicket_item'], $_POST['name']);

		sendTicketEmail($stringim, $_POST['BitTicket_recipient']);

		print '<div id="sent">Sent</div>';
	}
}

function shortID($in, $to_num = false, $pad_up = 2, $passKey = null){$index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";if ($passKey !== null) {for ($n = 0; $n<strlen($index); $n++) {$i[] = substr( $index,$n ,1);}$passhash = hash('sha256',$passKey);$passhash = (strlen($passhash) < strlen($index))?hash('sha512',$passKey): $passhash;for ($n=0; $n < strlen($index); $n++) {$p[] =  substr($passhash, $n ,1);}array_multisort($p,  SORT_DESC, $i);$index = implode($i);}$base  = strlen($index);if ($to_num) {$in  = strrev($in);$out = 0;$len = strlen($in) - 1;for ($t = 0; $t <= $len; $t++) {$bcpow = pow($base, $len - $t);$out= $out + strpos($index, substr($in, $t, 1)) * $bcpow;}if (is_numeric($pad_up)) {$pad_up--;if ($pad_up > 0) {$out -= pow($base, $pad_up);}}$out = sprintf('%F', $out);$out = substr($out, 0, strpos($out, '.'));} else {if (is_numeric($pad_up)) {$pad_up--;if ($pad_up > 0) {$in += pow($base, $pad_up);}}$out = "";for ($t = floor(log($in, $base)); $t >= 0; $t--){$bcp = pow($base, $t);$a   = floor($in / $bcp) % $base;$out = $out . substr($index, $a, 1);$in  = $in - ($a * $bcp);}$out = strrev($out);}return $out;}
?>
