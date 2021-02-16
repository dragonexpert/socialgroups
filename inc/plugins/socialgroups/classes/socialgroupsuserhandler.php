<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

class socialgroupsuserhandler
{
    /* In functions where the parameter $uid is set to 0, it uses the current user for the parameter. */

    /* A cache of group members.
    * Format is $this->members[$gid][$uid] */

    public $members = array();

    /* A cache of group leaders.
    * Format is $this->leaders[$gid][$uid] */

    public $leaders = array();

    /* A cache of moderators.
   * Format is $this->moderators[$uid] */

    public $moderators = array();

    function __construct()
    {
        // Nothing
    }

    /* This function checks if a person is a member of the specified group. */

    public function is_member($gid=1, $uid=0)
    {
        global $mybb, $db, $socialgroups;
        $gid = (int) $gid;
        if(!$gid)
        {
            $socialgroups->error("invalid_group");
        }
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if($socialgroups->group[$gid]['uid'] == $uid)
        {
            return TRUE;
        }
        if(array_key_exists($gid, $this->members))
        {
            if(in_array($uid, $this->members[$gid]))
            {
                return TRUE;
            }
            return FALSE;
        }
        else
        {
            // we have to manually fetch
            $query = $db->simple_select("socialgroup_members", "*", "gid=$gid");
            while($user = $db->fetch_array($query))
            {
                $this->members[$gid][] = (int) $user['uid'];
            }
            if(in_array($uid, $this->members[$gid]))
            {
                return TRUE;
            }
            return FALSE;
        }
    }

    /* This function checks if a person is a group leader. */

    public function is_leader($gid=1, $uid=0)
    {
        global $db, $mybb, $socialgroups;
        $gid = (int) $gid;
        if(!$gid)
        {
            $socialgroups->error("invalid_group");
        }
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }

