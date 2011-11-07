<?php
/*
Plugin Name: VideoWhisper Video Presentation
Plugin URI: http://www.videowhisper.com/?p=WordPress+Video+Presentation
Description: Video Presentation
Version: 3.17	
Author: VideoWhisper.com
Author URI: http://www.videowhisper.com/
Contributors: videowhisper, VideoWhisper.com
*/

if (!class_exists("VWvideoPresentation")) 
{
	
 class VWvideoPresentation 
 {
        
	function VWvideoPresentation() 
	{ //constructor	
    }
	
	function settings_link($links) {
	  $settings_link = '<a href="options-general.php?page=videowhisper_presentation.php">'.__("Settings").'</a>';
	  array_unshift($links, $settings_link);
	  return $links;
	}
	
	function init()
	{
	    $plugin = plugin_basename(__FILE__);
	    add_filter("plugin_action_links_$plugin",  array('VWvideoPresentation','settings_link') );
	  
	    wp_register_sidebar_widget('videoPresentationWidget','VideoWhisper Presentation', array('VWvideoPresentation', 'widget') );
	  
	    //check db
	  	$vw_dbvp_version = "1.1";

		global $wpdb;
		$table_name = $wpdb->prefix . "vw_vpsessions";
			
		$installed_ver = get_option( "vw_dbvp_version" );

		if( $installed_ver != $vw_dbvp_version ) 
		{
		$wpdb->flush();
		
		$sql = "DROP TABLE IF EXISTS `$table_name`;
		CREATE TABLE `$table_name` (
		  `id` int(11) NOT NULL auto_increment,
		  `session` varchar(64) NOT NULL,
		  `username` varchar(64) NOT NULL,
		  `room` varchar(64) NOT NULL,
		  `message` text NOT NULL,
		  `sdate` int(11) NOT NULL,
		  `edate` int(11) NOT NULL,
		  `status` tinyint(4) NOT NULL,
		  `type` tinyint(4) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `status` (`status`),
		  KEY `type` (`type`),
		  KEY `room` (`room`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Video Whisper: Sessions - 2009@videowhisper.com' AUTO_INCREMENT=1 ;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		if (!$installed_ver) add_option("vw_dbvp_version", $vw_dbvp_version);
		else update_option( "vw_dbvp_version", $vw_dbvp_version );
			
		$wpdb->flush();
		}			
		
		$options = VWvideoPresentation::getAdminOptions();
		
		$page_id = get_option("vw_vp_page");
		if (!$page_id || ($page_id=="-1" && $options['disablePage']=='0')) add_action('wp_loaded', array('VWvideoPresentation','updatePage'));
		
	}
	
	function updatePage()
	{
		
	$root_url = get_bloginfo( "url" ) . "/";
		
	$baseurl = $root_url . "wp-content/plugins/videowhisper-video-presentation/vp/";
	$swfurl = $baseurl . "consultation.swf?room=" . $roomname;
	$bgcolor="#051e43";
	
	$pagecode=<<<ENDCODE
	<div id="videopresentation_container" style="height:650px">
	<object width="100%" height="100%">
	<param name="movie" value="$swfurl" /><param name="bgcolor" value="$bgcolor" /><param name="salign" value="lt" /><param name="scale" value="noscale" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /> <param name="base" value="$baseurl" /> <embed width="100%" height="100%" scale="noscale" salign="lt" src="$swfurl" bgcolor="$bgcolor" base="$baseurl" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true"></embed>
	</object>
	<noscript>
	<p align=center><a href="http://www.videowhisper.com/?p=WordPress+Video+Presentation"><strong>VideoWhisper Video Presentation Software</strong></a></p>
	<p align="center"><strong>This content requires the Adobe Flash Player:
	<a href="http://www.macromedia.com/go/getflash/">Get Flash</a></strong>!</p>
	</noscript>
	</div>
	<p><a href="$baseurl">Click here to open video presentation interface on a full page!</a></p>
ENDCODE;
		

		
		global $user_ID;
		$page = array();
		$page['post_type']    = 'page';
		$page['post_content'] = $pagecode;
		$page['post_parent']  = 0;
		$page['post_author']  = $user_ID;
		$page['post_status']  = 'publish';
		$page['post_title']   = 'Video Presentation';
		
		$page_id = get_option("vw_vp_page");
		if ($page_id>0) $page['ID'] = $page_id;
			
		$pageid = wp_insert_post ($page);
	
		update_option( "vw_vp_page", $pageid);
	}
	
	function deletePage()
	{
		$page_id = get_option("vw_vp_page");
		if ($page_id > 0) 
		{
		wp_delete_post($page_id);
		update_option( "vw_vp_page", -1);
		}
	}
	
	function widgetContent()
	{

		global $wpdb;
		$table_name = $wpdb->prefix . "vw_vpsessions";
		
		$root_url = get_bloginfo( "url" ) . "/";
		$raw_url = $root_url . "wp-content/plugins/videowhisper-video-presentation/vp/";
		
		$page_id = get_option("vw_vp_page");
		if ($page_id > 0) $permalink = get_permalink( $page_id );		
		else $permalink = $raw_url;
			 
		//clean recordings
		$exptime=time()-30;
		$sql="DELETE FROM `$table_name` WHERE edate < $exptime";
		$wpdb->query($sql);
			
		$wpdb->flush();
		
		$items =  $wpdb->get_results("SELECT room as room, count(*) as users FROM `$table_name` where status='1' and type='1' GROUP BY room ORDER BY users DESC");

		echo "<ul>";
		if ($items)	foreach ($items as $item) echo "<li><B><a href='$raw_url?room=".urlencode($item->room)."' target='_blank'>".$item->room."</a></B> (" . $item->users .")</a></li>";
		else echo "<li>No active presentation rooms.</li>";
		echo "</ul>";

	?><a href="<?php echo $permalink; ?>"><img src="<?php echo $root_url; ?>wp-content/plugins/videowhisper-video-presentation/vp/templates/consultation/i_webcam.png" align="absmiddle" border="0">Enter Presentation</a>
	<?
	
		$options = get_option('VWvideoPresentationOptions');
		$state = 'block' ;
		if (!$options['videowhisper']) $state = 'none';	
		echo '<div id="VideoWhisper" style="display: ' . $state . ';"><p>Powered by VideoWhisper <a href="http://www.videowhisper.com/?p=WordPress+Video+Presentation">Live Video Presentation Software</a>.</p></div>';
		
	}
	
	function widget($args) 
	{
	  extract($args);
	  echo $before_widget;
	  echo $before_title;?>Video Presentation<?php echo $after_title;
	  VWvideopresentation::widgetContent();
	  echo $after_widget;
	}

	function menu() {
	  add_options_page('Video Presentation Options', 'Video Presentation', 9, basename(__FILE__), array('VWvideoPresentation', 'options'));
	}
	
	function getAdminOptions() 
	{			
				$adminOptions = array(
				'disablePage' => '0',
				'userName' => 'display_name',
				'rtmp_server' => 'rtmp://localhost/videowhisper',
				'rtmp_amf' => 'AMF3',
				'canAccess' => 'all',
				'accessList' => '',
				
				'serverRTMFP' => 'rtmfp://stratus.adobe.com/f1533cc06e4de4b56399b10d-1a624022ff71/',
				'p2pGroup' => 'VideoWhisper',
				'supportRTMP' => '1',
				'supportP2P' => '1',
				'alwaysRTMP' => '0',
				'alwaysP2P' => '0',
				'disableBandwidthDetection' => '0',
				'videowhisper' => 0
				);
			
				$options = get_option('VWvideoPresentationOptions');
				if (!empty($options)) {
					foreach ($options as $key => $option)
						$adminOptions[$key] = $option;
				}            
				update_option('VWvideoPresentationOptions', $adminOptions);
				return $adminOptions;
	}
	
	function options() 
	{
		$options = VWvideopresentation::getAdminOptions();

		if (isset($_POST['updateSettings'])) 
		{
				if (isset($_POST['rtmp_server'])) $options['rtmp_server'] = $_POST['rtmp_server'];
				if (isset($_POST['rtmp_amf'])) $options['rtmp_amf'] = $_POST['rtmp_amf'];
				if (isset($_POST['disablePage'])) $options['disablePage'] = $_POST['disablePage'];
				if (isset($_POST['userName'])) $options['userName'] = $_POST['userName'];
				if (isset($_POST['canAccess'])) $options['canAccess'] = $_POST['canAccess'];
				if (isset($_POST['accessList'])) $options['accessList'] = $_POST['accessList'];
				
				if (isset($_POST['serverRTMFP'])) $options['serverRTMFP'] = $_POST['serverRTMFP'];
				if (isset($_POST['p2pGroup'])) $options['p2pGroup'] = $_POST['p2pGroup'];
				if (isset($_POST['supportRTMP'])) $options['supportRTMP'] = $_POST['supportRTMP'];
				if (isset($_POST['supportP2P'])) $options['supportP2P'] = $_POST['supportP2P'];
				if (isset($_POST['alwaystRTMP'])) $options['alwaystRTMP'] = $_POST['alwaystRTMP'];
				if (isset($_POST['alwaystP2P'])) $options['alwaystP2P'] = $_POST['alwaystP2P'];
				if (isset($_POST['disableBandwidthDetection'])) $options['disableBandwidthDetection'] = $_POST['disableBandwidthDetection'];
				if (isset($_POST['videowhisper'])) $options['videowhisper'] = $_POST['videowhisper'];
				
				update_option('VWvideoPresentationOptions', $options);
		}
		
		$page_id = get_option("vw_vp_page");
		if ($page_id != '-1' && $options['disablePage']!='0') VWvideopresentation::deletePage();
				
	  ?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>VideoWhisper Video presentation Settings</h2>
</div>

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

<h3>General Settings</h3>
<h5>RTMP Address</h5>
<p>To run this, make sure your hosting environment meets all <a href="http://www.videowhisper.com/?p=Requirements" target="_blank">requirements</a>.  If you don't have a videowhisper rtmp address yet (from a managed rtmp host), go to <a href="http://www.videowhisper.com/?p=RTMP+Applications" target="_blank">RTMP Application Setup</a> for  installation details.</p>
<input name="rtmp_server" type="text" id="rtmp_server" size="64" maxlength="256" value="<?=$options['rtmp_server']?>"/>
<h5>Username</h5>
<select name="userName" id="userName">
  <option value="display_name" <?=$options['userName']=='display_name'?"selected":""?>>Display Name</option>
  <option value="user_login" <?=$options['userName']=='user_login'?"selected":""?>>Login (Username)</option>
  <option value="user_nicename" <?=$options['userName']=='user_nicename'?"selected":""?>>Nicename</option>  
</select>

<h5>Disable Bandwidth Detection</h5>
<p>Required on some rtmp servers that don't support bandwidth detection and return a Connection.Call.Fail error.</p>
<select name="disableBandwidthDetection" id="disableBandwidthDetection">
  <option value="0" <?=$options['disableBandwidthDetection']?"":"selected"?>>No</option>
  <option value="1" <?=$options['disableBandwidthDetection']?"selected":""?>>Yes</option>
</select>
<h5>Show VideoWhisper Powered by</h5>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?=$options['videowhisper']?"":"selected"?>>No</option>
  <option value="1" <?=$options['videowhisper']?"selected":""?>>Yes</option>
</select>

<h5>RTMFP Address</h5>
<p> Get your own independent RTMFP address by registering for a free <a href="https://www.adobe.com/cfusion/entitlement/index.cfm?e=cirrus" target="_blank">Adobe Cirrus developer key</a>. This is required for P2P support.</p>
<input name="serverRTMFP" type="text" id="serverRTMFP" size="80" maxlength="256" value="<?=$options['serverRTMFP']?>"/>
<h5>P2P Group</h5>
<input name="p2pGroup" type="text" id="p2pGroup" size="32" maxlength="64" value="<?=$options['p2pGroup']?>"/>
<h5>Support RTMP Streaming</h5>
<select name="supportRTMP" id="supportRTMP">
  <option value="0" <?=$options['supportRTMP']?"":"selected"?>>No</option>
  <option value="1" <?=$options['supportRTMP']?"selected":""?>>Yes</option>
</select>
<h5>Always do RTMP Streaming</h5>
<p>Enable this if you want all streams to be published to server, no matter if there are registered subscribers or not (in example if you're using server side video archiving and need all streams published for recording).</p>
<select name="alwaystRTMP" id="alwaystRTMP">
  <option value="0" <?=$options['alwaystRTMP']?"":"selected"?>>No</option>
  <option value="1" <?=$options['alwaystRTMP']?"selected":""?>>Yes</option>
</select>
<h5>Support P2P Streaming</h5>
<select name="supportP2P" id="supportP2P">
  <option value="0" <?=$options['supportP2P']?"":"selected"?>>No</option>
  <option value="1" <?=$options['supportP2P']?"selected":""?>>Yes</option>
</select>
<h5>Always do P2P Streaming</h5>
<select name="alwaysP2P" id="alwaysP2P">
  <option value="0" <?=$options['alwaysP2P']?"":"selected"?>>No</option>
  <option value="1" <?=$options['alwaysP2P']?"selected":""?>>Yes</option>
</select>


<h5>Disable Page</h5>
<p>Add a Video Presentation page to the menu</p>
<select name="disablePage" id="disablePage">
  <option value="0" <?=$options['disablePage']=='0'?"selected":""?>>Yes</option>
  <option value="1" <?=$options['disablePage']=='1'?"selected":""?>>No</option>
</select>
<h5>Who can access video presentation</h5>
<select name="canAccess" id="canAccess">
  <option value="all" <?=$options['canAccess']=='all'?"selected":""?>>Anybody</option>
  <option value="members" <?=$options['canAccess']=='members'?"selected":""?>>All Members</option>
  <option value="list" <?=$options['canAccess']=='list'?"selected":""?>>Members in List</option>  
</select>
<h5>Members allowed to access video presentation</h5>
<textarea name="accessList" cols="64" rows="3" id="accessList"><?=$options['accessList']?>
</textarea>
<div class="submit">
  <input type="submit" name="updateSettings" id="updateSettings" value="<?php _e('Update Settings', 'VWvideoPresentation') ?>" />
</div>
</form>
	 <?
	}
	
 }
}

//instantiate
if (class_exists("VWvideoPresentation")) {
    $videoPresentation = new VWvideoPresentation();
}

//Actions and Filters   
if (isset($videoPresentation)) 
{
	add_action("plugins_loaded", array(&$videoPresentation , 'init'));
	add_action('admin_menu', array(&$videoPresentation , 'menu'));
	
	/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */
	function videopresentationBP_init() 
	{
		require( dirname( __FILE__ ) . '/bp.php' );
	}

	add_action( 'bp_init', 'videoPresentationBP_init' );

}
?>
