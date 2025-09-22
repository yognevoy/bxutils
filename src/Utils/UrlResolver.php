<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use CCrmOwnerType;

/*
 * Example of usage:
 *
 * if (UrlResolver::getInstance()->isCompanyDetail()) {
 *     Extension::load('vendor.extname');
 * }
*/
class UrlResolver
{
    /**
     * Suffix for the URL of the entity list
     */
    protected const LIST_SUFFIX = 'list/';

    /**
     * Suffix for the URL of the entity detail
     */
    protected const DETAIL_SUFFIX = 'details/\d+/';

    /**
     * Basic URL templates for CRM entities
     */
    protected const CRM_SECTIONS = [
        CCrmOwnerType::Lead => '^/crm/lead/',
        CCrmOwnerType::Deal => '^/crm/deal/',
        CCrmOwnerType::Contact => '^/crm/contact/',
        CCrmOwnerType::Company => '^/crm/company/',
    ];

    protected static ?UrlResolver $instance = null;

    /**
     * Basic URL templates
     *
     * @var array
     */
    protected array $sections = [];

    /**
     * Cache of prepared regular expressions
     *
     * @var array
     */
    protected array $preparedSections = [];

    public static function getInstance(): UrlResolver
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function __construct()
    {
        Loader::includeModule('crm');
        Loader::includeModule('intranet');

        $this->sections = $this->loadSections();
    }

    protected function __clone()
    {
    }

    /**
     * Returns true if the URL is a lead list page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isLeadList(?string $url = null): bool
    {
        return $this->isEntityList(CCrmOwnerType::Lead, $url);
    }

    /**
     * Returns true if the URL is a deal list page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isDealList(?string $url = null): bool
    {
        $url = $url ?: static::getCurrentUrl();
        if (empty($url)) {
            return false;
        }

        return static::matchOne([
            '#^/crm/deal/list/#',
            '#^/crm/deal/category/#',
        ], $url);
    }

    /**
     * Returns true if the URL is a contact list page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isContactList(?string $url = null): bool
    {
        return $this->isEntityList(CCrmOwnerType::Contact, $url);
    }

    /**
     * Returns true if the URL is a company list page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isCompanyList(?string $url = null): bool
    {
        return $this->isEntityList(CCrmOwnerType::Company, $url);
    }

    /**
     * Returns true if the URL is a dynamic type list page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isDynamicTypeList(int $entityTypeId, ?string $url = null): bool
    {
        if (!CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)) {
            return false;
        }
        return $this->isEntityList($entityTypeId, $url);
    }

    /**
     * Returns true if the URL is a lead detail page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isLeadDetail(?string $url = null): bool
    {
        return $this->isEntityDetail(CCrmOwnerType::Lead, $url);
    }

    /**
     * Returns true if the URL is a deal detail page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isDealDetail(?string $url = null): bool
    {
        return $this->isEntityDetail(CCrmOwnerType::Deal, $url);
    }

    /**
     * Returns true if the URL is a contact detail page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isContactDetail(?string $url = null): bool
    {
        return $this->isEntityDetail(CCrmOwnerType::Contact, $url);
    }

    /**
     * Returns true if the URL is a company detail page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isCompanyDetail(?string $url = null): bool
    {
        return $this->isEntityDetail(CCrmOwnerType::Company, $url);
    }

    /**
     * Returns true if the URL is a dynamic type detail page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isDynamicTypeDetail(int $entityTypeId, ?string $url = null): bool
    {
        if (!CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)) {
            return false;
        }
        return $this->isEntityDetail($entityTypeId, $url);
    }

    /**
     * Returns true if the URL is a tasks list page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isTasksList(?string $url = null): bool
    {
        $url = $url ?: static::getCurrentUrl();
        if (empty($url)) {
            return false;
        }

        return static::match(
            '#^/company/personal/user/\d+/tasks/?/$#',
            $url
        );
    }

    /**
     * Returns true if the URL is a task view page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isTaskView(?string $url = null): bool
    {
        $url = $url ?: static::getCurrentUrl();
        if (empty($url)) {
            return false;
        }

        return static::match(
            '#^/company/personal/user/\d+/tasks/task/view/\d+/?/#',
            $url
        );
    }

    /**
     * Returns true if the URL is a task edit page.
     *
     * @param string|null $url
     * @return bool
     */
    public function isTaskEdit(?string $url = null): bool
    {
        $url = $url ?: static::getCurrentUrl();
        if (empty($url)) {
            return false;
        }

        return static::match(
            '#^/company/personal/user/\d+/tasks/task/edit/\d+/?/#',
            $url
        );
    }

    /**
     * Returns true if the URL is a specified entity list page.
     *
     * @param int $entityTypeId Entity type ID
     * @param string|null $url
     * @return bool
     */
    protected function isEntityList(int $entityTypeId, ?string $url = null): bool
    {
        $url = $url ?: static::getCurrentUrl();
        if (empty($url)) {
            return false;
        }

        $sections = $this->getListSections();
        if (!isset($sections[$entityTypeId])) {
            return false;
        }

        $pattern = $sections[$entityTypeId];
        return static::matchOne($pattern, $url);
    }

