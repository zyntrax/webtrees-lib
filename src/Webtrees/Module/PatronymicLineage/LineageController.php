<?php
/**
 * webtrees-lib: MyArtJaub library for webtrees
 *
 * @package MyArtJaub\Webtrees
 * @subpackage PatronymicLineage
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2009-2016, Jonathan Jaubart
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3
 */
namespace MyArtJaub\Webtrees\Module\PatronymicLineage;

use \Fisharebest\Webtrees\Filter;
use \Fisharebest\Webtrees\I18N;
use \Fisharebest\Webtrees\Query\QueryName;
use Fisharebest\Webtrees\Controller\PageController;
use Fisharebest\Webtrees\Module\AbstractModule;
use MyArtJaub\Webtrees\Globals;
use MyArtJaub\Webtrees\Module\PatronymicLineage\Model\LineageBuilder;
use MyArtJaub\Webtrees\Mvc\Controller\MvcController;
use MyArtJaub\Webtrees\Mvc\View\ViewBag;
use MyArtJaub\Webtrees\Mvc\View\ViewFactory;

/**
 * Controller for Lineage
 */
class LineageController extends MvcController
{   
    
    /**
     * Generate the patronymic lineage for this surname
     * @var string $surname Reference surname
     */
    private $surname;   
    
    /**
     * Initial letter
     * @var string $alpha
     */
    private $alpha;
    
    /**
     * Show all names (values: yes|no)
     * @var bool $show
     */
    private $show_all;
    
    /**
     * Page to display (values: surn|lineage)
     * @var unknown $show
     */
    private $show;
    
    /**
     * Page title
     * @var string $legend
     */
    private $legend;
    
    /**
     * {@inheritDoc}
     * @see \MyArtJaub\Webtrees\Mvc\Controller\MvcController::__construct(AbstractModule $module)
     */
    public function __construct(AbstractModule $module) {        
        parent::__construct($module);
        
        $this->surname     = Filter::get('surname');
        $this->alpha       = Filter::get('alpha'); // All surnames beginning with this letter where "@"=unknown and ","=none
        $this->show_all    = Filter::get('show_all', 'no|yes', 'no'); // All indis
        // Make sure selections are consistent.
        // i.e. can’t specify show_all and surname at the same time.
        if ($this->show_all === 'yes') {
            $this->alpha   = '';
            $this->surname = '';
            $this->legend  = I18N::translate('All');
            $this->show    = Filter::get('show', 'surn|lineage', 'surn');
        } elseif ($this->surname) {
            $this->alpha    = QueryName::initialLetter($this->surname); // so we can highlight the initial letter
            $this->show_all = 'no';
            if ($this->surname === '@N.N.') {
                $this->legend = I18N::translateContext('Unknown surname', '…');
            } else {
                $this->legend = Filter::escapeHtml($this->surname);
                // The surname parameter is a root/canonical form.
                // Display it as the actual surname
                foreach (QueryName::surnames(Globals::getTree(), $this->surname, $this->alpha, false, false) as $details) {
                    $this->legend = implode('/', array_keys($details));
                }                
            }
            $this->show = 'lineage'; // SURN list makes no sense here
        } elseif ($this->alpha === '@') {
            $this->show_all = 'no';
            $this->legend   = I18N::translateContext('Unknown surname', '…');
            $this->show     = 'lineage'; // SURN list makes no sense here
        } elseif ($this->alpha === ',') {
            $this->show_all = 'no';
            $this->legend   = I18N::translate('None');
            $this->show     = 'lineage'; // SURN list makes no sense here
        } elseif ($this->alpha) {
            $this->show_all = 'no';
            $this->legend   = Filter::escapeHtml($this->alpha) . '…';
            $this->show     = Filter::get('show', 'surn|lineage', 'surn');
        } else {
            $this->show_all = 'no';
            $this->legend   = '…';
            $this->show     = 'none'; // Don't show lists until something is chosen
        }
        $this->legend = '<span dir="auto">' . $this->legend . '</span>';
          
    }
    
    /**
     * Indicates whether the list of surname should be displayed
     * @return bool
     */
    protected function isShowingSurnames() {
        return $this->show === 'surn';
    }
    
    /**
     * Indicates whether the lineages should be displayed
     * @return bool
     */
    protected function isShowingLineages() {
        return $this->show === 'lineage';
    } 
    
    /**
     * Get list of surnames, starting with the specified initial
     * @return array
     */
    protected function getSurnamesList() {        
        return QueryName::surnames(Globals::getTree(), $this->surname, $this->alpha, false, false);
    }
    
    /**
     * Get the lineages for the controller's specified surname
     */
    protected function getLineages() {		
        $builder = new LineageBuilder($this->surname, Globals::getTree());
		$lineages = $builder->buildLineages();
		
    	return $lineages;
    }    
    
    /**
     * Pages
     */
    
    /**
     * Lineage@index
     */
    public function index() {
        $controller = new PageController();
        $controller->setPageTitle(I18N::translate('Patronymic Lineages') . ' : ' . $this->legend);
        
        $view_bag = new ViewBag();
        $view_bag->set('title', $controller->getPageTitle());
        $view_bag->set('tree', Globals::getTree());
        $view_bag->set('alpha', $this->alpha);
        $view_bag->set('surname', $this->surname);
        $view_bag->set('legend', $this->legend);
        $view_bag->set('show_all', $this->show_all);
        if($this->isShowingSurnames()) {
            $view_bag->set('issurnames', true);
            $view_bag->set('surnameslist', $this->getSurnamesList());
        }
        if($this->isShowingLineages()) {
            $view_bag->set('islineages', true);
            $view_bag->set('lineages', $this->getLineages());

            if ($this->show_all==='no') {
            	$view_bag->set('table_title', I18N::translate('Individuals in %s lineages', $this->legend));
            }
            else {
            	$view_bag->set('table_title', I18N::translate('All lineages'));
            }
        }
        
        ViewFactory::make('Lineage', $this, $controller, $view_bag)->render();   
    }
    
    
    
}