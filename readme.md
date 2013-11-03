DitttoTableBundle
=================

What is it?
-----------

This allows creation of lists of data, including pagination, such as those used in admin systems, or a list of
products. It is not designed for huge amounts of data or unique ways of displaying data but instead provides a quick
and simple way of building an admin system fast.


Setup
-----

Add blah to your composer.json and run `composer install` / `composer update`.

Check your AppKernel to make sure the following code has been added successfully:

    new \Dittto\TableBundle\DitttoTableBundle(),


How to use it
-------------

You'll need to extend the Table class to use it. If you try and call the Table class directly it will throw an
exception. This is because the table needs to be created with various settings and I prefer to keep these in child
classes. Below is a simple example of how to extend it for a news entity:

    namespace A\DifferentBundle\Table;

    use Dittto\TableBundle\Table\Table;
    use Doctrine\ORM\EntityRepository;

    /**
     * Class NewsTable
     * The table for the news list
     *
     * @package A\DifferentBundle\Table
     */
    class NewsTable extends Table
    {
        /**
         * {@inheritDoc}
         */
        public function __construct(EntityRepository $repository, $order = null, $direction = 'asc', $page = 1, $perPage = 10)
        {
            // setup the parent as normal
            parent::__construct($repository, $order, $direction, $page, $perPage);

            // set the table alias
            $this->setAlias('a');

            // setup the fields for the news table
            $this->setFields(array(
                'title' => array('alias' => 'a', 'order' => 'asc', 'name' => 'Title'),
                'slug' => array('alias' => 'a', 'order' => true, 'name' => 'Slug'),
                'createdAt' => array('alias' => 'a', 'order' => true, 'name' => 'Created date')
            ));
        }
    }


Now you have your own Table class, you need to instantiate a render for it, set the routes the renderer is allowed
to use, and use this renderer to create the html for the table:

    /**
     * Class NewsController
     * Handles the updates for the news articles
     *
     * @package A\DifferentBundle\Controller
     * @Route("/news")
     */
    class NewsController extends Controller
    {
        /**
         * Shows a list of all news articles
         *
         * @Route("", name="news_list")
         */
        public function listAction()
        {
            // setup a renderer for the news table
            $renderer = new HTMLRenderer($this->container->get('templating'));
            $renderer->setRoutes(array(
                'new' => 'news_new',
                'edit' => 'news_edit',
                'delete' => 'news_delete',
            ));

            // create and render the news table
            $table = new NewsTable($this->getDoctrine()->getRepository('ADifferentBundle:News'));
            $tableCode = $table->createTable($renderer);

            return $this->render('ADifferentBundle:News:list.html.twig', array('table' => $tableCode));
        }
    }


Customisation
-------------

** Translations / Tag names **

You will notice that the pagination isn't showing after install and instead showing something similar to
`pagination.count`. This is because translation files are used to replace the default text. The translation files needed
are named after class used to create the table, so in the example above the translation file is named
`newstable.en.xliff`.

If you copy the file from `TableBundle/Resources/translations/table.en.xliff` and place it in the translations folder
in either your bundle or in your `app/Resources/translations` folder, rename it, then run:

    app/console cache:clear

When you refresh your browser, you should see the default translations appear, assuming your site is set up for
english translations. If not, edit your `app/config/config.yml` to include either one of the following lines. The first
will force your site into english and the second will rely on a parameters.yml property called `locale`:

    translator:      { fallback: en }
    or
    translator:      { fallback: %locale% }


** Views **

To override the twig templates used in this bundle, you can use one or all of the following lines of code in your
constructor, or you can override the `HTMLRenderer` class. These can be chained if your require:

    $renderer->setTableTemplate('ADifferentBundle:Table:table.html.twig');
    $renderer->setHeaderTemplate('ADifferentBundle:Table:header.html.twig');
    $renderer->setBodyTemplate('ADifferentBundle:Table:body.html.twig');
    $renderer->setRowTemplate('ADifferentBundle:Table:row.html.twig');
    $renderer->setRowActionsTemplate('ADifferentBundle:Table:rowActions.html.twig');
    $renderer->setFooterTemplate('ADifferentBundle:Table:footer.html.twig');
    $renderer->setPaginationTemplate('ADifferentBundle:Table:pagination.html.twig');