    /**
     * Returns true if the URL is a specified entity detail page.
     *
     * @param int $entityTypeId Entity type ID
     * @param string|null $url
     * @return bool
     */
    protected function isEntityDetail(int $entityTypeId, ?string $url = null): bool
    {
        $url = $url ?: static::getCurrentUrl();
        if (empty($url)) {
            return false;
        }

        $sections = $this->getDetailSections();
        if (!isset($sections[$entityTypeId])) {
            return false;
        }

        $pattern = $sections[$entityTypeId];
        return static::matchOne($pattern, $url);
    }

    /**
     * Returns prepared regular expressions for the list pages.
     *
     * @return array
     */
    protected function getListSections(): array
    {
        return $this->getSections(static::LIST_SUFFIX);
    }

    /**
     * Returns prepared regular expressions for the detail pages.
     *
     * @return array
     */
    protected function getDetailSections(): array
    {
        return $this->getSections(static::DETAIL_SUFFIX);
    }

    /**
     * Prepares regular expressions with the specified suffix.
     *
     * @param string $suffix URL suffix to add to basic templates
     * @return array An array of prepared regular expressions in the format:
     *  [
     *     entityTypeId => [
     *       '#^/crm/lead/list/#',
     *       '#^/custom/path/to/lead/list/#'
     *     ]
     *  ]
     *
     */
    protected function getSections(string $suffix = ''): array
    {
        if (isset($this->preparedSections[$suffix])) {
            return $this->preparedSections[$suffix];
        }

        $result = [];

        foreach ($this->sections as $entityTypeId => $patterns) {
            $tmp = [];
            foreach ($patterns as $pattern) {
                $tmp[] = '#' . $pattern . $suffix . '#';
            }
            $result[$entityTypeId] = $tmp;
        }

        $this->preparedSections[$suffix] = $result;
        return $result;
    }

    /**
     * Loads all URL templates for all types of entities.
     *
     * @return array Array of basic URL templates
     */
    protected function loadSections(): array
    {
        $sections = [];

        $sectionsArrays = [
            $this->getDefaultSections(),
            $this->getDynamicTypeSections(),
            $this->getCustomSections()
        ];

        foreach ($sectionsArrays as $patterns) {
            foreach ($patterns as $id => $pages) {
                if (!isset($sections[$id])) {
                    $sections[$id] = [];
                }

                if (!is_array($pages)) {
                    $pages = [$pages];
                }

                foreach ($pages as $page) {
                    if (!in_array($page, $sections[$id], true)) {
                        $sections[$id][] = $page;
                    }
                }
            }
        }

        return $sections;
    }

    /**
     * Returns basic URL templates for CRM entities.
     *
     * @return array
     */
    protected function getDefaultSections(): array
    {
        return static::CRM_SECTIONS;
    }

    /**
     * Returns basic URL templates for dynamic types.
     *
     * @return array
     */
    protected function getDynamicTypeSections(): array
    {
        $sections = [];

        $dynamicTypes = TypeTable::getList([
            'select' => [
                'ENTITY_TYPE_ID',
                'CODE'
            ]
        ]);

        while ($type = $dynamicTypes->fetch()) {
            $typeId = $type['ENTITY_TYPE_ID'];
            $sections[$typeId] = "^/crm/type/$typeId/";
        }

        return $sections;
    }

    /**
     * Returns basic URL templates for custom sections.
     *
     * @return array
     */
    protected function getCustomSections(): array
    {
        $sections = [];

        $customSections = IntranetManager::getCustomSections();
        foreach ($customSections as $section) {
            $pages = $section->getPages();
            foreach ($pages as $page) {
                $entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($page->getSettings());
                if ($entityTypeId) {
                    $pageUrl = IntranetManager::getUrlForCustomSectionPage(
                        $section->getCode(),
                        $page->getCode()
                    )->getPath();
                    $sections[$entityTypeId] = "^" . $pageUrl . "type/${entityTypeId}/";
                }
            }
        }

        return $sections;
    }

    /**
     * Returns the current URL.
     *
     * @return string|null
     */
    protected static function getCurrentUrl(): ?string
    {
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        if ($request instanceof HttpRequest) {
            $url = $request->getRequestUri();
            if ($url) {
                return $url;
            }
        }
        return null;
    }

    /**
     * Returns true if the URL matches at least one template from the array.
     *
     * @param array $patterns Array of regular expressions
     * @param string $url
     * @return bool
     */
    protected static function matchOne(array $patterns, string $url): bool
    {
        foreach ($patterns as $pattern) {
            if (static::match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the URL matches the specified regular expression.
     *
     * @param string $pattern
     * @param string $url
     * @return bool
     */
    protected static function match(string $pattern, string $url): bool
    {
        return (bool)preg_match($pattern, $url);
    }
}
