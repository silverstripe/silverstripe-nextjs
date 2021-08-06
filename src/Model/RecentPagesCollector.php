<?php


namespace SilversStripe\NextJS\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\Limitable;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\FieldList;

class RecentPagesCollector extends StaticBuildCollector
{
    /**
     * @var string
     */
    private static $table_name = 'RecentPagesCollector';

    /**
     * @var string
     */
    private static $singular_name = 'Recent Pages Collector';

    /**
     * @var string
     */
    private static $plural_name = 'Recent Pages Collectors';

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function getDescription(): string
    {
        return _t(__CLASS__ . '.DESCRIPTION', 'Most recently edited pages');
    }

    /**
     * @return Limitable
     */
    public function collect(): Limitable
    {
        return SiteTree::get()->sort('LastEdited', 'DESC')
            ->limit($this->Limit);
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CMS_ACCESS_CMSMain');
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CMS_ACCESS_CMSMain');
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CMS_ACCESS_CMSMain');
    }

    /**
     * @param null
     * @param array
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

}
