<?php
/**
 * Socialgroups Plugin
 * Author: Mark Janssen
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

class socialgroupsapi extends socialgroups
{
    /**
     * @var int The version of the API
     */
    public $api_version = 1;

    /**
     * @var string The key unique to socialgroups
     */
    private $api_key = "ahowhownliuq1u2";

    function __construct()
    {
    }

    /**
     * This function sends basic information for troubleshooting and improving the Socialgroups Product
     */
    public function send_server_info()
    {
        global $mybb, $db;
        $server_info = array(
            "phpversion" => PHP_VERSION,
            "dbtype" => $db->engine,
            "dbversion" => $db->get_version(),
            "boardurl" => $mybb->settings['bburl'],
            "api_key" => $this->api_key
        );
        $forum_stats = $this->get_socialgroups_stats();
        $server_info = array_merge($server_info, $forum_stats);
        $ch = curl_init("https://teamdimensional.net/testforums/api.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $server_info);
        $result = curl_exec($ch);
    }

    /**
     * This function gets stats about socialgroups.
     * @return array An array of stats for the forum.
     */
    public function get_socialgroups_stats(): array
    {
        global $db, $cache;
        $query = $db->simple_select("socialgroup_threads", "COUNT(tid) AS threadcount", "visible=1");
        $threadcount = $db->fetch_field($query, "threadcount");
        $db->free_result($query);
        $query = $db->simple_select("socialgroup_posts", "COUNT(pid) AS postcount", "visible=1");
        $postcount = $db->fetch_field($query, "postcount");
        $socialgroups_cache = $cache->read("socialgroups");
        $socialgroups_category_cache = $cache->read("socialgroups_categories");
        $query = $db->simple_select("stats", "*", "", array("order_by" => "dateline", "order_dir" => "DESC", "limit" => 1));
        $forumstats = $db->fetch_array($query);
        $socialgroups_stats = array(
            "categories" => count($socialgroups_category_cache),
            "groups" => count($socialgroups_cache),
            "groupthreads" => $threadcount,
            "groupposts" => $postcount
        );
        return array_merge($forumstats, $socialgroups_stats);
    }
}