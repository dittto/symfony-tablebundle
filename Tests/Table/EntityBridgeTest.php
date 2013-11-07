<?php
namespace Dittto\TableBundle\Tests\Table;

require_once 'Table/BridgeInterface.php';
require_once 'Table/Bridge.php';
require_once 'Table/EntityBridge.php';

use Dittto\TableBundle\Table\EntityBridge;

/**
 * Class EntityBridgeTest
 * Tests the entity bridge class
 *
 * @package Dittto\TableBundle\Tests\Table
 */
class EntityBridgeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The entity bridge to be mocked by setup
     * @var EntityBridge
     */
    private $entityBridge;

    /**
     * Sets up the entity bridge object
     */
    public function setup()
    {
        // setup the fake data
        $data = array('test');
        $count = 1;

        // build a query object to return any array data
        $queryObject = $this->getMock('\FakeObject', array('getResult', 'getSingleScalarResult'));
        $queryObject->
            expects($this->any())->
            method('getResult')->
            will($this->returnValue($data));
        $queryObject->
            expects($this->any())->
            method('getSingleScalarResult')->
            will($this->returnValue($count));

        // create a mock query manager
        $queryBuilder = $this->getMock(
            'Doctrine\ORM\QueryBuilder',
            array('select', 'orderBy', 'setFirstResult', 'setMaxResults', 'addSelect', 'getQuery'));
        $queryBuilder->
            expects($this->any())->
            method('getQuery')->
            will($this->returnValue($queryObject));


        // create a mock entity manager - ignores createQueryBuilder's alias arg
        $entityManager = $this->getMock(
            'Doctrine\ORM\EntityRepository',
            array('createQueryBuilder'));
        $entityManager->
            expects($this->any())->
            method('createQueryBuilder')->
            will($this->returnValue($queryBuilder));

        // create the entity bridge
        $this->entityBridge = new EntityBridge($entityManager);
    }

    /**
     * Tests the setting and getting of the alias string
     */
    public function testAlias()
    {
        // test setting and retrieving the alias
        $alias = 'a';
        $this->entityBridge->setAlias($alias);
        $this->assertEquals($alias, $this->entityBridge->getAlias());
    }

    /**
     * Tests the create query builder
     */
    public function testQueryBuilder()
    {
        $this->assertEquals(true, $this->entityBridge->createQueryBuilder());
    }

    /**
     * Tests setting the fields to see if the fields update with extra values
     */
    public function testSetFields()
    {
        // init fields to test
        $fields = array(
            'name' => array('order' => 'asc'),
            'slug' => array('order' => 'true'),
            'is_active' => array('order' => 'false')
        );

        // test setting the fields
        $this->entityBridge->createQueryBuilder();
        $this->entityBridge->setFields($fields);

        // test to see if the fields match the defaults
        $testFields = array(
            'name' => array(
                'order' => 'asc',
                'alias' => 'a',
                'name' => 'Name',
                'autoAdd' => true,
                'fieldAlias' => 'field_name'),
            'slug' => array(
                'order' => 'true',
                'alias' => 'a',
                'name' => 'Slug',
                'autoAdd' => true,
                'fieldAlias' => 'field_slug'),
            'is_active' => array(
                'order' => 'false',
                'alias' => 'a',
                'name' => 'Is active',
                'autoAdd' => true,
                'fieldAlias' => 'field_is_active')
        );
        $this->assertEquals($testFields, $this->entityBridge->getFields());
    }

    /**
     * Tests the ordering of the changes
     *
     * @param string $field The name of the field to test
     * @param string $direction The direction to order in, asc or desc
     * @param string $expectedField The name the field should be
     * @param string $expectedDirection The name of the direction
     *
     * @dataProvider getOrderings
     */
    public function testOrderingChanges($field, $direction, $expectedField, $expectedDirection)
    {
        // set the entity as this is used in the field and create the query builder
        $this->entityBridge->setAlias('a');
        $this->entityBridge->createQueryBuilder();

        // init fields to test
        $fields = array(
            'name' => array('order' => 'asc'),
            'slug' => array('order' => 'true'),
            'is_active' => array('order' => 'false')
        );
        $this->entityBridge->setFields($fields);

        // get the ordering fields
        $ordering = $this->entityBridge->setOrderingChanges($field, $direction);
        $this->assertEquals($expectedField, $ordering['order']);
        $this->assertEquals($expectedDirection, $ordering['direction']);
    }

    /**
     * Specifies the data for testOrderingChanges
     *
     * @return array
     */
    public function getOrderings()
    {
        return array(
            array('field' => 'name', 'direction' => 'desc', 'expectedField' => 'a.name', 'expectedDirection' => 'desc'),
            array('field' => 'is_active', 'direction' => 'asc', 'expectedField' => 'a.is_active', 'expectedDirection' => 'asc'),
            array('field' => '', 'direction' => '', 'expectedField' => 'a.name', 'expectedDirection' => 'asc'),
            array('field' => 'Is_Active', 'direction' => 'DESC', 'expectedField' => '', 'expectedDirection' => '') // check for errors
        );
    }

    /**
     * Test the setPaginationChanges method. This is run after pagination has been validated
     *
     * @param $page The page number
     * @param $perPage The number of items to show on a page
     * @param $offset The offset in a list of data
     * @param $limit The max number of items to show
     *
     * @dataProvider getPaginations()
     */
    public function testPaginationChanges($page, $perPage, $offset, $limit)
    {
        // store the pagination changes
        $this->entityBridge->createQueryBuilder();
        $results = $this->entityBridge->setPaginationChanges($page, $perPage);
        $this->assertEquals($offset, $results['offset']);
        $this->assertEquals($limit, $results['limit']);
    }

    /**
     * Get the pagination data for testPaginationChanges
     */
    public function getPaginations()
    {
        return array(
            array('page' => '1', 'perPage' => '10', 'offset' => '0', 'limit' => '10'),
            array('page' => '5', 'perPage' => '20', 'offset' => '80', 'limit' => '20')
        );
    }

    /**
     * Test that the bridge retrieves the data stored
     */
    public function testData()
    {
        $this->entityBridge->createQueryBuilder();
        $data = $this->entityBridge->getData();
        $this->assertEquals(array('test'), $data);
    }

    /**
     * Test that the bridge counts the data correctly
     */
    public function testCount()
    {
        $this->entityBridge->createQueryBuilder();
        $count = $this->entityBridge->getCount();
        $this->assertEquals(1, $count);
    }
}