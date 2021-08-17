<?php
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function task_socialgroups_stats($task)
{
    global $mybb, $db, $cache, $lang;

    $updated_stats = array();
    $query = $db->simple_select("socialgroup_threads", "COUNT(tid) AS threadcount", "visible=1");
    $updated_stats['numgroupthreads'] = $db->fetch_field($query, "threadcount");
    $db->free_result($query);
    $query = $db->simple_select("socialgroup_posts", "COUNT(pid) AS postcount", "visible=1");
    $updated_stats['numgroupposts'] = $db->fetch_field($query, "postcount");
    $db->free_result($query);
    $socialgroups_cache = $cache->read("socialgroups");
    $updated_stats['numgroups'] = count($socialgroups_cache);

    // Now fetch the previous entry
    $query = $db->query("SELECT * FROM " . TABLE_PREFIX . "stats ORDER BY dateline DESC LIMIT 1");
    $stats = $db->fetch_array($query);
    $db->update_query("stats", $updated_stats, "dateline=" . $stats['dateline']);

    add_task_log($task, "Socialgroups Stats Task Ran");
}
