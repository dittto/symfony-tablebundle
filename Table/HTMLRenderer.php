<?php
namespace Dittto\TableBundle\Table;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

/**
 * Class Renderer
 * An object to handle the table rendering to html using twig. To call this from a controller, do the following:
 * $renderer = new HTMLRenderer($this->container->get('templating'));
 *
 * @package Dittto\TableBundle\Table
 */
class HTMLRenderer
{
    /**
     * The twig templating engine used to create the html
     * @var EngineInterface
     */
    private $engine;

    /**
     * The default template path for the whole table template
     * @var string
     */
    private $tableTemplate = 'DitttoTableBundle:Table:table.html.twig';

    /**
     * The default template path for the header of the table template
     * @var string
     */
    private $headerTemplate = 'DitttoTableBundle:Table:header.html.twig';

    /**
     * The default template path for the body of the table template
     * @var string
     */
    private $bodyTemplate = 'DitttoTableBundle:Table:body.html.twig';

    /**
     * The default template path for the row of the table template
     * @var string
     */
    private $rowTemplate = 'DitttoTableBundle:Table:row.html.twig';

    /**
     * The default template path for the row actions template
     * @var string
     */
    private $rowActionsTemplate = 'DitttoTableBundle:Table:rowActions.html.twig';

    /**
     * The default template path for the footer of the table template
     * @var string
     */
    private $footerTemplate = 'DitttoTableBundle:Table:footer.html.twig';

    /**
     * The default template path for the pagination template
     * @var string
     */
    private $paginationTemplate = 'DitttoTableBundle:Table:pagination.html.twig';

    /**
     * A list of all routes used in the templates. If using the default templates, this route array must contain
     * routes for new, edit, and delete
     * @var array
     */
    private $routes = array();

    /**
     * A list of extra options to pass to the templates
     * @var array
     */
    private $options = array();

    /**
     * The list of ordering options, containing the order field, direction, page, and per page options
     * @var array
     */
    private $ordering = array();

    /**
     * Class constructor
     *
     * @param EngineInterface $engine The engine engine used to create the html
     */
    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Stores the path to the whole table template
     *
     * @param string $template The template used for the whole table
     * @return $this
     */
    public function setTableTemplate($template)
    {
        $this->tableTemplate = $template;

        return $this;
    }

    /**
     * Stores the path to the header of the table template
     *
     * @param string $template The template used for the header
     * @return $this
     */
    public function setHeaderTemplate($template)
    {
        $this->headerTemplate = $template;

        return $this;
    }

    /**
     * Stores the path to the body of the table template
     *
     * @param string $template The template used for the body
     * @return $this
     */
    public function setBodyTemplate($template)
    {
        $this->bodyTemplate = $template;

        return $this;
    }

    /**
     * Stores the path to the row template
     *
     * @param string $template The template used for each row
     * @return $this
     */
    public function setRowTemplate($template)
    {
        $this->rowTemplate = $template;

        return $this;
    }

    /**
     * Stores the path to the row actions template
     *
     * @param string $template The template used for each row actions section
     * @return $this
     */
    public function setRowActionsTemplate($template)
    {
        $this->rowActionsTemplate = $template;

        return $this;
    }

    /**
     * Stores the path to the footer of the table template
     *
     * @param string $template The template used for the footer
     * @return $this
     */
    public function setFooterTemplate($template)
    {
        $this->footerTemplate = $template;

        return $this;
    }

    /**
     * Stores the path to the pagination template
     *
     * @param string $template The template used for pagination
     * @return $this
     */
    public function setPaginationTemplate($template)
    {
        $this->paginationTemplate = $template;

        return $this;
    }

    /**
     * Stores the routes used by the template. The default template needs at least 3 routes entitled new, edit, and
     * delete. The routes need to be existing routes defined in Symfony. The edit and delete routes should take
     * an id parameter
     *
     * @param array $routes The routes to store
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Stores the options for all view templates
     *
     * @param array $options The options to store
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Stores the ordering options
     *
     * @param string $order The order field to order the data by
     * @param string $direction Either asc or desc
     */
    public function setOrdering($order, $direction)
    {
        $this->ordering['order'] = $order;
        $this->ordering['direction'] = $direction;
    }

    /**
     * Handles the rendering of the table as a whole, rendering each individual section and returning the completed
     * html
     *
     * @param string $translationName The name of the file to use for translation of the field names
     * @param array $fields The fields as specified in Table->setFields()
     * @param array $data The data found by the table class. This contains either an array or model object of the
     * data as well as the named fields
     * @param array $pagination An array containing the pagination values: total, page, perPage, maxPage
     * @return string
     */
    public function render($translationName, array $fields, array $data, array $pagination = array())
    {
        // init column count
        $numRows = sizeof($fields) + ($this->hasRowActions() ? 1 : 0);

        // loop through and create each row
        $rowCode = '';
        $count = 0;
        foreach ($data as $row) {
            // create the row actions if requested
            $rowActionsCode = '';
            if ($this->hasRowActions($row)) {
                $rowActionsCode = $this->engine->render($this->rowActionsTemplate, array(
                    'data' => $row,
                    'routes' => $this->routes,
                    'translationName' => $translationName,
                    'pagination' => $pagination,
                    'options' => $this->options,
                    'ordering' => $this->ordering));
            }

            // create the row
            $rowCode .= $this->engine->render($this->rowTemplate, array(
                'fields' => $fields,
                'data' => $row,
                'count' => $count++,
                'rowActions' => $rowActionsCode,
                'routes' => $this->routes,
                'translationName' => $translationName,
                'pagination' => $pagination,
                'options' => $this->options,
                'ordering' => $this->ordering));
        }

        // create the sections of the table
        $headerCode = $this->engine->render($this->headerTemplate, array(
            'fields' => $fields,
            'pagination' => $pagination,
            'hasRowActions' => $this->hasRowActions(),
            'routes' => $this->routes,
            'translationName' => $translationName,
            'options' => $this->options,
            'ordering' => $this->ordering));
        $bodyCode = $this->engine->render($this->bodyTemplate, array(
            'rows' => $rowCode,
            'pagination' => $pagination,
            'routes' => $this->routes,
            'translationName' => $translationName,
            'options' => $this->options,
            'ordering' => $this->ordering));
        $footerCode = $this->engine->render($this->footerTemplate, array(
            'pagination' => $pagination,
            'numRows' => $numRows,
            'routes' => $this->routes,
            'translationName' => $translationName,
            'options' => $this->options,
            'ordering' => $this->ordering));
        $paginationCode = $this->engine->render($this->paginationTemplate, array(
            'pagination' => $pagination,
            'routes' => $this->routes,
            'translationName' => $translationName,
            'options' => $this->options,
            'ordering' => $this->ordering));

        // create the table
        $code = $this->engine->render($this->tableTemplate, array(
            'header' => $headerCode,
            'body' => $bodyCode,
            'footer' => $footerCode,
            'pagination' => $paginationCode,
            'routes' => $this->routes,
            'translationName' => $translationName,
            'options' => $this->options,
            'ordering' => $this->ordering));

        return $code;
    }

    /**
     * A function to be overridden to calculate if the row show have the row actions template
     *
     * @param object $row The row data, either as an object or an array. If this is null then this is the test for
     * the header row
     * @return bool
     */
    protected function hasRowActions($row = null)
    {
        return true;
    }
}