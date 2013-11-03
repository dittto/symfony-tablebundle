<?php
namespace Dittto\TableBundle\Table;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Table
 * Creates a simple table
 *
 * @package Dittto\TableBundle\Table
 */
class Table
{
    /**
     * The repository to get the data for
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repository;

    /**
     * An array of fields for the table. An example of this would be array('name' => 'asc', 'slug' => 'true',
     * 'is_active' => 'false'). The key is the name of the field to retrieve, and the value is either the default order
     * of the data, or whether a field can be ordered
     * @var array
     */
    private $fields;

    /**
     * The alias of the table in the sql code
     * @var string
     */
    private $alias = 'a';

    /**
     * The name of the field to order by as requested by the browser. This will need to match one of the field
     * names or it will be ignored
     * @var string
     */
    private $order;

    /**
     * The direction to order the data by. This must either be asc or desc
     * @var string
     */
    private $direction;

    /**
     * The number of the page of data to show. If this is not between 1 and the max number of pages then it will be
     * ignored
     * @var int
     */
    private $page;

    /**
     * The number of rows to show per page. If this is less than 1 or more than 1000 then it'll be ignored
     * @var int
     */
    private $perPage;

    /**
     * The maximum number of a page to show
     * @var int
     */
    private $maxPage;

    /**
     * The number of total items in the table, across all pages
     * @var int
     */
    private $total;

    /**
     * The constructor
     *
     * @param EntityRepository $repository The main repository to get the data from
     * @param string|null $order The name of the field to order by. This must match a key in the $fields array
     * @param string $direction This must be either asc or desc
     * @param int $page The number of the page to retrieve
     * @param int $perPage The number of results per page to return
     */
    public function __construct(EntityRepository $repository, $order = null, $direction = 'asc', $page = 1, $perPage = 10)
    {
        // store vars
        $this->repository = $repository;
        $this->order = $order;
        $this->direction = $direction;
        $this->page = $page;
        $this->perPage = $perPage;
    }

    /**
     * Change the alias of the table in the sql code
     *
     * @param string $alias The alias of the table
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Stores the fields for the table
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
     * Renders the table using twig templates
     *
     * @param HTMLRenderer $renderer The object used to render the data to twig templates
     * @return string The rendered HTML
     * @throws \Exception Thrown when this class has not been extended
     */
    public function createTable(HTMLRenderer $renderer)
    {
        // stop this being called directly as there will be no fields
        if (get_called_class() == 'Dittto\TableBundle\Table\Table') {
            throw new \Exception('You need to extend Table class and call setFields in the constructor');
        }

        // get the data to use as a table
        $data = $this->getData();

        // force pagination into an array
        $pagination = array(
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'maxPages' => $this->maxPage);

        // get the name for the translation files
        $translationName = $this->getTranslationName();

        // render template and return
        return $renderer->render($translationName, $this->fields, $data, $pagination);
    }

    /**
     * Gets the data from the db, adding ordering and pagination to it
     *
     * @return mixed[] The data from the db
     * @throws \Exception Thrown when $this->fields is empty
     */
    protected function getData()
    {
        // init vars
        $data = null;

        // throw an error if the fields are missing
        if (!$this->fields) {
            throw new \Exception('You have not set the fields up. Call setFields in the extended controller');
        }

        // update the fields values with missing values
        $this->fields = $this->updateFieldsValues($this->fields, $this->alias);

        // get the query builder from the repository
        $queryBuilder = $this->repository->createQueryBuilder($this->alias);

        // apply any extra changes to the query builder
        $this->getExtraQueryChanges($queryBuilder);

        // apply the ordering from the query
        $this->getOrderingQuery($queryBuilder, $this->fields, $this->order, $this->direction);

        // apply the pagination
        $pagination = $this->getPaginationQuery($queryBuilder, $this->page, $this->perPage);
        list($this->total, $this->page, $this->perPage, $this->maxPage) = $pagination;

        // add the fields directly to the query builder so that child fields can be easily retrieved
        foreach ($this->fields as $field => $options) {
            if ($options['autoAdd'] === true) {
                $queryBuilder->addSelect($options['alias'].'.'.$field.' AS '.$options['fieldAlias']);
            }
        }

        // retrieve the data
        $data = $queryBuilder->getQuery()->getResult();

        return $data;
    }

