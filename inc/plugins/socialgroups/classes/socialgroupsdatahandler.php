<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
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
        }
        $socialgroups->update_cache();
    }
}
