<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */

if(!defined("IN_MYBB"))
{
    die("Direct access to the user handler is not allowed.");
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

    /**
     * This function checks if a person is a member of the specified group.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     * @return bool Whether the user is a member
     */

    public function is_member(int $gid=1, int $uid=0): bool
    {
        global $mybb, $db, $socialgroups;
        if(!$gid)
        {
            $socialgroups->error("invalid_group");
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if($socialgroups->group[$gid]['uid'] == $uid)
        {
            return true;
        }
        if(array_key_exists($gid, $this->members))
        {
            if(in_array($uid, $this->members[$gid]))
            {
                return true;
            }
            return false;
        }
        else
        {
            // we have to manually fetch
            $query = $db->simple_select("socialgroup_members", "*", "gid=" . $gid . " AND uid=" . $uid);
            if($db->num_rows($query) != 0)
            {
                $user = $db->fetch_array($query);
                $this->members[$gid][] = $user['uid'];
                return true;
            }
            return false;
        }
    }

    /**
     * This function checks if a person is a group leader.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     * @return bool Whether the user is a leader of the group.
     */

    public function is_leader(int $gid=1, int $uid=0): bool
    {
        global $db, $mybb, $socialgroups;
        if(!$gid)
        {
            $socialgroups->error("invalid_group");
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }

        if(array_key_exists($gid, $this->leaders))
        {
            if(in_array($uid, $this->leaders[$gid]))
            {
                return true;
            }
            return false;
        }
        else
        {
            $query = $db->simple_select("socialgroup_leaders", "*", "gid=$gid");
            $this->leaders[$gid] = array();
            while($leader = $db->fetch_array($query))
            {
                $this->leaders[$gid][$leader['uid']] = $leader['uid'];
            }
            $db->free_result($query);
            if(in_array($uid, $this->leaders[$gid]))
            {
                return true;
            }
            return false;
        }
    }

    /**
     * This function checks if a person is a moderator.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     * @return bool Whether the user is a moderator of the group.
     */

    public function is_moderator(int $gid=1, int $uid=0): bool
    {
        global $mybb, $db;
        // Admins and global moderators should be automatic moderators
        if($mybb->usergroup['cancp'] || $mybb->usergroup['issupermod'])
        {
            return true;
        }
        if(!$gid)
        {
            // We return false here instead of above because admins should be allowed to moderate anything.
            return false;
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        // Check for any moderators for all social groups
        $moderators = $this->load_moderators($gid);
        if(in_array($uid, $moderators['users']))
        {
            return true;
        }
        return false;
    }

    /**
     * This function loads the members of a group.
     * @param int $gid The id of the group.
     * @return Array An array of group members.
     */

    public function load_members(int $gid=0): array
    {
        global $db, $socialgroups;
        if(array_key_exists($gid, $this->members))
        {
            return $this->members[$gid];
        }
        $query = $db->simple_select("socialgroup_members", "*", "gid=$gid");
        if(!$db->num_rows($query))
        {
            $socialgroups->error("invalid_group");
        }
        while($user = $db->fetch_array($query))
        {
            $this->members[$gid][] = (int) $user['uid'];
        }
        $db->free_result($query);
        return $this->members[$gid];
    }

    /**
     * This function loads the moderators for social groups.  Typically called after fetching a group.
     * @param int $gid The id of the group.
     * @return Array Associative array with the keys users and usergroups, both of which are arrays.
     */

    public function load_moderators(int $gid=1) : array
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
        $db->free_result($query);
        // Now capture the usergroups
        $query = $db->simple_select("usergroups", "gid", "issupermod=1");
        $this->moderators[$gid]['usergroups'] = array();
        while($moderator = $db->fetch_array($query))
        {
            $this->moderators[$gid]['usergroups'][] = $moderator['gid'];
        }
        $db->free_result($query);
        return $this->moderators[$gid];
    }

    /**
     * This function loads all leaders of a group.
     * @param int $gid The id of the group.
     * @return Array An array of leaders.
     */

    public function load_leaders(int $gid=1): array
    {
        global $db;
        if(array_key_exists($gid, $this->leaders))
        {
            return $this->leaders[$gid];
        }
        $query = $db->simple_select("socialgroup_leaders", "uid", "gid=" . $gid);
        while($leader = $db->fetch_array($query))
        {
            $this->leaders[$gid][] = $leader['uid'];
        }
        $db->free_result($query);
        return $this->leaders[$gid];
    }

    /**
     * This checks if a person is able to join a group.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     * @return bool Whether the user can join.
     */

    public function can_join(int $gid=1, int $uid=0): bool
    {
        global $mybb, $socialgroups, $plugins;
        if(!$gid)
        {
            return false;
        }
        if(isset($socialgroups->group[$gid]))
        {
            $cid = $socialgroups->group[$gid]['cid'];
        }
        else
        {
            $socialgroups->load_group($gid);
            $cid = $socialgroups->group[$gid]['cid'];
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        $status = true;
        if(!$uid) // it must be a guest
        {
            $status = false;
        }
        if($this->is_member($gid, $uid))
        {
            $status = false;
        }
        if($socialgroups->group[$gid]['staffonly'] && !$this->is_moderator($gid, $uid))
        {
            $status = false;
        }
        if($socialgroups->category[$cid]['staffonly'] && !$this->is_moderator($gid, $uid))
        {
            $status = false;
        }
        if($socialgroups->group[$gid]['inviteonly'] && !$this->has_invite($gid, $uid))
        {
            $status = false;
        }
        if($socialgroups->group[$gid]['jointype'] == 1 && $this->has_join_request($gid, $uid))
        {
            $status = false;
        }
        // Hook in case you want to work with Newpoints or some other stuff
        $status = $plugins->run_hooks("class_socialgroupsuserhandler_can_join", $status);
        return $status;
    }

    /**
     * This function checks if a member has an invitation to join a group.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     * @return bool Whether the user has an invite to the group.
     */

    public function has_invite(int $gid=1, int $uid=0): bool
    {
        global $db, $mybb;
        if(!$gid)
        {
            return false;
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        if(!$uid)
        {
            return false;
        }
        $query = $db->simple_select("socialgroup_invites", "*", "gid=$gid AND touid=$uid");
        if($db->num_rows($query))
        {
            return true;
        }
        return false;
    }

    /**
     * This checks if a join request exists for the user.
     * @param int $gid  The id of the group.
     * @param int $uid The id of the user.
     * @return bool Whether the user has a join request for the group.
     */

    public function has_join_request(int $gid=0, int $uid=0): bool
    {
        global $db;
        $query = $db->simple_select("socialgroup_join_requests", "*", "gid=" . $gid . " AND uid=" . $uid);
        if($db->num_rows($query) >= 1)
        {
            $db->free_result($query);
            return true;
        }
        $db->free_result($query);
        return false;
    }

    /**
     * This function puts a member in a group.
     * @param int $gid The id of the group.
     * @param int $uid THe id of the user.
     * @param int $force Whether to insert regardless of permissions.
     */

    public function join(int $gid=0, int $uid=0, int $force=0)
    {
        global $db, $mybb, $socialgroups, $plugins;
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
            "gid" => $gid,
            "dateline" => time()
        );
        $db->insert_query("socialgroup_members", $new_member);
        $db->delete_query("socialgroup_invites", "touid=$uid AND gid=$gid");
        $db->delete_query("socialgroup_join_requests", "uid=$uid AND gid=$gid");
        $plugins->run_hooks("class_socialgroupsuserhandler_join_group");
    }

    /**
     * Remove a member from a group.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     */

    public function remove_member(int $gid=0, int $uid=0)
    {
        global $mybb, $db, $socialgroups, $plugins;
        if(!$this->is_member($gid, $uid) || $this->is_leader($gid, $uid))
        {
            $socialgroups->error("invalid_user");
        }
        if(!$uid)
        {
            $uid = $mybb->user['uid'];
        }
        $db->delete_query("socialgroup_members", "uid=$uid AND gid=$gid");
        $plugins->run_hooks("class_socialgroupsuserhandler_remove_member");
    }

    /**
     * This function adds a leader to a group.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     * @return false|int False on failure and the id of the leader on success.
     */

    public function add_leader(int $gid=0, int $uid=0)
    {
        global $db, $mybb, $plugins;
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
            "gid" =>  $gid,
            "uid" =>  $uid
        );
        $lid = $db->insert_query("socialgroup_leaders", $new_leader);
        $plugins->run_hooks("class_socialgroupsuserhandler_add_leader");
        return $lid;
    }

    /**
     * This removes a leader from a group.
     * @param int $gid The id of the group.
     * @param int $uid The id of the user.
     * @param bool $admin_overide Allow an admin overide. Mainly for Admin CP.
     * @return bool Whether the leader got deleted.
     */

    public function remove_leader(int $gid=0, int $uid=0, bool $admin_overide = false): bool
    {
        global $db, $mybb, $socialgroups, $plugins;
        // First load the group.
        $groupinfo = $socialgroups->load_group($gid);
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
                $plugins->run_hooks("class_socialgroupsuserhandler_remove_leader");
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
                $plugins->run_hooks("class_socialgroupsuserhandler_remove_leader");
                return true;
            }
            return false;
        }
    }

    /**
     * This transfers ownership of the group to someone else.
     * @param int $original_owner The id of the owner.
     * @param int $new_owner The id of the new owner.
     * @param int $gid The id of the group.
     * @param bool $stay_leader Whether the old owner should stay as a leader.
     * @return bool Whether the transfer was successful.
     */

    public function transfer_ownership(int $original_owner=0, int $new_owner=0, int $gid=0,bool $stay_leader=true): bool
    {
        global $mybb, $db;
        if($original_owner == $new_owner)
        {
            return false;
        }
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
        return true;
    }

    /**
     * This determines if a user can access Group CP.
     * @param int $userid The id of the user.
     * @return array|bool true if global moderator. False if no groups. Array if not moderator, but leader.
     */

    public function can_groupcp(int $userid=0)
    {
        global $mybb, $db;
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
        $db->free_result($query);
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
        $db->free_result($query);
        return $my_groups;
    }

    /**
     * This generates HTML for who is viewing a particular group.
     * Uses the who's online cutoff.
     * @param int $gid The id of the group.
     */

    public function viewing_group(int $gid=0)
    {
        global $db, $mybb, $socialgroups, $templates, $online_members;
        if(!$gid)
        {
            $socialgroups->error("invalid_group");
        }
        $cutoff = time() - 60 * $mybb->settings['wolcutoffmins'];

        // Account for invisible users.
        $invisible = 0;
        if($mybb->usergroup['canviewwolinvis'])
        {
            $invisible = 1;
        }

        $query = $db->query("SELECT u.username, u.usergroup, u.displaygroup, u.invisible, s.uid FROM " . TABLE_PREFIX . "users u
        LEFT JOIN " . TABLE_PREFIX . "sessions s ON(u.uid=s.uid)
        WHERE s.location LIKE '%showgroup.php?gid=" . $gid
            . "%' AND s.time >= " . $cutoff . " AND u.invisible <= " . $invisible);
        $comma = $online_members = "";
        while($user = $db->fetch_array($query))
        {
            $invisiblemark = "";
            if($user['invisible'] && $mybb->usergroup['canviewwolinvis'])
            {
                $invisiblemark = "*";
            }
            $user['formattedname'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
            $user['profilelink'] = build_profile_link($user['formattedname'], $user['uid']);
            eval("\$online_members .= \"".$templates->get("forumdisplay_usersbrowsing_user")."\";");
            $comma = ",";
        }
        $db->free_result($query);
    }
}
