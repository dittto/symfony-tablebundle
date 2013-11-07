<?php
namespace Dittto\TableBundle\Tests\Table;

require_once 'Table/BridgeInterface.php';
require_once 'Table/Bridge.php';

/**
 * Class BridgeTest
 * Test the abstract bridge class
 *
 * @package Dittto\TableBundle\Tests\Table
 */
class BridgeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test setting and getting the fields works
     */
    public function testFields()
    {
        // init fields to test
        $fields = array(
            'name' => array('order' => 'asc'),
            'slug' => array('order' => 'true'),
            'is_active' => array('order' => 'false')
        );

        // set the fields and retrieve them and test the data hasn't changed
        $bridge = $this->getMockForAbstractClass('Dittto\TableBundle\Table\Bridge');
        $fieldData = $bridge->getFields($bridge->setFields($fields));
        $this->assertEquals($fields, $fieldData);
    }
}