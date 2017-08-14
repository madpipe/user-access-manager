<?php
/**
 * Config.php
 *
 * The Config class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

use UserAccessManager\Cache\Cache;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Config
 *
 * @package UserAccessManager\Config
 */
class MainConfig extends Config
{
    const MAIN_CONFIG_KEY = 'uamAdminOptions';
    const DEFAULT_TYPE = 'default';
    const CACHE_PROVIDER_NONE = 'none';

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ConfigParameterFactory
     */
    protected $configParameterFactory;

    /**
     * @var string
     */
    private $baseFile;

    /**
     * @var array
     */
    private $mimeTypes = null;

    /**
     * Config constructor.
     *
     * @param Wordpress              $wordpress
     * @param ObjectHandler          $objectHandler
     * @param Cache                  $cache
     * @param ConfigParameterFactory $configParameterFactory
     * @param String                 $baseFile
     */
    public function __construct(
        Wordpress $wordpress,
        ObjectHandler $objectHandler,
        Cache $cache,
        ConfigParameterFactory $configParameterFactory,
        $baseFile
    ) {
        $this->objectHandler = $objectHandler;
        $this->cache = $cache;
        $this->configParameterFactory = $configParameterFactory;
        $this->baseFile = $baseFile;

        parent::__construct($wordpress, self::MAIN_CONFIG_KEY);
    }

    /**
     * Returns the default config parameters settings
     *
     * @return array<string,ConfigParameter>
     */
    protected function getDefaultConfigParameters()
    {
        if ($this->defaultConfigParameters === []) {
            /**
             * @var array<string,ConfigParameter> $configParameters
             */
            $configParameters = [];

            $postTypes = $this->objectHandler->getPostTypes();
            array_unshift($postTypes, self::DEFAULT_TYPE);

            foreach ($postTypes as $postType) {
                if ($postType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
                    continue;
                }

                if ($postType !== self::DEFAULT_TYPE) {
                    $id = "{$postType}_use_default";
                    $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);
                }

                $id = "hide_{$postType}";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                $id = "hide_{$postType}_title";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                $id = "{$postType}_title";
                $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                    $id,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS
                );

                $id = "{$postType}_content";
                $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                    $id,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_ENTRY
                );

