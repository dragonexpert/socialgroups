<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This file handles reported content from Social Groups.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

class socialgroupsreports extends socialgroups
{
    /* This class handles all aspects of reporting posts. */

    private $reportedposts = array();

    private $canreport = array();

    function __construct()
    {
        // Once this is actually implemented, uncomment the line below.
        // $this->load_reported_posts(1);
    }

    /**
     * Loads the reported posts.
     * @param int $read Whether to include read reports.
     * @return array An array of reported posts.
     */
    public function load_reported_posts(int $read=0): array
    {
        global $db;
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
        return $this->reportedposts;
    }

    /**
     * This function checks if a user can report a post.
     * @param int $uid The id of the user.
     * @param int $pid The id of the post.
     * @return bool false or true.
     */
    public function can_report(int $uid=0, int $pid=0): bool
    {
        global $mybb, $db, $socialgroups, $plugins;
        if(!$pid)
        {
            $socialgroups->error("invalid_post");
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if($uid == 0) // Don't allow guests to report
        {
            return false;
        }
        // Now check if the post has been reported before.  If so, rely on the setting.
        if(isset($this->reportedposts[$pid]) && $mybb->settings['socialgroups_multiple_report'] == 0)
        {
            return false;
        }
        if(isset($this->canreport[$uid]))
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
        $canreport = true;
        while($usergroup = $db->fetch_array($usergroupquery))
        {
            if($usergroup['isbannedgroup'])
            {
                $canreport = false;
            }
        }
        $this->canreport[$uid] = $canreport;
        $plugins->run_hooks("class_socialgroups_reports_can_report", $this->can_report[$uid]);
        return $this->canreport[$uid];
    }

    /**
     * This function handles reporting the post.
     * @param array $data An array of data about the post.
     * @return mixed The id of the report if succesful.
     */
    public function report_post(array $data)
    {
        global $mybb, $db, $socialgroups, $plugins;
        if(!isset($data['pid']))
        {
            $socialgroups->error("invalid_post");
        }
        if(!isset($data['reason']))
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
        $plugins->run_hooks("class_socialgroups_reports_report_post", $reportinfo);
        $rid = $db->insert_query("socialgroup_reported_posts", $reportinfo);
        return $rid;
    }

    /**
     * This function handles a report update.
     * @param int $rid The id of the report.
     * @param int $uid The id of the user.
     */
    public function handle_report(int $rid, int $uid=0)
    {
        global $db, $mybb, $socialgroups, $plugins;
        if(!$rid)
        {
            $socialgroups->error("invalid_report.");
        }
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
        $plugins->run_hooks("class_socialgroups_reports_handle_report", $updated_report);
        $db->update_query("socialgroup_reported_posts", $updated_report, "rid=$rid");
    }

    /**
     * This function prunes reports from socialgroups.
     */
    public function prune_reports()
    {
        global $mybb, $db;
        $cutoff = TIME_NOW - 86400 * $mybb->settings['socialgroups_report_prune'];
        $db->delete_query("socialgroup_reported_posts", "status=1 AND dateline < $cutoff");
    }
}
