<?php
namespace Dittto\TableBundle\Table;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class EntityBridge
 * An extension of the Table class that uses entities to retrieve the data for the table
 *
 * @package Dittto\TableBundle\Table
 */
class EntityBridge extends Bridge
{
    /**
     * The repository to get the data for
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repository;

    /**
     * The query builder for the data repository
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * The alias of the table in the sql code
     * @var string
     */
    private $alias = 'a';

    /**
     * The constructor
     *
     * @param EntityRepository $repository The main repository to get the data from
     */
    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns the alias set for this table
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Change the alias of the table in the sql code. After you've called this, run setFields
     * as it relies on the latest alias
     *
     * @param string $alias The alias of the table
     */
    public function setAlias($alias)
    {
        $this->alias = (string)$alias;
    }

    /**
     * Override the setFields method so we can update the fields
     *
     * {@inheritDoc}
     */
    public function setFields(array $fields)
    {
        // init vars
        $alias = $this->alias;

        // update any fields values that are missing
        foreach ($fields as $field => $options) {
            $fields[$field]['alias'] = isset($options['alias']) ? $options['alias'] : $alias;
            $fields[$field]['order'] = isset($options['order']) ? $options['order'] : false;
            $fields[$field]['name'] = isset($options['name']) ? $options['name'] : ucfirst(strtolower(str_replace('_', ' ', $field)));
            $fields[$field]['autoAdd'] = isset($options['autoAdd']) ? $options['autoAdd'] : true;
            $fields[$field]['fieldAlias'] = isset($options['fieldAlias']) && $options['fieldAlias'] ? $options['fieldAlias'] : 'field_'.$field;
        }

        // update using the parent method
        parent::setFields($fields);
    }

    /**
     * Creates the query builder for the repository
     *
     * @return boolean True if the query builder has been loaded correctly
     */
    public function createQueryBuilder()
    {
        // get the query builder from the repository
        $this->queryBuilder = $this->repository->createQueryBuilder($this->alias);

        return $this->queryBuilder ? true : false;
    }

    /**
     * Sets any additional changes that may need to occur on the entity repository
     *
     * @return null|void
     */
    public function setAdditionalChanges()
    {
        $this->setExtraQueryChanges($this->queryBuilder);
    }

    /**
     * Changes the ordering of a query
     *
     * @param string $order The name of the field to order by
     * @param string $direction Either asc or desc
     * @return array An array containing the order and direction
     */
    public function setOrderingChanges($order, $direction)
    {
        // init vars
        $directions = array('asc', 'desc');
        $orderField = '';
        $directionString = '';

        // make sure we are ordering by the correct field
        foreach ($this->getFields() as $field => $options) {
            if (isset($options['order'])) {
                // checks for either a matching field or a missing field name and the default field
                if (($order === $field && $options['order'] !== false) || (!$order && in_array($options['order'], $directions))) {
                    $orderField = $options['alias'].'.'.$field;
                    $directionString = in_array($direction, $directions) ? $direction : ($options['order'] !== true ? $options['order'] : 'asc');
                }
            }
        }

        // add the order to the query builder
        if ($orderField !== '' && $directionString !== '') {
            $this->queryBuilder->orderBy($orderField, $directionString);
        }

        return array('order' => $orderField, 'direction' => $directionString);
    }

    /**
     * Adds the pagination to the query. This first calculates which page should be shown and then returns this
     * information to be stored
     *
     * @param int $page The page number requested by the browser
     * @param int $perPage The number of items per page to return by the browser
     * @return array An array containing the offset and the limit
     */
    public function setPaginationChanges($page, $perPage)
    {
        // update the query builder
        $this->queryBuilder->setFirstResult($perPage * ($page - 1));
        $this->queryBuilder->setMaxResults($perPage);

        return array('offset' => $perPage * ($page - 1), 'limit' => $perPage);
    }

    /**
     * Gets the array of data from the bridge. Includes any last-minute changes
     *
     * @return array
     */
    public function getData()
    {
        // add the fields directly to the query builder so that child fields can be easily retrieved
        foreach ($this->getFields() as $field => $options) {
            if ($options['autoAdd'] === true) {
                $this->queryBuilder->addSelect($options['alias'].'.'.$field.' AS '.$options['fieldAlias']);
            }
        }

        // retrieve the data
        $data = $this->queryBuilder->getQuery()->getResult();

        return $data;
    }

    /**
     * Gets the total number of rows of data
     *
     * @return int
     */
    public function getCount()
    {
        return $this->getNumberOfRows(clone $this->queryBuilder, $this->alias);
    }

    /**
     * Sets any extra query builder options from extended functions and adds them to the main search
     *
     * @param QueryBuilder $queryBuilder The query builder to update
     */
    protected function setExtraQueryChanges(QueryBuilder $queryBuilder)
    {
    }

    /**
     * Get the number of rows using the query builder. Make sure you use a clone or you'll lose your settings
     *
     * @param QueryBuilder $queryBuilder A clone of the query builder to get the number of rows
     * @param string $alias The alias of the table. This is because we use the id of the main table to get the count
     * @return int
     */
    protected function getNumberOfRows(QueryBuilder $queryBuilder, $alias)
    {
        // get the number of rows
        $queryBuilder->select('count('.$alias.'.id)');
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count;
    }
}
