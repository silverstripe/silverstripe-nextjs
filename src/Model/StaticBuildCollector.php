<?php


namespace SilverStripe\NextJS\Model;

use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Limitable;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use BadMethodCallException;

class StaticBuildCollector extends DataObject
{
    /**
     * @var array
     */
    private static $db = [
        'Limit' => 'Int',
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'StaticBuild' => StaticBuild::class,
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Description' => 'Type',
        'Limit' => 'Limit',
    ];

    private static $defaults = [
        'Limit' => '100',
    ];

    /**
     * @var string
     * @config
     */
    private static $hide_ancestor = __CLASS__;

    /**
     * @var string
     */
    private static $table_name = 'StaticBuildCollector';

    /**
     * @var string
     */
    private static $singular_name = 'Collector';

    /**
     * @var string
     */
    private static $plural_name = 'Collectors';

    /**
     * @var string
     */
    private static $default_sort = 'Sort ASC';

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = FieldList::create(TabSet::create('Root'));
        $fields->addFieldsToTab('Root.Main', [
            NumericField::create('Limit', _t(__CLASS__ . 'LIMIT', 'Limit'))
                ->setDescription(
                    _t(
                        __CLASS__ . 'LIMIT_DESCRIPTION',
                        'Limit the number of records (note: this will be superseded by the limit of {count} on the
                        aggregate collection)',
                        ['count' => $this->StaticBuild()->Limit]
                    )
                )
        ]);
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function getTitle(): string
    {
        return $this->getDescription();
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        if ($this->ClassName === static::class) {
            return static::class;
        }

        return DataObject::singleton($this->ClassName)->getDescription();
    }

    public function collect(): Limitable
    {
        return ArrayList::create();
//        throw new BadMethodCallException(sprintf(
//            'Model %s must define a collect() method',
//            static::class
//        ));
    }

    public function validate()
    {
        $result = parent::validate();
        $max = $this->StaticBuild()->Limit;
        if ($this->Limit > $max) {
            $result->addFieldError('Limit', _t(
                __CLASS__ . '.LIMIT_ERROR',
                'You have chosen a limit that is higher than the total build limit of {count}.',
                ['count' => $max]
            ));
        }

        return $result;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->isInDB()) {
            $this->Sort = static::get()->max('Sort') + 1;
        }
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
