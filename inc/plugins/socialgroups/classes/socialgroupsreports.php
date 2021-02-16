<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 * This file handles reported content from Social Groups.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

class socialgroupsreports
{
    /* This class handles all aspects of reporting posts. */

    public $reportedposts = array();

    public $canreport = array();

    function __construct()
    {
        $this->load_reported_posts(1);
    }

    public function load_reported_posts($read=0)
    {
        global $db;
        $read = (int) $read;
        if($read)
        {
            $query = $db->simple_select("socialgroup_reported_posts", "*");
        }
        else
        {
            $query = $db->simple_select("socialgroup_reported_posts", "*", "status=0");
        }
        while($post = $db->fetch_array($query))
        {
            $this->reportedposts[$post['pid']] = $post;
        }
    }

    public function can_report($uid=0, $pid=0)
    {
        global $mybb, $db, $socialgroups, $plugins;
        $pid = (int) $pid;
        if(!$pid)
        {
            $socialgroups->error("invalid_post");
        }
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if($uid == 0) // Don't allow guests to report
        {
            return FALSE;
        }
        // Now check if the post has been reported before.  If so, rely on the setting.
        if(array_key_exists($pid, $this->reportedposts) && $mybb->settings['socialgroups_multiple_report'] == 0)
        {
            return FALSE;
        }
        if(array_key_exists($uid, $this->canreport))
        {
            return $this->canreport[$uid];
        }
        $userquery = $db->simple_select("users", "usergroup, additionalgroups", "uid=$uid");
        $usergroupinfo = $db->fetch_array($userquery);
        if($usergroupinfo['addtionalgroups'])
        {
            $usergroupinfo['usergroup'] .= ",";
        }
        // Now we have the usergroups, get the permissions.
        $usergroupquery = $db->simple_select("usergroups", "*", "gid IN(" . $usergroupinfo['usergroup'] . $usergroupinfo['additionalgroups'] . ")");
        $canreport = TRUE;
        while($usergroup = $db->fetch_array($usergroupquery))
        {
            if($usergroup['isbannedgroup'])
            {
                $canreport = FALSE;
            }
        }
        $this->canreport[$uid] = $canreport;
        $plugins->run_hooks("class_socialgroups_reports_can_report", $this->can_report[$uid]);
        return $this->canreport[$uid];
    }

    public function report_post($data)
    {
        global $mybb, $db, $socialgroups, $plugins;
        if(!$data['pid'])
        {
            $socialgroups->error("invalid_post");
        }
        if(!$data['reason'])
        {
            $socialgroups->error("missing_reason");
        }
        if(!$this->can_report("", $data['pid']))
        {
            $socialgroups->error("cant_report");
        }
        $data['tid'] = (int) $data['tid'];
        if($data['tid']) // Manually fetch tid if not provided
        {
            $query = $db->simple_select("socialgroup_posts", "tid", "pid=$pid");
            $tid = $db->fetch_field($query, "tid");
        }
        else
        {
            $tid = $data['tid'];
        }
        $reportinfo = array(
            "pid" =>(int) $data['pid'],
            "tid" => $tid,
            "uid" => $mybb->user['uid'],
            "dateline" => TIME_NOW,
            "status" => 0,
            "reason" => $db->escape_string($data['reason'])
        );
        $plugins->run_hooks("class_socialgroups_reports_report", $reportinfo);
        $rid = $db->insert_query("socialgroup_reported_posts", $reportinfo);
        return $rid;
    }

    public function handle_report($rid, $uid=0)
    {
        global $db, $mybb, $socialgroups;
        $rid = (int) $rid;
        if(!$rid)
        {
            $socialgroups->error("invalid_report.");
        }
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if(!$socialgroups->socialgroupsuserhandler->is_moderator(1, $uid))
        {
            error_no_permission();
        }
        $updated_report = array(
            "status" => 1,
            "handledby" => $uid,
            "handledate" => TIME_NOW
        );
        $db->update_query("socialgroup_reported_posts", $updated_report, "rid=$rid");
    }

    public function prune_reports()
    {
        global $mybb, $db;
        $cutoff = TIME_NOW - 86400 * $mybb->settings['socialgroups_report_prune'];
        $db->delete_query("socialgroup_reported_posts", "status=1 AND dateline < $cutoff");
    }
}