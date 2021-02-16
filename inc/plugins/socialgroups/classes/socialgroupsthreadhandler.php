<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

class socialgroupsthreadhandler
{

    function __construct()
    {
        // Nothing
    }

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
            "replies" => 0,
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
        $plugins->run_hooks("socialgroupsthreadhandler_insert_thread");
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
        $message = "The thread has been posted.";
        redirect("groupthread.php?tid=$tid", $message);
    }

    public function new_post($data=array())
    {
        global $mybb, $db, $socialgroups, $plugins;
        if(!$data['gid'])
        {
            $socialgroups->error("invalid_group");
        }
        if(!$data['tid'])
        {
            $socialgroups->error("invalid_thread");
        }
        if(!$data['uid'])
        {
            $data['uid'] = $mybb->user['uid'];
        }
        if(!$data['message'])
        {
            $socialgroups->error("no_message");
        }
        $new_post = array(
            "tid" => (int)$data['tid'],
            "uid" => (int) $data['uid'],
            "gid" => (int) $data['gid'],
            "dateline" => TIME_NOW,
            "visible" => 1,
            "ipaddress" => get_ip(),
            "message" => $db->escape_string($data['message'])
        );
        $cutoff = TIME_NOW - 3600; /* 1 hour post merge */
        $plugins->run_hooks("socialgroupsthreadhandler_insert_post");
        // Flood check
        $query = $db->simple_select("socialgroup_posts", "pid, editcount, uid, message, dateline", "tid=" . $new_post['tid'] . " ORDER BY dateline DESC LIMIT 1");
        $postinfo = $db->fetch_array($query);
        if($postinfo['uid'] != $new_post['uid'] || $postinfo['dateline'] < $cutoff)
        {
            $db->insert_query("socialgroup_posts", $new_post);
        }
        else
        {
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
            $updatethread = TRUE;
        }
        if(array_key_exists("sticky", $data))
        {
            $updated_thread['sticky'] = (int) $data['sticky'];
            $updatethread = TRUE;
        }
        if($updatethread)
        {
            $db->update_query("socialgroup_threads", $updated_thread, "tid=" . $data['tid']);
        }
        $this->recount_posts($new_post['gid'], $new_post['tid']);
        $this->recount_threads($new_post['gid']);
        $message = "Your message has been posted.";
        redirect("groupthread.php?tid=" . $new_post['tid'], $message);
    }

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

        $plugins->run_hooks("socialgroups_update_post");
        $db->update_query("socialgroup_posts", $updated_post, "pid=" . $postinfo['pid']);
        $this->recount_posts($postinfo['gid'], $postinfo['tid']);
        $this->recount_threads($postinfo['gid']);
        $message = "The post has been updated.";
        redirect("groupthread.php?tid=" . $postinfo['tid'], $message);
    }

    /* Delete a post.
   * $permanent: if 1, the post is removed from the database.
   * If not 1, the post can be restored in the ACP.
   * Only the group owner or moderators can actually delete posts. */

    public function delete_post($pid, $gid=0, $permanent=0)
    {
        global $mybb, $db, $lang, $socialgroups;
        $pid = (int) $pid;
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
            $query = $db->query("SELECT p.*, t.*
            FROM " . TABLE_PREFIX . "socialgroup_posts
            LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
            WHERE p.pid=$pid");
            $thread = $db->fetch_array($query);
            if($permanent == 1)
            {
                $db->delete_query("socialgroup_posts", "pid=$pid");
                $action = $lang->soft_delete_post;
            }
            else
            {
                $action = $lang->delete_post;
                $db->update_query("socialgroup_posts", array("visible" => -1), "pid=$pid");
            }
            $this->recount_posts($gid, $thread['tid']);

            // We use conid instead of tid because otherwise the mod log will try and fetch a thread.
            $data = array(
                "gid" => $thread['gid'],
                "subject" => $db->escape_string($thread['subject']),
                "conid" => $thread['tid']
            );
            log_moderator_action($data, $action);
        }
        else
        {
            error_no_permission();
        }
    }

    /* This function deletes a thread.
    * When $permanent is 1 and the user is a moderator, the thread is permanently deleted.
    * All other cases result in soft deletion */

    public function delete_thread($tid=0, $gid=0, $permanent=0)
    {
        global $db, $mybb, $lang, $socialgroups;
        $gid = (int) $gid;
        $tid = (int) $tid;
        if(!$tid)
        {
            $this->error("invalid_thread");
        }
        // Fetch the thread info
        $query = $db->simple_select("socialgroup_threads", "*", "tid=$tid");
        $thread = $db->fetch_array($query);
        if($socialgroups->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid'] && $permanent == 1))
        {
            $db->delete_query("socialgroup_threads", "tid=$tid");
            $db->delete_query("socialgroup_posts", "tid=$tid");
            $action = $lang->delete_thread;
        }
        else if($socialgroups->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']) || $socialgroups->socialgroupsuserhandler->is_moderator($gid, $uid) && $permanent != 1)
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

        $data = array(
            "gid" => $thread['gid'],
            "conid" => $thread['tid'],
            "subject" => $db->escape_string($thread['subject']),
        );

        log_moderator_action($data, $action);
    }

    /* Recount the number of posts a group has.
* When $tid is set it recounts the number of posts in that thread as well. */

    function recount_posts($gid, $tid=0)
    {
        global $db;
        $gid = (int) $gid;
        $tid = (int) $tid;
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroups
            SET posts=(SELECT COUNT(pid) FROM " . TABLE_PREFIX . "socialgroup_posts WHERE gid=$gid AND visible=1)
            WHERE gid=$gid");

        if($tid)
        {
            $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroup_threads
            SET replies=(SELECT COUNT(pid) FROM " . TABLE_PREFIX . "socialgroup_posts WHERE gid=$gid AND visible=1 AND tid=$tid)
            WHERE gid=$gid AND tid=$tid");
        }
    }

    /* Recount the number of threads a group has. */

    function recount_threads($gid)
    {
        global $db;
        $gid = (int) $gid;
        $db->write_query("UPDATE " . TABLE_PREFIX . "socialgroups
            SET threads=(SELECT COUNT(tid) FROM " . TABLE_PREFIX . "socialgroup_threads WHERE gid=$gid AND visible=1)
            WHERE gid=$gid");
    }
}