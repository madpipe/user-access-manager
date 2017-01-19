<?php
/**
 * Plugin Name: User Access Manager
 * Plugin URI: https://wordpress.org/plugins/user-access-manager/
 * Author URI: https://twitter.com/GM_Alex
 * Version: 1.2.14
 * Author: Alexander Schneider
 * Description: Manage the access to your posts, pages, categories and files.
 * 
 * user-access-manager.php
 *
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
*/

//Check requirements
$blStop = false;

//Check php version
$sPhpVersion = phpversion();

if (version_compare($sPhpVersion, '5.3') === -1) {
    add_action(
        'admin_notices',
        create_function(
            '',
            'echo \'<div id="message" class="error"><p><strong>'.
            sprintf(TXT_UAM_PHP_VERSION_TO_LOW, $sPhpVersion).
            '</strong></p></div>\';'
        )
    );
    
    $blStop = true;
}

//Check wordpress version
global $wp_version;

if (version_compare($wp_version, '4.6') === -1) {
    add_action(
        'admin_notices',
        create_function(
            '',
            'echo \'<div id="message" class="error"><p><strong>'.
            sprintf(TXT_UAM_WORDPRESS_VERSION_TO_LOW, $wp_version).
            '</strong></p></div>\';'
        )
    );
    
    $blStop = true;
}

//If we have a error stop plugin.
if ($blStop) {
    return;
}

require_once 'autoloader.php';

//Paths
load_plugin_textdomain('user-access-manager', false, 'user-access-manager/lang');
define('UAM_URLPATH', plugins_url('', __FILE__).'/');
define('UAM_REALPATH', WP_PLUGIN_DIR.'/'.plugin_basename(dirname(__FILE__)).'/');

//Defines
require_once 'includes/language.define.php';

//Classes
use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileProtectionFactory;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Util\Util;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Wrapper\Wordpress;

$oWrapper = new Wordpress();
$oUtil = new Util();
$oConfigParameterFactory = new ConfigParameterFactory();
$oConfig = new Config($oWrapper, $oConfigParameterFactory);
$oCache = new Cache();
$oDatabase = new Database($oWrapper);
$oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
$oUserGroupFactory = new UserGroupFactory(
    $oWrapper,
    $oDatabase,
    $oConfig,
    $oCache,
    $oUtil,
    $oObjectHandler
);
$oAccessHandler = new AccessHandler(
    $oWrapper,
    $oConfig,
    $oCache,
    $oDatabase,
    $oObjectHandler,
    $oUtil,
    $oUserGroupFactory
);
$oFileHandler = new FileHandler($oWrapper, $oConfig);
$oControllerFactory = new ControllerFactory($oWrapper, $oConfig, $oAccessHandler, $oUserGroupFactory);
$oFileProtectionFactory = new FileProtectionFactory(
    $oWrapper,
    $oConfig,
    $oUtil,
    $oFileHandler
);
$oUserAccessManager = new UserAccessManager(
    $oWrapper,
    $oConfig,
    $oDatabase,
    $oObjectHandler,
    $oAccessHandler,
    $oFileHandler,
    $oUtil,
    $oControllerFactory,
    $oFileProtectionFactory
);

if (isset($oUserAccessManager)) {
    //install
    if (function_exists('register_activation_hook')) {
        register_activation_hook(__FILE__, array($oUserAccessManager, 'install'));
    }
    
    //uninstall
    if (function_exists('register_uninstall_hook')) {
        register_uninstall_hook(__FILE__, 'UserAccessManager\UserAccessManager::uninstall()');
    } elseif (function_exists('register_deactivation_hook')) {
        //Fallback
        register_deactivation_hook(__FILE__, array($oUserAccessManager, 'uninstall'));
    }
    
    //deactivation
    if (function_exists('register_deactivation_hook')) {
        register_deactivation_hook(__FILE__, array($oUserAccessManager, 'deactivate'));
    }
    
    //Redirect
    if ($oConfig->getRedirect() !== false || isset($_GET['uamgetfile'])) {
        add_filter('wp_headers', array($oUserAccessManager, 'redirect'), 10, 2);
    }

    //Actions
    if (function_exists('add_action')) {
        //add_action('registered_post_type', array(&$this, 'registeredPostType'), 10, 2); //TODO object handler
        add_action('admin_enqueue_scripts', array($oUserAccessManager,'enqueueAdminStylesAndScripts'));
        add_action('wp_enqueue_scripts', array($oUserAccessManager, 'enqueueStylesAndScripts'));
        add_action('admin_init', array($oUserAccessManager, 'registerAdminActionsAndFilters'));
        add_action('admin_menu', array($oUserAccessManager, 'registerAdminMenu'));
    }
    
    //Filters
    if (function_exists('add_filter')) {
        add_filter('wp_get_attachment_thumb_url', array($oUserAccessManager, 'getFileUrl'), 10, 2);
        add_filter('wp_get_attachment_url', array($oUserAccessManager, 'getFileUrl'), 10, 2);
        add_filter('the_posts', array($oUserAccessManager, 'showPosts'));
        add_filter('posts_where_paged', array($oUserAccessManager, 'showPostSql'));
        add_filter('get_terms_args', array($oUserAccessManager, 'getTermArguments'));
        add_filter('wp_get_nav_menu_items', array($oUserAccessManager, 'showCustomMenu'));
        add_filter('comments_array', array($oUserAccessManager, 'showComment'));
        add_filter('the_comments', array($oUserAccessManager, 'showComment'));
        add_filter('get_pages', array($oUserAccessManager, 'showPages'), 20);
        add_filter('get_terms', array($oUserAccessManager, 'showTerms'), 20, 2);
        add_filter('get_term', array($oUserAccessManager, 'showTerm'), 20, 2);
        add_filter('get_ancestors', array($oUserAccessManager, 'showAncestors'), 20, 4);
        add_filter('get_next_post_where', array($oUserAccessManager, 'showNextPreviousPost'));
        add_filter('get_previous_post_where', array($oUserAccessManager, 'showNextPreviousPost'));
        add_filter('post_link', array($oUserAccessManager, 'cachePostLinks'), 10, 2);
        add_filter('edit_post_link', array($oUserAccessManager, 'showGroupMembership'), 10, 2);
        add_filter('parse_query', array($oUserAccessManager, 'parseQuery'));
        add_filter('getarchives_where', array($oUserAccessManager, 'showPostSql'));
        add_filter('wp_count_posts', array($oUserAccessManager, 'showPostCount'), 10, 2);
        add_filter('wpseo_sitemap_entry', array($oUserAccessManager, 'wpSeoUrl'), 1, 3); // Yaost Sitemap Plugin
    }
}

//Add the cli interface to the known commands
if (defined('WP_CLI') && WP_CLI) {
    include __DIR__.'/includes/wp-cli.php';
}
