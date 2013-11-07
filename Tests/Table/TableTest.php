<?php
namespace Dittto\TableBundle\Tests\Table;

require_once 'Table/Table.php';

use Dittto\TableBundle\Table\Table;

/**
 * Class TableTest
 * Tests the table class
 *
 * @package Dittto\TableBundle\Tests\Table
 */
class TableClass extends \PHPUnit_Framework_TestCase
{

    /**
     * As the table class only has 1 public class, this tests everything
     *
     * @param int $count The total number of results
     * @param int $page The page number requested
     * @param int $perPage The number of results per page
     * @param int $actualPage The expected page number
     * @param int $actualPerPage The expected number of results per page
     * @param int $maxPages The total number of pages
     *
     * @dataProvider setTableData
     */
    public function testTable($count, $page, $perPage, $actualPage, $actualPerPage, $maxPages)
    {
        // init a bridge object
        $bridge = $this->getMock(
            'Dittto\TableBundle\Table\BridgeInterface',
            array('getFields', 'setFields', 'createQueryBuilder', 'setAdditionalChanges', 'setOrderingChanges', 'getData', 'getCount', 'setPaginationChanges'));
        $bridge->
            expects($this->any())->
            method('getFields')->
            will($this->returnValue(array()));
        $bridge->
            expects($this->any())->
            method('getData')->
            will($this->returnValue(array()));
        $bridge->
            expects($this->any())->
            method('getCount')->
            will($this->returnValue($count));

        // init an html renderer
        $renderer = $this->getMock('Dittto\TableBundle\Table\HTMLRenderer', array('render'));
        $renderer->
            expects($this->any())->
            method('render')->
            will($this->returnCallback(
                function($translationName, array $fields, array $data, array $pagination = array()) {
                    return array($translationName, $fields, $data, $pagination);
                }
            ));

        // init the table
        $table = new Table($bridge, null, 'asc', $page, $perPage);
        list($translationName, $fields, $data, $pagination) = $table->createTable($renderer);

        // assert that the table data is correct - don't check fields and data as they're not set
        $this->assertEquals('table', $translationName);
        $this->assertEquals($count, $pagination['total'], 'Count');
        $this->assertEquals($actualPage, $pagination['page'], 'Page');
        $this->assertEquals($actualPerPage, $pagination['perPage'], 'Per Page');
        $this->assertEquals($maxPages, $pagination['maxPages'], 'Max Pages');
    }

    /**
     * Sets the data for the testTable tests
     *
     * @return array
     */
    public function setTableData()
    {
        return array(
            array('count' => 52, 'page' => 2, 'perPage' => 15, 'actualPage' => 2, 'actualPerPage' => 15, 'maxPages' => 4),
            array('count' => 8, 'page' => 1, 'perPage' => 15, 'actualPage' => 1, 'actualPerPage' => 15, 'maxPages' => 1),
            array('count' => 2, 'page' => 2, 'perPage' => 10, 'actualPage' => 1, 'actualPerPage' => 10, 'maxPages' => 1),
            array('count' => 0, 'page' => 1, 'perPage' => 10, 'actualPage' => 1, 'actualPerPage' => 10, 'maxPages' => 0),
            array('count' => 100, 'page' => -1, 'perPage' => 10, 'actualPage' => 1, 'actualPerPage' => 10, 'maxPages' => 10),
            array('count' => 100, 'page' => 12, 'perPage' => 10, 'actualPage' => 1, 'actualPerPage' => 10, 'maxPages' => 10),
            array('count' => 100, 'page' => 1, 'perPage' => 10000, 'actualPage' => 1, 'actualPerPage' => 10, 'maxPages' => 10),
            array('count' => 100, 'page' => 1, 'perPage' => 0, 'actualPage' => 1, 'actualPerPage' => 10, 'maxPages' => 10),
        );
    }
}