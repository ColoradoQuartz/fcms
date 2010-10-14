<?php
set_error_handler("fcmsErrorHandler");
include_once('gettext.inc');
include_once('locale.php');

// Setup MySQL
$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysql_select_db($cfg_mysql_db);

// Setup php-gettext
if (isset($_SESSION['language'])) {
    T_setlocale(LC_MESSAGES, $_SESSION['language']);
} else {
    $lang = getLanguage();
    T_setlocale(LC_MESSAGES, $lang);
}
T_bindtextdomain('messages', './language');
T_bind_textdomain_codeset('messages', 'UTF-8');
T_textdomain('messages');

// Email Headers and Smileys
$smileydir = "themes/smileys/";
$smiley_array = array(':smile:', ':none:', ':)', '=)', ':wink:', ';)', ':tongue:', ':biggrin:', ':sad:', ':(', ':sick:', ':cry:', ':shocked:', ':cool:', ':sleep:', 'zzz', ':angry:', ':mad:', ':embarrassed:', ':shy:', 
    ':rolleyes:', ':nervous:', ':doh:', ':love:', ':please:', ':1please:', ':hrmm:', ':quiet:', ':clap:', ':twitch:', ':blah:', ':bored:', ':crazy:', ':excited:', ':noidea:', ':disappointed:', ':banghead:', 
    ':dance:', ':laughat:', ':ninja:', ':pirate:', ':thumbup:', ':thumbdown:', ':twocents:'
);
$smiley_file_array = array('smile.gif', 'smile.gif', 'smile.gif', 'smile.gif', 'wink.gif', 'wink.gif', 'tongue.gif', 'biggrin.gif', 'sad.gif', 'sad.gif', 'sick.gif', 'cry.gif', 'shocked.gif', 'cool.gif', 
    'sleep.gif', 'sleep.gif', 'angry.gif', 'angry.gif', 'embarrassed.gif', 'embarrassed.gif', 'rolleyes.gif', 'nervous.gif', 'doh.gif', 'love.gif', 'please.gif', 'please.gif', 'hrmm.gif', 'quiet.gif', 
    'clap.gif', 'twitch.gif', 'blah.gif', 'bored.gif', 'crazy.gif', 'excited.gif', 'noidea.gif', 'disappointed.gif', 'banghead.gif', 'dance.gif', 'laughat.gif', 'ninja.gif', 'pirate.gif', 'thumbup.gif', 
    'thumbdown.gif', 'twocents.gif'
);

/**
 * getEmailHeaders 
 * 
 * @param   string  $name 
 * @param   string  $email 
 * @return  string
 */
function getEmailHeaders ($name = '', $email = '')
{
    if (empty($name)) {
        $name = getSiteName();
    }
    if (empty($email)) {
        $email = getContactEmail();
    }
    return "From: $name <$email>\r\n" . 
        "Reply-To: $email\r\n" . 
        "Content-Type: text/plain; charset=UTF-8;\r\n" . 
        "MIME-Version: 1.0\r\n" . 
        "X-Mailer: PHP/" . phpversion();
}

/**
 * getTheme 
 * 
 * @param   int     $userid 
 * @param   string  $d          the path
 * @return  void
 */
