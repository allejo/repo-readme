<?php
/*
Plugin Name: Repo Readme
Plugin URI: http://allejo.me/projects/wordpress/plugins/repo-readme
Description: Allows you to retrieve the content of a README file and output the generated HTML
Version: 1.0.1
Author: Vladimir Jimenez
Author URI: http://allejo.me/
License: GPLv3

Copyright 2014 Vladimir Jimenez (allejo@me.com)
*/

require_once 'markdownlib/Michelf/Markdown.inc.php';

use \Michelf\Markdown;

/**
 * The function that gets called to generate the HTML from the README
 *
 * @param  string[] $attributes Parameters that are passed in the short code
 *
 * @return string   The HTML for the widget to be displayed
 */
function readme_widget_handler($attributes)
{
    // Load necessary CSS files
    wp_register_style('markdown-css', plugins_url('style.css', __FILE__ ));
    wp_enqueue_style('markdown-css');

    // Build the HTML from the README data
    $readme_html = readme_builder($attributes);

    // Return the HTML to be displayed
    return '<article class="markdown">' . $readme_html . '</article>';
}

/**
 * Builds the widget out of available HTML templates with the specified information
 *
 * @param  string[] $attributes Parameters that are passed in the short code
 *
 * @return string   The HTML for the widget to be displayed
 */
function readme_builder($attributes)
{
    // Get all the parameters that were passed in the short code and save them in variables
    // Here are our default values in case the parameters were not passed
    extract(shortcode_atts(array(
        'host' => 'github',
        'user' => 'octocat',
        'repo' => 'Hello-World'
    ), $attributes));

    // Fetch the JSON for the README file along with parsing the markdown
    $parsedMarkdown = readme_json("github", array('user' => $user, 'repo' => $repo));

    // Return the parsed HTML
    return $parsedMarkdown;
}

/**
 * Make a POST JSON query
 * 
 * @param  string   $url The URL to send the POST query to
 * 
 * @return string[] An array of the information gotten from the JSON data
 */
function fetch_json($url)
{
    // Setup cURL so can show web servers that we're legitimate and spamming them
    $curl_handler = curl_init();
    curl_setopt($curl_handler, CURLOPT_URL, $url);
    curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handler, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($curl_handler, CURLOPT_USERAGENT, 'curl/' . $t_vers['version']);

    // Execute the cURL command
    $json = curl_exec($curl_handler);

    // Close the handler
    curl_close($curl_handler);

    // Return the decoded JSON information in the form of an array
    return json_decode($json, true);
}

/**
 * Make a JSON request and get required information
 * 
 * @param  string   $host   The respective host to make the JSON query to
 * @param  string[] $array  An array of values to be passed that will be used to make the GET query
 * 
 * @return string[] The necessary information retrieved from the JSON query
 */
function readme_json($host, $array)
{
    // Build a name for the transient so we can "cache" information
    $transient = "repo-readme_" . $array['user'] . "-" . $array['repo'];

    // Check whether or not the transient exists
    $status = get_transient($transient);

    // If the transient exists, return that
    if ($status)
    {
        return $status;
    }

    // Make a JSON query to GitHub
    if ($host == "github")
    {
        // The user and repository name combination used to build the URL
        $repo_location = $array['user'] . '/' . $array['repo'];

        // Retrieve the base64 encoded version of the README file
        $repo_data = fetch_json("https://api.github.com/repos/" . $repo_location . "/readme");

        // Decode the base64 encoded information
        $readme_content = base64_decode($repo_data['content']);

        // Parse the markdown into HTML
        $readme_content = Markdown::defaultTransform($readme_content);

        // Store the information in the transient in order to cache it for 10 minutes
        // since READMEs don't typically change often
        set_transient($transient, $readme_content, 600);

        // Return the generated HTML of the README
        return $readme_content;
    }
}

/**
 * Return get the content inside of HTML tags
 * 
 * @param  string $htmlContent The string containing the HTML content we will be looking through
 * @param  string $tag The HTML tag we will be looking for
 * 
 * @return string[] An array of all of the contents inside of all the tags
 */
function getHtmlContents ($htmlContent, $tag)
{
    $pattern = '%(<' . $tag . '[^>]*>.*?</' . $tag . '>)%i';
    
    preg_match_all($pattern, $htmlContent, $matches);
    
    return $matches[1];
}

// Register the 'readme' short code and make the handler function the main function
add_shortcode('readme', 'readme_widget_handler');