        if(array_key_exists($gid, $this->leaders))
        {
            if(in_array($uid, $this->leaders[$gid]))
            {
                return TRUE;
            }
            return FALSE;
        }
        else
        {
            $query = $db->simple_select("socialgroup_leaders", "*", "gid=$gid");
            $this->leaders[$gid] = array();
            while($leader = $db->fetch_array($query))
            {
                $this->leaders[$gid][$leader['uid']] = $leader['uid'];
            }
            if(in_array($uid, $this->leaders[$gid]))
            {
                return TRUE;
            }
            return FALSE;
        }
    }

    /* This function checks if a person is a moderator.*/

    public function is_moderator($gid=1, $uid=0)
    {
        global $mybb, $db;
        // Admins and global moderators should be automatic moderators
        if($mybb->usergroup['cancp'] || $mybb->usergroup['issupermod'])
        {
            return TRUE;
        }
        $gid = (int) $gid;
        if(!$gid)
        {
            // We return false here instead of above because admins should be allowed to moderate anything.
            return FALSE;
        }
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        // Check for any moderators for all social groups
        $moderators = $this->load_moderators($gid);
        if(in_array($uid, $moderators['users']))
        {
            return TRUE;
        }
        return FALSE;
    }

    /* This function loads the members of a group. */

    public function load_members($gid)
    {
        global $db, $socialgroups;
        $gid = (int) $gid;
        if(array_key_exists($gid, $this->members))
        {
            return $this->members[$gid];
        }
        $query = $db->simple_select("socialgroup_members", "*", "gid=$gid");
        if($db->num_rows($query) == 0)
        {
            $socialgroups->error("invalid_group");
        }
        while($user = $db->fetch_array($query))
        {
            $this->members[$gid][] = (int) $user['uid'];
        }
        return $this->members[$gid];
    }

    /* This function loads the moderators for social groups.  Typically called after fetching a group.
    * @Param 1: an optional parameter used to cache data if you need to call it later in the script.
    * @Return: associative array with the keys users and usergroups, both of which are arrays. */

    public function load_moderators($gid=1)
    {
        global $db;
        if(array_key_exists($gid, $this->moderators))
        {
            return $this->moderators[$gid];
        }
        $query = $db->simple_select("socialgroup_moderators", "uid");
        $this->moderators[$gid]['users'] = array();
        while($moderator = $db->fetch_array($query))
        {
            $this->moderators[$gid]['users'][] = $moderator['uid'];
        }
        // Now capture the usergroups
        $query = $db->simple_select("usergroups", "gid", "issupermod=1");
        $this->moderators[$gid]['usergroups'] = array();
        while($moderator = $db->fetch_array($query))
        {
            $this->moderators[$gid]['usergroups'][] = $moderator['gid'];
        }
        return $this->moderators[$gid];
    }

    public function load_leaders($gid=1)
    {
        global $db;
        if(array_key_exists($gid, $this->leaders))
        {
            return $this->leaders[$gid];
        }
        $query = $db->simple_select("socialgroup_leaders", "uid", "gid=" . (int) $gid);
        while($leader = $db->fetch_array($query))
        {
            $this->leaders[$gid][] = $leader['uid'];
        }
        return $this->leaders[$gid];
    }

    /* This checks if a person is able to join a group. */

    public function can_join($gid=1, $uid=0)
    {
        global $mybb, $db;
        $gid = (int) $gid;
        if(!$gid)
        {
            return FALSE;
        }
        $cid = $this->group[$gid]['cid'];
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if(!$uid) // it must be a guest
        {
            return FALSE;
        }
        if($this->is_member($gid, $uid))
        {
            return FALSE;
        }
        if($this->group[$gid]['staffonly'] && !$this->is_moderator($gid, $uid))
        {
            return FALSE;
        }
        if($this->category[$cid]['staffonly'] && !$this->is_moderator($gid, $uid))
        {
            return FALSE;
        }
        if($this->group[$gid]['inviteonly'] && !$this->has_invite($gid, $uid))
        {
            return FALSE;
        }
        return TRUE;
    }

    /* This function checks if a member has an invitation to join a group. */

    public function has_invite($gid=1, $uid=0)
    {
        global $db, $mybb;
        $gid = (int) $gid;
        $uid = (int) $uid;
        if(!$gid)
        {
            return FALSE;
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if(!$uid)
        {
            return FALSE;
        }
        $query = $db->simple_select("socialgroup_invites", "*", "gid=$gid AND touid=$uid");
        if($db->num_rows($query))
        {
            return TRUE;
        }
        return FALSE;
    }

    /* This function puts a member in a group.
    * The force parameter forces a person into the group.  Useful for ACP implementation or invites. */

    public function join($gid=0, $uid=0, $force=0)
    {
        global $db, $mybb, $socialgroups;
        $gid = (int) $gid;
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if($force != 1 && !$this->can_join($gid, $uid))
        {
            $socialgroups->error("cant_join");
        }
        $new_member = array(
            "uid" => $uid,
            "gid" => $gid
        );
        $db->insert_query("socialgroup_members", $new_member);
        $db->delete_query("socialgroup_invites", "touid=$uid AND gid=$gid");
        $db->delete_query("socialgroup_join_requests", "uid=$uid AND gid=$gid");
    }

    /* Remove a member from a group. */

    public function remove_member($gid, $uid=0)
    {
        global $mybb, $db, $socialgroups;
        if(!$this->is_member($gid, $uid) || $this->is_leader($gid, $uid))
        {
            $socialgroups->error("invalid_user");
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        $db->delete_query("socialgroup_members", "uid=$uid AND gid=$gid");
    }

    public function add_leader($gid, $uid=0)
    {
        global $db, $mybb;
        $uid = (int) $uid;
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        // First check if the person is a leader of the group.
        if($this->is_leader($gid, $uid))
        {
            return false;
        }
        $new_leader = array(
            "gid" => (int) $gid,
            "uid" => (int) $uid
        );
        $lid = $db->insert_query("socialgroup_leaders", $new_leader);
        return $lid;
    }

    public function remove_leader($gid, $uid=0, $admin_overide = false)
    {
        global $db, $mybb, $socialgroups;
        // First load the group.
        $groupinfo = $socialgroups->load_group($gid);
        $uid = (int) $uid;
        if($admin_overide)
        {
            if(!$mybb->usergroup['cancp'])
            {
                error_no_permission();
            }
            // Don't remove group owner.  Must use transfer ownership.
            if($uid == $groupinfo['uid'])
            {
                error_no_permission();
            }
            if($this->is_leader($gid, $uid))
            {
                $db->delete_query("socialgroup_leaders", "uid=" . $uid);
                return true;
            }
            return false;
        }
        else
        {
            if($mybb->user['uid'] != $groupinfo['uid'])
            {
                error_no_permission();
            }
            if($uid == $groupinfo['uid'])
            {
                error_no_permission();
            }
            if($this->is_leader($gid, $uid))
            {
                $db->delete_query("socialgroup_leaders", "uid=" . $uid);
                return true;
            }
            return false;
        }
    }

    public function transfer_ownership($original_owner, $new_owner, $gid, $stay_leader=true)
    {
        global $mybb, $db;
        if(!$stay_leader)
        {
            $db->delete_query("socialgroup_leaders", "gid=$gid AND uid=$original_owner");
        }
        if(!$this->is_member($gid, $new_owner))
        {
            $this->join($gid, $new_owner, 1);
        }
        $update_group = array(
            "uid" => $new_owner
        );
        $db->update_query("socialgroups", $update_group, "gid=$gid");
        if(!$this->is_leader($gid, $new_owner))
        {
            $this->add_leader($gid, $new_owner);
        }
    }

    public function can_groupcp($userid)
    {
        global $mybb, $db;
        $userid = (int) $userid;
        if($mybb->usergroup['issupermod'] || $mybb->usergroup['cancp'])
        {
            return true;
        }
        $query = $db->simple_select("socialgroup_moderators", "mid,uid");
        $moderators = array();
        while($moderator = $db->fetch_array($query))
        {
            $moderators[$moderator['mid']] = $moderator['uid'];
        }
        if(in_array($userid, $moderators))
        {
            return true;
        }
        // Still no?  Check if they are the leader of one or more groups.
        $query = $db->simple_select("socialgroup_leaders", "*", "uid=$userid");
        if($db->num_rows($query) == 0)
        {
            return false;
        }
        $my_groups = array();
        while($leader = $db->fetch_array($query))
        {
            $my_groups[] = $leader['gid'];
        }
        return $my_groups;
    }
}