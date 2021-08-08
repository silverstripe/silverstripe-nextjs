<?php


namespace SilverStripe\NextJS\Model;

use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Headless\Extensions\DataObjectHashExtension;
use SilverStripe\Headless\Extensions\DataObjectNavigationExtension;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\View\ArrayData;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class StaticBuild extends DataObject implements PermissionProvider
{
    /**
     * @var array
     */
    private static $db = [
        'Limit' => 'Int',
    ];

    private static $has_many = [
        'Collectors' => StaticBuildCollector::class,
    ];

    private static $defaults = [
        'Limit' => '100',
    ];

    /**
     * @var string
     */
    private static $table_name = 'StaticBuild';

    /**
     * @var string
     */
    private static $singular_name = 'Static Build';

    /**
     * @var string
     */
    private static $plural_name = 'Static Builds';

    /**
     * @var array
     * @config
     */
    private static $excluded_classes = [];
    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = FieldList::create(TabSet::create('Root'));
        $fields->addFieldsToTab('Root.Main', [
            NumericField::create('Limit', 'Limit')
                ->setDescription(
                    _t(
                        __CLASS__ . 'LIMIT_DESCRIPTION',
                        'Limit the size of the static build. This will supersede the cumulative limit of
                        each collector.'
                    )
                ),
            GridField::create(
                'Collectors',
                'Collectors',
                $this->Collectors(),
                GridFieldConfig_RecordEditor::create()
                    ->removeComponentsByType(GridFieldAddNewButton::class)
                    ->addComponent((new GridFieldAddNewMultiClass()))
                    ->addComponent(new GridFieldOrderableRows())
            ),
            HiddenField::create('ID')
        ]);
        if ($this->Collectors()->exists()) {
            $fields->addFieldsToTab('Root.Preview', [
                $gridField = GridField::create(
                    'Preview',
                    'Your current static build',
                    $this->getCollection(),
                    GridFieldConfig_Base::create()
                )
            ]);

            $gridField->getConfig()->getComponentByType(GridFieldPaginator::class)
                ->setItemsPerPage(100);
            $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class)
                ->setDisplayFields([
                    'Title' => 'Page name',
                    'Link' => 'URL',
                    'Source' => 'Source',
                ]);
        }
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * @return FieldList
     */
    public function getCMSActions()
    {
        if (Permission::check('ADMIN') || Permission::check('EDIT_STATICBUILD')) {
            $actions = FieldList::create(
                FormAction::create(
                    'saveBuild',
                    _t('SilverStripe\\CMS\\Controllers\\CMSMain.SAVE', 'Save')
                )->addExtraClass('btn-primary font-icon-save')
            );
        } else {
            $actions = new FieldList();
        }

        $this->extend('updateCMSActions', $actions);

        return $actions;
    }



    /**
     * Setup a default StaticBuild record if none exists.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $config = DataObject::get_one(static::class);

        if (!$config) {
            static::createBuild();

            DB::alteration_message("Added static build", "created");
        }
    }

    /**
     * @return StaticBuild
     */
    public static function currentBuild(): StaticBuild
    {
        $curr = DataObject::get_one(static::class);

        return $curr ?: static::createBuild();
    }

    /**
     * @return StaticBuild
     * @throws ValidationException
     */
    public static function createBuild(): StaticBuild
    {
        $build = static::create();
        $build->write();

        return $build;
    }

    /**
     * @return ArrayList
     */
    public function getCollection(): ArrayList
    {
        $disallowed = static::config()->get('excluded_classes');

        $max = $this->Limit;
        $result = ArrayList::create();
        $usedHashes = [];
        /* @var StaticBuildCollector $collector */
        foreach ($this->Collectors() as $i => $collector) {
            $remaining = $max - $result->count();
            if ($remaining === 0) {
                break;
            }
            $set = $collector->collect();
            $limit = min($set->count(), $remaining);
            $limited = $set->limit($limit);
            /* @var DataObject&DataObjectHashExtension&DataObjectNavigationExtension $record */
            foreach ($limited as $record) {
                if (in_array($record->ClassName, $disallowed)) {
                    continue;
                }
                $hash = $record->getHashID();
                $exists = $usedHashes[$hash] ?? null;
                if ($exists) {
                    continue;
                }
                $data = ArrayData::create([
                    'ID' => $hash,
                    'CleanLink' => $record->getCleanLink(),
                    'Link' => $record->Link(),
                    'Title' => $record->getTitle(),
                    'Source' => sprintf('[%s]: %s', $i + 1, $collector->getTitle()),
                ]);
                $result->push($data);
                $usedHashes[$hash] = true;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function providePermissions(): array
    {
        return [
            'EDIT_STATICBUILD' => [
                'name' => _t(self::class . '.EDIT_PERMISSION', 'Manage static build'),
                'category' => _t(
                    'SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY',
                    'Roles and access permissions'
                ),
                'help' => _t(
                    self::class . '.EDIT_PERMISSION_HELP',
                    'Ability to define the static build for headless NextJS instance.'
                ),
                'sort' => 400
            ]
        ];
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
