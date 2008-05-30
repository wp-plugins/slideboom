<?php

// SlideBoom plugin for WordPress (http://wordpress.org/)  
//
// Copyright (c) 2008 iSpring Solutions, Inc.
// http://www.slideboom.com
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESSED OR IMPLIED
// WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
// MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
// *****************************************************************

/*
	Plugin Name: SlideBoom
	Plugin URI: http://blog.slideboom.com/index.php/slideboom-wordpress-plugin/
	Description: Helps you easily embed SlideBoom presentations into your WordPress posts by inserting the WordPress embed code. You can change the default width of embedded presentations in <a href="options-general.php?page=slideboom.php">plugin settings</a>.  
	Version: 1.0
	Author: Slava Uskov 
	Author URI: http://blog.slideboom.com/
*/

define('WP_EMBED_REGEXP', '/\[slideboom id\=(\d+)\&amp\;w\=(\d+)\&amp\;h\=(\d+)\]/');
define('EMBED_TEMPLATE', '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="425" height="370" id="onlinePlayer"><param name="allowScriptAccess" value="always" /><param name="movie" value="http://www.slideboom.com/player/player.swf?id_resource={ID_RESOURCE}" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" /><param name="flashVars" value="mode=0&idResource={IID_RESOURCE}&siteUrl=http://www.slideboom.com&embed=1" /><param name="allowFullScreen" value="true" /><embed src="http://www.slideboom.com/player/player.swf?id_resource={ID_RESOURCE}" quality="high" bgcolor="#ffffff" width="425" height="370" name="onlinePlayer" allowScriptAccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" allowFullScreen="true" flashVars="mode=0&idResource={ID_RESOURCE}&siteUrl=http://www.slideboom.com&embed=1"></embed></object>');
define('CONFIG_PAGE_TEMPLATE', '<div class="wrap">
				<h2>SlideBoom Options</h2>
				<h3>Explanation of usage</h3>
				<p>
					Just copy and paste the "Embed into WordPress" code from <a href="http://www.slideboom.com/" title="SlideBoom - Share Live PowerPoint Presentations Online">SlideBoom</a>, and you\'re done.
				</p>
				<form action="" method="post" id="slideboom_options">
					{WP_NONCE}
					<fieldset class="options">
						<p><label for="slideboom_post_width">Default width:</label><input size="4" type="text" id="slideboom_post_width" name="slideboom_post_width" value="{POST_WIDTH}"/> pixels</p>
						<h3>Explanation of default width</h3>
						<p>
							If you enter nothing here, the width and height from an embed code will be used.  
						</p>
						<p>
							If you <em>do</em> enter a value, it will always replace the width with that value.
						</p>
					</fieldset>
					<p class="submit">
						<input type="submit" name="submit" value="Update SlideBoom Options" />
					</p>
				</form>
			</div>');

if ( !class_exists( 'SlideBoomOptions' ) )
{
	class SlideBoomOptions
	{
		function add_config_page()
		{
			global $wpdb;
			if ( function_exists('add_submenu_page') ) 
			{
				add_options_page('SlideBoom Options', 'SlideBoom', 10, basename(__FILE__),array('SlideBoomOptions','config_page'));
			}
		}
		
		function config_page()
		{
			if ( isset($_POST['submit']) ) 
			{
				if (!current_user_can('manage_options')) die(__('You cannot edit the SlideBoom options.'));
				check_admin_referer('slideboom-updatesettings');

				if (isset($_POST['slideboom_post_width'])) {
					$options['post_width'] = $_POST['slideboom_post_width'];
				}
				
				$options = serialize($options);
				update_option('SlideBoom', $options);
			}

			$options  = get_option('SlideBoom');
			$options = unserialize($options);
			
			echo slideboom_prepare_config_page_code();
		}
		
	}
}

function slideboom_prepare_config_page_code()
{
	if (function_exists('wp_nonce_field')) 
	{ 
		$wp_nonce = wp_nonce_field('slideboom-updatesettings');
		$post_width = $options['postwidth'];
		$page_code = CONFIG_PAGE_TEMPLATE;
		$page_code = str_replace('{WP_NONCE}', $wp_nonce, $page_code);
		$page_code = str_replace('{POST_WIDTH}', $post_width, $page_code);
		return $page_code; 
	}
	
	return '';
}

function slideboom_prepare_embed_code($content, $width)
{
	if (isset($content) && is_string($content) && $content != '')
	{
		preg_match_all(WP_EMBED_REGEXP, $content, $matches);

		for ($i = 0; $i < count($matches[0]); $i++) 
		{
			if ( $matches[1][$i] > 0 && $matches[2][$i] > 0 && $matches[3][$i] > 0 )
			{
				$id_resource = $matches[1][$i];
				$standard_width = $matches[2][$i];
				$standard_height = $matches[3][$i];
				if ( isset($width) && $width > 0 )
				{
					$scale_factor = $standard_height / $standard_width;
					$height = $width * $scale_factor;
				}
				else
				{
					$widtht = $standard_width;
					$height = $standard_height;
				}
				$embed_code = EMBED_TEMPLATE;
				$embed_code = str_replace('{ID_RESOURCE}', $id_resource, $embed_code);
				$embed_code = str_replace('{WIDTH}', $width, $embed_code);
				$embed_code = str_replace('{HEIGHT}', $height, $embed_code);
				$content = str_replace($matches[0][$i], $embed_code, $content);
			}
		}
	}
	
	return $content;
}

function slideboom_insert_embed_code($content)
{
	$options = get_option('SlideBoom');	
	$options = unserialize($options);
	return slideboom_prepare_embed_code($content, $options['post_width']); 
}

add_action('admin_menu', array('SlideBoomOptions','add_config_page'));
add_filter('the_content', 'slideboom_insert_embed_code');

?>