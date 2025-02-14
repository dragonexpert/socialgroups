<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

class socialgroupsthreadhandler extends socialgroups
{

    function __construct()
    {
        // Nothing
    }

    /**
     * This function creates a new thread.
     * @param array $data An array of data about the thread.
     */
    public function new_thread($data=array())
    {
        global $mybb, $socialgroups, $db, $plugins;
        if(!$data['gid'])
        {
            $socialgroups->error("invalid_group");
        }
        if(!$data['message'])
        {
            $socialgroups->error("no_message");
        }
        if(!$data['uid'])
        {
            $data['uid'] = $mybb->user['uid'];
        }
        if(!$data['username'])
        {
            $data['username'] = $mybb->user['username'];
        }
        $new_thread = array(
            "gid" => (int) $data['gid'],
            "uid" => (int) $data['uid'],
            "subject" => $db->escape_string($data['subject']),
            "dateline" => TIME_NOW,
            "sticky" => (int) $data['sticky'],
            "visible" => 1,
            "closed" => (int) $data['closed'],
            "replies" => 1,
            "views" => 0,
            "lastposttime" => TIME_NOW,
            "lastpostuid" => (int) $data['uid'],
            "lastpostusername" => $db->escape_string($data['username'])
        );
        // Double post check
        $cutoff = TIME_NOW - 300; // Five minute cutoff
        $query = $db->simple_select("socialgroup_threads", "COUNT(tid) as duplicates", "uid=" . (int) $data['uid'] . " AND subject='" . $new_thread['subject'] . "' AND dateline>=$cutoff AND gid=" . (int) $data['gid']);
        $duplicates = $db->fetch_field($query, "duplicates");
        if($duplicates)
        {
            // Duplicate was found redirect to group.
            redirect("showgroup.php?gid=" . (int) $data['gid']);
        }
        // Otherwise we are good to go. :)
        $plugins->run_hooks("class_socialgroupsthreadhandler_insert_thread");
        $tid = $db->insert_query("socialgroup_threads", $new_thread);
        // Get the data for the post
        $new_post = array(
            "tid" => $tid,
            "gid" => (int) $data['gid'],
            "uid" => (int) $data['uid'],
            "dateline" => TIME_NOW,
            "visible" => 1,
            "ipaddress" => get_ip(),
            "message" => $db->escape_string($data['message'])
        );
        $pid = $db->insert_query("socialgroup_posts", $new_post);

        $update_thread = array(
            "firstpost" => $pid
        );

        $db->update_query("socialgroup_threads", $update_thread, "tid=" . $tid);

        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads SET firstpost=$pid WHERE tid=$tid");
        $this->recount_threads($new_post['gid']);
        $this->recount_posts($new_post['gid']);

        // Now update the last post info.  We join the threads table so we don't need to store the last post uid.
        $last_post_info = array(
            "lastposttime" => $new_post['dateline'],
            "lastposttid" => $new_post['tid']
        );
        $db->update_query("socialgroups", $last_post_info, "gid=" . $new_post['gid']);
        $socialgroups->update_cache();
        $this->update_user_stats($new_post['uid'], true);
        $message = "The thread has been posted.";
        redirect("groupthread.php?tid=$tid", $message);
    }

