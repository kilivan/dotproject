<?php

require_once 'smarty/Smarty.class.php';

/**
 * The base view class which can contain any number of elements including child views.
 * 
 * The DP_View class is the most general representation of a HTML box. It can be subclassed to produce any 
 * element of display. Each DP_View object must have a unique identifier (required by the constructor), which 
 * will be used by the DP_Template system to assign the DP_View's output to the location specified in the template.
 * 
 * @package dotproject
 * @subpackage system
 * @author ebrosnan
 * @version not.even.alpha
 * @todo Assess whether a full instance of smarty is required to insert subviews/child views.
 */
class DP_View {
	/**
	 * @var mixed $id This view's identifier.
	 */
	protected $id;
	/**
	 * @var object $smarty Instance of Smarty templating system.
	 */
	protected $smarty;
	/**
	 * @var integer $width Desired width of this element.
	 */
	protected $width;
	/**
	 * @var integer $parent_view_id Identifier of the parent view object.
	 */
	protected $parent_view_id;
	/**
	 * @var array $child_views Array of child views inside this view.
	 */
	protected $child_views;
	
	function __construct($id) {
		$this->id = $id;
		$this->parent_view_id = -1;
		$this->width = "100%";
		$this->child_views = Array();
		$this->smarty = new Smarty();
		
		// TODO - Use a subclass of DP_Template or a lightweight version of DP_Template
		$this->_smarty->template_dir = array(
			DP_BASE_CODE.'/modules/'.$this->mod.'/views/style/'.(isset($ui->style) ? $ui->style : 'default'),
			DP_BASE_CODE.'/modules/'.$this->mod.'/views/style',
			DP_BASE_CODE.'/style/'.(isset($ui->style) ? $ui->style : 'default'),
			DP_BASE_CODE.'/style/_smarty/'.$this->mod,
			DP_BASE_CODE.'/style/_smarty'
		);
		$this->_smarty->compile_dir = DP_BASE_DIR . '/files/cache/smarty_templates';
		$this->_smarty->cache_dir = DP_BASE_DIR . '/files/cache/smarty';
		$this->_smarty->plugins_dir[] = DP_BASE_CODE . '/lib/plugins';
	}
	
	/**
	 * Get the template identifier for this view object.
	 * 
	 * @return string Unique string identifying this view object.
	 */
	public function id() {
		return $this->id;
	}
	
	/**
	 * Get the identifier of the parent view.
	 * 
	 * @return string Unique string identifying the parent view object.
	 */
	public function parentId() {
		return $this->parent_view_id;
	}
	
	/**
	 * Set the identifier of the parent view.
	 * 
	 * @param integer $id Unique string identifying the parent view object.
	 */
	public function setParentViewId($id) {
		$this->parent_view_id = $id;
	}
	
	/**
	 * Add a child view.
	 * 
	 * Add a DP_View which will be inserted inside this view.
	 * @todo Determine standardised behaviour for views that cannot contain other views. Eg. listview
	 */
	public function add(DP_View $view) {
		$view->setParentViewId($this->id());
		$this->child_views[] = $view;
	}
	
	/**
	 * Get the desired width of this view object.
	 * 
	 * @return integer Width of this view object in any acceptable HTML unit.
	 */
	public function width() {
		return $this->width;
	}
	
	/**
	 * Get the desired height of this view object.
	 * 
	 * @todo Implement this stub method.
	 */
	public function height() {
		
	}
	
	/**
	 * Get the desired alignment of this object inside the parent.
	 * 
	 * @todo Implement this stub method.
	 */
	public function align() {
		
	}
	
	/**
	 * Render this DP_View object's contents into HTML
	 * All DP_View objects must implement this method.
	 * 
	 * @return string HTML output of this DP_View object.
	 * @todo Force implementation of this method for subclasses using an abstract
	 */
	public function render() {
	}
	
	/**
	 * Render all child DP_View objects into HTML.
	 */
	protected function renderChildren() {
		$output = "";
		foreach ($this->child_views as $child) {
			$output .= $child->render();
		}
		return $output;
	}
}
?>