<?php
/*
Plugin Name: VRIKS Video Embedding
Plugin URI: http://vriks.de
Description: A Plug-in to embed your Videos from https://vriks.de/ and https://familienvideo.de/ into your WordPress Site.
Version: 1.0
Author: Rafaela Neff
Author URI: http://amidisturing.com
*/
$plugin_dir = plugin_dir_path( __FILE__ );
/* The options page */
include_once($plugin_dir . 'admin.php');
/*
function load_css() {
        wp_enqueue_style( 'custom_style', get_stylesheet_directory_uri() . '/css/custom_style.css', array(), 0.256, 'all');
}
add_action( 'wp_print_styles', 'load_css', 99 );
*/
function register_videojs(){
	$options = get_option('videojs_options');/*?*/
	wp_register_style( 'videojs', plugins_url( 'css/video-js.min.css' , __FILE__ ) );
	wp_enqueue_style( 'videojs' );
	wp_register_style( 'vriks_style', plugins_url( 'css/vriks-style.css' , __FILE__ ) );
	wp_enqueue_style( 'vriks_style' );
	wp_register_script( 'videojs', plugins_url( 'js/node_modules/video.js/dist/video.min.js' , __FILE__ ) );
	wp_register_script('videojs_cdn', 'https://cdn.jsdelivr.net/npm/videojs-contrib-hls@5.14.1/dist/videojs-contrib-hls.min.js');
	wp_register_script( 'videojs_contrib', plugins_url( 'js/videojs-contrib-quality-levels.min.js' , __FILE__ ) );
	wp_register_script( 'videojs_hls', plugins_url( 'js/videojs-hls-quality-selector.min.js' , __FILE__ ) );
	wp_register_script( 'videojs_config', plugins_url( 'js/videojs_config.js' , __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'register_videojs' );

/* Include the scripts before </body> */
function add_videojs_header(){
	wp_enqueue_script( 'videojs' );
}

function get_attrs($data, $domain, $source, $video_route, $rest_id) {
	$error_code = $data->result->videoresponse->error;
	//if everything is fine
	if ($error_code == 0) {
		$title = $data->result->videoresponse->data->title;
		$description = $data->result->videoresponse->data->description;
		$hls_source = $data->result->videoresponse->data->hlsurl;
		$poster = $data->result->videoresponse->data->poster->original;
		$link_start_sentence =  'Schau das Videos auf ';
		$watch_ahref = $source;
	}
	//if the video doesn't exist on server
	if ($error_code == 1) {
		$title = 'Dieses Video existiert nicht (mehr) auf unserem Server!';
		$description = 'Bitte kontrolliere die Video-URL!';
		$hls_source = plugins_url( 'fallback/not-existing.m3u8' , __FILE__ );
		$poster = plugins_url( 'img/not-existing.png' , __FILE__ );
		$link_start_sentence = 'Schau Videos auf ';
		$watch_ahref = $domain.$video_route.$rest_id;
	}
	//if the video isn't public
	if ($error_code == 2) {
		$title = 'Dieses Video ist privat!';
		$description = 'Mache das Video öffentlich um es mit Anderen teilen zu können!';
		$hls_source = plugins_url( 'fallback/private.m3u8' , __FILE__ );
		$poster = plugins_url( 'img/private.png' , __FILE__ );
		$link_start_sentence =  'Schau Videos auf ';
		$watch_ahref = $domain.$video_route.$rest_id;
	}
	
	return array('title' => $title, 'description' => $description, 'hls_source' => $hls_source, 'poster' => $poster, 'link_start_sentence' => $link_start_sentence, 'watch_ahref' => $watch_ahref);
}

function video_shortcode($atts) {
	add_videojs_header();
	
	$options = get_option('videojs_options'); //load the defaults

	extract(shortcode_atts(array(
		"src" => ''
	), $atts));
	
	$source = $atts['src'];
	/*load domain according to shortcode attribute*/
	preg_match('/(?:https?:\/\/)?(?:www\.)?([^\/]*)\.de\//', $source, $matches);
	if ($matches[1] == 'vriks'){
		$domain = 'https://vriks.de';
		$video_route = '/videos/v';
		$logo = plugins_url( 'img/vriks-logo-black.png' , __FILE__ );
	}
	if ($matches[1] == 'familienvideo'){
		$domain = 'https://familienvideo.de';
		$video_route = '/video';
		$logo = plugins_url( 'img/familienvideo-logo.png' , __FILE__ );
	}	
	$route = $domain.'/rest/0.1/wp/';
	// ID is required for multiple videos to work
	$vjs_id = 'my-vjs'.rand();
	//Regex: find a number which is followed by 0 or more non-slash string until end.
	preg_match('/\/([0-9]+)(?:[\/]*?)(?=[^\/]*$)/', $atts['src'], $output_array);
	//$rest_id is defined as a number.
	//In case there is no number found at the end of the URL $rest_id set to NONE
	// Errors are handles in function get_attrs()
	if ($output_array[1] == ''){
		$rest_id = 'NONE';
	} else {
	$rest_id = $output_array[1];
	}
	
	$rest_id_endpoint = $route.$rest_id;

	$content = file_get_contents($rest_id_endpoint);
	$data = json_decode($content);

	$attrs = get_attrs($data, $domain, $source, $video_route, $rest_id);
	

	return '<div class="vriksoutercontainer"><div class="vrikscontainer"><div class="vrikstitle">'.$attrs['title'].'</div><video id="'.$vjs_id.'" class="video-js vjs-fluid vjs-big-play-centered" controls preload="metadata" poster="'.$attrs['poster'].'" data-setup="{}"><source  src="'.$attrs['hls_source'].'" type="application/x-mpegURL"><p class="vjs-no-js">
			To view this video please enable JavaScript, and consider upgrading to a web browser that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a></p></video><div class="vrikssub">'.$attrs["description"].'</div><span class="vrikslink"><a href="'.$attrs['watch_ahref'].'" target="_blanc">'.$attrs[link_start_sentence].$domain.'!</a><div class="vrikslogo" style = "float: right;"><img src="'.$logo.'" /></span></div></div></div>';
}
 
add_shortcode("vriks-embed", "video_shortcode"); 
 
?>