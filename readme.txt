=== VideoWhisper Video Presentation ===
Contributors: videowhisper, VideoWhisper.com
Author: VideoWhisper.com
Author URI: http://www.videowhisper.com
Plugin Name: VideoWhisper Video Presentation
Plugin URI: http://www.videowhisper.com/?p=WordPress+Video+Presentation
Donate link: http://www.videowhisper.com/?p=Invest
Tags: video, presentation, consultation, live, conference, sharing, file, chat, webcam, BuddyPress, flash, fms, red5, wowza, audio, video chat, videochat, widget, plugin, media, av, channel, sidebar, cam, group, groups, tab, slide, show, slideshow, moderator, administrator, speaker, encoder, e-learning, P2P, webinar, collaboration
Requires at least: 2.7
Tested up to: 3.8
Stable tag: trunk

Implement live video presentation and consultation rooms for moderated, 2 way, few to many, group video conferencing.

== Description ==
VideoWhisper Video Consultation is a web based video communication solution designed for online video consultations, interactive live presentations, trainings, webinars, coaching and online collaboration.

* Easy install and update as WordPress plugin
* Widget with online rooms
* Configurable landing room: lobby/personal
* BuddyPress group rooms
* Control access by roles, ID, email, BP Group
* Membership site ready

**Moderators** control what participant is displayed on main screen (speaker) and can also add an additional participant (inquirer) to ask questions or assist.

**Participants** can change their public status (i.e. request to speak), upload and download room files, text and video chat depending on setup permissions.


This plugin uses the WordPress username to login existing users. From plugin settings wordpress admin can configure who will be able to use this (everybody, site members, user list).

Includes a widget that displays active rooms (with number of participants) and presentation access link.
A Video Presentation page is added to the website and can be disabled from settings.

There is a settings page with multiple parameters and permissions (what users can access - all, only members, predefined list).

BuddyPress integration: If BuddyPress is installed this will add a Video Presentation tab to the group, where users can video chat and watch the presentations realtime in group room.
Only group admins have moderator and presentation privileges.

Special requirements: This plugin has requirements beyond regular WordPress hosting specifications: a RTMP host is needed for persistent connections to manage live interactions and streaming. More details about this, including solutions are provided on the Installation section pages.

== Installation ==
* Before installing this make sure all hosting requirements are met: http://www.videowhisper.com/?p=Requirements
* Install the RTMP application using these instructions: http://www.videowhisper.com/?p=RTMP+Applications
* Copy this plugin folder to your wordpress installation in your plugins folder. You should obtain wp-content/plugins/videowhisper-video-presentation .
* Enable the plugin from Wordpress admin area and fill the "Settings", including rtmp address there.
* Enable the widget if you want to display active rooms (with number of participants) and Presentation access link. 

== Screenshots ==
1. Live Video Presentation
2. Toolbar Buttons
3. External Video Encoder

== Desktop Sharing / Screen Broadcasting ==
If your users want to broadcast their screen (when playing a game, using a program, tutoring various computer skills) they can do that easily just by using a screen sharing driver that simulates a webcam from desktop contents. Read more on http://www.videochat-scripts.com/screen-sharing-with-flash-video-chat-software/ . 

== Documentation ==
* Plugin Homepage : http://www.videowhisper.com/?p=WordPress+Video+Presentation
* Application Homepage : http://www.videowhisper.com/?p=Video+Consultation

== Demo ==
* See BuddyPress integration live on http://livon.tv/
* See WordPress integration live on http://www.videochat-scripts.com/video-presentation/

== Extra ==
More information, the latest updates, other plugins and non-WordPress editions can be found at http://www.videowhisper.com/ .

== Changelog ==

= 3.31 =
* Integrated latest application version 3.31
* Setting tabs
* Configure default room

= 3.25 =
* Integrated latest application version 3.25
* Codec settings

= 3.17 =
* Integrated latest application version that include P2P. 
* Added more settings to control P2P / RTMP streaming, bandwidth detection.
* Fixed some possible security vulnerabilites for hosts with magic_quotes Off.

= 1.1 =
* BuddyPress integration: If BuddyPress is installed this will add a Video Presentation tab to the group where users can video chat realtime in group room.
* Everything is in the plugin folder to allow automated updates.
* Settings page to fill rtmp address.
* Choose name to use in application (display name, login, nice name).
* Access permissions (all, members, list).
* List number of participants for each room.
