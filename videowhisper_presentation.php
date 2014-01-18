<?php
/*
Plugin Name: VideoWhisper Video Presentation
Plugin URI: http://www.videowhisper.com/?p=WordPress+Video+Presentation
Description: Video Presentation
Version: 3.31.2	
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

			      'landingRoom' => 'lobby',
 			      'lobbyRoom' => 'Lobby',
 			      				
				'camResolution' => '480x360',
				'camFPS' => '15',

				'camBandwidth' => '40960',
				'camMaxBandwidth' => '81920',
								
				'videoCodec'=>'H264',
				'codecProfile' => 'main',
				'codecLevel' => '3.1',
				
				'soundCodec'=> 'Speex',
				'soundQuality' => '9',
				'micRate' => '22',
				
				'serverRTMFP' => 'rtmfp://stratus.adobe.com/f1533cc06e4de4b56399b10d-1a624022ff71/',
				'p2pGroup' => 'VideoWhisper',
				'supportRTMP' => '1',
				'supportP2P' => '0',
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

		
			if (isset($_POST))
			{

				foreach ($options as $key => $value)
					if (isset($_POST[$key])) $options[$key] = $_POST[$key];
					update_option('VWvideoPresentationOptions', $options);
			}
			
		
		$page_id = get_option("vw_vp_page");
		if ($page_id != '-1' && $options['disablePage']!='0') VWvideopresentation::deletePage();
		
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'server';
							
	  ?>
<div class="wrap">
<?php screen_icon(); ?>
<h2>VideoWhisper Video Presentation Settings</h2>

<h2 class="nav-tab-wrapper">
	<a href="<?php echo $_SERVER["REQUEST_URI"]; ?>&tab=server" class="nav-tab <?php echo $active_tab=='server'?'nav-tab-active':'';?>">Server</a>
	<a href="<?php echo $_SERVER["REQUEST_URI"]; ?>&tab=integration" class="nav-tab <?php echo $active_tab=='integration'?'nav-tab-active':''; ?>">Integration</a>
    <a href="<?php echo $_SERVER["REQUEST_URI"]; ?>&tab=video" class="nav-tab <?php echo $active_tab=='video'?'nav-tab-active':'';?>">Video</a>
    <a href="<?php echo $_SERVER["REQUEST_URI"]; ?>&tab=participants" class="nav-tab <?php echo $active_tab=='participants'?'nav-tab-active':''; ?>">Participants</a>
</h2>

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

<?php
			switch ($active_tab)
			{
			case 'server':
?>
<h3>Server and Streaming Settings</h3>
<h4>RTMP Address</h4>
<p>To run this, make sure your hosting environment meets all <a href="http://www.videowhisper.com/?p=Requirements" target="_blank">requirements</a>.  If you don't have a <a href="http://www.videowhisper.com/?p=RTMP+Hosting">videowhisper rtmp address</a> yet (from a managed rtmp host), go to <a href="http://www.videowhisper.com/?p=RTMP+Applications" target="_blank">RTMP Application Setup</a> for  installation details.</p>
<input name="rtmp_server" type="text" id="rtmp_server" size="64" maxlength="256" value="<?=$options['rtmp_server']?>"/>

<?php submit_button(); ?>

<h4>Disable Bandwidth Detection</h4>
<p>Required on some rtmp servers that don't support bandwidth detection and return a Connection.Call.Fail error.</p>
<select name="disableBandwidthDetection" id="disableBandwidthDetection">
  <option value="0" <?=$options['disableBandwidthDetection']?"":"selected"?>>No</option>
  <option value="1" <?=$options['disableBandwidthDetection']?"selected":""?>>Yes</option>
</select>


<h4>RTMFP Address</h4>
<p> Get your own independent RTMFP address by registering for a free <a href="https://www.adobe.com/cfusion/entitlement/index.cfm?e=cirrus" target="_blank">Adobe Cirrus developer key</a>. This is required for P2P support.</p>
<input name="serverRTMFP" type="text" id="serverRTMFP" size="80" maxlength="256" value="<?=$options['serverRTMFP']?>"/>
<h4>P2P Group</h4>
<input name="p2pGroup" type="text" id="p2pGroup" size="32" maxlength="64" value="<?=$options['p2pGroup']?>"/>
<h4>Support RTMP Streaming</h4>
<select name="supportRTMP" id="supportRTMP">
  <option value="0" <?=$options['supportRTMP']?"":"selected"?>>No</option>
  <option value="1" <?=$options['supportRTMP']?"selected":""?>>Yes</option>
</select>
<h4>Always do RTMP Streaming</h4>
<p>Enable this if you want all streams to be published to server, no matter if there are registered subscribers or not (in example if you're using server side video archiving and need all streams published for recording).</p>
<select name="alwaystRTMP" id="alwaystRTMP">
  <option value="0" <?=$options['alwaystRTMP']?"":"selected"?>>No</option>
  <option value="1" <?=$options['alwaystRTMP']?"selected":""?>>Yes</option>
</select>
<h4>Support P2P Streaming</h4>
<select name="supportP2P" id="supportP2P">
  <option value="0" <?=$options['supportP2P']?"":"selected"?>>No</option>
  <option value="1" <?=$options['supportP2P']?"selected":""?>>Yes</option>
</select>
<h4>Always do P2P Streaming</h4>
<select name="alwaysP2P" id="alwaysP2P">
  <option value="0" <?=$options['alwaysP2P']?"":"selected"?>>No</option>
  <option value="1" <?=$options['alwaysP2P']?"selected":""?>>Yes</option>
</select>

<?php
			break;
			case 'integration':
?>
<h4>Username</h4>
<select name="userName" id="userName">
  <option value="display_name" <?=$options['userName']=='display_name'?"selected":""?>>Display Name</option>
  <option value="user_login" <?=$options['userName']=='user_login'?"selected":""?>>Login (Username)</option>
  <option value="user_nicename" <?=$options['userName']=='user_nicename'?"selected":""?>>Nicename</option>  
</select>

<h4>Default landing room</h4>

<select name="landingRoom" id="landingRoom">
  <option value="lobby" <?=$options['landingRoom']=='lobby'?"selected":""?>>Lobby</option>
  <option value="username" <?=$options['landingRoom']=='username'?"selected":""?>>Username</option>

</select>
<BR>Username will allow registered users to start their own rooms on access when no room name is provided.
  
<h4>Lobby room name</h4>
<input name="lobbyRoom" type="text" id="lobbyRoom" size="16" maxlength="16" value="<?=$options['lobbyRoom']?>"/>
<BR>Ex: Lobby

<h4>Easy Access Page</h4>
<p>Add a Video Presentation page to the menu</p>
<select name="disablePage" id="disablePage">
  <option value="0" <?=$options['disablePage']=='0'?"selected":""?>>Yes</option>
  <option value="1" <?=$options['disablePage']=='1'?"selected":""?>>No</option>
</select>

<h4>Show VideoWhisper Powered by</h4>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?=$options['videowhisper']?"":"selected"?>>No</option>
  <option value="1" <?=$options['videowhisper']?"selected":""?>>Yes</option>
</select>

<?php
			break;
			case 'video':
?>
<h4>Default Webcam Resolution</h4>
<select name="camResolution" id="camResolution">
<?php
				foreach (array('160x120','320x240','480x360', '640x480', '720x480', '720x576', '1280x720', '1440x1080', '1920x1080') as $optItm)
				{
?>
  <option value="<?php echo $optItm;?>" <?php echo $options['camResolution']==$optItm?"selected":""?>> <?php echo $optItm;?> </option>
  <?php
				}
?>
 </select>

<h4>Default Webcam Frames Per Second</h4>
<select name="camFPS" id="camFPS">
<?php
				foreach (array('1','8','10','12','15','29','30','60') as $optItm)
				{
?>
  <option value="<?php echo $optItm;?>" <?php echo $options['camFPS']==$optItm?"selected":""?>> <?php echo $optItm;?> </option>
  <?php
				}
?>
 </select>


<h4>Video Stream Bandwidth</h4>
<input name="camBandwidth" type="text" id="camBandwidth" size="7" maxlength="7" value="<?php echo $options['camBandwidth']?>"/> (bytes/s)
<h4>Maximum Video Stream Bandwidth (at runtime)</h4>
<input name="camMaxBandwidth" type="text" id="camMaxBandwidth" size="7" maxlength="7" value="<?php echo $options['camMaxBandwidth']?>"/> (bytes/s)

<h4>Video Codec</h4>
<select name="videoCodec" id="videoCodec">
  <option value="H264" <?=$options['videoCodec']=='H264'?"selected":""?>>H264</option>
  <option value="H263" <?=$options['videoCodec']=='H263'?"selected":""?>>H263</option>  
</select>

<h4>H264 Video Codec Profile</h4>
<select name="codecProfile" id="codecProfile">
  <option value="main" <?=$options['codecProfile']=='main'?"selected":""?>>main</option>
  <option value="baseline" <?=$options['codecProfile']=='baseline'?"selected":""?>>baseline</option>  
</select>

<h4>H264 Video Codec Level</h4>
<input name="codecLevel" type="text" id="codecLevel" size="32" maxlength="64" value="<?=$options['codecLevel']?>"/> (1, 1b, 1.1, 1.2, 1.3, 2, 2.1, 2.2, 3, 3.1, 3.2, 4, 4.1, 4.2, 5, 5.1)

<h4>Sound Codec</h4>
<select name="soundCodec" id="soundCodec">
  <option value="Speex" <?=$options['soundCodec']=='Speex'?"selected":""?>>Speex</option>
  <option value="Nellymoser" <?=$options['soundCodec']=='Nellymoser'?"selected":""?>>Nellymoser</option>  
</select>

<h4>Speex Sound Quality</h4>
<input name="soundQuality" type="text" id="soundQuality" size="3" maxlength="3" value="<?=$options['soundQuality']?>"/> (0-10)

<h4>Nellymoser Sound Rate</h4>
<input name="micRate" type="text" id="micRate" size="3" maxlength="3" value="<?=$options['micRate']?>"/> (11/22/44)


<?php
			break;
			case 'participants':
?>


<h4>Who can access video presentation</h4>
<select name="canAccess" id="canAccess">
  <option value="all" <?=$options['canAccess']=='all'?"selected":""?>>Anybody</option>
  <option value="members" <?=$options['canAccess']=='members'?"selected":""?>>All Members</option>
  <option value="list" <?=$options['canAccess']=='list'?"selected":""?>>Members in List</option>  
</select>
<h4>Members allowed to access video presentation (comma separated roles, BP groups, usernames, emails, IDs)</h4>
<textarea name="accessList" cols="64" rows="3" id="accessList"><?=$options['accessList']?>
</textarea>
<?php
			break;
}

submit_button(); 
?>
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
		if (class_exists('BP_Group_Extension'))	 require( dirname( __FILE__ ) . '/bp.php' );
	}

	add_action( 'bp_init', 'videoPresentationBP_init' );

}
?>
