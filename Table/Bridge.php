<?php
namespace Dittto\TableBundle\Table;

/**
 * Class Bridge
 * A base class for all bridges handling the basic methods
 *
 * @package Dittto\TableBundle\Table
 */
abstract class Bridge implements BridgeInterface
{
    /**
     * An array of fields for the table. An example of this would be array('name' => 'asc', 'slug' => 'true',
     * 'is_active' => 'false'). The key is the name of the field to retrieve, and the value is either the default order
     * of the data, or whether a field can be ordered
     * @var array
     */
    private $fields = array();

    /**
     * Stores the fields for the table. These need to be stored as they can be altered by the bridge interface
     *
     * @param array $fields The fields that make up the table. An example of this would be array('name' => 'asc', 'slug' => 'true',
     * 'is_active' => 'false'). The key is the name of the field to retrieve, and the value is either the default order
     * of the data, or whether a field can be ordered
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Returns the fields for the table
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}