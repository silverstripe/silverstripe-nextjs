<?php


namespace SilversStripe\NextJS\Model;

use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;

class LinkIndex extends DataObject
{
    /**
     * @var array
     */
    private static $db = [
        'Link' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Object' => DataObject::class,
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Link' => 'Link',
        'Object.Title' => 'Title',
        'Object.ClassName' => 'Content class',
    ];

    /**
     * @var string
     */
    private static $table_name = 'LinkIndex';

    /**
     * @var string
     */
    private static $singular_name = 'LinkIndex';

    /**
     * @var string
     */
    private static $plural_name = 'LinkIndexes';

    /**
     * @var string
     */
    private static $default_sort = 'ID ASC';

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = FieldList::create(TabSet::create('Root'));
        $fields->addFieldsToTab('Root.Main', [
            ReadonlyField::create('Link', 'Link'),
            ReadonlyField::create('ObjectClass', 'Object class'),
            ReadonlyField::create('ObjectID', 'Object ID'),
        ]);
        $this->extend('updateCMSFields', $fields);

        return $fields;
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
        return false;
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
