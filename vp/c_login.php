<?php
include("../../../../wp-config.php");

$options = get_option('VWvideoPresentationOptions');
$rtmp_server = $options['rtmp_server'];
$rtmp_amf = $options['rtmp_amf'];
$userName =  $options['userName']; if (!$userName) $userName='user_nicename';
$canAccess = $options['canAccess'];
$accessList = $options['accessList'];

$serverRTMFP = $options['serverRTMFP'];
$p2pGroup = $options['p2pGroup'];
$supportRTMP = $options['supportRTMP'];
$supportP2P = $options['supportP2P'];
$alwaystRTMP = $options['alwaystRTMP'];
$alwaystP2P = $options['alwaystP2P'];
$disableBandwidthDetection = $options['disableBandwidthDetection'];


	$camRes = explode('x',$options['camResolution']);
	
	
$room=$_GET['room_name'];

  include_once("incsan.php");
  sanV($room);

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
if ($group->admins) if (is_array($group->admins))  
foreach ($group->admins as $usr) if ( $usr->user_login == $current_user->user_login ) $group_admin=1;

if ($group_admin) $administrator=1;

	if ($group_member)
	{
		$userkeys[] = $room;
		$regularCams=1;
		$regularWatch=1;
		$privateTextchat=1;
		
		$extra_info = "<BR><font color=\"#3CA2DE\">&#187;</font> You are group member in this video presentation room. A group administrator is required to manage presentations.";
	}
}

//username
//if ($current_user->$userName) $username=urlencode($current_user->$userName);
//$username=preg_replace("/[^0-9a-zA-Z_]/","-",$username);

$loggedin=0;
$msg="";

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


switch ($canAccess)
{	
	case "all":
	$loggedin=1;
	if (!$username) 
	{
		$username="Guest".base_convert((time()-1224350000).rand(0,10),10,36);
		$visitor=1; //ask for username
	}
	break;
	case "members":
		if ($username) $loggedin=1;
		else $msg=urlencode("<a href=\"/\">Please login first or register an account if you don't have one! Click here to return to website.</a>");
	break;
	case "list";
		if ($username)
			if (inList($userkeys, $accessList)) $loggedin=1;
			else $msg=urlencode("<a href=\"/\">$username, you are not in the video presentation access list.</a>");
		else $msg=urlencode("<a href=\"/\">Please login first or register an account if you don't have one! Click here to return to website.</a>");
	break;
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

$parameters = html_entity_decode($options['parameters']);

if ($administrator)
{

$parameters = html_entity_decode($options['parametersAdmin']);
//&change_background=0&administrator=0&regularCams=0&regularWatch=0&privateTextchat=0&externalStream=0&slideShow=0&publicVideosAdd=<0

$extra_info = "<BR><font color=\"#3CA2DE\">&#187;</font> You are moderator in this video presentation room. You can set any user as main speaker or inquirer on public video panels, show presentation slides, kick users.";
}

$debug="";

//replace bad words or expression
$filterRegex=urlencode("(?i)(fuck|cunt)(?-i)");
$filterReplace=urlencode(" ** ");

//message
$welcome=urlencode( html_entity_decode($options['welcome']) . $extra_info);

?>firstVar=fixed&server=<?=$rtmp_server?>&serverAMF=<?=$rtmp_amf?>&serverRTMFP=<?=urlencode($serverRTMFP)?>&p2pGroup=<?=$p2pGroup?>&supportRTMP=<?=$supportRTMP?>&supportP2P=<?=$supportP2P?>&alwaysRTMP=<?=$alwaysRTMP?>&alwaysP2P=<?=$alwaysP2P?>&disableBandwidthDetection=<?=$disableBandwidthDetection?>&room=<?=$room?>&welcome=<?=$welcome?>&username=<?=$username?>&msg=<?=$message?>&visitor=0&loggedin=<?=$loggedin?>&background_url=<?=urlencode("templates/consultation/background.jpg")?>&camWidth=<?php echo $camRes[0];?>&camHeight=<?php echo $camRes[1];?>&camFPS=<?php echo $options['camFPS']?>&camBandwidth=<?php echo $camBandwidth?>&camMaxBandwidth=<?php echo $camMaxBandwidth?>&videoCodec=<?=$options['videoCodec']?>&codecProfile=<?=$options['codecProfile']?>&codecLevel=<?=$options['codecLevel']?>&soundCodec=<?=$options['soundCodec']?>&soundQuality=<?=$options['soundQuality']?>&micRate=<?=$options['micRate']?>&layoutCode=<?=urlencode(html_entity_decode($options['layoutCode']))?>&filterRegex=<?=$filterRegex?>&filterReplace=<?=$filterReplace?>&loadstatus=1<?php echo $parameters; ?>&debugmessage=<?=urlencode($debug)?>