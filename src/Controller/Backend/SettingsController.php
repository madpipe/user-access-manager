<?php
/**
 * SettingsController.php
 *
 * The SettingsController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\File\FileHandler;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\Form\ValueSetFormElement;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class SettingsController extends Controller
{
    use ControllerTabNavigationTrait;

    const GROUP_POST_TYPES = 'post_types';
    const GROUP_TAXONOMIES = 'taxonomies';
    const GROUP_FILES = 'file';
    const SECTION_FILES = 'file';
    const GROUP_AUTHOR = 'author';
    const SECTION_AUTHOR = 'author';
    const GROUP_CACHE = 'cache';
    const GROUP_OTHER = 'other';
    const SECTION_OTHER = 'other';

    /**
     * @var MainConfig
     */
    private $mainConfig;

    /**
     * @var string
     */
    protected $template = 'AdminSettings.php';

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var FormHelper
     */
    private $formHelper;

    /**
     * SettingsController constructor.
     *
     * @param Php             $php
     * @param Wordpress       $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig      $mainConfig
     * @param Cache           $cache
     * @param FileHandler     $fileHandler
     * @param FormFactory     $formFactory
     * @param FormHelper      $formHelper
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Cache $cache,
        FileHandler $fileHandler,
        FormFactory $formFactory,
        FormHelper $formHelper
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->mainConfig = $mainConfig;
        $this->cache = $cache;
        $this->fileHandler = $fileHandler;
        $this->formFactory = $formFactory;
        $this->formHelper = $formHelper;
    }

    /**
     * Returns the tab groups.
     *
     * @return array
     */
    public function getTabGroups()
    {
        $activeCacheProvider = $this->mainConfig->getActiveCacheProvider();
        $cacheProviderSections = [$activeCacheProvider];
        $cacheProviders = $this->cache->getRegisteredCacheProviders();

        foreach ($cacheProviders as $cacheProvider) {
            if ($cacheProvider->getId() !== $activeCacheProvider) {
                $cacheProviderSections[] = $cacheProvider->getId();
            }
        }

        return [
            self::GROUP_POST_TYPES => array_merge([MainConfig::DEFAULT_TYPE], array_keys($this->getPostTypes())),
            self::GROUP_TAXONOMIES => array_merge([MainConfig::DEFAULT_TYPE], array_keys($this->getTaxonomies())),
            self::GROUP_FILES => [self::SECTION_FILES],
            self::GROUP_AUTHOR => [self::SECTION_AUTHOR],
            self::GROUP_CACHE => $cacheProviderSections,
            self::GROUP_OTHER => [self::SECTION_OTHER]
        ];
    }

    /**
     * Returns the pages.
     *
     * @return array
     */
    private function getPages()
    {
        $pages = $this->wordpress->getPages('sort_column=menu_order');
        return is_array($pages) !== false ? $pages : [];
    }

    /**
     * Returns the post types as object.
     *
     * @return \WP_Post_Type[]
     */
    private function getPostTypes()
    {
        return $this->wordpress->getPostTypes(['public' => true], 'objects');
    }

    /**
     * Returns the taxonomies as objects.
     *
     * @return \WP_Taxonomy[]
     */
    private function getTaxonomies()
    {
        return $this->wordpress->getTaxonomies(['public' => true], 'objects');
    }

    /**
     * @param string $key
     * @param bool   $description
     *
     * @return string
     */
    public function getText($key, $description = false)
    {
        return $this->formHelper->getText($key, $description);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getGroupText($key)
    {
        return $this->getText($key);
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function getGroupSectionText($key)
    {
        return ($key === MainConfig::DEFAULT_TYPE) ?
            TXT_UAM_SETTINGS_GROUP_SECTION_DEFAULT : $this->getObjectName($key);
    }

    /**
     * Returns the object name.
     *
     * @param string $objectKey
     *
     * @return string
     */
    public function getObjectName($objectKey)
    {
        $objects = $this->wordpress->getPostTypes(['public' => true], 'objects')
            + $this->wordpress->getTaxonomies(['public' => true], 'objects');

        return (isset($objects[$objectKey]) === true) ? $objects[$objectKey]->labels->name : $objectKey;
    }

    /**
     * Returns the post settings form.
     *
     * @param string $postType
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getPostSettingsForm($postType = MainConfig::DEFAULT_TYPE)
    {
        $textarea = null;
        $configParameters = $this->mainConfig->getConfigParameters();

        if (isset($configParameters["{$postType}_content"]) === true) {
            $configParameter = $configParameters["{$postType}_content"];
            $textarea = $this->formFactory->createTextarea(
                $configParameter->getId(),
                $configParameter->getValue(),
                $this->formHelper->getParameterText($configParameter, false, $postType),
                $this->formHelper->getParameterText($configParameter, true, $postType)
            );
        }

        $parameters = ($postType !== MainConfig::DEFAULT_TYPE) ? ["{$postType}_use_default"] : [];
        $parameters = array_merge($parameters, [
            "hide_{$postType}",
            "hide_{$postType}_title",
            "{$postType}_title",
            $textarea,
            "hide_{$postType}_comment",
            "{$postType}_comment_content",
            "{$postType}_comments_locked"
        ]);

        if ($postType === 'post') {
            $parameters[] = "show_{$postType}_content_before_more";
        }

        return $this->formHelper->getSettingsForm($parameters, $postType);
    }

    /**
     * Returns the taxonomy settings form.
     *
     * @param string $taxonomy
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getTaxonomySettingsForm($taxonomy = MainConfig::DEFAULT_TYPE)
    {
        $parameters = ($taxonomy !== MainConfig::DEFAULT_TYPE) ? ["{$taxonomy}_use_default"] : [];
        $parameters = array_merge($parameters, [
            "hide_empty_{$taxonomy}"
        ]);

        return $this->formHelper->getSettingsForm($parameters, $taxonomy);
    }

    /**
     * Checks if x send file is available.
     *
     * @return bool
     */
    private function isXSendFileAvailable()
    {
        $content = file_get_contents($this->wordpress->getHomeUrl().'?testXSendFile');
        $this->fileHandler->removeXSendFileTestFile();

        return ($content === 'success');
    }

    /**
     * Disables the xSendFileOption
     *
     * @param Form $form
     */
    private function disableXSendFileOption(Form $form)
    {
        $formElements = $form->getElements();

        if (isset($formElements['download_type']) === true) {
            /** @var ValueSetFormElement $downloadType */
            $downloadType = $formElements['download_type'];
            $possibleValues = $downloadType->getPossibleValues();

            if (isset($possibleValues['xsendfile']) === true) {
                $possibleValues['xsendfile']->markDisabled();
            }
        }
    }

    /**
     * Adds the lock file types config parameter to the parameters.
     *
     * @param array $configParameters
     * @param array $parameters
     */
    private function addLockFileTypes(array $configParameters, array &$parameters)
    {
        if (isset($configParameters['lock_file_types']) === true
            && $this->wordpress->isNginx() === false
        ) {
            $parameters['lock_file_types'] = [
                'selected' => 'locked_file_types',
                'not_selected' => 'not_locked_file_types'
            ];

            if ($this->wordpress->gotModRewrite() === false) {
                $parameters[] = 'file_pass_type';
            }
        }
    }

    /**
     * Returns the files settings form.
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getFilesSettingsForm()
    {
        $fileProtectionFileName = $this->fileHandler->getFileProtectionFileName();
        $fileContent = (file_exists($fileProtectionFileName) === true) ?
            file_get_contents($fileProtectionFileName) : '';

        $configParameters = $this->mainConfig->getConfigParameters();

        $parameters = [
            'lock_file',
            'download_type',
            'inline_files',
            'no_access_image_type' => ['custom' => 'custom_no_access_image'],
            'use_custom_file_handling_file',
            $this->formFactory->createTextarea(
                'custom_file_handling_file',
                $fileContent,
                TXT_UAM_CUSTOM_FILE_HANDLING_FILE,
                TXT_UAM_CUSTOM_FILE_HANDLING_FILE_DESC
            ),
            'locked_directory_type' => ['custom' => 'custom_locked_directories']
        ];

        $this->addLockFileTypes($configParameters, $parameters);

        $form = $this->formHelper->getSettingsForm($parameters);

        if ($this->isXSendFileAvailable() === false) {
            $this->disableXSendFileOption($form);
        }

        return $form;
    }

    /**
     * Returns the author settings form.
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getAuthorSettingsForm()
    {
        $parameters = [
            'authors_has_access_to_own',
            'authors_can_add_posts_to_groups',
            'full_access_role'
        ];

        return $this->formHelper->getSettingsForm($parameters);
    }

    /**
     * Returns the author settings form.
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getOtherSettingsForm()
    {
        $redirect = null;
        $configParameters = $this->mainConfig->getConfigParameters();

        if (isset($configParameters['redirect'])) {
            $values = [
                $this->formFactory->createMultipleFormElementValue('false', TXT_UAM_NO),
                $this->formFactory->createMultipleFormElementValue('blog', TXT_UAM_REDIRECT_TO_BLOG),
            ];

            if (isset($configParameters['redirect_custom_page']) === true) {
                $redirectCustomPage = $configParameters['redirect_custom_page'];
                $redirectCustomPageValue = $this->formFactory->createMultipleFormElementValue(
                    'selected',
                    TXT_UAM_REDIRECT_TO_PAGE
                );

                $possibleValues = [];
                $pages = $this->getPages();

                foreach ($pages as $page) {
                    $possibleValues[] = $this->formFactory->createValueSetFromElementValue(
                        (int)$page->ID,
                        $page->post_title
                    );
                }

                $formElement = $this->formFactory->createSelect(
                    $redirectCustomPage->getId(),
                    $possibleValues,
                    (int)$redirectCustomPage->getValue()
                );

                $redirectCustomPageValue->setSubElement($formElement);
                $values[] = $redirectCustomPageValue;
            }

            if (isset($configParameters['redirect_custom_url']) === true) {
                $values[] = $this->formHelper->createMultipleFromElement(
                    'custom_url',
                    TXT_UAM_REDIRECT_TO_URL,
                    $configParameters['redirect_custom_url']
                );
            }

            $configParameter = $configParameters['redirect'];

            $redirect = $this->formFactory->createRadio(
                $configParameter->getId(),
                $values,
                $configParameter->getValue(),
                TXT_UAM_REDIRECT,
                TXT_UAM_REDIRECT_DESC
            );
        }

        $parameters = [
            'lock_recursive',
            'protect_feed',
            $redirect,
            'blog_admin_hint',
            'blog_admin_hint_text',
            'show_assigned_groups'
        ];

        return $this->formHelper->getSettingsForm($parameters);
    }

    /**
     * Returns the full settings from.
     *
     * @param array    $types
     * @param array    $ignoredTypes
     * @param Callable $formFunction
     *
     * @return array
     */
    private function getFullSettingsFrom(array $types, array $ignoredTypes, $formFunction)
    {
        $groupForms = [];
        $groupForms[MainConfig::DEFAULT_TYPE] = $formFunction();

        foreach ($ignoredTypes as $ignoredType) {
            unset($types[$ignoredType]);
        }

        foreach ($types as $type => $typeObject) {
            $groupForms[$type] = $formFunction($type);
        }

        return $groupForms;
    }

    /**
     * Returns the full taxonomy post forms.
     *
     * @return array
     */
    private function getFullPostSettingsForm()
    {
        return $this->getFullSettingsFrom(
            $this->getPostTypes(),
            [ObjectHandler::ATTACHMENT_OBJECT_TYPE],
            function ($type = MainConfig::DEFAULT_TYPE) {
                return $this->getPostSettingsForm($type);
            }
        );
    }

    /**
     * Returns the full taxonomy settings forms.
     *
     * @return array
     */
    private function getFullTaxonomySettingsForm()
    {
        return $this->getFullSettingsFrom(
            $this->getTaxonomies(),
            [ObjectHandler::POST_FORMAT_TYPE],
            function ($type = MainConfig::DEFAULT_TYPE) {
                return $this->getTaxonomySettingsForm($type);
            }
        );
    }

    /**
     * Returns the full cache providers froms.
     *
     * @return array
     */
    private function getFullCacheProvidersFrom()
    {
        $groupForms = [];
        $cacheProviders = $this->cache->getRegisteredCacheProviders();
        $groupForms[MainConfig::CACHE_PROVIDER_NONE] = null;

        foreach ($cacheProviders as $cacheProvider) {
            $groupForms[$cacheProvider->getId()] = $this->formHelper->getSettingsFormByConfig(
                $cacheProvider->getConfig()
            );
        }

        return $groupForms;
    }

    /**
     * Returns the current settings form.
     *
     * @return \UserAccessManager\Form\Form[]
     */
    public function getCurrentGroupForms()
    {
        $group = $this->getCurrentTabGroup();
        $groupForms = [];

        if ($group === self::GROUP_POST_TYPES) {
            $groupForms = $this->getFullPostSettingsForm();
        } elseif ($group === self::GROUP_TAXONOMIES) {
            $groupForms = $this->getFullTaxonomySettingsForm();
        } elseif ($group === self::GROUP_FILES) {
            $groupForms = [self::SECTION_FILES => $this->getFilesSettingsForm()];
        } elseif ($group === self::GROUP_AUTHOR) {
            $groupForms = [self::SECTION_AUTHOR => $this->getAuthorSettingsForm()];
        } elseif ($group === self::GROUP_CACHE) {
            $groupForms = $this->getFullCacheProvidersFrom();
        } elseif ($group === self::GROUP_OTHER) {
            $groupForms = [self::SECTION_OTHER => $this->getOtherSettingsForm()];
        }

        return $groupForms;
    }

    /**
     * Updates the file handling file.
     *
     * @param array $configParameters
     */
    private function updateFileProtectionFile(array $configParameters)
    {
        $key = 'custom_file_handling_file';
        $customFileHandlingFile = isset($configParameters[$key]) === true ? $configParameters[$key] : null;
        unset($configParameters[$key]);

        $this->mainConfig->setConfigParameters($configParameters);

        if ($this->mainConfig->lockFile() === false) {
            $this->fileHandler->deleteFileProtection();
        } elseif ($this->mainConfig->useCustomFileHandlingFile() === false) {
            $this->fileHandler->createFileProtection();
        } elseif ($customFileHandlingFile !== null) {
            $this->php->filePutContents(
                $this->fileHandler->getFileProtectionFileName(),
                htmlspecialchars_decode($customFileHandlingFile)
            );
        }
    }

    /**
     * Update settings action.
     */
    public function updateSettingsAction()
    {
        $this->verifyNonce('uamUpdateSettings');
        $group = $this->getCurrentTabGroup();
        $newConfigParameters = $this->getRequestParameter('config_parameters');

        if ($group === self::GROUP_CACHE) {
            $section = $this->getCurrentTabGroupSection();
            $cacheProviders = $this->cache->getRegisteredCacheProviders();

            if (isset($cacheProviders[$section]) === true) {
                $cacheProviders[$section]->getConfig()->setConfigParameters($newConfigParameters);
                $newConfigParameters = ['active_cache_provider' => $section];
            } elseif ($section === MainConfig::CACHE_PROVIDER_NONE) {
                $newConfigParameters = ['active_cache_provider' => $section];
            }
        }

        $this->updateFileProtectionFile($newConfigParameters);
        $this->wordpress->doAction('uam_update_options', $this->mainConfig);
        $this->setUpdateMessage(TXT_UAM_UPDATE_SETTINGS);
    }

    /**
     * Checks if the group is a post type.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isPostTypeGroup($key)
    {
        $postTypes = $this->getPostTypes();

        return isset($postTypes[$key]);
    }
}
