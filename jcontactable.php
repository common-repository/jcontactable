<?php
/*
Plugin Name: jContactable
Description: This plugin adds a jQuery contactable button to wordpress template.
Author: Jacer Omri
Version: 1.0
Author URI: http://jacer.info
*/

//inject contactable code for public
add_action('wp_print_scripts', 'jc_loadit');

//settings for contactable
$jc_settings = array(
	  'jc_name' => 'Name Label'
	, 'jc_email' => 'Email Label'
	, 'jc_dropdownTitle' => 'Dropdown Title'
	, 'jc_dropdownOptions' => 'Dropdown Options'
	, 'jc_message' => 'Message Label'
	, 'jc_submit' => 'Submit Label'
	, 'jc_recievedMsg' => 'Received Message'
	, 'jc_notRecievedMsg' => 'Not Received Message'
	, 'jc_disclaimer' => 'disclaimer'
	, 'jc_hideOnSubmit' => 'Hide on Submit'
);

//register the settings
function register_jcontactable() {
	global $jc_settings;
	add_settings_section('jcontactable-labels', 'Labels Options', 'jcontactable_labels_code', __FILE__);
	add_settings_section('jcontactable-settings', 'Labels Options', 'jcontactable_settings_code', __FILE__);
	foreach($jc_settings as $setting => $name){
		register_setting( 'jcontactable-settings-group', $setting );
		if($setting == 'jc_hideOnSubmit')
			add_settings_field($setting, $name, 'jcontactable_checkbox_code',  __FILE__, 'jcontactable-settings', array($setting));
		else
			add_settings_field($setting, $name, 'jcontactable_text_code',  __FILE__, 'jcontactable-labels', array($setting));
	}
}

function jcontactable_labels_code() {
	echo '<p>' . _e("This section allow you to configure jContactable Labels") . '</p>';
}

function jcontactable_settings_code() {
	echo '<p>' . _e("This section allow you to configure jContactable Options") . '</p>';
}

function jcontactable_text_code(array $args) {
	echo '<input id="'.$args[0].'" name="'.$args[0].'" type="text" value="'.get_option($args[0]).'" size="50" /><br />';
}

function jcontactable_checkbox_code(array $args) {
	echo '<input id="'.$args[0].'" name="'.$args[0].'" type="checkbox"  ' . checked( get_option($args[0]), 1, false ) . ' value="1" /><br />';
}

register_activation_hook( __FILE__, 'jc_set_up_options' );

// create custom plugin settings menu
add_action('admin_menu', 'jc_create_menu');

function jc_create_menu() {
	add_submenu_page('options-general.php', 'jContactable Settings', 'jContactable', 'administrator', __FILE__, 'jcontactable_settings_page'/*,plugins_url('/images/icon.png', __FILE__)*/);
	add_action( 'admin_init', 'register_jcontactable' );
}

function jc_set_up_options(){
	$jc_settings_def = array(
		  'jc_name' => 'Name'
		, 'jc_email' => 'Email'
		, 'jc_dropdownTitle' => 'Issue'
		, 'jc_dropdownOptions' => 	'General, Website bug, Feature request'
		, 'jc_message' => 'Message'
		, 'jc_submit' => 'Send'
		, 'jc_recievedMsg' => 'Thank you for your message'
		, 'jc_notRecievedMsg' => 'Sorry but your message could not be sent, try again later'
		, 'jc_disclaimer' => 'Please feel free to get in touch, we value your feedback'
		, 'jc_hideOnSubmit' => '1'
	);
	foreach($jc_settings_def as $setting => $value)
		add_option($setting, $value);
}

function jcontactable_settings_page() { ?>
	<div class="wrap">
		<h2>jContactable</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'jcontactable-settings-group' );
				  do_settings_sections(__FILE__);
				  submit_button(); ?>
		</form>
	</div><?php 
}


//ajax functions
add_action('wp_ajax_nopriv_send_mail', 'jc_send_mail');
add_action('wp_ajax_send_mail', 'jc_send_mail');

function jc_send_mail() {
	$admin_email = get_bloginfo('admin_email');
	// Assign contact info
	$name = stripcslashes($_POST['name']);
	$emailAddr = stripcslashes($_POST['email']);
	$issue = stripcslashes($_POST['issue']);
	$comment = stripcslashes($_POST['message']);
	$subject = stripcslashes($_POST['subject']);	
	
	// Set headers
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	// Format message
	$contactMessage =  
	"<div>
	<p><strong>Name:</strong> $name <br />
	<strong>E-mail:</strong> $emailAddr <br />
	<strong>Issue:</strong> $issue </p>

	<p><strong>Message:</strong> $comment </p>

	<p><strong>Sending IP:</strong> $_SERVER[REMOTE_ADDR]<br />
	<strong>Sent via:</strong> $_SERVER[HTTP_HOST]</p>
	</div>";

	// Send and check the message status
	@$response = (mail($admin_email, $subject, $contactMessage, $headers) ) ? "success" : "failure" ;
	$output = json_encode(array("response" => $response));
	
	header('content-type: application/json; charset=utf-8');
	die($output);
}

//public output
function jc_loadit(){
	wp_enqueue_style( 'contactable.css', plugins_url( 'contactable.css' , __FILE__ ), array());
	wp_enqueue_script( 'jcontactable.js', plugins_url( 'jquery.contactable.min.js' , __FILE__ ), array('jquery'));
	//wp_enqueue_script( 'contactable.js', plugins_url( 'contactable.js' , __FILE__ ), array('jquery', 'jcontactable.js'));
	add_action( 'wp_footer', function() {?>
	<!--start jcontactable -->
	<div id="jcontactable"><!-- jcontactable html placeholder --></div>
	<!--end jcontactable -->
	<script type="text/javascript">
	//<![CDATA[
	var jc = {
		subject: 'feedback URL:'+location.href,
		url: ajaxurl,
		name: '<?php echo addslashes(get_option('jc_name')); ?>',
		email: '<?php echo addslashes(get_option('jc_email')); ?>',
		dropdownTitle: '<?php echo addslashes(get_option('jc_dropdownTitle')); ?>',
		dropdownOptions: ['<?php echo implode("', '", explode(',', get_option('jc_dropdownOptions'))) ?>'],
		message : '<?php echo addslashes(get_option('jc_message')); ?>',
		submit : '<?php echo addslashes(get_option('jc_submit')); ?>',
		recievedMsg : '<?php echo addslashes(get_option('jc_recievedMsg')); ?>',
		notRecievedMsg : '<?php echo addslashes(get_option('jc_notRecievedMsg')); ?>',
		disclaimer: '<?php echo addslashes(get_option('jc_disclaimer')); ?>',
		hideOnSubmit: <?php echo get_option('jc_hideOnSubmit') ? 'true' : 'false'; ?>
	}
	jQuery(function(){
		jQuery('#jcontactable').contactable(jc);
	});
	//]]>
	</script>
<?php });
}
