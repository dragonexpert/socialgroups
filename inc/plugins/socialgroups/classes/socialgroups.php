<?php
/**
 * Socialgroups plugin created by Mark Janssen.
 * This is not a free plugin.
 */

if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

class socialgroups
{
    /* The version of socialgroups */
    public $version = 1800;

    /* A cache of social groups already loaded
    * Format is $this->group[$gid]['field'] */

    public $group = array();

    /* A listing of all groups with information. */

    public $group_list = array();

    /* A cache of categories loaded.
    * Format is $this->category[$cid][$field] */

    public $category = array();

    /* An array of viewable categories for the current user.
    * Format is $this->viewable_categories[$cid] = $name */

    public $viewable_categories= array();


    /* A cache of info about the group.
    * Format is $this->permissions[$gid][$field] */

    public $permissions = array();

    /* A cache of announcements for the group.
    * Format is $this->announcements[$gid][$aid] */

    public $announcements = array();

    /* A cache of posts.
    * Format is $this->posts[$tid][$pid][$field] */

    public $posts = array();

    /* This can be used to set up some information about our object.  When a group is supplied, all necessary data is loaded.
    * $gids Mixed: Can be either an array or an integer of the group(s) you wish to load.
    * $loadannouncements: Whether announcements for the group should be loaded.
    * $loadmembers: Whether the members of the group should be loaded.
    * $loadmoderators: Whether moderators for social groups should be loaded.
    * $loadleaders: Whether the group leaders should be loaded.
    */

    public function __construct($gids=0, $loadannouncements=0, $loadmembers=1, $loadmoderators=1, $loadleaders=1)
    {
        global $mybb, $lang;
        $lang->load("socialgroups");
        if(!$mybb->settings['socialgroups_enable'] || $mybb->settings['no_plugins'] == 1 || defined("NO_PLUGINS"))
        {
            error($lang->socialgroups_not_enabled);
        }
        // Load the rest of the classes.
        $files = scandir(MYBB_ROOT . "/inc/plugins/socialgroups/classes");
        foreach($files as $file)
        {
            if(preg_match("/\A(.*)\.php\Z/is", $file) && $file != "socialgroups.php" && $file !== "." && $file !== "..")
            {
                require_once $file;
                $class = str_replace(".php", "", $file);
                if(class_exists($class, false)) // Provide a failsafe incase someone doesn't upload a class file to the directory
                {
                    $this->$class = new $class();
                }
            }
        }

        if(!$gids)
        {
            return;
        }
        if(is_array($gids))
        {
            foreach($gids as $gid)
            {
                $this->load_group($gid);
                $this->load_category($this->group[$gid]['cid']);
                if($loadannouncements == 1)
                {
                    $this->load_announcements($gid);
                }
                if($loadmembers == 1)
                {
                    $this->socialgroupsuserhandler->load_members($gid);
                }
                if($loadmoderators == 1)
                {
                    $this->socialgroupsuserhandler->load_moderators($gid);
                }
                if($loadleaders == 1)
                {
                    $this->socialgroupsuserhandler->load_leaders($gid);
                }
            }
        }
        if(is_numeric($gids))
        {
            $this->load_group($gids);
            $this->load_category($this->group[$gids]['cid']);
            if($loadannouncements == 1)
            {
                $this->load_announcements($gids);
            }
            if($loadmembers == 1)
            {
                $this->socialgroupsuserhandler->load_members($gids);
            }
            if($loadmoderators == 1)
            {
                $this->socialgroupsuserhandler->load_moderators($gids);
            }
            if($loadleaders == 1)
            {
                $this->socialgroupsuserhandler->load_leaders($gids);
            }
        }
    }

    /* A generic error function that prepends socialgroups_ to the string for language purposes. */

    public function error($string)
    {
        global $lang;
        $variable = "socialgroups_" . $string;
        error($lang->$variable);
    }

    /* A function to generate a link to a group.*/

    public function socialgroups_grouplink($gid, $name, $action="")
    {
        global $mybb;
        $gid = (int) $gid;
        $name = htmlspecialchars_uni($name);
        $action = htmlspecialchars_uni($action);
        $grouplink = "<a href='" . $mybb->settings['bburl'] . "/showgroup.php?gid=$gid";
        if($action)
        {
            $grouplink .= "&amp;action=$action";
        }
        $grouplink .= "'>$name</a>";
        return $grouplink;
    }



