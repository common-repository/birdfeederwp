<?php
/*
Plugin Name: BirdFeeder for WordPress 
Plugin URI: http://turmsegler.net/20070624/wp-plugin-birdfeederwp/
Description: Integrate Mint Bird Feeder with WordPress 2.x (Feed categories Articles, Comments and Comments on Articles with their respective flavours will be reported.)
Author: Benjamin Stein
Author URI: http://turmsegler.net/
Version: 1.0.4
*/ 

// $Id: BirdFeederWP.php 27117 2007-12-18 06:35:52Z turmsegler $
// $HeadURL: https://plugins.svn.wordpress.org/birdfeederwp/trunk/BirdFeederWP.php $

function ts_birdfeeder_init()
{
    global $Mint, $BirdFeeder, $wpdb, $feed, $withcomments;
    $feedName = '';
    $feedType = '';
    
    if ( is_feed() )
    {
        if     ( !strstr( $feed, 'comments' ) && !is_single() && $wp->query_vars['category_name'] == '' && ($withcomments != 1) ) 
        {
            $feedName = 'Articles';
        }
        elseif ( ( strstr( $feed, 'comments' ) || $withcomments == 1 ) && !is_single() ) 
        {
            $feedName = 'Comments';
        }
        elseif ( is_single() ) 
        {
            $feedName = 'Comments on Articles';
        }

        if     ( strstr ( $feed, 'atom' ) ) 
        {
            $feedType = 'Atom';
        }
        elseif ( strstr ( $feed, 'rss2' ) || $feed == 'feed' )
        {
            $feedType = 'RSS2';
        }
        elseif ( strstr ( $feed, 'rdf' ) )
        {
            $feedType = 'RDF';
        }
        elseif ( strstr ( $feed, 'RSS' ) )
        {
            $feedType = 'RSS';
        }

        if ( feedType != '' )
        {
            $feedName = $feedName . ' (' . $feedType . ')';
        }
        
        if ( $feedName != '' ) 
        {
            if ( !defined('BIRDFEED') )
            {
                define('BIRDFEED', $feedName);
                include($_SERVER['DOCUMENT_ROOT'].'/feeder/index.php');
                $wpdb->select(DB_NAME);
            }
        }
    }

    return $feedName;
}

function ts_birdfeeder_headers($wpObj)
{
    if ( is_feed() )
    {
        $feedName = ts_birdfeeder_init();
    }
}

function ts_birdfeeder_wp_seed($url) 
{
    global $Mint, $BirdFeeder;
    $feedName = '';
    
    if ( is_feed() )
    {
        $feedName = ts_birdfeeder_init();

        $Mint->loadPepper();
        $BirdFeederId =  $Mint->cfg['pepperLookUp']['SI_BirdFeeder'];
        $BirdFeeder   =& $Mint->pepper[$BirdFeederId];
             
        if ( strstr($feedName, 'Comments') )
        {
            global $comment;
            
            $title = get_the_title($comment->comment_post_ID);
            $title = apply_filters('the_title', $title);
            $title = apply_filters('the_title_rss', $title);
            $url = $BirdFeeder->seed($title, $url. '#comment-' . $comment->comment_ID, true);
        }
        else
        {
            $url = $BirdFeeder->seed(get_the_title_rss(), $url, true);
        }
        
        if ( $Mint->db['database'] != DB_NAME ||
             $Mint->db['server']   != DB_HOST ||
             $Mint->db['username'] != DB_USER )
        {
            // Mint is running in another DB instance.
            // The WP database connection most likely 
            // has been dropped. We have to reestablish it.

           global $wpdb, $table_prefix;
            
           $wpdb                     = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
           $wpdb->prefix             = $table_prefix;
           $wpdb->posts              = $wpdb->prefix . 'posts';
           $wpdb->users              = $wpdb->prefix . 'users';
           $wpdb->categories         = $wpdb->prefix . 'categories';
           $wpdb->post2cat           = $wpdb->prefix . 'post2cat';
           $wpdb->comments           = $wpdb->prefix . 'comments';
           $wpdb->link2cat           = $wpdb->prefix . 'link2cat';
           $wpdb->links              = $wpdb->prefix . 'links';
           $wpdb->options            = $wpdb->prefix . 'options';
           $wpdb->postmeta           = $wpdb->prefix . 'postmeta';
           $wpdb->usermeta           = $wpdb->prefix . 'usermeta';
           $wpdb->terms              = $wpdb->prefix . 'terms';
           $wpdb->term_taxonomy      = $wpdb->prefix . 'term_taxonomy';
           $wpdb->term_relationships = $wpdb->prefix . 'term_relationships';

           if ( defined('CUSTOM_USER_TABLE') )
               $wpdb->users = CUSTOM_USER_TABLE;
           if ( defined('CUSTOM_USER_META_TABLE') )
               $wpdb->usermeta = CUSTOM_USER_META_TABLE;
        }
    }
    
    return $url;
}

if (function_exists('add_action')) 
{
    add_action('send_headers','ts_birdfeeder_headers');
}


if (function_exists('add_filter')) 
{
    add_filter('post_link','ts_birdfeeder_wp_seed');
}
?>