    /**
     * This function creates a new post in a thread.
     * @param array $data An array of data about the post.
     */
    public function new_post($data=array())
    {
        global $mybb, $db, $socialgroups, $plugins;
        if(!isset($data['gid']))
        {
            $socialgroups->error("invalid_group");
        }
        if(!isset($data['tid']))
        {
            $socialgroups->error("invalid_thread");
        }
        if(!isset($data['uid']))
        {
            $data['uid'] = $mybb->user['uid'];
        }
        if(!isset($data['message']))
        {
            $socialgroups->error("no_message");
        }
        if(!isset($data['visible']))
        {
            // Give an option for an unapproved post.
            $data['visible'] = 1;
        }
        $new_post = array(
            "tid" => (int)$data['tid'],
            "uid" => (int) $data['uid'],
            "gid" => (int) $data['gid'],
            "dateline" => TIME_NOW,
            "visible" => (int) $data['visible'],
            "ipaddress" => get_ip(),
            "message" => $db->escape_string($data['message'])
        );
        $cutoff = TIME_NOW - 3600; /* 1 hour post merge */
        $plugins->run_hooks("class_socialgroupsthreadhandler_insert_post");
        // Flood check
        $query = $db->simple_select("socialgroup_posts", "pid, editcount, uid, tid, message, dateline", "tid=" . $new_post['tid'] . " ORDER BY dateline DESC LIMIT 1");
        $postinfo = $db->fetch_array($query);
        $updated_thread = array();
        // Admins can always do a new post.
        if($postinfo['uid'] != $new_post['uid'] || $postinfo['dateline'] < $cutoff || $mybb->usergroup['cancp'] == 1)
        {
            $pid = $db->insert_query("socialgroup_posts", $new_post);
            $updated_thread['lastposttime'] = $new_post['dateline'];
            $updated_thread['lastpostuid'] = $new_post['uid'];
            if(isset($data['username']))
            {
                $updated_thread['lastpostusername'] = $db->escape_string($data['username']);
            }
            else
            {
                $updated_thread['lastpostusername'] = $db->escape_string($mybb->user['username']);
            }
        }
        else
        {
            $pid = $postinfo['pid'];
            $updated_post = array(
                "message" => $postinfo['message'] . "[hr]" . $new_post['message'],
                "lastedit" => TIME_NOW,
                "lasteditby" => $mybb->user['username'],
                "editcount" => $postinfo['editcount'] + 1
            );
            $db->update_query("socialgroup_posts", $updated_post, "pid=" . $postinfo['pid']);
        }
        if(array_key_exists("closed", $data))
        {
            $updated_thread['closed'] = (int) $data['closed'];
        }
        if(array_key_exists("sticky", $data))
        {
            $updated_thread['sticky'] = (int) $data['sticky'];
        }

        $db->update_query("socialgroup_threads", $updated_thread, "tid=" . $data['tid']);
        $this->recount_posts($new_post['gid'], $new_post['tid']);
        $this->recount_threads($new_post['gid']);

        // Update the last post info
        $last_post_info = array(
            "lastposttime" => $new_post['dateline'],
            "lastposttid" => $new_post['tid']
        );
        $db->update_query("socialgroups", $last_post_info, "gid=" . $new_post['gid']);
        $socialgroups->update_cache();
        $this->update_user_stats($new_post['uid'], true);
        $message = "Your message has been posted.";
        redirect("groupthread.php?tid=" . $new_post['tid'], $message);
    }

    /**
     * This function updates a post.
     * @param int $pid The id of the post.
     * @param array $data An array of data about the post.
     */
    public function update_post($pid=0, $data=array())
    {
        global $mybb, $db, $plugins, $socialgroups;
        $pid = (int) $pid;
        // Does the post exist?
        $query = $db->simple_select("socialgroup_posts", "*", "pid=$pid");
        $postinfo = $db->fetch_array($query);
        if(!$postinfo['pid'])
        {
            $socialgroups->error("invalid_post");
        }
        if(!$data['message'])
        {
            $socialgroups->error("no_message");
        }
        if(!$data['uid'])
        {
            $data['uid'] = $mybb->user['uid'];
        }
        $updated_post = array(
            "message" => $db->escape_string($data['message']),
            "lastedit" => TIME_NOW,
            "lasteditby" => $mybb->user['uid'],
        );

        $plugins->run_hooks("class_socialgroupsthreadhandler_update_post");
        $db->update_query("socialgroup_posts", $updated_post, "pid=" . $postinfo['pid']);
        $this->recount_posts($postinfo['gid'], $postinfo['tid']);
        $this->recount_threads($postinfo['gid']);
        $message = "The post has been updated.";
        redirect("groupthread.php?tid=" . $postinfo['tid'], $message);
    }

    /**
     * This function deletes a post.
     * @param int $pid The id of the post.
     * @param int $gid The id of the group.
     * @param int $permanent Whether to physically remove from database.
     */