    /* This function loads the selected group. This should generally be the first method called. */

    public function load_group($gid=1)
    {
        global $mybb, $lang, $db, $plugins;
        if(!is_numeric($gid))
        {
            $this->error("invalid_group");
        }
        if($gid < 1)
        {
            $this->error("invalid_group");
        }

        $query = $db->simple_select("socialgroups", "*", "gid=$gid");
        $group = $db->fetch_array($query);
        $group['name'] = htmlspecialchars_uni($group['name']);
        $group['description'] = htmlspecialchars_uni($group['description']);
        $group['logo'] = htmlspecialchars_uni($group['logo']);
        // Add a hook so other devs can use it to load conveniently
        $plugins->run_hooks("class_socialgroups_load_group", $group);
        $this->group[$gid] = $group;
        return $this->group[$gid];
    }

    /* This function loads a category.  Typically called after loading a group. */

    public function load_category($cid=1)
    {
        global $db;
        $cid = (int) $cid;
        if(!$cid)
        {
            $this->error("invalid_category");
        }
        if(array_key_exists($cid, $this->category))
        {
            return $this->category[$cid];
        }
        $query = $db->simple_select("socialgroup_categories", "*", "cid=$cid");
        if(!$db->num_rows($query))
        {
            $this->error("invalid_category");
        }
        $this->category[$cid] = $db->fetch_array($query);
        return $this->category[$cid];
    }

    /* This function loads some permissions about a group.  This should be called after fetching a group. */

    public function load_permissions($gid=1)
    {
        global $db;
        $gid = (int) $gid;
        if(array_key_exists($gid, $this->permissions))
        {
            return $this->permissions[$gid];
        }
        else
        {
            // Fetch permissions from the database
            $query = $db->simple_select("socialgroup_member_permissions", "*", "gid=$gid");
            if(!$db->num_rows($query))
            {
                $this->permissions[$gid] = array("postthread"=> 1, "postreplies"=> 1, "inviteusers" => 1, "deleteposts" => 0);
            }
            $this->permissions[$gid] = $db->fetch_array($query);
        }
    }



    /* This function loads the announcements for a group including global announcements. */

