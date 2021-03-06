<?php
/**
 * webtrees-lib: MyArtJaub library for webtrees
 *
 * @package MyArtJaub\Webtrees
 * @subpackage Mvc
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2016, Jonathan Jaubart
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3
 */
namespace MyArtJaub\Webtrees\Mvc\View;

/**
 * Abstract class for MVC Views.
 */
abstract class AbstractView {
    
    /**
     * Reference controller
     * @var \Fisharebest\Webtrees\Controller\BaseController $ctrl
     */
    protected $ctrl;
    
    /**
     * Structure containing the data of the view
     * @var ViewBag $data
     */
    protected $data;
    
    /**
     * Constructor 
     * @param \Fisharebest\Webtrees\Controller\BaseController $ctrl Controller
     * @param ViewBag $data ViewBag holding view data
     */
    public function __construct(\Fisharebest\Webtrees\Controller\BaseController $ctrl, ViewBag $data) {
        $this->ctrl = $ctrl;
        $this->data = $data;
    }
    
    /**
     * Render the view to the page, including header.
     * 
     * @throws \Exception
     */
    public function render() {
        global $controller;        
        
        if(!$this->ctrl) throw new \Exception('Controller not initialised');
        
		$controller = $this->ctrl;
        $this->ctrl->pageHeader();
        
        echo $this->renderContent();
    }
    
    /**
     * Render the view to the page, without any header
     */
    public function renderPartial() {
        echo $this->getHtmlPartial();
    }
    
    /**
     * Return the HTML code generated by the view, without any header
     */
    public function getHtmlPartial() {
        ob_start();
        $html_render = $this->renderContent();
        $html_buffer = ob_get_clean();        
        
        return empty($html_render) ? $html_buffer : $html_render;
    }
    
    /**
     * Abstract method containing the details of the view.
     */
    abstract protected function renderContent();
    
}
 