    /**
     * Gets any extra query builder options from extended functions and adds them to the main search
     *
     * @param QueryBuilder $queryBuilder The query builder to update
     */
    protected function getExtraQueryChanges(QueryBuilder $queryBuilder)
    {
    }

    /**
     * Updates any fields that are missing default options
     *
     * @param array $fields The fields to update
     * @param string $alias The alias of the main table to add if it's missing
     * @return array The updated fields array
     */
    private function updateFieldsValues(array $fields, $alias)
    {
        // update any fields values that are missing
        foreach ($fields as $field => $options) {
            $fields[$field]['alias'] = isset($options['alias']) ? $options['alias'] : $alias;
            $fields[$field]['order'] = isset($options['order']) ? $options['order'] : false;
            $fields[$field]['name'] = isset($options['name']) ? $options['name'] : ucwords(strtolower($field));
            $fields[$field]['autoAdd'] = isset($options['autoAdd']) ? $options['autoAdd'] : true;
            $fields[$field]['fieldAlias'] = isset($options['fieldAlias']) && $options['fieldAlias'] ? $options['fieldAlias'] : 'field_'.$field;
        }

        return $fields;
    }

    /**
     * Changes the ordering of the query
     *
     * @param QueryBuilder $queryBuilder The query builder to update with an order
     * @param array $fields The fields to check against to make sure we can order by what's requested
     * @param string $order The name of the field to order by
     * @param string $direction Either asc or desc
     */
    private function getOrderingQuery(QueryBuilder $queryBuilder, array $fields, $order, $direction)
    {
        // init vars
        $directions = array('asc', 'desc');
        $orderField = '';
        $directionString = '';

        // make sure we are ordering by the correct field
        foreach ($fields as $field => $options) {
            if (isset($options['order'])) {
                if (($order === $field && $options['order'] !== false) || (!$order && in_array($options['order'], $directions))) {
                    $orderField = $options['alias'].'.'.$field;
                    $directionString = in_array($direction, $directions) ? $direction : ($options['order'] !== true ? $options['order'] : 'asc');
                }
            }
        }

        // add the order to the query builder
        if ($orderField !== '' && $directionString !== '') {
            $queryBuilder->orderBy($orderField, $directionString);
        }
    }

    /**
     * Adds the pagination to the query. This first calculates which page should be shown and then returns this
     * information to be stored
     *
     * @param QueryBuilder $queryBuilder The query builder to update with pagination
     * @param int $page The page number requested by the browser
     * @param int $perPage The number of items per page to return by the browser
     * @return array An array containing the total number of items, the page number, the number of items per page, and
     * the total number of pages
     */
    private function getPaginationQuery(QueryBuilder $queryBuilder, $page, $perPage)
    {
        // get the total number of items
        $total = $this->getNumberOfRows(clone $queryBuilder, $this->alias);

        // calculate the page values
        $perPage = $perPage > 1 && $perPage < 1000 ? $perPage : 1;
        $maxPage = ceil($total / $perPage);
        $page = $page > 1 && $page < $maxPage ? $page : 1;

        // update the query builder
        $queryBuilder->setFirstResult($perPage * ($page - 1));
        $queryBuilder->setMaxResults($perPage);

        return array($total, $page, $perPage, $maxPage);
    }

    /**
     * Gets the name of the current class to pass to the renderer to use as the name of the translation xliff file
     *
     * @return string
     */
    private function getTranslationName()
    {
        // get the lowercase version of the class name
        $classPath = explode('\\', get_called_class());
        $className = end($classPath);

        return strtolower($className);
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