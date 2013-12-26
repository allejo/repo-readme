<?php
/*
Plugin Name: Repo Readme
Plugin URI: http://allejo.me/projects/wordpress/plugins/repo-readme
Description: Allows you to retrieve the content of a README file and output the generated HTML
Version: 0.8.0
Author: Vladimir Jimenez
Author URI: http://allejo.me/
License: GPLv3

Copyright 2013 Vladimir Jimenez (allejo@me.com)
*/

require_once 'markdownlib/Michelf/Markdown.inc.php';

use \Michelf\Markdown;

/**
 * The function that gets called to build a BZFS widget
 *
 * @param $attributes array Parameters that are passed in the short code
 *
 * @return string The HTML for the widget to be displayed
 */
function readme_widget_handler($attributes)
{
    // Load necessary CSS and JS files
    wp_register_style('markdown-css', plugins_url('style.css', __FILE__ ));
    wp_enqueue_style('markdown-css');

    // Build the HTML for the widget
    $readme_html = readme_builder($attributes);

    // Return the widget HTML to be displayed
    return $readme_html;
}

/**
 * Builds the widget out of available HTML templates with the specified information
 *
 * @param $attributes array Parameters that are passed in the short code
 *
 * @return string The HTML for the widget to be displayed
 */
function readme_builder($attributes)
{
    $widget = ""; // We'll store the HTML here

    // Get all the parameters that were passed in the short code and save them in variables
    // Here are our default values in case the parameters were not passed
    extract(shortcode_atts(array(
        'host' => 'github',
        'user' => 'octocat',
        'repo' => 'Hello-World'
    ), $attributes));

    $data = readme_json("github", array('user' => $user, 'repo' => $repo));

    // Return the generated HTML
    return $data;
}

/**
 * @param $url
 * @return array|mixed
 */
function fetch_json($url)
{
    $curl_handler = curl_init();
    curl_setopt($curl_handler, CURLOPT_URL, $url);
    curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handler, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($curl_handler, CURLOPT_USERAGENT, 'curl/' . $t_vers['version']);

    $json = curl_exec($curl_handler);

    curl_close($curl_handler);

    return json_decode($json, true);
}

/**
 * Make a JSON request and get required information
 *
 * @param $host string The respective host to make the JSON query to
 * @param $array array An array of values to be passed that will be used to make the GET query
 *
 * @return array|mixed The necessary information retrieved from the JSON query
 */
function readme_json($host, $array)
{
    $transient = "repo-readme_" . $array['user'] . "-" . $array['repo']; // Build a name for the transient so we can "cache" information
    $status = get_transient($transient); // Check whether or not the transient exists

    // If the transient exists, return that
    if ($status)
    {
        return $status;
    }

    // Make a JSON query to GitHub
    if ($host == "github")
    {
        $repo_location = $array['user'] . '/' . $array['repo']; // The user and repository name combination used to build the URL

        // Retrieve information about the repository itself
        $repo_data = fetch_json("https://api.github.com/repos/" . $repo_location . "/readme");

        // Store the necessary information in an array for easy access
        $readme_content = base64_decode($repo_data['content']);
        $readme_content = Markdown::defaultTransform($readme_content);

        // Store the information in the transient in order to cache it
        set_transient($transient, $readme_content, 600);

        // Return our array of information
        return $readme_content;
    }
}

// Register the 'readme' short code and make the handler function the main function
add_shortcode('readme', 'readme_widget_handler');