    public function delete_post(int $pid=0, int $gid=0, int $permanent=0)
    {
        global $mybb, $db, $lang, $socialgroups, $plugins;
        if(!$pid)
        {
            $this->error("invalid_post");
        }
        if(!$gid) // Manually fetch the gid.
        {
            $query = $db->simple_select("socialgroup_posts", "*", "pid=$pid");
            $post = $db->fetch_array($query);
            $gid = $post['gid'];
        }
        if($socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']) || $socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']) || $socialgroups->group[$gid]['uid'] == $mybb->user['uid'])
        {
            // Get the thread so we have mod log data.
            $query = $db->query("SELECT p.uid as poster, p.*, t.*
            FROM " . TABLE_PREFIX . "socialgroup_posts p
            LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
            WHERE p.pid=$pid");
            $thread = $db->fetch_array($query);

            $plugins->run_hooks("class_socialgroupsthreadhandler_delete_post");
            if($permanent == 1)
            {
                $db->delete_query("socialgroup_posts", "pid=$pid");
                $action = "Permanently Deleted Post";
            }
            else
            {
                $action = "Soft Deleted Post";
                $db->update_query("socialgroup_posts", array("visible" => -1), "pid=$pid");
            }
            $this->recount_posts($gid, $thread['tid']);

            // We need to fetch the latest post from the database.
            $query = $db->query("SELECT * FROM " . TABLE_PREFIX . "socialgroup_posts WHERE gid=" . $gid . " AND visible=1
            ORDER BY dateline DESC LIMIT 1");
            $lastpost = $db->fetch_array($query);
            $db->free_result($query);
            $last_post_info = array(
                "lastposttime" => $lastpost['dateline'],
                "lastposttid" => $lastpost['tid']
            );
            $db->update_query("socialgroups", $last_post_info, "gid=" . $gid);

            $query = $db->query("SELECT p.*, t.tid, u.username FROM " . TABLE_PREFIX . "socialgroup_posts p
            LEFT JOIN " . TABLE_PREFIX . "users u ON(p.uid=u.uid)
            LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
            WHERE p.tid=" . $thread['tid'] . " AND p.visible=1 AND t.visible=1"
            . " ORDER BY p.dateline DESC
            LIMIT 1");
            $post = $db->fetch_array($query);
            $db->free_result($query);
            $thread_last_post_info = array(
                "lastposttime" => $post['dateline'],
                "lastpostuid" => $post['uid'],
                "lastpostusername" =>$db->escape_string($post['username'])
            );
            $db->update_query("socialgroup_threads", $thread_last_post_info, "tid=" . $thread['tid']);
            $socialgroups->update_cache();
            $this->update_user_stats($thread['poster'], true);

            // We use conid instead of tid because otherwise the mod log will try and fetch a thread.
            $data = array(
                "gid" => $thread['gid'],
                "subject" => $db->escape_string($thread['subject']),
                "conid" => $thread['tid'],
                "action" => "Deleted Post"
            );
            log_moderator_action($data, $action);
        }
        else
        {
            error_no_permission();
        }
    }

    /**
     * This function restores a thread
     * @param int $tid The id of the thread
     * @param bool $validity_check Whether to validate if the thread exists.
     */
    public function restore_thread(int $tid=0, bool $validity_check = true)
    {
        global $db, $socialgroups, $plugins, $mybb, $lang;
        if(!$tid)
        {
            $socialgroups->error("invalid_thread");
        }
        $query = $db->simple_select("socialgroup_threads", "*", "tid=" . $tid);
        $thread = $db->fetch_array($query);
        if($validity_check)
        {
            if(!isset($thread['tid']))
            {
                $socialgroups->error("invalid_thread");
            }
        }
        $db->update_query("socialgroup_threads", array("visible" => 1), "tid=" . $tid);
        $data = array(
            "gid" => $thread['gid'],
            "subject" => $db->escape_string($thread['subject']),
            "conid" => $thread['tid'],
            "action" => "Restored Thread"
        );
        log_moderator_action($data, "Restore Thread");

        $this->recount_posts($thread['gid']);
        $this->recount_threads($thread['gid']);

        // Update last post info
        $query = $db->query("SELECT p.*, t.tid FROM " . TABLE_PREFIX . "socialgroup_posts p
        LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
        WHERE p.gid=" . $thread['gid'] . " AND p.visible=1 AND t.visible=1
        ORDER BY dateline DESC
        LIMIT 1");
        $lastpost = $db->fetch_array($query);
        $db->free_result($query);

        $last_post_info = array(
            "lastposttime" => $lastpost['dateline'],
            "lastposttid" => $lastpost['tid']
        );
        $db->update_query("socialgroups", $last_post_info, "gid=" . $thread['gid']);
        $userquery = $db->simple_select("socialgroup_threads", "DISTINCT uid", "tid=" . $tid);
        while($poster = $db->fetch_array($userquery))
        {
            $this->update_user_stats($poster['uid'], true);
        }
        $db->free_result($userquery);
        $plugins->run_hooks("class_socialgroupsthreadhandler_restore_thread");
        $socialgroups->update_cache();
    }

    /**
     * This function restores a deleted post.
     * @param int $pid The id of the post
     * @param bool $sanity_check Whether to verify if the post exists.
     */
    public function restore_post(int $pid=0, bool $sanity_check = true)
    {
        global $db, $socialgroups, $plugins, $mybb, $lang;
        if(!$pid)
        {
            $socialgroups->error("invalid_post");
        }
        $query = $db->query("SELECT p.*, t.subject FROM " . TABLE_PREFIX . "socialgroup_posts p
        LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.pid=t.firstpost)
        WHERE p.pid=" . $pid);
        $post = $db->fetch_array($query);
        if($sanity_check)
        {
            if (!isset($post['pid']))
            {
                $socialgroups->error("invalid_post");
            }
        }
        $db->update_query("socialgroup_posts", array("visible" => 1), "pid=" . $pid);
        $data = array(
            "gid" => $post['gid'],
            "subject" => $db->escape_string($post['subject']),
            "conid" => $post['tid'],
            "action" => "Restored Post"
        );
        log_moderator_action($data, "Restore Post");
        $this->recount_posts($post['gid']);
        $this->update_user_stats($post['uid'], true);

        // Update last post info
        $query = $db->query("SELECT p.*, t.tid FROM " . TABLE_PREFIX . "socialgroup_posts p
        LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
        WHERE p.gid=" . $post['gid'] . " AND p.visible=1 AND t.visible=1
        ORDER BY dateline DESC
        LIMIT 1");
        $lastpost = $db->fetch_array($query);
        $db->free_result($query);

        $last_post_info = array(
            "lastposttime" => $lastpost['dateline'],
            "lastposttid" => $lastpost['tid']
        );
        $db->update_query("socialgroups", $last_post_info, "gid=" . $post['gid']);
        $plugins->run_hooks("class_socialgroupsthreadhandler_restore_post");
        $socialgroups->update_cache();
    }

    /**
     * This function deletes an entire thread.
     * @param int $tid The id of the thread.
     * @param int $gid The id of the group.
     * @param int $permanent Whether to physically remove from the database.
     */

    public function delete_thread(int $tid=0, int $gid=0, int $permanent=0)
    {
        global $db, $mybb, $lang, $socialgroups, $plugins;
        if(!$tid)
        {
            $this->error("invalid_thread");
        }
        // Fetch the thread info
        $query = $db->simple_select("socialgroup_threads", "*", "tid=$tid");
        $thread = $db->fetch_array($query);
        $plugins->run_hooks("class_socialgroupsthreadhandler_delete_thread");
        $action = "";
        if($permanent == 1)
        {
            $action = $lang->delete_thread;
            if ($socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
            {
                $db->delete_query("socialgroup_threads", "tid=$tid");
                $db->delete_query("socialgroup_posts", "tid=$tid");
            }
        }
        else if(($socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid'])) && $permanent != 1)
        {
            $db->update_query("socialgroup_threads", array("visible" => -1), "tid=$tid");
            $action = $lang->soft_delete_thread;
        }
        else
        {
            error_no_permission();
        }
        $this->recount_posts($gid);
        $this->recount_threads($gid);

        // Update last post info
        $query = $db->query("SELECT p.*, t.tid FROM " . TABLE_PREFIX . "socialgroup_posts p
        LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
        WHERE p.gid=" . $gid . " AND p.visible=1 AND t.visible=1
        ORDER BY dateline DESC
        LIMIT 1");
        $lastpost = $db->fetch_array($query);
        $db->free_result($query);

        $last_post_info = array(
            "lastposttime" => $lastpost['dateline'],
            "lastposttid" => $lastpost['tid']
        );
        $db->update_query("socialgroups", $last_post_info, "gid=" . $gid);
        $userquery = $db->simple_select("socialgroup_threads", "DISTINCT uid", "tid=" . $tid);
        while($poster = $db->fetch_array($userquery))
        {
            $this->update_user_stats($poster['uid'], true);
        }
        $db->free_result($userquery);
        $socialgroups->update_cache();
        $data = array(
            "gid" => $thread['gid'],
            "conid" => $thread['tid'],
            "subject" => $db->escape_string($thread['subject']),
            "action" => $action
        );

        log_moderator_action($data, $action);
    }

    /** Recount the number of posts a group has.
     * When $tid is set it recounts the number of posts in that thread as well.
     * @param int $gid The id of the group.
     * @param int $tid The id of the thread.
     */

    function recount_posts(int $gid=0, int $tid=0)
    {
        global $db;
        if($gid > 0)
        {
            $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroups
            SET posts=(SELECT COUNT(pid) FROM " . TABLE_PREFIX . "socialgroup_posts WHERE gid=$gid AND visible=1)
            WHERE gid=$gid");
        }
        if($tid > 0)
        {
            $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads
            SET replies=(SELECT COUNT(pid) FROM " . TABLE_PREFIX . "socialgroup_posts WHERE gid=$gid AND visible=1 AND tid=$tid)
            WHERE gid=$gid AND tid=$tid");
        }
    }

    /** Recount the number of threads a group has.
     * @param int $gid The id of the group.
     */

    function recount_threads(int $gid=0)
    {
        global $db;
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroups
            SET threads=(SELECT COUNT(tid) FROM " . TABLE_PREFIX . "socialgroup_threads WHERE gid=$gid AND visible=1)
            WHERE gid=$gid");
    }

    /**
     * This function loads the threads for a group the user can see.
     * @param int $gid The id of the group.
     * @param int $page The page to load.
     * @param int $perpage How many to load.
     * @param array $sort An array of options to sort. (field, direction)
     * @return mixed|void An array on success. False on failure.
     */

    public function load_threads(int $gid=1, $page=1, $perpage=20, $sort=array())
    {
        global $mybb, $db, $socialgroups, $plugins;
        // First we have to see if the user can even see threads.
        if($mybb->usergroup['isbannedgroup'])
        {
            error_no_permission();
        }
        if($gid < 1)
        {
            $socialgroups->error("invalid_group");
        }
        $group = $socialgroups->load_group($gid);
        if($group['private'] == 1 && !is_member($gid, $mybb->user['uid']))
        {
            return;
        }
        $page = (int) $page;
        if($page < 1 || $page == "") // Fallback to prevent an error
        {
            $page = 1;
        }
        $perpage = (int) $perpage;
        if(!$perpage) // Fallback if it was forgotten to avoid an ugly error
        {
            $perpage = 20;
        }

        $start = $page * $perpage - $perpage;

        // Moderators and leaders can view unapproved threads
        if($socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']) || $socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']))
        {
            $visible = 0;
        }
        else
        {
            $visible = 1;
        }

        switch($sort['field'])
        {
            case "dateline":
                $sortfield = "t.dateline";
                break;

            case "username":
                $sortfield = "u.username";
                break;

            case "subject":
                $sortfield = "t.subject";
                break;

            case "replies":
                $sortfield = "t.replies";
                break;

            case "views":
                $sortfield = "t.views";
                break;

            default:
                $sortfield = "t.dateline";
                break;
        }

        if($sort['direction'] == "asc")
        {
            $sortdirection = "ASC";
        }
        else if($sort['direction'] == "desc")
        {
            $sortdirection = "DESC";
        }
        else
        {
            $sortdirection = "DESC";
        }

        $query = $db->query("SELECT t.*, p.*, u.*
        FROM " . TABLE_PREFIX . "socialgroup_threads t
        LEFT JOIN " . TABLE_PREFIX . "socialgroup_posts p ON(t.firstpost=p.pid)
        LEFT JOIN " . TABLE_PREFIX . "users u ON(t.uid=u.uid)
        WHERE t.gid=$gid AND t.visible >= $visible
        ORDER BY t.sticky DESC, $sortfield $sortdirection
        LIMIT $start , $perpage");

        while($thread = $db->fetch_array($query))
        {
            $threads[$thread['tid']] = $thread;
            // Do the profile links, avatars, and time management here to make it easy to access
            $thread['formattedname'] = format_name($thread['username'], $thread['usergroup'], $thread['displaygroup']);
            $thread['profilelink'] = build_profile_link($thread['formattedname'], $thread['uid']);
            $thread['lastposter_profilelink'] = build_profile_link($thread['lastpostusername'],$thread['lastpostuid']);
            // Only build the avatar if the usercp says yes
            if($mybb->user['showavatars'])
            {
                $thread['avatar'] = format_avatar($thread['avatar'], $thread['avatardimensions']);
            }
            $thread['dateline'] = my_date("relative", $thread['dateline']);
            $thread['lastposttime'] = my_date("relative", $thread['lastposttime']);
            $thread = $plugins->run_hooks("class_socialgroupsthreadhandler_load_thread", $thread);
            $socialgroups->threads[$gid][$thread['tid']] = $thread;
        }
        return $socialgroups->threads[$gid];
    }

    /**
     * This function loads posts in a thread.
     * @param int $gid The id of the group.
     * @param int $tid The id of the thread.
     * @param int $page The page number.
     * @param int $perpage How many to load.
     * @return mixed An array on success. Error page on failure.
     */
    public function load_posts(int $gid=0, int $tid=0, int $page=1, int $perpage=20)
    {
        global $db, $mybb, $cache, $templates, $socialgroups, $plugins;
        if(!$tid)
        {
            $socialgroups->error("invalid_thread");
        }
        if(!$gid)
        {
            // Attempt to load from thread.
            $query = $db->simple_select("socialgroup_threads", "*", "tid=$tid");
            $thread = $db->fetch_array($query);
            $gid = $thread['gid'];
            $socialgroups->thread[$gid][$thread['tid']] = $thread;
            if(!$gid)
            {
                $this->error("invalid_thread");
            }
        }
        $visible = 1;
        if($socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']))
        {
            $visible = 0;
        }
        if(!$perpage)
        {
            $perpage = 20;
        }
        if(!$page || $page < 1)
        {
            $page = 1;
        }
        if($page > 1)
        {
            // We have to figure out how many pages so we don't load any empty set.
            $countquery = $db->simple_select("socialgroup_posts", "COUNT(pid) as posts", "tid=$tid AND visible >=$visible");
            $total = $db->fetch_field($countquery, "posts");
            $pages = ceil($total / $perpage);
            if($page > $pages)
            {
                $page = $pages;
            }
        }
        $start = $page * $perpage - $perpage;
        $query = $db->query("SELECT p.dateline as postdateline, p.*, u.*, u.username AS userusername, f.*, t.*, eu.username AS editusername
        FROM " . TABLE_PREFIX . "socialgroup_posts p
        LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
        LEFT JOIN " . TABLE_PREFIX . "users u ON(p.uid=u.uid)
        LEFT JOIN ".TABLE_PREFIX."userfields f ON (u.uid=f.ufid)
        LEFT JOIN ".TABLE_PREFIX."users eu ON (eu.uid=p.lasteditby)
        WHERE p.tid=$tid AND t.visible >=$visible AND p.visible >= $visible
        ORDER BY p.dateline ASC
        LIMIT $start , $perpage");
        while($post = $db->fetch_array($query))
        {
            $post = $plugins->run_hooks("class_socialgroupsthreadhandler_load_post", $post);
            $post['dateline'] = $post['postdateline'];
            $socialgroups->posts[$tid][$post['pid']] = $post;
        } // End the post loop
        return $socialgroups->posts[$tid];
    }

    /**
     * This function updates a users socialgroup posts and thread counts.
     * @param int $userid The id of the user.
     * @param bool $update_post_count Whether to update post count as well.
     */
    public function update_user_stats(int $userid=0, bool $update_post_count=false)
    {
        global $db;
        $query = $db->simple_select("socialgroup_threads", "COUNT(tid) AS threadcount", "uid=" . $userid . " AND visible=1");
        $update_user = array(
            "socialgroups_threads" => $db->fetch_field($query, "threadcount")
        );
        $db->free_result($query);
        if($update_post_count === true)
        {
            $query = $db->query("SELECT COUNT(*) as postcount FROM " . TABLE_PREFIX . "socialgroup_posts p
            LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
            WHERE p.visible=1 AND t.visible=1 AND p.uid=" . $userid);
            $update_user['socialgroups_posts'] = $db->fetch_field($query, "postcount");
        }
        $db->update_query("users", $update_user, "uid=" . $userid);
    }
}