<?php


namespace SilversStripe\NextJS\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\Limitable;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\FieldList;

class SectionCollector extends StaticBuildCollector
{
    private static $has_one = [
        'Section' => SiteTree::class,
    ];

    /**
     * @var string
     */
    private static $table_name = 'SectionCollector';

    /**
     * @var string
     */
    private static $singular_name = 'Section Collector';

    /**
     * @var string
     */
    private static $plural_name = 'Section Collectors';

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', TreeDropdownField::create(
            'SectionID',
            'Section',
            SiteTree::class,
        ));
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }


    public function getDescription(): string
    {
        return _t(__CLASS__ . '.DESCRIPTION', 'Pages in section');
    }

    /**
     * @return Limitable
     */
    public function collect(): Limitable
    {
        return $this->Section()->Children()->limit($this->Limit);
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
