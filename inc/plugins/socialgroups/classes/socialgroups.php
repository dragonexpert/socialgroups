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

    public $socialgroupsdatahandler;

    public $socialgroupsreports;

    public $socialgroupsthreadhandler;

    public $socialgroupsuserhandler;

    /**
     * socialgroups constructor.
     * This funciton loads all information about our socialgroups object.
     * @param int $gids Can be array of integers or single integer.  Loads the designated groups.
     * @param false $loadannouncements Whether to load announcements for the group.
     * @param bool $loadmembers Whether to load the list of members for the group.
     * @param bool $loadmoderators Whether to load the moderators for the group.
     * @param bool $loadleaders Whether to load the group leaders for the group.
     */

    public function __construct($gids=0, $loadannouncements=false, $loadmembers=true, $loadmoderators=true, $loadleaders=true)
    {
        global $mybb, $lang, $plugins;
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
                if($loadannouncements)
                {
                    $this->load_announcements($gid);
                }
                if($loadmembers)
                {
                    $this->socialgroupsuserhandler->load_members($gid);
                }
                if($loadmoderators)
                {
                    $this->socialgroupsuserhandler->load_moderators($gid);
                }
                if($loadleaders)
                {
                    $this->socialgroupsuserhandler->load_leaders($gid);
                }
                $plugins->run_hooks("socialgroups_constructor");
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
            $plugins->run_hooks("socialgroups_constructor");
        }
    }

    /** A generic error function that prepends socialgroups_ to the string for language purposes.
     * @param string $string The string to pass to the error handler.
     */

    public function error(string $string)
    {
        global $lang;
        $variable = "socialgroups_" . $string;
        if(isset($lang->$variable))
        {
            error($lang->$variable);
        }
        else
        {
            error($string);
        }
    }

    /**
     * This function generates a link to the group.
     * This incorporates a setting for SEO Urls.
     * @param int $gid The id of the group.
     * @param string $name The name of the group.
     * @param string $action The action parameter.
     * @return string A link to the group.
     */

    public function grouplink(int $gid=0, string $name="", string $action=""): string
    {
        global $mybb;
        $name = htmlspecialchars_uni($name);
        $action = htmlspecialchars_uni($action);
        $additional_classes = "";

        if($mybb->settings['socialgroups_seo_urls'])
        {
            $find = array(",", " ", "!", ".", "+", "%", "^", "&", "*", "(", ")", "=", "/", "\\", "[", "]", "{", "}", "|", "?", "<", ">");
            $safe_name = str_replace($find, "-", $name);
            $safe_name = str_replace("--", "-", $safe_name);
            $link = $mybb->settings['bburl'] . "/group-" . $gid . "-" . $safe_name;
            if($action)
            {
                $link .= "-action-" . $action;
                $additional_classes = $action . "_link";
            }
            $link .= ".html";
        }
        else
        {
            $link = $mybb->settings['bburl'] . "/showgroup.php?gid=$gid";
            if ($action)
            {
                $link .= "&amp;action=$action";
                $additional_classes = $action . "_link";
            }
        }
        return "<a href='" . $link . "' class='group_link " . $additional_classes . "'>" . $name . "</a>";
    }

    /**
     * This function generates a link to a category.
     * This incorporates SEO setting.
     * @param int $cid The id of the category.
     * @param string $name The name of the category.
     * @return string A link to the category.
     */
    public function categorylink(int $cid=0, string $name="")
    {
        global $mybb;
        $name = htmlspecialchars_uni($name);

        if($mybb->settings['socialgroups_seo_urls'])
        {
            $find = array(",", " ", "!", ".", "+", "%", "^", "&", "*", "(", ")", "=", "/", "\\", "[", "]", "{", "}", "|", "?", "<", ">");
            $safe_name = str_replace($find, "-", $name);
            $safe_name = str_replace("--", "-", $safe_name);
            $link = $mybb->settings['bburl'] . "/group-category-" . $cid . "-" . $safe_name . ".html";
        }
        else
        {
            $link = $mybb->settings['bburl'] . "/groups.php?cid=$cid";
        }
        return "<a href='" . $link . "' class='group_category_link'>" . $name . "</a>";
    }

    /**
     * This function generates a link to a group thread.
     * This function incorporates SEO setting.
     * @param int $tid The id of the thread.
     * @param string $name The title of the thread.
     * @param string $action Any action that is being done.
     * @return string A link to the group thread.
     */
    public function groupthreadlink(int $tid=0, string $name="", string $action="")
    {
        global $mybb;
        $name = htmlspecialchars_uni($name);
        $action = htmlspecialchars_uni($action);
        $additional_classes = "";

        if($mybb->settings['socialgroups_seo_urls'])
        {
            $find = array(",", " ", "!", ".", "+", "%", "^", "&", "*", "(", ")", "=", "/", "\\", "[", "]", "{", "}", "|", "?", "<", ">");
            $safe_name = str_replace($find, "-", $name);
            $safe_name = str_replace("--", "-", $safe_name);
            $link = $mybb->settings['bburl'] . "/groupthread-" . $tid . "-" . $safe_name;
            if($action)
            {
                $link .= "-action-" . $action;
                $additional_classes = $action . "_link";
            }
            $link .= ".html";
        }
        else
        {
            $link = $mybb->settings['bburl'] . "/groupthread.php?tid=$tid";
            if ($action)
            {
                $link .= "&amp;action=$action";
                $additional_classes = $action . "_link";
            }
        }
        return "<a href='" . $link . "' class='groupthread_link " . $additional_classes . "'>" . $name . "</a>";
    }

    /**
     * @param string $type The type of page: ( group, groupthread, category, or custom by using a plugin hook )
     * @param int $id The primary key.
     * @param string $name The name. Only used if SEO is enabled.
     * @return string The proper link for breadcrumb links.
     */
    public function breadcrumb_link(string $type="group", int $id=0, string $name="")
    {
        global $mybb, $plugins;
        $name = htmlspecialchars_uni($name);
        if($mybb->settings['socialgroups_seo_urls'])
        {
            $find = array(",", " ", "!", ".", "+", "%", "^", "&", "*", "(", ")", "=", "/", "\\", "[", "]", "{", "}", "|", "?", "<", ">");
            $safe_name = str_replace($find, "-", $name);
            $safe_name = str_replace("--", "-", $safe_name);
            switch($type)
            {
                case "group":
                    return "group-" . $id . "-" . $safe_name . ".html";
                    break;
                case "category":
                    return "group-category-" . $id . "-" . $safe_name . ".html";
                    break;
                case "groupthread":
                    return "groupthread-" . $id . "-" . $safe_name . ".html";
                    break;
                default:
                    // This way others can take advantage of the SEO Engine
                    return $plugins->run_hooks("socialgroups_breadcrumb_link");
            }
        }
        else
        {
            switch($type)
            {
                case "group":
                    return "showgroup.php?gid=" . $id;
                    break;
                case "category":
                    return "groups.php?cid=" . $id;
                    break;
                case "groupthread":
                    return "groupthread.php?tid=" . $id;
                    break;
                default:
                    return $plugins->run_hooks("socialgroups_breadcrumb_link");
            }
        }
    }


    /**
     * This function loads a group and should be the first function called.
     * @param int $gid The id of the group.
     * @param bool $use_cache Load from cache when true. Load from database if false.
     * @return mixed An array of group information on success.  Error page on failure.
     */

    public function load_group(int $gid=1, bool $use_cache=true): array
    {
        global $mybb, $lang, $db, $plugins, $cache;
        if($gid < 1)
        {
            $this->error("invalid_group");
        }
        if($use_cache)
        {
            $groups = $cache->read("socialgroups");
            if(is_array($groups))
            {
                if(is_array($groups[$gid]))
                {
                    $groups[$gid]['name'] = htmlspecialchars_uni($groups[$gid]['name']);
                    $groups[$gid]['description'] = htmlspecialchars_uni($groups[$gid]['description']);
                    $groups[$gid]['logo'] = htmlspecialchars_uni($groups[$gid]['logo']);
                    $this->group[$gid] = $groups[$gid];
                    $plugins->run_hooks("class_socialgroups_load_group", $groups);
                    return $groups[$gid];
                }
                else
                {
                    $this->error("invalid_group");
                }
            }
            else
            {
                // The cache doesn't exist.
                $this->update_cache();
                $groups = $cache->read("socialgroups");
                if(!is_array($groups[$gid]))
                {
                    $this->error("invalid_group");
                }
                $groups[$gid]['name'] = htmlspecialchars_uni($groups[$gid]['name']);
                $groups[$gid]['description'] = htmlspecialchars_uni($groups[$gid]['description']);
                $groups[$gid]['logo'] = htmlspecialchars_uni($groups[$gid]['logo']);
                $this->group[$gid] = $groups[$gid];
                return $groups[$gid];
            }
        }

        $query = $db->simple_select("socialgroups", "*", "gid=$gid");
        $group = $db->fetch_array($query);
        $db->free_result($query);
        $group['name'] = htmlspecialchars_uni($group['name']);
        $group['description'] = htmlspecialchars_uni($group['description']);
        $group['logo'] = htmlspecialchars_uni($group['logo']);
        // Add a hook so other devs can use it to load conveniently
        $plugins->run_hooks("class_socialgroups_load_group", $group);
        $this->group[$gid] = $group;
        return $this->group[$gid];
    }

    /** This function loads a category.  Typically called after loading a group.
     * @param int $cid The id of the category.
     * @param bool $use_cache Whether to load from cache.
     * @return array An array of category information.
     */

    public function load_category(int $cid=1, bool $use_cache = true): array
    {
        global $db, $cache;
        if(!$cid)
        {
            $this->error("invalid_category");
        }
        if(array_key_exists($cid, $this->category))
        {
            return $this->category[$cid];
        }
        if($use_cache)
        {
            $categories = $cache->read("socialgroups_categories");
            $this->category = $categories;
            if(is_array($categories))
            {
                if(array_key_exists($cid, $categories))
                {
                    return $categories[$cid];
                }
                else
                {
                    $this->error("invalid_category");
                }
            }
            else
            {
                $this->update_socialgroups_category_cache();
                $categories = $cache->read("socialgroups_categories");
                if(!array_key_exists($cid, $categories))
                {
                    $this->error("invalid_category");
                }
                else
                {
                    $this->category = $categories;
                    return $categories[$cid];
                }
            }
        }
        else
        {
            $query = $db->simple_select("socialgroup_categories", "*", "cid=$cid");
            if (!$db->num_rows($query))
            {
                $this->error("invalid_category");
            }
            $this->category[$cid] = $db->fetch_array($query);
            $db->free_result($query);
            return $this->category[$cid];
        }
    }

    /** This function loads some permissions about a group.  This should be called after fetching a group.
     * @param int $gid The id of the group.
     * @return array An array of permissions.
     */

    public function load_permissions(int $gid=1): array
    {
        global $db;
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
            else
            {
                $this->permissions[$gid] = $db->fetch_array($query);
            }
            return $this->permissions[$gid];
        }
    }



    /** This function loads the announcements for a group including global announcements.
     * @param int $gid The id of the group.
     * @param bool $showhidden Whether to show inactive announcements.
     * @param int $limit How many announcements to fetch.
     * @return array An array of announcements.
     */

    public function load_announcements(int $gid=0, bool $showhidden=false, int $limit=5): array
    {
        global $db;
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
        $db->free_result($query);
        return $this->announcements[$gid];
    }



    /** This functions returns an array of social groups.
     * @Param string $cid: When not 0, it returns only groups in that category.
     * @Param string $sortfield: What to sort the groups by.
     * @Param string $keywords: The keywords to look for in a group.
     * @Param int $perpage: The number of groups to fetch.  Larger installations will need to test performance.
     * @Param int  $currentpage: What page we are currently on.  If not specified, this will be calculated based on
     * the query string used to get to the page.
     * @return Array An array of groups.
     */
    public function list_groups(string $cid="", string $sortfield="", string $keywords="", int $perpage=50, int $currentpage=0): array
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
                "category" => $group['categoryname'],
                "name" => $group['name'],
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
        $db->free_result($query);
        return $this->group_list;
    }

    /** This function renders the list of groups.  This should be called after list_groups.
     * @return String The HTML content of groups.
     */
    public function render_groups(): string
    {
        global $mybb, $templates, $lang;
        if(count($this->group_list) < 1) // Provide a fallback to those who do it wrong.
        {
            $this->list_groups();
        }
        $html = "";
        $currentcid = 0;
        if(is_array($this->group_list))
        {
            foreach ($this->group_list as $mainkey => $value)
            {
                foreach ($this->group_list[$mainkey] as $subkey)
                {
                    if ($subkey['cid'] != $currentcid)
                    {
                        $subkey['category_link'] = $this->categorylink($subkey['cid'], $subkey['category']);
                        eval("\$html .=\"" . $templates->get("socialgroups_category") . "\";");
                        $currentcid = $subkey['cid'];
                        $subkey['category_link'] = "";
                    }
                    if ($subkey['logo'] != "")
                    {
                        $groupinfo['logo'] = $subkey['logo'];
                        eval("\$group_logo =\"" . $templates->get("socialgroups_logo") . "\";");
                    }
                    $subkey['group_link'] = $this->grouplink($subkey['gid'], $subkey['name']);
                    eval("\$html .=\"" . $templates->get("socialgroups_group") . "\";");
                    $group_logo = "";
                    $subkey['group_link'] = "";
                }
                eval("\$html .=\"" . $templates->get("socialgroups_category_split") . "\";");
            }
            return $html;
        }
    }


    /** This gives an array of viewable categories.
     * The keys will be the cid while the value is the name.
     * @return Array An array of viewable categories.
     */

    public function get_viewable_categories(): array
    {
        global $db, $mybb, $cache;
        if(count(array_keys($this->viewable_categories)) >= 1)
        {
            return $this->viewable_categories;
        }
        $categories = $cache->read("socialgroups_categories");
        if(is_array($categories))
        {
            foreach ($categories as $category)
            {
                if ($category['staffonly'] == 1 && $mybb->usergroup['canmodcp'] || $category['staffonly'] == 0)
                {
                    $this->viewable_categories[$category['cid']] = htmlspecialchars_uni($category['name']);
                }
            }
        }
        else
        {

            $query = $db->simple_select("socialgroup_categories", "cid, name", "staffonly <= " . $mybb->usergroup['canmodcp']);
            $this->viewable_categories = array();
            while ($category = $db->fetch_array($query))
            {
                $this->viewable_categories[$category['cid']] = htmlspecialchars_uni($category['name']);
            }
            $db->free_result($query);
        }
        return $this->viewable_categories;
    }

    /**
     * This function updates the cache so we can use it for performance.
     * The main time this should be used is when adding, editing, or deleting a group.
     * Do not rely on this for the number of posts and threads.
     */
    public function update_cache()
    {
        global $db, $cache;
        $data = array();
        $query = $db->simple_select("socialgroups", "*");
        while($group = $db->fetch_array($query))
        {
            $data[$group['gid']] = $group;
        }
        $db->free_result($query);
        $cache->update("socialgroups", $data);
    }


    /**
     * This function updates the cache for socialgroup categories.
     * This will help for performance.
     */
    public function update_socialgroups_category_cache()
    {
        global $db, $cache;
        $data = array();
        $query = $db->simple_select("socialgroup_categories", "*");
        while($category = $db->fetch_array($query))
        {
            $data[$category['cid']] = $category;
        }
        $db->free_result($query);
        $cache->update("socialgroups_categories", $data);
    }
}
