<?php
namespace Dittto\TableBundle\Table;

/**
 * Class Table
 * Creates a simple table
 *
 * @package Dittto\TableBundle\Table
 */
class Table
{
    /**
     * The bridge used between the get data functions in this class and actually retrieving the data
     * @var BridgeInterface
     */
    private $bridge;

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
     * @param BridgeInterface $bridge The bridge object required to retrieve the data for the table
     * @param string|null $order The name of the field to order by. This must match a key in the $fields array
     * @param string $direction This must be either asc or desc
     * @param int $page The number of the page to retrieve
     * @param int $perPage The number of results per page to return
     */
    public function __construct(BridgeInterface $bridge, $order = null, $direction = 'asc', $page = 1, $perPage = 10)
    {
        // store vars
        $this->bridge = $bridge;
        $this->order = $order;
        $this->direction = $direction;
        $this->page = $page;
        $this->perPage = $perPage;
    }

    /**
     * Renders the table using twig templates
     *
     * @param HTMLRenderer $renderer The object used to render the data to twig templates
     * @return string The rendered HTML
     */
    public function createTable(HTMLRenderer $renderer)
    {
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
        return $renderer->render($translationName, $this->bridge->getFields(), $data, $pagination);
    }

    /**
     * Gets the data from the db, adding ordering and pagination to it
     *
     * @return array The data from the db
     */
    protected function getData()
    {
        // init vars
        $data = null;

        // get any extra changes
        $this->bridge->createQueryBuilder();
        $this->bridge->setAdditionalChanges();

        // apply the ordering from the query
        $this->bridge->setOrderingChanges($this->order, $this->direction);

        // apply the pagination
        $pagination = $this->calculatePagination($this->bridge, $this->page, $this->perPage);
        list($this->total, $this->page, $this->perPage, $this->maxPage) = $pagination;

        // retrieve the data
        $data = $this->bridge->getData();

        return $data;
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
     * This first calculates which page should be shown and then returns this information to be stored
     *
     * @param BridgeInterface $bridge The bridge object used to get the number of rows and update with pagination
     * changes
     * @param int $page The page number requested by the browser
     * @param int $perPage The number of items per page to return by the browser
     * @return array An array containing the total number of items, the page number, the number of items per page, and
     * the total number of pages
     */
    private function calculatePagination(BridgeInterface $bridge, $page, $perPage)
    {
        // get the total number of rows
        $total = $bridge->getCount();

        // calculate the page values
        $perPage = $perPage > 1 && $perPage < 1000 ? $perPage : 10;
        $maxPage = ceil($total / $perPage);
        $page = $page > 1 && $page < $maxPage ? $page : 1;

        // update the query builder
        $bridge->setPaginationChanges($page, $perPage);

        return array($total, $page, $perPage, $maxPage);
    }
}
