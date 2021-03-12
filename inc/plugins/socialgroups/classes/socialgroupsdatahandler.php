<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This file handles database writes for group changes.
 */
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed to the data handler.");
}

class socialgroupsdatahandler
{

    function __construct()
    {
        // Nothing yet
    }

    /**
     * A function to update a group.
     * @param array $data An array of data about the group.
     * @param string $method Either insert or update.
     * @param string $where The where clause.  Used for update.
     */

    public function save_group(array $data, string $method="update", string $where="")
    {
        global $mybb, $db, $plugins, $lang, $socialgroups, $socialgroupsuserhandler;
        if($method != "insert" && $method != "update")
        {
            $socialgroups->error("invalid_method");
        }
        // Make a set array with types then make it safe
        $fieldtypes = array(
            "gid" => "int",
            "cid" => "int",
            "name" => "text",
            "description" => "text",
            "logo" => "text",
            "private" => "int",
            "staffonly" => "int",
            "inviteonly" => "int",
            "uid" => "int",
            "threads" => "int",
            "posts" => "int"
        );

        // Let other plugins be able to hook into this.
        $plugins->run_hooks("class_socialgroups_socialgroupsdatahandler_save_fields", $fieldtypes);

        foreach($data as $key => $value)
        {
            if(in_array($key, $fieldtypes))
            {
                $type = $fieldtypes[$key];
            }
            else
            {
                $type = "text";
            }
            if($type == "int")
            {
                $data[$key] = (int) $data[$key];
                if($data[$key] < 0 || strlen($data[$key]) == 0)// Provide a fallback to negative values
                {
                    $data[$key] = 0;
                }
            }
            else if($type == "text")
            {
                $data[$key] = $db->escape_string($data[$key]);
            }
            else if($type == "bin")
            {
                $data[$key] = $db->escape_binary($data[$key]);
            }
            else // If they forget to assign it, fallback to escaping as a string
            {
                $data[$key] = $db->escape_string($data[$key]);
            }
        } // End loop

        // Check that a where clause exists.  Fail if it doesn't.
        if($method == "update" && !$where)
        {
            $socialgroups->error("no_where_clause");
        }
        if($method == "update")
        {
            $db->update_query("socialgroups", $data, $where);
        }
        if($method == "insert")
        {
            if(!$data['uid'])
            {
                $data['uid'] = $mybb->user['uid'];
            }
            $gid = $db->insert_query("socialgroups", $data);
            $socialgroups->socialgroupsuserhandler->join($gid, $data['uid'], 1);
            $socialgroups->socialgroupsuserhandler->add_leader($gid, $data['uid']);
            // Need to update the number of groups in a category
            $query = $db->simple_select("socialgroups", "COUNT(gid) AS category_count", "cid=" . $data['cid']);
            $category_count = $db->fetch_field($query, "category_count");
            $update_category = array(
                "groups" => $category_count
            );
            $db->update_query("socialgroup_categories", $update_category, "cid=" . $data['cid']);
            $socialgroups->update_socialgroups_category_cache();
        }
        $socialgroups->update_cache();
    }

    /**
     * This function deletes a group.
     * @param int $gid The id of the group.
     * @return bool Whether the operation succeeded.
     */
    function delete_group(int $gid=0): bool
    {
        global $db, $socialgroups, $plugins;
        if($gid <= 0)
        {
            return false;
        }
        // Get the data about a group.
        $groupquery = $db->simple_select("socialgroups", "*", "gid=" . $gid);
        $data = $db->fetch_array($groupquery);
        if(!$data['gid'])
        {
            return false;
        }
        $plugins->run_hooks("socialgroupsdatahandler_delete_group");
        $db->delete_query("socialgroups", "gid=$gid");
        $db->delete_query("socialgroup_members", "gid=$gid");
        $db->delete_query("socialgroup_member_permissions", "gid=$gid");
        $db->delete_query("socialgroup_leaders", "gid=$gid");
        $db->delete_query("socialgroup_invites", "gid=$gid");
        $db->delete_query("socialgroup_announcements", "gid=$gid");
        $db->delete_query("socialgroup_threads", "gid=$gid");
        $db->delete_query("socialgroup_posts", "gid=$gid");
        $query = $db->simple_select("socialgroups", "COUNT(gid) AS category_count", "cid=" . $data['cid']);
        $category_count = $db->fetch_field($query, "category_count");
        $update_category = array(
            "groups" => $category_count
        );
        $db->update_query("socialgroup_categories", $update_category, "cid=" . $data['cid']);
        $socialgroups->update_cache();
        $socialgroups->update_socialgroups_category_cache();
        return true;
    }
}
