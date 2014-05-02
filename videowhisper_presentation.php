<?php
/*
Plugin Name: VideoWhisper Video Presentation
Plugin URI: http://www.videowhisper.com/?p=WordPress+Video+Presentation
Description: Video Presentation
Version: 3.31.5	
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


        //shortcodes
           add_shortcode('videowhisperconsultation_hls', array( 'VWvideoPresentation', 'shortcode_hls'));
           add_shortcode('videowhisperconsultation', array( 'VWvideoPresentation', 'shortcode'));

            //ajax
            add_action( 'wp_ajax_vwcns_trans', array('VWvideoPresentation','vwcns_trans') );
            add_action( 'wp_ajax_nopriv_vwcns_trans', array('VWvideoPresentation','vwcns_trans'));


	  
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
		
	$pagecode=<<<ENDCODE
[videowhisperconsultation room=""]
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

       function shortcode($atts)
        {

$atts = shortcode_atts(array('room' => '', 'link' => 1), $atts, 'videowhisperconsultation');

            $room = $atts['room']; 
            if (!$room) $room = $_GET['room'];
            if (!$room) $room = $_GET['r'];
            $room = sanitize_file_name($room);

//iOS?
$agent = $_SERVER['HTTP_USER_AGENT'];
if( strstr($agent,'iPhone') || strstr($agent,'iPod') || strstr($agent,'iPad'))
return do_shortcode("[videowhisperconsultation_hls channel=\"$room\"]");        


  $baseurl="";
  $swfurl=$baseurl."consultation.swf?room=".$room;
  $bgcolor="#333333";
  
            $swfurl = plugin_dir_url(__FILE__) . 'vp/consultation.swf?room=' . urlencode($room);
            $swfurl .= "&prefix=" . urlencode(plugin_dir_url(__FILE__) . 'vp/');
            $swfurl .= '&ws_res=' . urlencode( plugin_dir_url(__FILE__) . 'vp/');

$htmlCode = <<<HTMLCODE
<div id="videowhisper_presentation_$room">
<object width="100%" height="100%" type="application/x-shockwave-flash" data="$swfurl">
<param name="movie" value="$swfurl" /><param name="bgcolor" value="$bgcolor" /><param name="salign" value="lt" /><param name="scale" value="noscale" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /> <param name="base" value="$baseurl" /> <param name="wmode" value="transparent" />
</object>
<noscript>
<p align=center><a href="http://www.videowhisper.com/?p=WordPress+Video+Presentation"><strong>WordPress Live Video Presentation Plugin</strong></a></p>
<p align="center"><strong>This content requires the Adobe Flash Player:
<a href="http://www.macromedia.com/go/getflash/">Get Flash</a></strong>!</p>
</noscript>
</div>
<br style="clear:both" />
<style type="text/css">
<!--

#videowhisper_presentation_$room
{
width: 100%;
height:700px;
background: $bgcolor;
}

-->
</style>
HTMLCODE;

if ($atts['link']) $htmlCode .= "<a class='button' target='_top' href='".plugin_dir_url(__FILE__) . 'vp/?room='.urlencode($room)."'>Open Room in Full Page Layout</a>";

$options = get_option('VWvideoPresentationOptions');

if (!$options['disableTranscoder'])
{
//moderator?
$userName =  $options['userName']; if (!$userName) $userName='user_nicename';

				//username
				global $current_user;
				get_currentuserinfo();
				if ($current_user->$userName) $username=sanitize_file_name($current_user->$userName);

				//access keys
				if ($current_user)
				{
					$userkeys = $current_user->roles;
					$userkeys[] = $current_user->user_login;
					$userkeys[] = $current_user->ID;
					$userkeys[] = $current_user->user_email;
					$userkeys[] = $current_user->display_name;
				}

//get apartenence if used with a BuddyPress group
if ($room) 
if (class_exists('BP_Groups_Group'))
{
$group_id =  BP_Groups_Group::group_exists( $room );
$group = new BP_Groups_Group( $group_id );
$group_member = $group->is_member;

$group_admin=0;
if ($group->admins) if (is_array($group->admins)) foreach ($group->admins as $usr) if ( $usr->user_login == $current_user->user_login ) $group_admin=1;

if ($group_admin) $administrator=1;
}

//username
//if ($current_user->$userName) $username=urlencode($current_user->$userName);
//$username=preg_replace("/[^0-9a-zA-Z_]/","-",$username);


		//if any key matches any listing
		function inList($keys, $data)
		{
			if (!$keys) return 0;

			$list=explode(",", strtolower(trim($data)));

			foreach ($keys as $key)
				foreach ($list as $listing)
					if ( strtolower(trim($key)) == trim($listing) ) return 1;

					return 0;
		}



if (!$room && !$visitor) 
{
	if ($options['landingRoom']=='username') 	//can create	
	{		
	$room=$username;
	$administrator=1;	
	}
	else $room = $options['lobbyRoom']; //or go to default
}
 else if (!$room) $room = $options['lobbyRoom'];  //visitor can't create room
	
//if room name == username -> administrator	
if (!$options['disableModeratorByName']) 
if ($room == $username) $administrator = 1;
	
if (inList($userkeys, $options['moderatorList'])) $administrator = 1;

if ($administrator)
{
$stream = $username;
 
$admin_ajax = admin_url() . 'admin-ajax.php';
  
$htmlCode .= <<<HTMLCODE
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

<div id="vwinfo">
iOS Transcoding (iPhone/iPad)<BR>
<input type="text" id="stream" name="stream" size="24" maxlength="64" value="$stream" class="social-input" ><BR>
<a href='#' class="button" id="transcoderon">ON</a>
<a href='#' class="button" id="transcoderoff">OFF</a>

<div id="result">A stream must be broadcast for transcoder to start.</div>
<p align="right">(<a href="javascript:void(0)" onClick="vwinfo.style.display='none';">hide</a>)</p>
</div>

<style type="text/css">
<!--

#vwinfo
{
	font-family: Verdana;
	font-size: 14px;
	color:#333;

	float: right;
	width: 25%;
	position: absolute;
	bottom: 10px;
	right: 10px;
	text-align:left;
	padding: 10px;
	margin: 10px;
	background-color: #666;
	border: 1px dotted #AAA;
	z-index: 1;

	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#999', endColorstr='#666'); /* for IE */
	background: -webkit-gradient(linear, left top, left bottom, from(#999), to(#666)); /* for webkit browsers */
	background: -moz-linear-gradient(top,  #999,  #666); /* for firefox 3.6+ */

	box-shadow: 2px 2px 2px #333;


	-moz-border-radius: 9px;
	border-radius: 9px;
}

#vwinfo > a {
	color: #F77;
	text-decoration: none;
}

#vwinfo > .button, .button {
	-moz-box-shadow:inset 0px 1px 0px 0px #f5978e;
	-webkit-box-shadow:inset 0px 1px 0px 0px #f5978e;
	box-shadow:inset 0px 1px 0px 0px #f5978e;
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #db4f48), color-stop(1, #944038) );
	background:-moz-linear-gradient( center top, #db4f48 5%, #944038 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#db4f48', endColorstr='#944038');
	background-color:#db4f48;
	border:1px solid #d02718;
	display:inline-block;
	color:#ffffff;
	font-family: Verdana;
	font-size: 12px;
	font-weight:normal;
	font-style:normal;
	text-decoration:none;
	text-align:center;
	text-shadow:1px 1px 0px #810e05;
	padding: 5px;
	margin: 2px;
}
#vwinfo > .button:hover, .button:hover {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #944038), color-stop(1, #db4f48) );
	background:-moz-linear-gradient( center top, #944038 5%, #db4f48 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#944038', endColorstr='#db4f48');
	background-color:#944038;
}

-->
</style>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script type="text/javascript">
	$.ajaxSetup ({
		cache: false
	});
	var ajax_load = "Loading...";

	$("#transcoderon").click(function(){
		$("#result").html(ajax_load).load("$admin_ajax?action=vwcns_trans&task=mp4&room=$room&stream="+ $("#stream").val());
	});

	$("#transcoderoff").click(function(){
	$("#result").html(ajax_load).load("$admin_ajax?action=vwcns_trans&task=close&room=$room&stream="+ $("#stream").val());
	});
</script>
HTMLCODE;
} //end administrator

}//end transcoding

