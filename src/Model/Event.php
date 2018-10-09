<?php

namespace SilverCart\FacebookPlugins\Model;

use SilverCart\Dev\Tools;
use SilverCart\FacebookPlugins\Model\Pages\EventsPage;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\PaginatedList;

/**
 * Represents a Facebook event.
 * 
 * @package SilverCart
 * @subpackage FacebookPlugins\Model
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 07.10.2018
 * @copyright 2018 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class Event extends DataObject
{
    use Facebookable;
    use \SilverCart\ORM\ExtensibleDataObject;
    
    /**
     * DB table name.
     *
     * @var string
     */
    private static $table_name = 'FacebookEvent';
    /**
     * DB attributes.
     *
     * @var array
     */
    private static $db = [
        'FacebookID'  => 'Varchar(32)',
        'Name'        => 'Varchar(64)',
        'Place'       => 'Varchar(142)',
        'Description' => DBText::class,
        'StartTime'   => DBDatetime::class,
        'EndTime'     => DBDatetime::class,
    ];
    /**
     * Has many relations.
     *
     * @var array
     */
    private static $has_one = [
        'Cover' => Image::class,
    ];
    /**
     * Has many relations.
     *
     * @var array
     */
    private static $has_many = [
        'EventTimes' => EventTime::class,
    ];
    /**
     * Default sort.
     *
     * @var string
     */
    private static $default_sort = 'StartTime ASC';
    
    /**
     * Returns the plural name.
     * 
     * @return string
     */
    public function plural_name()
    {
        return Tools::plural_name_for($this);
    }
    
    /**
     * Returns the singular name.
     * 
     * @return string
     */
    public function singular_name()
    {
        return Tools::singular_name_for($this);
    }
    
    /**
     * Returns the field labels.
     * 
     * @param bool $includerelations Include relations?
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 07.10.2018
     */
    public function fieldLabels($includerelations = true)
    {
        $this->beforeUpdateFieldLabels(function(&$labels) {
            $labels = array_merge(
                    $labels,
                    Tools::field_labels_for(self::class),
                    [
                        'OpenInFacebook' => _t(self::class . ".OpenInFacebook", "Open in Facebook"),
                    ]
            );
        });
        return parent::fieldLabels($includerelations);
    }
    
    /**
     * Returns the Facebook link to this Event.
     * 
     * @return string
     */
    public function FacebookLink()
    {
        return "https://www.facebook.com/events/{$this->FacebookID}";
    }
    
    /**
     * Returns the relative link to this Event.
     * 
     * @return string
     */
    public function Link()
    {
        $link = '';
        $page = EventsPage::get()->first();
        if ($page instanceof EventsPage) {
            $link = $page->Link("event/{$this->ID}");
        }
        return $link;
    }
    
    /**
     * Returns the absolute link to this Event.
     * 
     * @return string
     */
    public function AbsoluteLink()
    {
        return Director::absoluteURL($this->Link());
    }
    
    /**
     * Returns all upcoming events.
     * 
     * @return \SilverStripe\ORM\DataList
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 07.10.2018
     */
    public static function getUpcoming()
    {
        return self::get()
                ->leftJoin('FacebookEventTime', 'FacebookEvent.ID = FET.EventID', 'FET')
                ->where("FET.StartTime > NOW()")
                ->sort("StartTime", "ASC");
    }
    
    /**
     * Returns all upcoming event times related to this event.
     * 
     * @return \SilverStripe\ORM\DataList
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 08.10.2018
     */
    public function UpcomingTimes()
    {
        return EventTime::getUpcoming()->filter('EventID', $this->ID);
    }
    
    /**
     * Returns the upcoming event times related to this event as a paginated list.
     * 
     * @return PaginatedList
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 08.10.2018
     */
    public function PaginatedUpcomingTimes()
    {
        $list = PaginatedList::create($this->UpcomingTimes(), Controller::curr()->getRequest());
        $list->setPageLength(EventsPage::config()->get('event_time_page_length'));
        return $list;
    }
}