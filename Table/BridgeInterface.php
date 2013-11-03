<?php
namespace Dittto\TableBundle\Table;

/**
 * Class Bridge
 * An interface that defines what a bridge class requires
 *
 * @package Dittto\TableBundle\Table
 */
interface BridgeInterface
{
    /**
     * Stores the fields for the table. These need to be stored as they can be altered by the bridge interface
     *
     * @param array $fields The fields that make up the table. An example of this would be array('name' => 'asc', 'slug' => 'true',
     * 'is_active' => 'false'). The key is the name of the field to retrieve, and the value is either the default order
     * of the data, or whether a field can be ordered
     */
    public function setFields(array $fields);

    /**
     * Returns the fields for the table
     *
     * @return array
     */
    public function getFields();

    /**
     * These bridge assume that there is some object that takes the options supplied and builds a search and ordering
     * around it
     *
     * @return void
     */
    public function createQueryBuilder();

    /**
     * Sets any additional changes specified by an extended bridge
     *
     * @return void
     */
    public function setAdditionalChanges();

    /**
     * Changes the ordering of the query
     *
     * @param string $order The name of the field to order by
     * @param string $direction Either asc or desc
     * @return void
     */
    public function setOrderingChanges($order, $direction);

    /**
     * Adds the pagination to the query. This first calculates which page should be shown and then returns this
     * information to be stored
     *
     * @param int $page The page number requested by the browser
     * @param int $perPage The number of items per page to return by the browser
     * @return array An array containing the total number of items, the page number, the number of items per page, and
     * the total number of pages
     */
    public function setPaginationChanges($page, $perPage);

    /**
     * Gets the array of data from the bridge. Includes any last-minute changes
     *
     * @return array
     */
    public function getData();
}