                $id = "hide_{$postType}_comment";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                $id = "{$postType}_comment_content";
                $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                    $id,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_COMMENTS
                );

                $id = "{$postType}_comments_locked";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                if ($postType === 'post') {
                    $id = "show_{$postType}_content_before_more";
                    $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);
                }
            }

            $id = 'redirect';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'false',
                ['false', 'custom_page', 'custom_url']
            );

            $id = 'redirect_custom_page';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id);

            $id = 'redirect_custom_url';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id);

            $id = 'lock_recursive';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

            $id = 'authors_has_access_to_own';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);


            $id = 'authors_can_add_posts_to_groups';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

            $id = 'lock_file';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

            $id = 'file_pass_type';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'random',
                ['random', 'user']
            );

            $id = 'download_type';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'fopen',
                ['fopen', 'normal']
            );

            $id = 'lock_file_types';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'all',
                ['all', 'selected', 'not_selected']
            );

            $id = 'locked_file_types';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                $id,
                'zip,rar,tar,gz'
            );

            $id = 'not_locked_file_types';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                $id,
                'gif,jpg,jpeg,png'
            );

            $id = 'blog_admin_hint';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

            $id = 'blog_admin_hint_text';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id, '[L]');

            $taxonomies = $this->objectHandler->getTaxonomies();
            array_unshift($taxonomies, self::DEFAULT_TYPE);

            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy !== self::DEFAULT_TYPE) {
                    $id = "{$taxonomy}_use_default";
                    $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);
                }

                $id = 'hide_empty_'.$taxonomy;
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);
            }

            $id = 'protect_feed';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

            $id = 'full_access_role';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'administrator',
                ['administrator', 'editor', 'author', 'contributor', 'subscriber']
            );

            $id = 'active_cache_provider';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                self::CACHE_PROVIDER_NONE,
                array_merge([self::CACHE_PROVIDER_NONE], array_keys($this->cache->getRegisteredCacheProviders()))
            );

            $this->defaultConfigParameters = $configParameters;
        }

        return $this->defaultConfigParameters;
    }

    /**
     * Returns true if a user is at the admin panel.
     *
     * @return bool
     */
    public function atAdminPanel()
    {
        return $this->wordpress->isAdmin();
    }

    /**
     * Returns true if permalinks are active otherwise false.
     *
     * @return bool
     */
    public function isPermalinksActive()
    {
        $permalinkStructure = $this->getWpOption('permalink_structure');
        return (empty($permalinkStructure) === false);
    }

    /**
     * Returns the upload directory.
     *
     * @return null|string
     */
    public function getUploadDirectory()
    {
        $wordpressUploadDir = $this->wordpress->getUploadDir();

        if (empty($wordpressUploadDir['error'])) {
            return $wordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
        }

        return null;
    }

    /**
     * Returns the full supported mine types.
     *
     * @return array
     */
    public function getMimeTypes()
    {
        if ($this->mimeTypes === null) {
            $mimeTypes = $this->wordpress->getAllowedMimeTypes();
            $fullMimeTypes = [];

            foreach ($mimeTypes as $extensions => $mineType) {
                $extensions = explode('|', $extensions);

                foreach ($extensions as $extension) {
                    $fullMimeTypes[$extension] = $mineType;
                }
            }

            $this->mimeTypes = $fullMimeTypes;
        }

        return $this->mimeTypes;
    }

    /**
     * Returns the module url path.
     *
     * @return string
     */
    public function getUrlPath()
    {
        return $this->wordpress->pluginsUrl('', $this->baseFile).'/';
    }

    /**
     * Returns the module real path.
     *
     * @return string
     */
    public function getRealPath()
    {
        $dirName = dirname($this->baseFile);

        return $this->wordpress->getPluginDir().DIRECTORY_SEPARATOR
            .$this->wordpress->pluginBasename($dirName).DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the object parameter name.
     *
     * @param string $objectType
     * @param string $rawParameterName
     *
     * @return ConfigParameter|null
     */
    private function getObjectParameter($objectType, $rawParameterName)
    {
        $options = $this->getConfigParameters();
        $parameterName = sprintf($rawParameterName, $objectType);

        if (isset($options[$parameterName]) === false
            || isset($options["{$objectType}_use_default"]) === true
               && $options["{$objectType}_use_default"]->getValue() === true
        ) {
            $parameterName = sprintf($rawParameterName, self::DEFAULT_TYPE);
        }

        return (isset($options[$parameterName]) === true) ? $options[$parameterName] : null;
    }

    /**
     * Returns the option value if the option exists otherwise true.
     *
     * @param string $objectType
     * @param string $parameterName
     *
     * @return bool
     */
    private function hideObject($objectType, $parameterName)
    {
        $parameter = $this->getObjectParameter($objectType, $parameterName);
        return ($parameter !== null) ? $parameter->getValue() : true;
    }

    /**
     * Returns the option value if the option exists otherwise an empty string.
     *
     * @param string $objectType
     * @param string $parameterName
     *
     * @return string
     */
    private function getObjectContent($objectType, $parameterName)
    {
        $parameter = $this->getObjectParameter($objectType, $parameterName);
        return ($parameter !== null) ? $parameter->getValue() : '';
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function hidePostType($postType)
    {
        return $this->hideObject($postType, 'hide_%s');
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function hidePostTypeTitle($postType)
    {
        return $this->hideObject($postType, 'hide_%s_title');
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function hidePostTypeComments($postType)
    {
        return $this->hideObject($postType, 'hide_%s_comment');
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function lockPostTypeComments($postType)
    {
        return $this->hideObject($postType, '%s_comments_locked');
    }

    /**
     * @param string $postType
     *
     * @return string
     */
    public function getPostTypeTitle($postType)
    {
        return $this->getObjectContent($postType, '%s_title');
    }

    /**
     * @param string $postType
     *
     * @return string
     */
    public function getPostTypeContent($postType)
    {
        return $this->getObjectContent($postType, '%s_content');
    }

    /**
     * @param string $postType
     *
     * @return string
     */
    public function getPostTypeCommentContent($postType)
    {
        return $this->getObjectContent($postType, '%s_comment_content');
    }

    /**
     * @return string
     */
    public function getRedirect()
    {
        return $this->getParameterValue('redirect');
    }

    /**
     * @return string
     */
    public function getRedirectCustomPage()
    {
        return $this->getParameterValue('redirect_custom_page');
    }

    /**
     * @return string
     */
    public function getRedirectCustomUrl()
    {
        return $this->getParameterValue('redirect_custom_url');
    }

    /**
     * @return bool
     */
    public function lockRecursive()
    {
        return $this->getParameterValue('lock_recursive');
    }

    /**
     * @return bool
     */
    public function authorsHasAccessToOwn()
    {
        return $this->getParameterValue('authors_has_access_to_own');
    }

    /**
     * @return bool
     */
    public function authorsCanAddPostsToGroups()
    {
        return $this->getParameterValue('authors_can_add_posts_to_groups');
    }

    /**
     * @return bool
     */
    public function lockFile()
    {
        return $this->getParameterValue('lock_file');
    }

    /**
     * @return string
     */
    public function getFilePassType()
    {
        return $this->getParameterValue('file_pass_type');
    }

    /**
     * @return string
     */
    public function getLockFileTypes()
    {
        return $this->getParameterValue('lock_file_types');
    }

    /**
     * @return string
     */
    public function getDownloadType()
    {
        return $this->getParameterValue('download_type');
    }

    /**
     * @return string
     */
    public function getLockedFileTypes()
    {
        return $this->getParameterValue('locked_file_types');
    }

    /**
     * @return string
     */
    public function getNotLockedFileTypes()
    {
        return $this->getParameterValue('not_locked_file_types');
    }

    /**
     * @return bool
     */
    public function blogAdminHint()
    {
        return $this->getParameterValue('blog_admin_hint');
    }

    /**
     * @return string
     */
    public function getBlogAdminHintText()
    {
        return $this->getParameterValue('blog_admin_hint_text');
    }

    /**
     * @param string $taxonomy
     *
     * @return bool
     */
    public function hideEmptyTaxonomy($taxonomy)
    {
        $parameter = $this->getObjectParameter($taxonomy, 'hide_empty_%s');
        return ($parameter !== null) ? $parameter->getValue() : false;
    }

    /**
     * @return bool
     */
    public function protectFeed()
    {
        return $this->getParameterValue('protect_feed');
    }

    /**
     * @return bool
     */
    public function showPostContentBeforeMore()
    {
        return $this->getParameterValue('show_post_content_before_more');
    }

    /**
     * @return string
     */
    public function getFullAccessRole()
    {
        return $this->getParameterValue('full_access_role');
    }

    /**
     * @return null|string
     */
    public function getActiveCacheProvider()
    {
        return $this->getParameterValue('active_cache_provider');
    }
}