return $htmlCode;
     
        }
        

       function shortcode_hls($atts)
        {
        //[videowhisperconsultation_hls channel="username" width="480px" height="360px" transcoder="1"]
        
            $stream = '';
            $options = get_option('VWvideoPresentationOptions');

            $atts = shortcode_atts(array('channel' => $stream, 'width' => '480px', 'height' => '360px', 'transcoder' =>'1'), $atts, 'videowhisperconsultation_hls');


            if (!$stream) $stream = $atts['channel']; //parameter channel="name"
            if (!$stream) $stream = $_GET['n'];

            $stream = sanitize_file_name($stream);

            $width=$atts['width']; if (!$width) $width = "480px";
            $height=$atts['height']; if (!$height) $height = "360px";

            if (!$stream)
            {
                return "Watch HLS Error: Missing channel name!";
            }

            if ($atts['transcoder'] && !$options['disableTranscoder']) $streamName = "i_$stream";
            else $streamName = $stream;
            
            $streamURL = "${options['httpstreamer']}$streamName/playlist.m3u8";



            $dir = $options['uploadsPath']. "/_thumbs";
            $thumbFilename = "$dir/" . $stream . ".jpg";

            $htmlCode = <<<HTMLCODE
<video id="videowhisper_hls_$stream" width="$width" height="$height" autobuffer autoplay controls poster="">
 <source src="$streamURL" type='video/mp4'>
    <div class="fallback">
	    <p>You must have an HTML5 capable browser with HLS support (Ex. Safari) to open this live stream: $streamURL</p>
	</div>
</video>

HTMLCODE;
            return $htmlCode;
        }



        function vwcns_trans()
        {


            ob_clean();

            $stream = sanitize_file_name($_GET['stream']);
            $room = sanitize_file_name($_GET['room']);

            if (!$stream)
            {
                echo "No stream name provided!";
                return;
            }
            
            if (!$room)
            {
                echo "No room name provided!";
                return;
            }


            $options = get_option('VWvideoPresentationOptions');

            $uploadsPath = $options['uploadsPath'];
            if (!file_exists($uploadsPath)) mkdir($uploadsPath);
            //if (!$uploadsPath) echo "Missing uploadsPath!";

            $upath = $uploadsPath . "/$room/";
            if (!file_exists($upath)) mkdir($upath);

            $rtmp_server=$options['rtmp_server'];

            switch ($_GET['task'])
            {
            case 'mp4':

                if ( !is_user_logged_in() )
                {
                    echo "Not authorised!";
                    exit;
                }

                $cmd = "ps aux | grep '/i_$room -i rtmp'";
                exec($cmd, $output, $returnvalue);
                //var_dump($output);

                $transcoding = 0;

                foreach ($output as $line) if (strstr($line, "ffmpeg"))
                    {
                        $columns = preg_split('/\s+/',$line);
                        echo "Transcoder Already Active (".$columns[1]." CPU: ".$columns[2]." Mem: ".$columns[3].")";
                        $transcoding = 1;
                    }



                if (!$transcoding)
                {

                    global $current_user;
                    get_currentuserinfo();

                    global $wpdb;
                    $postID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . sanitize_file_name($stream) . "' and post_type='consultation' LIMIT 0,1" );

                    if ($options['externalKeysTranscoder'])
                    {
                        $key = md5('vw' . $options['webKey'] . $current_user->ID . $postID);

                        $keyView = md5('vw' . $options['webKey']. $postID);

                        //?session&room&key&broadcaster&broadcasterid
                        $rtmpAddress = $options['rtmp_serverX'] . '?'. urlencode('i_' . $room) .'&'. urlencode($room) .'&'. $key . '&1&' . $current_user->ID . '&videowhisper';
                        $rtmpAddressView = $options['rtmp_server'] . '?'. urlencode('ffmpeg_' . $stream) .'&'. urlencode($room) .'&'. $keyView . '&0&videowhisper';

                    }
                    else
                    {
                        $rtmpAddress = $options['rtmp_serverX'];
                        $rtmpAddressView = $options['rtmp_server'];
                    }

                    echo "Transcoding '$stream' ($postID) to '$room'... <BR>";
                    $log_file =  $upath . "videowhisper_transcoder.log";
                    $cmd = $options['ffmpegPath'] . " -s 480x360 -r 15 -vb 512k -vcodec libx264 -coder 0 -bf 0 -analyzeduration 0 -level 3.1 -g 30 -maxrate 768k -acodec libfaac -ac 2 -ar 22050 -ab 96k -x264opts vbv-maxrate=364:qpmin=4:ref=4 -threads 4 -rtmp_pageurl \"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] . "\" -rtmp_swfurl \"http://".$_SERVER['HTTP_HOST']."\" -f flv \"" .
                        $rtmpAddress . "/i_". $room . "\" -i \"" . $rtmpAddressView ."/". $stream . "\" >&$log_file & ";

                    //echo $cmd;
                    exec($cmd, $output, $returnvalue);
                    exec("echo '$cmd' >> $log_file.cmd", $output, $returnvalue);

                    $cmd = "ps aux | grep '/i_$stream -i rtmp'";
                    exec($cmd, $output, $returnvalue);
                    //var_dump($output);

                    foreach ($output as $line) if (strstr($line, "ffmpeg"))
                        {
                            $columns = preg_split('/\s+/',$line);
                            echo "Transcoder Started (".$columns[1].")<BR>";
                        }

                }

                $admin_ajax = admin_url() . 'admin-ajax.php';

                echo "<BR><a target='_blank' href='".$admin_ajax . "?action=vwcns_trans&task=html5&room=$room&stream=$room'> Preview </a> (open in Safari)";
                break;


            case 'close':
                if ( !is_user_logged_in() )
                {
                    echo "Not authorised!";
                    exit;
                }

                $cmd = "ps aux | grep '/i_$room -i rtmp'";
                exec($cmd, $output, $returnvalue);
                //var_dump($output);

                $transcoding = 0;
                foreach ($output as $line) if (strstr($line, "ffmpeg"))
                    {
                        $columns = preg_split('/\s+/',$line);
                        $cmd = "kill -9 " . $columns[1];
                        exec($cmd, $output, $returnvalue);
                        echo "<BR>Closing ".$columns[1]." CPU: ".$columns[2]." Mem: ".$columns[3];
                        $transcoding = 1;
                    }

                if (!$transcoding)
                {
                    echo "Transcoder not found for '$room'!";
                }

                break;
            case "html5";
?>
<p>iOS live stream link (open with Safari or test with VLC): <a href="<?php echo $options['httpstreamer']?>i_<?php echo $stream?>/playlist.m3u8"><br />
  <?php echo $stream?> Video</a></p>


<p>HTML5 live video embed below should be accessible <u>only in <B>Safari</B> browser</u> (PC or iOS):</p>
<?php
                echo do_shortcode('[videowhisperconsultation_hls channel="'.$stream.'"]');
?>
<p> Due to HTTP based live streaming technology limitations, video can have 15s or more latency. Use a browser with flash support for faster interactions based on RTMP. </p>
<p>Most devices other than iOS, support regular flash playback for live streams.</p>
</div>
<style type="text/css">
<!--
BODY
{
	margin:0px;
	background: #333;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 14px;
	color: #EEE;
	padding: 20px;
}

a {
	color: #F77;
	text-decoration: none;
}
-->
</style>
<?php

                break;
            }
            die;
        }



	
	function getAdminOptions() 
	{			
				$adminOptions = array(
				'disablePage' => '0',
				'userName' => 'display_name',
				'rtmp_server' => 'rtmp://localhost/videowhisper',
				'rtmp_serverX' => 'rtmp://localhost/videowhisper-x',
				'rtmp_amf' => 'AMF3',
				'canAccess' => 'all',
				'accessList' => 'Super Admin, Administrator, Editor, Author, Contributor, Subscriber',
				
				'disableModeratorByName' => '0',
				'moderatorList' => 'Super Admin, Administrator, Editor',

                'disableTranscoder' => '0',
                'httpstreamer' => 'http://localhost:1935/videowhisper-x/',
                'ffmpegPath' => '/usr/local/bin/ffmpeg',
                
                'uploadsPath' => plugin_dir_path(__FILE__) . 'vp/uploads/',
                
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
				'welcome' =>  "Welcome!<BR><font color=\"#3CA2DE\">&#187;</font> Click top bar icons to enable/disable features and panels. <BR><font color=\"#3CA2DE\">&#187;</font> Click any participant from users list for more options depending on your permissions. <BR><font color=\"#3CA2DE\">&#187;</font> Try pasting urls, youtube movie urls, picture urls, emails, twitter accounts as @videowhisper in your text chat. <BR><font color=\"#3CA2DE\">&#187;</font> Download daily chat logs from file list.",
				'layoutCode' => '',
				'parameters' => '&bufferLive=0.2&bufferFull=0.2&bufferLivePlayback=0.2&bufferFullPlayback=0.2&files_enabled=1&file_upload=1&file_delete=1&chat_enabled=1&floodProtection=3&writeText=1&room_limit=200&showTimer=1&showCredit=1&disconnectOnTimeout=1&showCamSettings=1&advancedCamSettings=1&configureSource=1&disableVideo=0&disableSound=0&users_enabled=1&publicVideosN=0&publicVideosMax=8&fillWindow=0&generateSnapshots=1&pushToTalk=1&change_background=0&administrator=0&regularCams=0&regularWatch=0&privateTextchat=0&externalStream=0&slideShow=0&publicVideosAdd=0',
					'parametersAdmin' => '&bufferLive=0.2&bufferFull=0.2&bufferLivePlayback=0.2&bufferFullPlayback=0.2&files_enabled=1&file_upload=1&file_delete=1&chat_enabled=1&floodProtection=3&writeText=1&room_limit=200&showTimer=1&showCredit=1&disconnectOnTimeout=1&showCamSettings=1&advancedCamSettings=1&configureSource=1&disableVideo=0&disableSound=0&users_enabled=1&publicVideosN=0&publicVideosMax=8&fillWindow=0&generateSnapshots=1&pushToTalk=0&change_background=1&administrator=1&regularCams=1&regularWatch=1&privateTextchat=1&externalStream=1&slideShow=1&publicVideosAdd=1',
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
<h4>Server and Streaming Settings</h3>
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

<h4>Transcoder</h4>
<p>If requirements are available, moderators can transcode web based video streams to iOS HLS compatible formats.</p>
<select name="disableTranscoder" id="disableTranscoder">
  <option value="0" <?=$options['disableTranscoder']=='0'?"selected":""?>>Yes</option>
  <option value="1" <?=$options['disableTranscoder']=='1'?"selected":""?>>No</option>
</select>
<BR> This requires the 'Always do RTMP Streaming' Yes so streaming can be started and transcoded without watchers in web applications.

<h4>HTTP Streaming URL</h4>
This is used for accessing transcoded streams on HLS playback. Usually available with <a href="http://www.videowhisper.com/?p=Wowza+Media+Server+Hosting">Wowza Hosting</a> .<br>
<input name="httpstreamer" type="text" id="httpstreamer" size="100" maxlength="256" value="<?php echo $options['httpstreamer']?>"/>
<BR>Application folder must match rtmp application. Ex. http://localhost:1935/videowhisper-x/ works when publishing to rtmp://localhost/videowhisper-x .

<h4>Publish Transcoding to RTMP Address</h4>
<input name="rtmp_serverX" type="text" id="rtmp_serverX" size="64" maxlength="256" value="<?=$options['rtmp_serverX']?>"/>
<br>Can be same as source and must match http setting above.

<h4>FFMPEG Path</h4>
<input name="ffmpegPath" type="text" id="ffmpegPath" size="100" maxlength="256" value="<?php echo $options['ffmpegPath']?>"/>
<BR> Path to latest FFMPEG. Required for transcoding of web based streams. 
<?php
echo "<BR>FFMPEG: ";
$cmd ="/usr/local/bin/ffmpeg -codecs";
exec($cmd, $output, $returnvalue); 
if ($returnvalue == 127)  echo "not detected: $cmd"; else echo "detected";

//detect codecs
if ($output) if (count($output)) 
foreach (array('h264','faac','speex', 'nellymoser') as $cod) 
{
$det=0; $outd="";
echo "<BR>$cod codec: ";
foreach ($output as $outp) if (strstr($outp,$cod)) { $det=1; $outd=$outp; };
if ($det) echo "detected ($outd)"; else echo "missing: please configure and install ffmpeg with $cod";
}
?>

<h4>Uploads Path</h4>
<p>Path where logs and snapshots will be uploaded.</p>
<input name="uploadsPath" type="text" id="uploadsPath" size="80" maxlength="256" value="<?php echo $options['uploadsPath']?>"/>
<br>Not fully implemented. Leave as default.


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
<select name="alwaysRTMP" id="alwaysRTMP">
  <option value="0" <?=$options['alwaysRTMP']?"":"selected"?>>No</option>
  <option value="1" <?=$options['alwaysRTMP']?"selected":""?>>Yes</option>
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
			
			 			$options['welcome'] = htmlentities(stripslashes($options['welcome']));
			$options['layoutCode'] = htmlentities(stripslashes($options['layoutCode']));
   			$options['parameters'] = htmlentities(stripslashes($options['parameters']));
   			$options['parametersAdmin'] = htmlentities(stripslashes($options['parametersAdmin']));


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
<BR>Username will allow registered users to start their own rooms on access when no room name is provided. Enable 'Moderator by Name' option below for them to be able to moderate in their rooms.
  
<h4>Lobby room name</h4>
<input name="lobbyRoom" type="text" id="lobbyRoom" size="16" maxlength="16" value="<?=$options['lobbyRoom']?>"/>
<BR>Ex: Lobby

<h4>Easy Access Page</h4>
<p>Add a Video Presentation page to the menu</p>
<select name="disablePage" id="disablePage">
  <option value="0" <?=$options['disablePage']=='0'?"selected":""?>>Yes</option>
  <option value="1" <?=$options['disablePage']=='1'?"selected":""?>>No</option>
</select>

<h4>Moderator by Name</h4>
<p>When room has same name as user, user becomes moderator.</p>
<select name="disableModeratorByName" id="disableModeratorByName">
  <option value="0" <?=$options['disableModeratorByName']=='0'?"selected":""?>>Yes</option>
  <option value="1" <?=$options['disableModeratorByName']=='1'?"selected":""?>>No</option>
</select>

<h4>Moderators (in all rooms)</h4>
<p>Comma separated roles, BP groups, usernames, emails, IDs</p>
<textarea name="moderatorList" cols="64" rows="3" id="moderatorList"><?=$options['moderatorList']?>
</textarea>

<h4>Welcome Message</h4>
<textarea name="welcome" id="welcome" cols="64" rows="8"><?=$options['welcome']?></textarea>
<br>Shows in chatbox when entering video conference.

<h4>Custom Layout Code</h4>
<textarea name="layoutCode" id="layoutCode" cols="64" rows="8"><?=$options['layoutCode']?></textarea>
<br>Generate by writing and sending "/videowhisper layout" in chat (contains panel positions, sizes, move and resize toggles). Copy and paste code here. 

<h4>Parameters for Participants</h4>
<textarea name="parameters" id="parameters" cols="64" rows="8"><?=$options['parameters']?></textarea>
<br>Documented on <a href="http://www.videowhisper.com/?p=php+video+consultation#customize">PHP Video Consultation</a> edition page.

<h4>Parameters for Moderators</h4>
<textarea name="parametersAdmin" id="parametersAdmin" cols="64" rows="8"><?=$options['parametersAdmin']?></textarea>
<br>Should include special permissions for moderators.
				
<h4>Show VideoWhisper Powered by</h4>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?=$options['videowhisper']?"":"selected"?>>No</option>
  <option value="1" <?=$options['videowhisper']?"selected":""?>>Yes</option>
</select>

<h4>Shortcodes</h4>
<ul>
<li><h5>[videowhisperconsultation room="Lobby" link="1"]</h5>Displays Video Consultation application interface for specified room, with link to open in full page layout.</li>
<li><h5>[videowhisperconsultation_hls channel="username"]</h5>Displays HTML5 HLS video code for specified stream name.</li>
</ul>
           
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
<h4>Members allowed to access video presentation</h4>
<p>Comma separated roles, BP groups, usernames, emails, IDs</p>
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