    public function load_announcements($gid=0, $showhidden=false, $limit=5)
    {
        global $db;
        $gid = (int) $gid;
        $limit = (int) $limit;
        if($limit < 1)
        {
            $limit = 1;
        }
        if($showhidden)
        {
            $hidden = " OR active=0 ";
        }
        if(array_key_exists($gid, $this->announcements))
        {
            return $this->announcements[$gid];
        }
        $this->announcements[$gid] = array();
        // Treat $gid = 0 as all groups
        if($gid == 0)
        {
            // Order by gid first because 0 are global and therefore, most likely important as they are admin set.
            $query = $db->query("SELECT a.*, u.username, u.usergroup, u.displaygroup
            FROM " . TABLE_PREFIX . "socialgroup_announcements a
            LEFT JOIN " . TABLE_PREFIX . "users u ON(a.uid=u.uid)
            WHERE active=1 $hidden
            ORDER BY a.gid ASC, a.dateline DESC
            LIMIT $limit");
        }
        else
        {
            $query = $db->query("SELECT a.*, u.username, u.usergroup, u.displaygroup, u.avatar, u.avatartype, u.avatardimensions
            FROM " . TABLE_PREFIX . "socialgroup_announcements a
            LEFT JOIN " . TABLE_PREFIX . "users u ON(a.uid=u.uid)
            WHERE active=1 AND gid IN(0,$gid)
            ORDER BY a.gid ASC, a.dateline DESC
            LIMIT $limit");
        }
        while($announcement = $db->fetch_array($query))
        {
            $announcement['formattedname'] = format_name($announcement['username'], $announcement['usergroup'], $announcement['displaygroup']);
            $announcement['profilelink'] = build_profile_link($announcement['formattedname'], $announcement['uid']);
            $announcement['dateline'] = my_date("relative", $announcement['dateline']);
            $announcement['avatar'] = format_avatar($announcement['avatar'], $announcement['avatardimensions']);
            $this->announcements[$gid][$announcement['aid']] = $announcement;
        }
        return $this->announcements[$gid];
    }

    /*
    * $gid The id of the group
    * $page The page to load
    * $perpage How many to show
    * $sort An array of options:(field, direction)
    */

    public function load_threads($gid, $page=1, $perpage=20, $sort=array())
    {
        global $mybb, $db;
        // First we have to see if the user can even see threads.
        if($mybb->usergroup['isbannedgroup'])
        {
            return;
        }
        $gid = (int) $gid;
        if($gid < 1)
        {
            $this->error("invalid_group");
        }
        $group = $this->load_group($gid);
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
        if($this->socialgroupsuserhandler->is_moderator($gid, $mybb->user['uid']) || $this->socialgroupsuserhandler->is_leader($gid, $mybb->user['uid']))
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
            // Only build the avatar if the usercp says yes
            if($mybb->user['showavatars'])
            {
                $thread['avatar'] = format_avatar($thread['avatar'], $thread['avatardimensions']);
            }
            $thread['dateline'] = my_date("relative", $thread['dateline']);
            $thread['lastposttime'] = my_date("relative", $thread['lastposttime']);
            $this->threads[$gid][$thread['tid']] = $thread;
        }
        return $this->threads[$gid];
    }

    public function load_posts($gid=0, $tid=0, $page=1, $perpage=20)
    {
        global $db, $mybb, $cache, $templates, $socialgroups;
        $gid = (int) $gid;
        $tid = (int) $tid;
        if(!$tid)
        {
            $this->error("invalid_thread");
        }
        if(!$gid)
        {
            // Attempt to load from thread.
            $query = $db->simple_select("socialgroup_threads", "*", "tid=$tid");
            $thread = $db->fetch_array($query);
            $gid = $thread['gid'];
            $this->thread[$gid][$thread['tid']] = $thread;
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
        $perpage = (int) $perpage;
        if(!$perpage)
        {
            $perpage = 20;
        }
        $page = (int) $page;
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
        $query = $db->query("SELECT p.*, u.*, u.username AS userusername, f.*, t.*, eu.username AS editusername
        FROM " . TABLE_PREFIX . "socialgroup_posts p
        LEFT JOIN " . TABLE_PREFIX . "socialgroup_threads t ON(p.tid=t.tid)
        LEFT JOIN " . TABLE_PREFIX . "users u ON(p.uid=u.uid)
        LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
        LEFT JOIN ".TABLE_PREFIX."users eu ON (eu.uid=p.lasteditby)
        WHERE p.tid=$tid AND t.visible >=$visible AND p.visible >= $visible
        ORDER BY p.dateline ASC
        LIMIT $start , $perpage");
        while($post = $db->fetch_array($query))
        {
            $this->posts[$tid][$post['pid']] = $post;
        } // End the post loop
        return $this->posts[$tid];
    }

    /* This functions returns an array of social groups.
    * @Param $cid: When not 0, it returns only groups in that category.
    * @Param $sortfield: What to sort the groups by.
    * @Param $keywords: The keywords to look for in a group.
    * @Param $perpage: The number of groups to fetch.  Larger installations will need to test performance.
    * @Param $currentpage: What page we are currently on.  If not specified, this will be calculated based on
    * the query string used to get to the page.
    */
    public function list_groups($cid=0, $sortfield="", $keywords="", $perpage=50, $currentpage=0)
    {
        global $db, $mybb, $lang;
        if($cid)
        {
            $cid = $db->escape_string($cid);
            $cidonly = " AND s.cid IN($cid) ";
        }
        switch($sortfield)
        {
            case "name":
                $ordersql = "s.name ASC";
                break;
            case "threads":
                $ordersql = "s.threads DESC";
                break;
            case "posts":
                $ordersql = "s.posts DESC";
                break;
            case "username":
                $ordersql = "u.username ASC";
                break;
            default:
                $ordersql = "s.gid ASC";
                break;
        }
        if($keywords)
        {
            // Check if group names are full text.  If so, use full text syntax.
            $keywords = (string) $keywords;
            $cleankeywords = $db->escape_string($keywords);
            if($db->is_fulltext("socialgroups", "name"))
            {
                $searchsql = ' AND MATCH(s.name) AGAINST ("' . $cleankeywords . '" IN BOOLEAN MODE) ';
            }
            else
            {
                $searchsql = " AND s.name LIKE '" . $cleankeywords . "%' ";
            }
        }
        $categories = implode(",", array_keys($this->get_viewable_categories()));
        $perpage = (int) $perpage;
        if($perpage <= 0) // Use this to avoid SQL errors.
        {
            $perpage = 1;
        }
        $currentpage = (int) $currentpage;
        if(!$currentpage)
        {
            if($mybb->input['page'])
            {
                $currentpage = (int) $mybb->input['page'];
            }
            else
            {
                $currentpage = 1;
            }
            if(!$currentpage) // Still nothing
            {
                $currentpage = 1;
            }
        }
        $start = $currentpage * $perpage - $perpage;
        if(!$categories)
        {
            $categories = 0;
        }

        $query = $db->query("SELECT s.*, c.name as categoryname, u.username, u.usergroup, u.displaygroup, u.avatar, u.avatardimensions
            FROM " . TABLE_PREFIX . "socialgroups s
            LEFT JOIN " . TABLE_PREFIX . "socialgroup_categories c ON(s.cid=c.cid)
            LEFT JOIN " . TABLE_PREFIX . "users u ON(s.uid=u.uid)
            WHERE s.cid IN(" . $categories . ") $cidonly $searchsql
            ORDER BY c.disporder ASC , $ordersql
            LIMIT $start , $perpage");
        $numgroups = $db->num_rows($query);
        if($keywords && $numgroups == 1)
        {
            $group = $db->fetch_array($query);
            $url = "showgroup.php?gid=" . $group['gid'];
            $message = $lang->socialgroups_only_result;
            redirect($url, $message);
        }
        while($group = $db->fetch_array($query))
        {
            $this->group_list[$group['cid']][$group['gid']] = array(
                "gid" => $group['gid'],
                "cid" => $group['cid'],
                "category" => htmlspecialchars_uni($group['categoryname']),
                "name" => htmlspecialchars_uni($group['name']),
                "description" => htmlspecialchars_uni($group['description']),
                "threads" => $group['threads'],
                "posts" => $group['posts'],
                "uid" => $group['uid'],
                "formattedname" => format_name(htmlspecialchars_uni($group['username']), $group['usergroup'], $group['displaygroup']),
                "profilelink" => build_profile_link(format_name(htmlspecialchars_uni($group['username']), $group['usergroup'], $group['displaygroup']), $group['uid']),
                "avatar" => htmlspecialchars_uni($group['avatar']),
                "avatardimensions" => $group['avatardimensions'],
                "logo" => htmlspecialchars_uni($group['logo'])
            );
        }
        return $this->group_list;
    }

    /* This function renders the list of groups.  This should be called after list_groups. */
    public function render_groups()
    {
        global $mybb, $templates, $lang;
        if(count($this->group_list) < 1) // Provide a fallback to those who do it wrong.
        {
            $this->list_groups();
        }
        $html = "";
        $currentcid = 0;
        foreach($this->group_list as $mainkey => $value)
        {
            foreach($this->group_list[$mainkey] as $subkey)
            {
                if($subkey['cid'] != $currentcid)
                {
                    eval("\$html .=\"".$templates->get("socialgroups_category")."\";");
                    $currentcid = $subkey['cid'];
                }
                if($subkey['logo'] != "")
                {
                    $groupinfo['logo'] = $subkey['logo'];
                    eval("\$group_logo =\"".$templates->get("socialgroups_logo")."\";");
                }
                eval("\$html .=\"".$templates->get("socialgroups_group")."\";");
                $group_logo = "";
            }
            eval("\$html .=\"".$templates->get("socialgroups_category_split")."\";");
        }
        return $html;
    }


    /* This gives an array of viewable categories.
    * The keys will be the cid while the value is the name.
    */

    public function get_viewable_categories()
    {
        global $db, $mybb;
        if(count(array_keys($this->viewable_categories)) >= 1)
        {
            return $this->viewable_categories;
        }
        $query = $db->simple_select("socialgroup_categories", "cid, name", "staffonly <= " . $mybb->usergroup['canmodcp']);
        while($category = $db->fetch_array($query))
        {
            $this->viewable_categories[$category['cid']] = htmlspecialchars_uni($category['name']);
        }
        return $this->viewable_categories;
    }
}