function getTheme ($userid, $d = "")
{
    if (empty($userid)) {
        return $d . "themes/default/";
    } else {
        $userid = cleanInput($userid, 'int');
        $sql = "SELECT `theme` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$userid'";
        $result = mysql_query($sql) or displaySQLError(
            'Theme Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        $pos = strpos($r['theme'], '.css');
        if ($pos === false) {
            return $d . "themes/" . basename($r['theme']) . "/";
        } else {
            return $d . "themes/" . substr($r['theme'], 0, $pos) . "/";
        }
    }
}

/**
 * getLanguage 
 * 
 * @return  string
 */
function getLanguage ()
{
    if (isset($_SESSION['login_id'])) {
        $sql = "SELECT `language` 
                FROM `fcms_user_settings` 
                WHERE `id` = '" . cleanInput($_SESSION['login_id'], 'int') . "'";
        $result = mysql_query($sql) or displaySQLError(
            'Language Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = mysql_fetch_array($result);
        if (mysql_num_rows($result) > 0) {
            return $row['language'];
        }
    }
    return 'en_US';
}

/*
 * getUserDisplayName
 *
 * Gets the user's name, displayed how they set in there settings, unless display option is set
 * which will overwrite the user's settings.
 * 
 * @param   int     $userid 
 * @param   int     $display 
 * @param   boolean $isMember 
 * @return  string
 */
function getUserDisplayName ($userid, $display = 0, $isMember = true)
{
    $userid = cleanInput($userid, 'int');

    if ($isMember) {
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname` "
             . "FROM `fcms_users` AS u, `fcms_user_settings` AS s "
             . "WHERE u.`id` = '$userid' "
             . "AND u.`id` = s.`user`";
    } else {
        $sql = "SELECT `fname`, `lname`, `username` "
             . "FROM `fcms_users` "
             . "WHERE `id` = '$userid' ";
    }
    $result = mysql_query($sql) or displaySQLError(
        'Displayname Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);

    // Do we want user's settings or overriding it?
    if ($display < 1) {
        $displayname = $r['displayname'];
    } else {
        $displayname = $display;
    }
    switch($displayname) {
        case '1': return $r['fname']; break;
        case '2': return $r['fname'].' '.$r['lname']; break;
        case '3': return $r['username']; break;
        default: return $r['username']; break;
    }
}

/**
 * getPMCount 
 *
 * Returns a string consisting of the user's new pm count in ()'s
 * 
 * @return  string
 */
function getPMCount ()
{
    $sql = "SELECT * FROM `fcms_privatemsg` 
            WHERE `read` < 1 
            AND `to` = '" . cleanInput($_SESSION['login_id'], 'int') . "'";
    $result = mysql_query($sql) or displaySQLError(
        'PM Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        return ' ('.mysql_num_rows($result).')';
    }
    return '';
}

/**
 * getUserEmail 
 * 
 * @param   string  $userid 
 * @return  string
 */
function getUserEmail ($userid)
{
    $userid = cleanInput($userid, 'int');

    $sql = "SELECT `email` "
         . "FROM `fcms_users` "
         . "WHERE `id` = '$userid'";
    $result = mysql_query($sql) or displaySQLError(
        'Email Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    return $r['email'];
}

/**
 * getDefaultNavUrl 
 *
 * Gets the url for the 'Share' default link
 * 
 * @return  string
 */
function getDefaultNavUrl ()
{
    $sql = "SELECT `link` 
            FROM `fcms_navigation` 
            WHERE `col` = 4 
            AND `order` = 1";
    $result = mysql_query($sql) or displaySQLError(
        'Default Nav Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    return getSectionUrl($r['link']);
}

/**
 * getNavLinks 
 *
 * Gets the links and order for the given sub menu (column)
 * 
 *      1. home
 *      2. my stuff
 *      3. communicate
 *      4. share
 *      5. misc.
 *      6. administration
 * 
 * @param int $column
 * 
 * @return  array
 */
function getNavLinks ()
{
    $sql = "SELECT `link`, `col`
            FROM `fcms_navigation` 
            WHERE `order` != 0 
            ORDER BY `col`, `order`";
    $result = mysql_query($sql) or displaySQLError(
        'Nav Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $ret = array();
    while ($r = mysql_fetch_array($result)) {
        $ret[$r['col']][] = array(
            'url'  => getSectionUrl($r['link']),
            'text' => getSectionName($r['link']),
        ); 
    }
    return $ret;
}

/**
 * getSectionName 
 *
 * Given the name of the section from the db, returns the translated text
 * 
 * @param   string  $section 
 * @return  string
 */
function getSectionName ($section)
{
    switch ($section) {
        case 'addressbook':
            return T_('Address Book');
            break;
        case 'calendar':
            return T_('Calendar');
            break;
        case 'chat':
            return T_('Chat');
            break;
        case 'documents':
            return T_('Documents');
            break;
        case 'familynews':
            return T_('Family News');
            break;
        case 'messageboard':
            return T_('Message Board');
            break;
        case 'photogallery':
            return T_('Photo Gallery');
            break;
        case 'prayers':
            return T_('Prayers');
            break;
        case 'profile':
            return T_('Profile');
            break;
        case 'pm':
            return T_('Private Messages');
            break;
        case 'recipes':
            return T_('Recipes');
            break;
        case 'settings':
            return T_('Settings');
            break;
        case 'tree':
            return T_('Family Tree');
            break;
        default:
            return 'error';
            break;
    }
}

/**
 * getSectionUrl 
 *
 * Given the name of the section from the db, returns the url for that section
 * 
 * @param   string  $section 
 * @return  string
 */
function getSectionUrl ($section)
{
    switch ($section) {
        case 'addressbook':
            return 'addressbook.php';
            break;
        case 'calendar':
            return 'calendar.php';
            break;
        case 'chat':
            return 'chat.php';
            break;
        case 'documents':
            return 'documents.php';
            break;
        case 'familynews':
            return 'familynews.php';
            break;
        case 'messageboard':
            return 'messageboard.php';
            break;
        case 'photogallery':
            return 'gallery/index.php';
            break;
        case 'prayers':
            return 'prayers.php';
            break;
        case 'profile':
            return 'profile.php';
            break;
        case 'pm':
            return 'privatemsg.php';
            break;
        case 'recipes':
            return 'recipes.php';
            break;
        case 'settings':
            return 'settings.php';
            break;
        case 'tree':
            return 'familytree.php';
            break;
        default:
            return 'home.php';
            break;
    }
}

/**
 * displayNewPM 
 * 
 * @param   int     $userid 
 * @param   string  $d 
 * @return  void
 */
function displayNewPM ($userid, $d = "")
{
    $userid = cleanInput($userid, 'int');

    $sql = "SELECT `id` 
            FROM `fcms_privatemsg` 
            WHERE `to` = '$userid' AND `read` < 1";
    $result = mysql_query($sql) or displaySQLError(
        'Get New PM', 'util_inc.php [' . __LINE__ . ']', $sql, mysql_error()
    );

    if (mysql_num_rows($result) > 0) {
        echo "<a href=\"" . $d . "privatemsg.php\" class=\"new_pm\">" . T_('New PM') . "</a> ";
    } else {
        echo " ";
    }
}

/**
 * checkAccess 
 *
 * Returns the access level as a number for the given user.
 * 
 * @param   int     $userid 
 * @return  int
 */
function checkAccess ($userid)
{
    $userid = cleanInput($userid, 'int');

    $sql = "SELECT `access` 
            FROM `fcms_users` 
            WHERE `id` = '$userid'";
    $result = mysql_query($sql) or displaySQLError(
        'Access Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    return $r['access'];
}

/**
 * getAccessLevel 
 *
 * Returns the access level name for the given user.
 * 
 * @param   int     $userid 
 * @return  string
 */
function getAccessLevel ($userid)
{
    $sql = "SELECT `access` 
            FROM `fcms_users` 
            WHERE `id` = '$userid'";
    $result = mysql_query($sql) or displaySQLError(
        'Access Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    $access = T_('Member');
    switch ($r['access']) {
        case 1:
            $access = T_('Admin');
            break;
        case 2:
            $access = T_('Helper');
            break;
        case 3:
            $access = T_('Member');
            break;
        case 4:
            $access = T_('Non-Photographer');
            break;
        case 5:
            $access = T_('Non-Poster');
            break;
        case 6:
            $access = T_('Commenter');
            break;
        case 7:
            $access = T_('Poster');
            break;
        case 8:
            $access = T_('Photographer');
            break;
        case 9:
            $access = T_('Blogger');
            break;
        case 10:
            $access = T_('Guest');
            break;
    }
    return $access;
}

/**
 * parse 
 * 
 * @param   string  $data 
 * @param   string  $d 
 * @return  void
 */
function parse ($data, $d = '')
{
    $data = htmlentities($data, ENT_COMPAT, 'UTF-8');
    $data = parse_smilies($data, $d);
    $data = parse_bbcodes($data);
    $data = nl2br_nospaces($data);
    return $data;
}

/**
 * parse_bbcodes 
 * 
 * @param   string  $data 
 * @return  void
 */
function parse_bbcodes ($data)
{
    $search = getBBCodeList();
    $replace = array(
        '<ins>$1</ins>', 
        '<del>$1</del>', 
        '<h1>$1</h1>', 
        '<h2>$1</h2>', 
        '<h3>$1</h3>', 
        '<h4>$1</h4>', 
        '<h5>$1</h5>', 
        '<h6>$1</h6>', 
        '<b>$1</b>', 
        '<i>$1</i>', 
        '<u>$1</u>', 
        '<a href="$1">$2</a>', 
        '<a href="$1">$1</a>', 
        '<div style="text-align: $1;">$2</div>', 
        '<img src="$1"/>', 
        '<img src="$1"/>', 
        '<a href="mailto:$1">$2</a>', 
        '<a href="mailto:$1">$1</a>', 
        '<span style="font-family: $1;">$2</span>', 
        '<span style="font-size: $1;">$2</span>', 
        '<span style="color: $1;">$2</span>', 
        '<span>$1</span>', 
        '<span class="$1">$2</span>',
        '<blockquote>$1</blockquote>',
        'unhtmlentities("\\1")'
    );
    $data = preg_replace ($search, $replace, $data);
    return $data; 
}

/**
 * removeBBCode 
 * 
 * @param   string  $str 
 * @return  string
 */
function removeBBCode ($str)
{
    $search = getBBCodeList();
    $replace = array(
        '$1', // ins 
        '$1', // del
        '$1', // h1
        '$1', // h2
        '$1', // h3
        '$1', // h4
        '$1', // h5
        '$1', // h6
        '$1', // b
        '$1', // i
        '$1', // u
        '$2', // url
        '$1', // url 
        '$2', // align
        '',   // img
        '',   // img
        '$2', // mail
        '$1', // mail
        '$2', // font
        '$2', // size
        '$2', // color
        '$1', // span
        '$2', // span
        '$1', // quote
        '',   // video
    );
    return preg_replace($search, $replace, stripslashes($str));
}

/**
 * getBBCodeList 
 *
 * Returns an array of regex for the current list of BBCodes that FCMS supports.
 *
 * @return  array
 */
function getBBCodeList ()
{
    return array(
        '/\[ins\](.*?)\[\/ins\]/is', 
        '/\[del\](.*?)\[\/del\]/is', 
        '/\[h1\](.*?)\[\/h1\]/is', 
        '/\[h2\](.*?)\[\/h2\]/is', 
        '/\[h3\](.*?)\[\/h3\]/is', 
        '/\[h4\](.*?)\[\/h4\]/is', 
        '/\[h5\](.*?)\[\/h5\]/is', 
        '/\[h6\](.*?)\[\/h6\]/is', 
        '/\[b\](.*?)\[\/b\]/is', 
        '/\[i\](.*?)\[\/i\]/is', 
        '/\[u\](.*?)\[\/u\]/is', 
        '/\[url\=(.*?)\](.*?)\[\/url\]/is', 
        '/\[url\](.*?)\[\/url\]/is', 
        '/\[align\=(left|center|right)\](.*?)\[\/align\]/is', 
        '/\[img\=(.*?)\]/is', 
        '/\[img\](.*?)\[\/img\]/is', 
        '/\[mail\=(.*?)\](.*?)\[\/mail\]/is', 
        '/\[mail\](.*?)\[\/mail\]/is', 
        '/\[font\=(.*?)\](.*?)\[\/font\]/is', 
        '/\[size\=(.*?)\](.*?)\[\/size\]/is', 
        '/\[color\=(.*?)\](.*?)\[\/color\]/is', 
        '/\[span\](.*?)\[\/span\]/is', 
        '/\[span\=(.*?)\](.*?)\[\/span\]/is', 
        '/\[quote\](.*?)\[\/quote\]/is', 
        '/\[video\](.*?)\[\/video\]/ise'
    );
}


/**
 * parse_smilies 
 * 
 * @param   string  $data 
 * @param   string  $d 
 * @return  void
 */
function parse_smilies ($data, $d = '')
{
    global $smiley_array, $smiley_file_array, $smileydir;
    $i = 0;
    while($i < count($smiley_array)) {
        $data = str_replace($smiley_array[$i], '<img src="' . $d . $smileydir . $smiley_file_array[$i] . '" alt="'. $smiley_array[$i] . '" />', $data);
        $i ++;
    }
    return $data;
}

/**
 * nl2br_nospaces 
 * 
 * @param   string  $string 
 * @return  void
 */
function nl2br_nospaces ($string)
{
    $string = str_replace(array("\r\n", "\r", "\n"), "<br/>", $string); 
    return $string; 
} 

// Used for PHP 4 and less
if (!function_exists('stripos')) {
    function stripos($haystack, $needle, $offset = 0) {
        return strpos(strtolower($haystack), strtolower($needle), $offset);
    }
}

// If php is compiled without mbstring support
if (!function_exists('mb_detect_encoding')) {
    function mb_detect_encoding($text) {
        return 'UTF-8';
    }
    function mb_convert_encoding($text,$target_encoding,$source_encoding) {
        return $text;
    }
}

/**
 * displaySmileys 
 * 
 * @return  void
 */
function displaySmileys ()
{
    global $smiley_array, $smiley_file_array;
    $i=0;
    $previous_smiley_file = '';
    foreach ($smiley_array as $smiley) {
        if ($smiley_file_array[$i] != $previous_smiley_file) {
            echo '<div class="smiley"><img src="../themes/smileys/' . $smiley_file_array[$i] . '" alt="' . $smiley . '" onclick="return addSmiley(\''.str_replace("'", "\'", $smiley).'\')" /></div>';
            $previous_smiley_file = $smiley_file_array[$i];
        }
        $i++;
    }
}

/**
 * escape_string 
 * 
 * @param   string  $string 
 * @return  string
 */
function escape_string ($string)
{
    if (version_compare(phpversion(), "4.3.0") == "-1") {
        return mysql_escape_string($string);
    } else {
        return mysql_real_escape_string($string);
    }
}

/**
 * fixMagicQuotes 
 *
 * Strips slashes if magic quotes is turned on
 * 
 * @return void
 */
function fixMagicQuotes ()
{
    if (get_magic_quotes_gpc()) {
        $_REQUEST = stripSlashesDeep($_REQUEST);
        $_GET = stripSlashesDeep($_GET);
        $_POST = stripSlashesDeep($_POST);
        $_COOKIE = stripSlashesDeep($_COOKIE);
    }
}

/**
 * stripSlashesDeep 
 *
 * recursively strips slashes on arrays.  if not array, just stripslashes
 * 
 * @param   mixed   $val 
 * @return  void
 */
function stripSlashesDeep ($value)
{
    $value = is_array($value) ? 
                array_map('stripSlashesDeep', $value) : 
                stripslashes($value);
    return $value;
}

/**
 * cleanInput 
 *
 * Cleans input from the user, so it's safe to insert into the DB.
 * 
 * @param   mixed   $input 
 * @param   string  $type 
 * @return  mixed
 */
function cleanInput ($input, $type = 'string')
{
    if ($type == 'int') {
        $input = (int)$input;
    }
    return escape_string($input);
}

/**
 * cleanOutput 
 *
 * Cleans output from the db or from the user so it can be displayed.
 * 
 * @param   mixed   $output 
 * @param   string  $type 
 * @return  mixed
 */
function cleanOutput ($output, $type = 'string')
{
    // Strings that may contain HTML
    if ($type == 'html') {
        return htmlentities($output, ENT_COMPAT, 'UTF-8');
    }

    // Strings without HTML
    $output = strip_tags($output);
    return htmlentities($output, ENT_COMPAT, 'UTF-8');
}

/**
 * cleanFilename 
 *
 * Removes unwanted characters from a filename.
 * 
 * @param string $filename 
 * 
 * @return  void
 */
function cleanFilename ($filename)
{
    // convert spaces to underscores
    $filename = str_replace(" ", "_", $filename);

    // remove everything else but numbers and letters _ -
    $filename = preg_replace('/[^.A-Za-z0-9_-]/', '', $filename);

    return $filename;
}
/**
 * unhtmlentities 
 *
 * html_entity_decode for PHP 4.3.0 and earlier:
 * 
 * @param   string  $string 
 * @return  string
 */
function unhtmlentities($string)
{
    // replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    // replace literal entities
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}

/**
 * getPostsById
 * 
 * Gets the post count and percentage of total posts for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getPostsById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_board_posts`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_board_posts` WHERE `user` = '$user_id'";
    $result = mysql_query($sql) or displaySQLError(
        'Count Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getPhotosById
 * 
 * Gets the photo count and percentage of total posts for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getPhotosById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_gallery_photos`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_gallery_photos` WHERE `user` = '$user_id'";
    $result = mysql_query($sql) or displaySQLError(
        'Count Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getCommentsById
 * 
 * Gets the news/gallery comment count and percentage of total news/gallery for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getCommentsById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_gallery_comments`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Gallery Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_gallery_comments` WHERE `user` = '$user_id'";
    $result = mysql_query($sql) or displaySQLError(
        'Count Gallery Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];

    // Check Family News if applicable
    if (usingFamilyNews()) {
        $sql = "SELECT COUNT(`id`) AS c FROM `fcms_news_comments`";
        $result = mysql_query($sql) or displaySQLError(
            'Total News Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $total = $total + $found['c'];
        $sql = "SELECT COUNT(`user`) AS c FROM `fcms_news_comments` WHERE `user` = '$user_id'";
        $result = mysql_query($sql) or displaySQLError(
            'Count News Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $count = $count + $found['c'];
    }
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getCalendarEntriesById
 * 
 * Gets the calendar entries count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getCalendarEntriesById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_calendar`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_calendar` WHERE `created_by` = '$user_id'";
    $result = mysql_query($sql) or displaySQLError(
        'Count Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getFamilyNewsById
 * 
 * Gets the news count and percentage of total news for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getFamilyNewsById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_news`";
    $result = mysql_query($sql) or displaySQLError(
        'Total News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_news` WHERE `user` = '$user_id' GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getRecipesById
 * 
 * Gets the recipes count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getRecipesById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_recipes`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Recipes Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_recipes` WHERE `user` = '$user_id' GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count Recipes Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getDocumentsById
. * 
 * Gets the documents count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getDocumentsById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_documents`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_documents` WHERE `user` = '$user_id' GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getPrayersById
 * 
 * Gets the prayers count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getPrayersById ($user_id, $option = 'both')
{
    $user_id = cleanInput($user_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_prayers`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_prayers` WHERE `user` = '$user_id' GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getNewsComments 
 * 
 * @param   int     $news_id 
 * @return  void
 */
function getNewsComments ($news_id)
{
    $news_id = cleanInput($news_id, 'int');

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_news_comments` 
            WHERE `news` = '$news_id'";
    $result = mysql_query($sql) or displaySQLError(
        'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    return  $found['c'] ? $found['c'] : 0;
}

/**
 * getUserParticipationPoints
 * 
 * Get the participation points for the given member.
 *
 *      Action      Points
 *      -------------------
 *      thread          5
 *      photo           3
 *      news            3
 *      recipe          2
 *      document        2
 *      prayer          2
 *      post            2
 *      comment         2
 *      address         1
 *      phone #         1
 *      date/event      1
 *      vote            1
 *
 * @param   int     $id 
 * @return  int
 */
function getUserParticipationPoints ($id)
{
    $id = cleanInput($id, 'int');

    $points = 0;
    $commentTables = array('fcms_gallery_comments');

    // Thread (5)
    $sql = "SELECT COUNT(`id`) AS thread
            FROM `fcms_board_threads`
            WHERE `started_by` = '$id'";
    $result = mysql_query($sql)  or displaySQLError(
        'Thread Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    $points += $r['thread'] * 5;

    // Photo (3)
    $sql = "SELECT COUNT(`id`) AS photo 
            FROM `fcms_gallery_photos` 
            WHERE `user` = '$id'";
    $result = mysql_query($sql)  or displaySQLError(
        'Photo Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    $points += $r['photo'] * 3;

    // News (3)
    if (usingFamilyNews()) {

        array_push($commentTables, 'fcms_news_comments');

        $sql = "SELECT COUNT(`id`) AS news 
                FROM `fcms_news` 
                WHERE `user` = '$id'";
        $result = mysql_query($sql)  or displaySQLError(
            'News Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        $points += $r['news'] * 3;
    }

    // Recipe (2)
    if (usingRecipes()) {

        array_push($commentTables, 'fcms_recipe_comment');

        $sql = "SELECT COUNT(`id`) AS recipe 
                FROM `fcms_recipes` 
                WHERE `user` = '$id'";
        $result = mysql_query($sql)  or displaySQLError(
            'Recipe Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        $points += $r['recipe'] * 2;
    }

    // Document (2)
    if (usingDocuments()) {
        $sql = "SELECT COUNT(`id`) AS doc 
                FROM `fcms_documents` 
                WHERE `user` = '$id'";
        $result = mysql_query($sql)  or displaySQLError(
            'Doc Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        $points += $r['doc'] * 2;
    }

    // Prayer (2)
    if (usingPrayers()) {
        $sql = "SELECT COUNT(`id`) AS prayer 
                FROM `fcms_prayers` 
                WHERE `user` = '$id'";
        $result = mysql_query($sql)  or displaySQLError(
            'Prayer Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        $points += $r['prayer'] * 2;
    }

    // Post (2)
    $sql = "SELECT COUNT(`id`) AS post 
            FROM `fcms_board_posts` 
            WHERE `user` = '$id'";
    $result = mysql_query($sql)  or displaySQLError(
        'Post Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    $points += $r['post'] * 2;

    // Comment (2)
    $from  = implode('`, `', $commentTables);
    $where = implode("`.`user` = '$id' AND `", $commentTables);

    $sql = "SELECT COUNT(*) AS comment 
            FROM `$from` 
            WHERE `$where`.`user` = '$id'";
    $result = mysql_query($sql)  or displaySQLError(
        'Comment Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    $points += $r['comment'] * 2;

    // Address/Phone (1)
    $sql = "SELECT `address`, `city`, `state`, `home`, `work`, `cell` 
            FROM `fcms_address` 
            WHERE `user` = '$id'";
    $result = mysql_query($sql)  or displaySQLError(
        'Addres Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    if (!empty($r['address']) && !empty($r['city']) && !empty($r['state'])) {
        $points++;
    }
    if (!empty($r['home'])) {
        $points++;
    }
    if (!empty($r['work'])) {
        $points++;
    }
    if (!empty($r['cell'])) {
        $points++;
    }

    // Date/Event
    $sql = "SELECT COUNT(`id`) AS event 
            FROM `fcms_calendar` 
            WHERE `created_by` = '$id'";
    $result = mysql_query($sql)  or displaySQLError(
        'Event Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    $points += $r['event'];

    // Vote
    $sql = "SELECT COUNT(`id`) AS vote 
            FROM `fcms_poll_votes` 
            WHERE `user` = '$id'";
    $result = mysql_query($sql)  or displaySQLError(
        'Vote Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    $points += $r['vote'];

    return $points;
}

/**
 * getUserParticipationLevel
 * 
 * Get the participation level for the given points.
 *
 *      Level   Points
 *      ---------------
 *      1           25
 *      2           50
 *      3          100
 *      4          200
 *      5          400
 *      6          800
 *      7        1,600
 *      8        3,200
 *      9        6,400
 *      10      12,800
 *      
 *
 * @param   int     $points
 * @return  string
 */
function getUserParticipationLevel ($points)
{
    $level = '';

    if ($points > 12800) {
        $level = '<div title="'.T_('Level 10').' ('.$points.')" class="level10"></div>';
    } elseif ($points > 6400) {
        $level = '<div title="'.T_('Level 9').' ('.$points.')" class="level9"></div>';
    } elseif ($points > 3200) {
        $level = '<div title="'.T_('Level 8').' ('.$points.')" class="level8"></div>';
    } elseif ($points > 1600) {
        $level = '<div title="'.T_('Level 7').' ('.$points.')" class="level7"></div>';
    } elseif ($points > 800) {
        $level = '<div title="'.T_('Level 6').' ('.$points.')" class="level6"></div>';
    } elseif ($points > 400) {
        $level = '<div title="'.T_('Level 5').' ('.$points.')" class="level5"></div>';
    } elseif ($points > 200) {
        $level = '<div title="'.T_('Level 4').' ('.$points.')" class="level4"></div>';
    } elseif ($points > 100) {
        $level = '<div title="'.T_('Level 3').' ('.$points.')" class="level3"></div>';
    } elseif ($points > 50) {
        $level = '<div title="'.T_('Level 2').' ('.$points.')" class="level2"></div>';
    } elseif ($points > 25) {
        $level = '<div title="'.T_('Level 1').' ('.$points.')" class="level1"></div>';
    } else {
        $level = '<div title="'.T_('Level 0').' ('.$points.')" class="level0"></div>';
    }

    return $level;
}

/**
 * getContactEmail 
 * 
 * @return  string
 */
function getContactEmail ()
{
    $result = mysql_query("SELECT `contact` FROM `fcms_config`");
    $r = mysql_fetch_array($result);
    return $r['contact'];
}

/**
 * getSiteName 
 * 
 * @return  string
 */
function getSiteName()
{
    $result = mysql_query("SELECT `sitename` FROM `fcms_config`");
    $r = mysql_fetch_array($result);
    return cleanOutput($r['sitename']);
}

/**
 * getCurrentVersion 
 * 
 * @return  void
 */
function getCurrentVersion()
{
    $result = mysql_query("SELECT `current_version` FROM `fcms_config`");
    $r = mysql_fetch_array($result);
    return $r['current_version'];
}

/**
 * displayMBToolbar 
 * 
 * @return  void
 */
function displayMBToolbar ()
{
    echo '
            <div id="toolbar" class="toolbar hideme">
                <input type="button" class="bold button" onclick="bb.insertCode(\'B\', \'bold\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Bold').'" />
                <input type="button" class="italic button" onclick="bb.insertCode(\'I\', \'italic\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Italic').'"/>
                <input type="button" class="underline button" onclick="bb.insertCode(\'U\', \'underline\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Underline').'"/>
                <input type="button" class="left_align button" onclick="bb.insertCode(\'ALIGN=LEFT\', \'left right\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Left Align').'"/>
                <input type="button" class="center_align button" onclick="bb.insertCode(\'ALIGN=CENTER\', \'center\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Center').'"/>
                <input type="button" class="right_align button" onclick="bb.insertCode(\'ALIGN=RIGHT\', \'align right\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Right Align').'"/>
                <input type="button" class="h1 button" onclick="bb.insertCode(\'H1\', \'heading 1\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Heading 1').'"/>
                <input type="button" class="h2 button" onclick="bb.insertCode(\'H2\', \'heading 2\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Heading 2').'"/>
                <input type="button" class="h3 button" onclick="bb.insertCode(\'H3\', \'heading 3\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Heading 3').'"/>
                <input type="button" class="board_quote button" onclick="bb.insertCode(\'QUOTE\', \'quote\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Quote').'"/>
                <input type="button" class="board_images button" onclick="window.open(\'inc/upimages.php\',\'name\',\'width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Insert Image').'"/>
                <input type="button" class="links button" onclick="bb.insertLink();" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Insert URL').'"/>
                <input type="button" class="smileys button" onclick="window.open(\'inc/smileys.php\',\'name\',\'width=500,height=200,scrollbars=no,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Insert Smiley').'"/>
                <input type="button" class="help button" onclick="window.open(\'inc/bbcode.php\',\'name\',\'width=400,height=300,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('BBCode Help').'"/>
            </div>';
}

/**
 * uploadImages 
 * 
 * @param string  $filetype 
 * @param string  $filename 
 * @param string  $filetmpname 
 * @param string  $destination 
 * @param int     $max_h 
 * @param int     $max_w 
 * @param boolean $unique 
 * @param boolean $show
 * @param boolean $square
 * 
 * @return  string
 */
function uploadImages ($filetype, $filename, $filetmpname, $destination, $max_h, $max_w, $unique = false, $show = true, $square = false)
{
    include_once('gallery_class.php');
    include_once('database_class.php');
    $currentUserId = cleanInput($_SESSION['login_id'], 'int');
    global $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass;
    $database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
    $gallery = new PhotoGallery($currentUserId, $database);

    $known_photo_types = array(
        'image/pjpeg' => 'jpeg', 
        'image/jpeg' => 'jpg', 
        'image/gif' => 'gif', 
        'image/bmp' => 'bmp', 
        'image/x-png' => 'png', 
        'image/png' => 'png'
    );
    $gd_function_suffix = array(
        'image/pjpeg' => 'JPEG', 
        'image/jpeg' => 'JPEG', 
        'image/gif' => 'GIF', 
        'image/bmp' => 'WBMP', 
        'image/x-png' => 'PNG', 
        'image/png' => 'PNG'
    );

    // Get extension of photo
    $ext = explode('.', $filename);
    $ext = end($ext);
    $ext = strtolower($ext);

    // Check mime type
    if (!array_key_exists($filetype, $known_photo_types)) {
        echo '
            <p class="error-alert">
                '.sprintf(T_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $filetype).'
            </p>';
    // Check file extension
    } elseif (!in_array($ext, $known_photo_types)) {
        echo '
            <p class="error-alert">
                '.sprintf(T_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $filetype).'
            </p>';
    } else {

        // Make filename unique
        if ($unique) {
            $new_id = uniqid("");
            $extention = $known_photo_types[$filetype];
            $filename = $new_id . "." . $extention;
        }

        copy($filetmpname, $destination . $filename);
        $size = GetImageSize($destination . $filename);

        if ($square) {
            $thumbnail = $gallery->getResizeSizeSquare(
                $size[0], 
                $size[1], 
                $max_w
            );
            $temp_width  = $thumbnail[0];
            $temp_height = $thumbnail[1];
            $width       = $thumbnail[2];
            $height      = $thumbnail[3];
        } else {
            $thumbnail = $gallery->getResizeSize(
                $size[0], 
                $size[1], 
                $max_w, 
                $max_h
            );
            $temp_width  = $thumbnail[0];
            $temp_height = $thumbnail[1];
            $width       = $thumbnail[0];
            $height      = $thumbnail[1];
        }


        if ($size[0] > $max_w && $size[1] > $max_h) {
            $function_suffix = $gd_function_suffix[$filetype];
            $function_to_read = "ImageCreateFrom".$function_suffix;
            $function_to_write = "Image".$function_suffix;
            $source_handle = $function_to_read($destination . $filename); 
            if ($source_handle) {
                $destination_handle = ImageCreateTrueColor($width, $height);
                ImageCopyResampled($destination_handle, $source_handle, 0, 0, 0, 0, $temp_width, $temp_height, $size[0], $size[1]);
            }
            $function_to_write($destination_handle, $destination . $filename);
            ImageDestroy($destination_handle );
        }
    }

    // Show thumbnail?
    if ($show) {
        echo "<img src=\"" . $destination . $filename . "\" alt=\"\"/>";
    }

    return $filename;
}


/**
 * displayPages
 * 
 * Function renamed in 2.0, needs to stay until old calls are updated.
 *
 * @deprecated deprecated since version 2.0 
 */
function displayPages ($url, $cur_page, $total_pages)
{
    displayPagination($url, $cur_page, $total_pages);
}

/**
 * displayPagination
 * 
 * Displays the pagination links.
 *
 * @param   url             the url of the page (index.php?uid=0)
 * @param   cur_page        the current page #
 * @param   total_pages     The total # of pages needed
 * @return  nothing
 */
function displayPagination ($url, $cur_page, $total_pages)
{
    // Check if we have a index.php url or a index.php?uid=0 url
    $end = substr($url, strlen($url) - 4);
    if ($end == '.php') {
        $divider = '?';
    } else {
        $divider = '&amp;';
    }

    if ($total_pages > 1) {
        echo '
            <div class="pages clearfix">
                <ul>';

        // First / Previous
        if ($cur_page > 1) {
            $prev = ($cur_page - 1);
            echo '
                    <li><a title="'.T_('First Page').'" class="first" href="'.$url.$divider.'page=1">'.T_('First').'</a></li>
                    <li><a title="'.T_('Previous Page').'" class="previous" href="'.$url.$divider.'page='.$prev.'">'.T_('Previous').'</a></li>';
        } else {
            echo '
                    <li><a title="'.T_('First Page').'" class="first" href="'.$url.$divider.'page=1">'.T_('First').'</a></li>
                    <li><a title="'.T_('Previous Page').'" class="previous" href="'.$url.$divider.'page=1">'.T_('Previous').'</a></li>';
        }

        // Numbers
        if ($total_pages > 8) {
            if ($cur_page > 2) {
                for ($i = ($cur_page-2); $i <= ($cur_page+5); $i++) {
                    if ($i <= $total_pages) {
                        $class = $cur_page == $i ? ' class="current"' : '';
                        echo '
                    <li><a href="'.$url.$divider.'page='.$i.'"'.$class.'>'.$i.'</a></li>';
                    }
                } 
            } else {
                for ($i = 1; $i <= 8; $i++) {
                    $class = $cur_page == $i ? ' class="current"' : '';
                    echo '
                    <li><a href="'.$url.$divider.'page='.$i.'"'.$class.'>'.$i.'</a></li>';
                } 
            }
        } else {
            for ($i = 1; $i <= $total_pages; $i++) {
                $class = $cur_page == $i ? ' class="current"' : '';
                echo '
                    <li><a href="'.$url.$divider.'page='.$i.'"'.$class.'>'.$i.'</a></li>';
            } 
        }

        // Next / Last
        if ($cur_page < $total_pages) { 
            $next = ($cur_page + 1);
            echo '
                    <li><a title="'.T_('Next Page').'" class="next" href="'.$url.$divider.'page='.$next.'">'.T_('Next').'</a></li>
                    <li><a title="'.T_('Last page').'" class="last" href="'.$url.$divider.'page='.$total_pages.'">'.T_('Last').'</a></li>';
        } else {
            echo '
                    <li><a title="'.T_('Next Page').'" class="next" href="'.$url.$divider.'page='.$total_pages.'">'.T_('Next').'</a></li>
                    <li><a title="'.T_('Last page').'" class="last" href="'.$url.$divider.'page='.$total_pages.'">'.T_('Last').'</a></li>';
        } 
        echo '
                </ul>
            </div>';
    }    
}

/**
 * formatSize 
 * 
 * @param   int     $file_size 
 * @return  string
 */
function formatSize($file_size)
{
    if ($file_size >= 1073741824) {
        $file_size = round($file_size / 1073741824 * 100) / 100 . "Gb";
    } elseif ($file_size >= 1048576) { 
        $file_size = round($file_size / 1048576 * 100) / 100 . "Mb";
    } elseif ($file_size >= 1024) {
        $file_size = round($file_size / 1024 * 100) / 100 . "Kb";
    } else {
        $file_size = $file_size . "b";
    }
    return $file_size;
}

/**
 * displayMembersOnline 
 * 
 * @return  void
 */
function displayMembersOnline ()
{
    $last15min = time() - (60 * 15);
    $lastday = time() - (60 * 60 * 24);
    $sql_last15min = mysql_query("SELECT * FROM fcms_users WHERE UNIX_TIMESTAMP(activity) >= $last15min ORDER BY `activity` DESC") or die('<h1>Online Error (util.inc.php 246)</h1>' . mysql_error());
    $sql_lastday = mysql_query("SELECT * FROM fcms_users WHERE UNIX_TIMESTAMP(activity) >= $lastday ORDER BY `activity` DESC") or die('<h1>Online Error (util.inc.php 247)</h1>' . mysql_error());
    echo '
            <h3>'.T_('Now').':</h3>
            <ul class="online-members">';
    $i = 1;
    $onlinenow_array = array();
    while ($e = mysql_fetch_array($sql_last15min)) {
        $displayname = getUserDisplayName($e['id']);
        $onlinenow_array[$i] = $e['id'];
        $i++;
        echo '
                <li>
                    <a href="profile.php?member='.$e['id'].'">
                        <img alt="avatar" src="'.getCurrentAvatar($e['id']).'"/> 
                        '.$displayname.'
                    </a>
                </li>';
    }
    echo '
            </ul>
            <h3>'.T_('Last 24 Hours').':</h3>
            <ul class="online-members">';
    while ($d = mysql_fetch_array($sql_lastday)) {
        $displayname = getUserDisplayName($d['id']);
        if (!array_search((string)$d['id'], $onlinenow_array)) {
            echo '
                <li>
                    <a href="profile.php?member='.$d['id'].'">
                        <img alt="avatar" src="'.getCurrentAvatar($d['id']).'"/> 
                        '.$displayname.'
                    </a>
                </li>';
        }
    }
    echo '
            </ul><br/><br/>';
}

/**
 * isLoggedIn
 * 
 * Checks whether user is logged in or not.  If user is logged in 
 * it just returns, if not, it redirects to login screen.
 * returns  boolean
 */
function isLoggedIn ($d = '')
{
    if ($d != '') {
        $up = '../';
    } else {
        $up = '';
    }

    // User has a session
    if (isset($_SESSION['login_id'])) {
        $id = $_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    // User has a cookie
    } elseif (isset($_COOKIE['fcms_login_id'])) {
        $_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
        $_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
        $_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
        $id = $_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    // User has nothing
    } else {
        $url = basename($_SERVER["REQUEST_URI"]);
        header("Location: {$up}index.php?err=login&url=$d$url");
        exit();
    }

    // Make sure id is a digit
    if (!ctype_digit($id)) {
        $url = basename($_SERVER["REQUEST_URI"]);
        header("Location: {$up}index.php?err=login&url=$d$url");
        exit();
    }

    // User's session/cookie credentials are good
    if (checkLoginInfo($id, $user, $pass)) {
        $sql = "SELECT `access`, `site_off` 
                FROM `fcms_users` AS u, `fcms_config` 
                WHERE u.`id` = ".escape_string($id)." LIMIT 1";
        $result = mysql_query($sql) or displaySQLError(
            'Site Status Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        // Site is off and your not an admin
        if ($r['site_off'] == 1 && $r['access'] > 1) {
            header("Location: {$up}index.php?err=off");
            exit();
        // Good login, you may proceed
        } else {
            return;
        }
    // The user's session/cookie credentials are bad
    } else {
        unset($_SESSION['login_id']);
        unset($_SESSION['login_uname']);
        unset($_SESSION['login_pw']);
        if (isset($_COOKIE['fcms_login_id'])) {
            setcookie('fcms_login_id', '', time() - 3600, '/');
            setcookie('fcms_login_uname', '', time() - 3600, '/');
            setcookie('fcms_login_pw', '', time() - 3600, '/');
        }
        header("Location: {$up}index.php?err=login");
        exit();
    }
}

/**
 * checkLoginInfo
 * 
 * Checks the user's username/pw combo
 *
 * @param   $userid     the id of the user you want to check
 * @param   $username   the username of the user
 * @param   $password   the password of the user
 * returns  boolean
 */
function checkLoginInfo ($userid, $username, $password)
{
    $userid = cleanInput($userid, 'int');
    $sql = "SELECT `username`, `password` 
            FROM `fcms_users` 
            WHERE `id` = '$userid' 
            LIMIT 1";
    $result = mysql_query($sql) or displaySQLError(
        'Login Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        $r = mysql_fetch_array($result);
        if ($r['username'] !== $username) {
            return false;
        } elseif ($r['password'] !== $password) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

/**
 * buildHtmlSelectOptions
 * 
 * Builds a list of select options, given an array of values and selected values.
 * 
 * @param   $options    array of available options, key is the value of the option
 * @param   $selected   array or string of selected options, key is the value of the option
 * returns  a string of options
 * 
 */
function buildHtmlSelectOptions ($options, $selected_options)
{
    $return = '';
    foreach ($options as $key => $value) {
        $selected = '';
        if (is_array($selected)) {
            if (array_key_exists($key, $selected_options)) {
                $selected = ' selected="selected"';
            }
        } else {
            if ($key == $selected_options) {
                $selected = ' selected="selected"';
            }
        }
        $return .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
    }
    return $return;
}

/**
 * usingFamilyNews 
 * 
 * Wrapper function for usingSection.
 * 
 * @return  boolean
 */
function usingFamilyNews()
{
    return usingSection('familynews');
}
/**
 * usingPrayers 
 * 
 * Wrapper function for usingSection.
 * 
 * @return  boolean
 */
function usingPrayers()
{
    return usingSection('prayers');
}
/**
 * usingRecipes 
 * 
 * Wrapper function for usingSection.
 * 
 * @return  boolean
 */
function usingRecipes()
{
    return usingSection('recipes');
}
/**
 * usingDocuments 
 * 
 * Wrapper function for usingSection.
 * 
 * @return  boolean
 */
function usingDocuments()
{
    return usingSection('documents');
}
/**
 * usingSection 
 * 
 * Checks whether the given section is currently being used.
 * 
 * @param   string  $section
 * @return  boolean
 */
function usingSection ($section)
{
    $sql = "SELECT * 
            FROM `fcms_navigation` 
            WHERE `link` = '" . cleanInput($section) . "' LIMIT 1";
    $result = mysql_query($sql) or displaySQLError(
        'Section Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        $r = mysql_fetch_array($result);
        if ($r['order'] > 0) {
            return true;
        }
    }
    return false;
}

/**
 * tableExists 
 * 
 * @param   string  $tbl 
 * @return  boolean
 */
function tableExists ($tbl)
{
    global $cfg_mysql_db;
    $tbl = cleanInput($tbl);
    $table = mysql_query("SHOW TABLES FROM `$cfg_mysql_db` LIKE '$tbl'");
    if (mysql_fetch_row($table) === false) {
        return false;
    } else {
        return true ;
    }
}

/**
 * getDomainAndDir 
 * 
 * @return  string
 */
function getDomainAndDir ()
{
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"])) { if ($_SERVER["HTTPS"] == "on") { $pageURL .= "s"; } }
    $pageURL .= "://";
    if (isset($_SERVER["SERVER_PORT"])) {
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
    }
    // Return the domain and any directories, but exlude the filename from the end
    return substr($pageURL, 0, strripos($pageURL, '/')+1);
}

/**
 * displaySQLError 
 * 
 * @param   string  $heading 
 * @param   string  $file 
 * @param   string  $sql 
 * @param   string  $error 
 * @return  void
 */
function displaySQLError ($heading, $file, $sql, $error)
{
    echo '
<div class="error-alert">
    <big><b>' . $heading . '</b></big><br/>
    <small><b>File:</b> ' . $file . '</small><br/>
    <small><b>Statement:</b> ' . $sql . '</small><br/>
    <small><b>Error:</b> ' . $error . '</small><br/>
    <small><b>MySQL Version:</b> ' . mysql_get_server_info() . '</small><br/>
    <small><b>PHP Version:</b> ' . phpversion() . '</small>
</div>';
}

/**
 * fcmsErrorHandler 
 * 
 * @param   string  $errno 
 * @param   string  $errstr 
 * @param   string  $errfile 
 * @param   string  $errline 
 * @return  void
 */
function fcmsErrorHandler($errno, $errstr, $errfile, $errline)
{
    $pos = strpos($errstr, "It is not safe to rely on the system's timezone settings");
    if ($pos === false) {
        switch ($errno) {
            case E_USER_ERROR:
                echo "<div class=\"error-alert\"><big><b>Fatal Error</b></big><br/><small><b>$errstr</b></small><br/>";
                echo  "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
                echo  "<small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                exit(1);
                break;
            case E_USER_WARNING:
                echo "<div class=\"error-alert\"><big><b>Warning</b></big><br/><small><b>$errstr</b></small><br/>";
                echo  "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
                echo  "<small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                break;
            case E_USER_NOTICE:
                echo "<div class=\"error-alert\"><big><b>Notice</b></big><br/><small><b>$errstr</b></small><br/>";
                echo "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
                echo  "<small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                break;
            default:
                echo "<div class=\"error-alert\"><big><b>Error</b></big><br/><small><b>$errstr</b></small><br/>";
                echo "<small><b>Where:</b> on line $errline in $errfile</small><br/>";
                echo "<small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                break;
        }
    }
    // Don't execute PHP internal error handler
    return true;
}

/**
 * displayWhatsNewAll 
 * 
 * @param   int     $userid 
 * @return  void
 */
function displayWhatsNewAll ($userid)
{
    global $cfg_mysql_host, $cfg_use_news, $cfg_use_prayers;
    $locale = new Locale();
    $userid = cleanInput($userid, 'int');
    $sql = "SELECT `timezone` 
            FROM `fcms_user_settings` 
            WHERE `user` = '$userid'";
    $t_result = mysql_query($sql) or displaySQLError(
        'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $t = mysql_fetch_array($t_result);
    $tz_offset = $t['timezone'];

    $sql = "SELECT p.`id`, `date`, `subject` AS title, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type 
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u 
            WHERE p.`thread` = t.`id` 
            AND p.`user` = u.`id` 
            AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT a.id, a.updated AS date, 0 AS title, a.user AS userid, a.entered_by AS id2, u.joindate AS id3, 'ADDRESSEDIT' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND DATE_FORMAT(a.updated, '%Y-%m-%d %h') != DATE_FORMAT(u.joindate, '%Y-%m-%d %h') 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT a.id, a.updated AS date, 0 AS title, a.user AS userid, a.entered_by AS id2, u.joindate AS id3, 'ADDRESSADD' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND u.`password` = 'NONMEMBER' 
            AND u.`activated` < 1 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT `id`, `joindate` AS date, 0 AS title, `id` AS userid, 0 AS id2, 0 AS id3, 'JOINED' AS type 
            FROM `fcms_users` 
            WHERE `password` != 'NONMEMBER' 
            AND `activated` > 0 
            AND `joindate` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    if (usingFamilyNews()) {
        $sql .= "UNION SELECT n.`id` AS id, n.`date`, `title`, u.`id` AS userid, 0 AS id2, 0 AS id3, 'NEWS' AS type 
                 FROM `fcms_users` AS u, `fcms_news` AS n 
                 WHERE u.`id` = n.`user` 
                 AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 
                 AND `username` != 'SITENEWS' 
                 AND `password` != 'SITENEWS' ";
    }
    if (usingPrayers()) {
        $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type 
                 FROM `fcms_prayers` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    }
    if (usingRecipes()) {
        $sql .= "UNION SELECT `id` AS id, `date`, `name` AS title, `user` AS userid, `category` AS id2, 0 AS id3, 'RECIPES' AS type 
                 FROM `fcms_recipes` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    }
    if (usingdocuments()) {
        $sql .= "UNION SELECT d.`id` AS 'id', d.`date`, `name` AS title, d.`user` AS userid, 0 AS id2, 0 AS id3, 'DOCS' AS type 
                 FROM `fcms_documents` AS d, `fcms_users` AS u 
                 WHERE d.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                 AND d.`user` = u.`id` ";
    }
    $sql .= "UNION SELECT DISTINCT p.`category` AS id, p.`date`, `name` AS title, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(p.`date`) AS id3, 'GALLERY' AS type 
             FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_category` AS c 
             WHERE p.`user` = u.`id` 
             AND p.`category` = c.`id` 
             AND 'date' >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             GROUP BY userid, title, id3 ";
    if (usingFamilyNews()) {
        $sql .= "UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`user` AS userid, 0 AS id2, 0 AS id3, 'NEWSCOM' AS type 
                 FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u 
                 WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                 AND nc.`user` = u.`id` 
                 AND n.`id` = nc.`news` ";
    }
    $sql .= "UNION SELECT p.`id`, gc.`date`, `comment` AS title, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type 
             FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p 
             WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             AND gc.`user` = u.`id` 
             AND gc.`photo` = p.`id` 

             UNION SELECT c.`id`, c.`date_added` AS date, `title`, `created_by` AS userid, `date` AS id2, `category` AS id3, 'CALENDAR' AS type 
             FROM `fcms_calendar` AS c, `fcms_users` AS u 
             WHERE c.`date_added` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             AND c.`created_by` = u.`id` AND `private` < 1 

             UNION SELECT `id`, `started` AS date, `question`, '0' AS userid, 'na' AS id2, 'na' AS id3, 'POLL' AS type 
             FROM `fcms_polls` 
             WHERE `started` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 

             ORDER BY date DESC LIMIT 0, 35";
    $result = mysql_query($sql) or displaySQLError(
        'Latest Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $lastday = '0-0';

    $today_start = $locale->fixDate('Ymd', $tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
    $today_end   = $locale->fixDate('Ymd', $tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

    $time = gmmktime(0, 0, 0, gmdate('m')  , gmdate('d')-1, gmdate('Y'));
    $yesterday_start = $locale->fixDate('Ymd', $tz_offset, gmdate('Y-m-d H:i:s', $time)) . '000000';
    $yesterday_end   = $locale->fixDate('Ymd', $tz_offset, gmdate('Y-m-d H:i:s', $time)) . '235959';

    while ($r=mysql_fetch_array($result)) {

        $updated     = $locale->fixDate('Ymd', $tz_offset, $r['date']);
        $updatedFull = $locale->fixDate('YmdHis', $tz_offset, $r['date']);

        if ($updated != $lastday) {
            if ($updatedFull >= $today_start && $updatedFull <= $today_end) {
                echo '
                <p><b>'.T_('Today').'</b></p>';
            } elseif ($updatedFull >= $yesterday_start && $updatedFull <= $yesterday_end) {
                echo '
                <p><b>'.T_('Yesterday').'</b></p>';
            } else {
                $date = $locale->fixDate('F j, Y', $tz_offset, $r['date']);
                echo '
                <p><b>'.$date.'</b></p>';
            }
        }
        $rdate = $locale->fixDate('g:i a', $tz_offset, $r['date']);

        if ($r['type'] == 'BOARD') {
            $check = mysql_query("SELECT min(`id`) AS id FROM `fcms_board_posts` WHERE `thread` = " . $r['id2']) or die("<h1>Thread or Post Error (util.inc.php 360)</h1>" . mysql_error());
            $minpost = mysql_fetch_array($check);
            $userName = getUserDisplayName($r['userid']);
            $userName = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$userName.'</a>';
            $subject = $r['title'];
            $pos = strpos($subject, '#ANOUNCE#');
            if ($pos !== false) {
                $subject = substr($subject, 9, strlen($subject)-9);
            }
            $title = cleanOutput($subject);
            $subject = cleanOutput($subject);
            $subject = '<a href="messageboard.php?thread='.$r['id2'].'" title="'.$title.'">'.$subject.'</a>';
            if ($r['id'] == $minpost['id']) {
                $class = 'newthread';
                $text = sprintf(T_('%s started the new thread %s.'), $userName, $subject);
            } else {
                $class = 'newpost';
                $text = sprintf(T_('%s replied to %s.'), $userName, $subject);
            }
            echo '
                <p class="'.$class.'">
                    '.$text.'. <small><i>'.$rdate.'</i></small>
                </p>';
        } elseif ($r['type'] == 'JOINED') {
            // A new user joined the site
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            echo '
                <p class="newmember">'.sprintf(T_('%s has joined the website.'), $displayname).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'ADDRESSEDIT') {
            // User updated his/her address
            $displayname = getUserDisplayName($r['id2']);
            $displayname = '<a class="u" href="profile.php?member='.$r['id2'].'">'.$displayname.'</a>';
            $address = '<a href="addressbook.php?address='.$r['id'].'">'.T_('address').'</a>';
            echo '
                <p class="newaddress">'.sprintf(T_('%s has updated his/her %s.'), $displayname, $address).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'ADDRESSADD') {
            // A user has added an address for a non-member
            $displayname = getUserDisplayName($r['id2']);
            $displayname = '<a class="u" href="profile.php?member='.$r['id2'].'">'.$displayname.'</a>';
            $for = '<a href="addressbook.php?address='.$r['id'].'">'.getUserDisplayName($r['userid'], 2, false).'</a>';
            echo '
                <p class="newaddress">'.sprintf(T_('%s has added address information for %s.'), $displayname, $for).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'NEWS') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $news = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.cleanOutput($r['title']).'</a>'; 
            echo '
                <p class="newnews">'.sprintf(T_('%s has added %s to his/her Family News.'), $displayname, $news).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'PRAYERS') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $for = '<a href="prayers.php">'.cleanOutput($r['title']).'</a>';
            echo '
                <p class="newprayer">'.sprintf(T_('%s has added a Prayer Concern for %s.'), $displayname, $for).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'RECIPES') {
            switch ($r['id2']) {
                case T_('Appetizer'): $url = "recipes.php?category=1&amp;id=".$r['id']; break;
                case T_('Breakfast'): $url = "recipes.php?category=2&amp;id=".$r['id']; break;
                case T_('Dessert'): $url = "recipes.php?category=3&amp;id=".$r['id']; break;
                case T_('Entree (Meat)'): $url = "recipes.php?category=4&amp;id=".$r['id']; break;
                case T_('Entree (Seafood)'): $url = "recipes.php?category=5&amp;id=".$r['id']; break;
                case T_('Entree (Vegetarian)'): $url = "recipes.php?category=6&amp;id=".$r['id']; break;
                case T_('Salad'): $url = "recipes.php?category=7&amp;id=".$r['id']; break;
                case T_('Side Dish'): $url = "recipes.php?category=8&amp;id=".$r['id']; break;
                case T_('Soup'): $url = "recipes.php?category=9&amp;id=".$r['id']; break;
                default: $url = "recipes.php"; break;
            }
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $rec = '<a href="'.$url.'">'.cleanOutput($r['title']).'</a>';
            echo '
                <p class="newrecipe">'.sprintf(T_('%s has added the %s recipe.'), $displayname, $rec).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'DOCS') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $doc = '<a href="documents.php">'.cleanOutput($r['title']).'</a>';
            echo '
                <p class="newdocument">'.sprintf(T_('%s has added a new Document (%s).'), $displayname, $doc).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'GALLERY') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $cat = '<a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'">'.cleanOutput($r['title']).'</a>';
            echo '
                    <p class="newphoto">
                        '.sprintf(T_('%s has added %d new photos to the %s category.'), $displayname, $r['id2'], $cat).' <small><i>'.$rdate.'</i></small><br/>';
            $limit = 4;
            if ($r['id2'] < $limit) {
                $limit = $r['id2'];
            }
            $sql = "SELECT * 
                    FROM `fcms_gallery_photos` 
                    WHERE `category` = '".cleanInput($r['id'], 'int')."' 
                    AND DAYOFYEAR(`date`) = '".cleanInput($r['id3'])."' 
                    ORDER BY `date` 
                    DESC LIMIT $limit";
            $photos = mysql_query($sql) or displaySQLError(
                'Photos Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($p=mysql_fetch_array($photos)) {
                echo '
                        <a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'&amp;pid='.$p['id'].'">
                            <img src="gallery/photos/member'.$r['userid'].'/tb_'.basename($p['filename']).'" alt="'.cleanOutput($p['caption']).'"/>
                        </a> &nbsp;';
            }
            echo '
                    </p>';
        } elseif ($r['type'] == 'NEWSCOM') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $news = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.cleanOutput($r['title']).'</a>';
            echo '
                    <p class="newcom">'.sprintf(T_('%s commented on Family News %s.'), $displayname, $news).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'GALCOM') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            echo '
                    <p class="newcom">
                        '.sprintf(T_('%s commented on the following photo:'), $displayname).' <small><i>'.$rdate.'</i></small><br/>
                        <a href="gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$r['id'].'">
                            <img src="gallery/photos/member'.$r['id2'].'/tb_'.basename($r['id3']).'"/>
                        </a>
                    </p>';
        } elseif ($r['type'] == 'CALENDAR') {
            $date_date   = $locale->fixDate('m-d-y', $tz_offset, $r['id2']);
            $date_date2  = $locale->fixDate('F j, Y', $tz_offset, $r['id2']);
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $for = '<a href="calendar.php?year='.date('Y', strtotime($date_date2))
                .'&amp;month='.date('m', strtotime($date_date2))
                .'&amp;day='.date('d', strtotime($date_date2)).'">'.cleanOutput($r['title']).'</a>';
            echo '
                    <p class="newcal">'.sprintf(T_('%s has added a new Calendar entry on %s for %s.'), $displayname, $date_date, $for).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'POLL') {
            $poll = '<a href="home.php?poll_id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';
            echo '
                <p class="newpoll">'.sprintf(T_('A new Poll (%s) has been added.'), $poll).' <small><i>'.$rdate.'</i></small></p>';
        }
        $lastday = $updated;
    }
}

/**
 * ImageCreateFromBMP 
 * 
 * @author  DHKold
 * @contact admin@dhkold.com
 * @date    The 15th of June 2005
 * @version 2.0B
 *
 * @param   string  $filename 
 * @return  void
 */
function ImageCreateFromBMP ($filename)
{
    if (! $f1 = fopen($filename,"rb")) return FALSE;
    
    $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
    if ($FILE['file_type'] != 19778) return FALSE;
    
    $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel/Vcompression/Vsize_bitmap/Vhoriz_resolution/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
    $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
    if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
    $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
    $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
    $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
    $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
    $BMP['decal'] = 4-(4*$BMP['decal']);
    if ($BMP['decal'] == 4) $BMP['decal'] = 0;
    
    $PALETTE = array();
    if ($BMP['colors'] < 16777216) {
        $PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
    }
    
    $IMG = fread($f1,$BMP['size_bitmap']);
    $VIDE = chr(0);
    
    $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
    $P = 0;
    $Y = $BMP['height']-1;
    while ($Y >= 0) {

    $X=0;
        while ($X < $BMP['width']) {
            if ($BMP['bits_per_pixel'] == 24) {
                $COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
            } elseif ($BMP['bits_per_pixel'] == 16) {  
                $COLOR = unpack("n",substr($IMG,$P,2));
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } elseif ($BMP['bits_per_pixel'] == 8) {  
                $COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } elseif ($BMP['bits_per_pixel'] == 4) {
                $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
                if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } elseif ($BMP['bits_per_pixel'] == 1) {
                $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
                if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
                elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
                elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
                elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
                elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
                elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
                elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
                elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } else {
                return FALSE;
            }
            imagesetpixel($res,$X,$Y,$COLOR[1]);
            $X++;
            $P += $BMP['bytes_per_pixel'];
        }
        $Y--;
        $P+=$BMP['decal'];
    }
    
    //Fermeture du fichier
    fclose($f1);
    
    return $res;
}

/**
 * usingAdvancedUploader 
 * 
 * @param   int     $userid 
 * @return  boolean
 */
function usingAdvancedUploader ($userid)
{
    $userid = cleanInput($userid, 'int');
    $sql = "SELECT `advanced_upload` FROM `fcms_user_settings` WHERE `user` = '$userid'";
    $result = mysql_query($sql) or displaySQLError(
        'Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    if ($r['advanced_upload'] == 1) {
        return true;
    } else {
        return false;
    }
}

/**
 * multi_array_key_exists
 *
 * @param   $needle     The key you want to check for
 * @param   $haystack   The array you want to search
 * @return  boolean
 */
function multi_array_key_exists ($needle, $haystack)
{
    foreach ($haystack as $key => $value) {
        if ($needle == $key) {
            return true;
        }
        if (is_array($value)) {
            if (multi_array_key_exists($needle, $value) == true) {
                return true;
            } else {
                continue;
            }
        }
    }
    return false;
}

/**
 * getLangName 
 * 
 * Given a gettext language code, it returns the translated
 * language full name
 *
 * @param   string  $code 
 * @return  string
 */
function getLangName ($code)
{
    switch($code) {
        case 'cs_CZ':
            return T_('Czech (Czech Republic)');
            break;
        case 'da_DK':
            return T_('Danish (Denmark)');
            break;
        case 'de_DE':
            return T_('German (Germany)');
            break;
        case 'en_US':
            return T_('English (United States)');
            break;
        case 'es_ES':
            return T_('Spanish (Spain)');
            break;
        case 'et':
            return T_('Estonian');
            break;
        case 'fr_FR':
            return T_('French (France)');
            break;
        case 'lv':
            return T_('Latvian');
            break;
        case 'nl':
            return T_('Dutch');
            break;
        case 'pt_BR':
            return T_('Portuguese (Brazil)');
            break;
        case 'sk_SK':
            return T_('Slovak');
            break;
        case 'zh_CN':
            return T_('Chinese (China)');
            break;
        case 'x-wrap':
            return T_('X Wrapped');
            break;
        default:
            return $code;
            break;
    }
}

/**
 * recursive_array_search 
 * 
 * @param   string  $needle 
 * @param   string  $haystack 
 * @return  void
 */
function recursive_array_search ($needle, $haystack)
{
    foreach($haystack as $key=>$value) {
        $current_key = $key;
        if (
                $needle === $value OR 
                (is_array($value) && recursive_array_search($needle,$value) !== false)
        ) {
            return $current_key;
        }
    }
    return false;
}

/**
 * printr 
 *
 * Development only, wraps pre tags around print_r output.
 * 
 * @param   string  $var 
 * @return  void
 */
function printr ($var)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

/**
 * getBirthdayCategory
 *
 * returns the id of the category for bithday, if available
 *
 * @return int
 */
function getBirthdayCategory ()
{
    $sql = "SELECT `id` 
            FROM `fcms_category` 
            WHERE `type` = 'calendar' 
                AND `name` like 'Birthday'";
    $result = mysql_query($sql) or displaySQLError(
        'Bday Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        $r = mysql_fetch_array($result);
        return $r['id'];
    } else {
        return 1;
    }
}

/**
 * getCalendarCategory 
 * 
 * Searches the db for a category that matches the given string
 *
 * @param   string  $cat 
 * @param   boolean $caseSensitive 
 * @return  int
 */
function getCalendarCategory ($cat, $caseSensitive = false)
{
    if ($caseSensitive) {
        $sql = "SELECT `id` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar' 
                    AND `name` like '$cat'";
    } else {
        $sql = "SELECT `id` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar' 
                    AND (
                        `name` like '".ucfirst($cat)."' OR
                        `name` like '".strtoupper($cat)."' OR
                        `name` like '".strtolower($cat)."' OR
                        `name` like '$cat'
                    )";
    }
    $result = mysql_query($sql) or displaySQLError(
        'Category Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        $r = mysql_fetch_array($result);
        return $r['id'];
    } else {
        return 1;
    }
}

/**
 * getCurrentAvatar 
 * 
 * @param   int     $id 
 * @param   boolean $gallery 
 * @return  string
 */
function getCurrentAvatar ($id, $gallery = true)
{
    $id = cleanInput($id, 'int');

    $sql = "SELECT `avatar`, `gravatar`
            FROM `fcms_users`
            WHERE `id` = '$id'";
    $result = mysql_query($sql) or displaySQLError(
        'Avatar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );

    // No Avatar set
    if (mysql_num_rows($result) <= 0) {
        return 'no_avatar.jpg';
    }

    $r = mysql_fetch_array($result);

    // include gallery directory
    $url = $gallery ? 'gallery/' : '';

    switch ($r['avatar'])
    {
        case 'no_avatar.jpg':
            return $url.'avatar/no_avatar.jpg';
            break;
        case 'gravatar':
            return 'http://www.gravatar.com/avatar.php?gravatar_id='.md5(strtolower($r['gravatar'])).'&amp;s=80'; 
        default:
            return $url.'avatar/'.basename($r['avatar']);
    }
}

?>
