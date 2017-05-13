<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** Part that outputs a button in html. Used by ButtonsPanel.
*
* This abstract superclass provides behavior for the concrete
* subclass ButtonPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntButtonPart extends PntPagePart {

	public $caption;
	public $script;
	public $ghost;
	public $width;
	
	public $minLength = 8;
	public $baseWidth = 24;
	public $widthMultiplier = 6;
	public $cssClass = 'funkyButton'; //extended with 'Ghost' if the button is ghosted
	public $buttonName = '';

	/* Constructor
	* @param $script onClick script for the button
	* The caption and width parameters should not contain HTML 
	* as they are converted to html when te button is printed 
	*/
	function __construct($whole, $requestData, $caption, $script, $ghost=false, $width=null) {
		parent::__construct($whole, $requestData);
		$this->caption = $caption;
		$this->script = $script;
		$this->ghost = $ghost;
		$this->width = $width;
	}

	function setMinLength($value) {
		$this->minLength = $value;
	}

	function setBaseWidth($value) {
		$this->baseWidth = $value;
	}

	function setWidthMultiplier($value) {
		$this->widthMultiplier = $value;
	}
	
	/** Sets the value for the class= in the button tag. 
	* will be extended with 'Ghost' if the button is ghosted
	*/
	function setCssClass($value) {
		$this->cssClass = $value;
	}

	function getName() {
		return 'ButtonPart';
	}

	function printBody($args=null, $type=null) {
		$class = $this->getButtonClass($type); 
		$width = $this->width;
		if ($width===null) {
			$len = strlen($this->caption);
			if ($len < $this->minLength) 
				$len=$this->minLength;
			$width = $len * $this->widthMultiplier + $this->baseWidth;
		}		
		
		if ($this->ghost)
			$disabled="disabled";
		else 
			$disabled="";
		$id = $this->buttonName ? $this->buttonName : str_replace(' ', '_', $this->caption);
		$this->printButton($disabled, $width, $class, $this->script, $this->caption, $type, $id);
	}
	
	/** Actually prints the button. 
	* @param $script onClick script for the button, 
	* 	caller must take care of proper encoding of eventual literal strings in the script
	* Parameters should not contain HTML 
	* as they are converted to html here */
	function printButton($disabled, $width, $cssClass, $script, $caption, $type, $id) {
		print "<input type=button id='";
		$this->htOut($id);
		print "' ";
		$this->htOut($disabled);
		print " style=\"width: ";
		$this->htOut((int) $width); //because no CSS encoding
		print "px;\" class='";
		$this->htOut($cssClass);
		print "' onClick=\"";
		$this->htOut($script);
		print "\" value=\"";
		$this->htOut($caption);
		print "\">";
	}
		
	/** override this method to get different css class for different type
	*/
	function getButtonClass($type) {
		if ($this->ghost)
			return $this->cssClass. 'Ghost';
		else
			return $this->cssClass;
	}
	
}
?>