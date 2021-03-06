<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */


Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** PagePart that generates a tab bar and corrsponding content divs 
* that are shown/hidden by clicking on a tab div in the tab bar.
* content is generated by printPart on the whole or on specified parts.
*
* This abstract superclass provides behavior for the concrete
* subclass TabsPart in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntTabsPart extends PntPagePart {
	
	/** Array with tab names. If a key is a string, it will be used as the tab label */
	public $tabsSpec;
	
	/** if set to false the contentparts should print their own divs with
	  * names according to the tabsSepc concatenated with 'Div' */
	public $printDivs = true;
	
	/** If set to true only the tabsbar is printen, no content parts */
	public $tabsBarOnly = false;
	
	/** name of selected tab as it appears in $tabSpec, can be set from print/getPart parameters */
	public $selected;	
	
	/** name of the javascript func tion called when the a tab is selected.
	* set this variable to name your own func tion and call pntTabSelected from there
	* if you need to do something more on tab selection then hiding/showing tabs
	* your func tion must take two parameters, the first an array with all tab keys
	* the second the key of the selected tab, and pass them on to pntTabSelected
	* @see scripts/general.js */
	public $jsSelectFunc = 'pntTabSelected';
	
	/** parts by tab name that generate content */
	public $contentParts;


	/** Constructor. $tabsSpec and $selected can be specified through
	* print/getPart parameters on the whole */
	function __construct($whole, $requestData, $tabsSpec, $selected=null) {
		parent::__construct($whole, $requestData);
		$this->initialize($tabsSpec, $selected);
	}
	
	/** call this method if multiple tabparts are used
	*/
	function initialize($tabsSpec, $selected=null) {
		if ($selected !== null)
			$this->setSelected($selected);
		$this->setTabsSpec($tabsSpec);

		$this->setHandler_printTabDiv($this);
		$this->setHandler_printContentDivTag($this);
		$this->setHandler_printContentPart($this);
	}

	/** Sets the Array with tab names (must be alphanumeric). If a key is a string, 
	* it will be used als the tab label (must be HTML)
	* If the value is an array, it should start with the tab name at index 0,
	* followed by the getPart parameters. The array will be replaced by the tab name
	*/
	function setTabsSpec($tabsSpec) {
		$this->tabsSpec = is_string($tabsSpec)
			? explode(',', $tabsSpec)
			: $tabsSpec;
		
		$this->contentParts = array();
		$this->processPartArgumentsConvertTabsSpec();
		
		//make sure selection is valid
		if (!in_array($this->selected, $this->tabsSpec))
			$this->selected = current($this->tabsSpec);
	}

	/* To support tabspec part parameters 
	* In the simpeler case values in $tabsSpec are Strings. 
	* Here we assume that if one is an array, a Part should be 
	* created and initialized with those parameters.
	* Afther that has been done the array is replaced by the tab name
	*/
	function processPartArgumentsConvertTabsSpec() {
		forEach(array_keys($this->tabsSpec) as $key) {
			$partSpec = $this->tabsSpec[$key];
			if (is_array($partSpec)) {
				$tabName = $this->checkAlphaNumeric($partSpec[0]);
				$partArgs = array_slice($partSpec, 1);
				$this->contentParts[$tabName] = $this->getPart($partArgs, false); //do not use part cache
				if ($this->contentParts[$tabName] == null)
					trigger_error("Could not get Part: $partArgs[0]", E_USER_WARNING);
				$this->tabsSpec[$key] = $tabName;
			} else {
				$this->checkAlphaNumeric($partSpec);
			}
		}
	}
	
	/** Sets the name of the tab that is initially selected as it appears in $tabSpec,  */
	function setSelected($value) {
		$this->selected = $value;
	}
	
	/** The name is used to identify the tabPart's divs in javascript,
	* if multiple tabparts are used on the same page, the name must be set
	* @param string $value alphanumeric string that can be used as a javascript variable name
	*/
	function setName($value) {
		$this->checkAlphaNumeric($name);
		$this->name = $value;
	}

	/** @see jsSelectFunc */
	function setJsSelectFunc($value) {
		$this->jsSelectFunc = $value;
	}

	function setHandler_printTabDiv($eventHandler) {
		$this->handler_printTabDiv = $eventHandler;
	}
	
	function setHandler_printContentDivTag($eventHandler) {
		$this->handler_printContentDivTag = $eventHandler;
	}
	
	function setHandler_printContentPart($eventHandler) {
		$this->handler_printContentPart = $eventHandler;
	}

	function getSelectedKey() {
		return $this->getName().$this->selected;
	}
	
	function getName() {
		if (isSet($this->name))
			return $this->name;
		else
			return 'TabsPart';
	}
	
	/** prints the entire part */
	function printBody() {
		//name is checkedAlphaNumeric by setName
		print "\n<DIV id='". $this->getName(). "' class='pntTabPart'>\n";
		$this->printScript();
		$this->printTabBar();
		if (!$this->tabsBarOnly)
			$this->printContent();
		print "<DIV id=pntTabTrailer></DIV></DIV>\n";
	}
	
	/* prints the javascript that sets the variable holding the array with tab keys 
	* the name of this varable can be retrieved from getJsTabsArrayName() */
	function printScript() {
		$varName = $this->getJsTabsArrayName(); //name is checkedAlphaNumeric by setName
		$myName = $this->getName();
		print "<script>var $varName;\n";
		print "$varName = new Array(";
		reset($this->tabsSpec);
		$sep = '';
		foreach ($this->tabsSpec as $name) {
			print $sep;
			print "'$myName$name'";
			$sep = ', ';
		}
		print "); </script>\n";
	}
	
	/* the name of the javascript variable holding the array with tab keys */
	function getJsTabsArrayName() {
		//name is checkedAlphaNumeric by setName
		return 'pnt'.$this->getName();
	}

	/* prints the bar of tab divs the user can klick on 
	*/
	function printTabBar() {
		reset($this->tabsSpec);
        foreach ($this->tabsSpec as $key => $name) {
			$label = is_string($key) ? $key : $name;
			$this->handler_printTabDiv->printTabDiv($this, $this->getName().$name, $label);
		}
	}
	
	/** eventhandler method, can be copied to other class  
	* @param string $key must be alphanumeric
	* @param HTML $label 
	*/
	function printTabDiv($tabsPart, $key, $label) {
		$selected = $key == $tabsPart->getSelectedKey() ? '_selected' : '';
		$id = $key.'Tab';  
		$arrayName = $tabsPart->getJsTabsArrayName();

		print "\n<DIV id='$id' class='pntTab$selected' onClick='$tabsPart->jsSelectFunc($arrayName, \"$key\");'>";
		print $label;
		print "</DIV>\n";
		
	}

	/** prints all content parts */
	function printContent() {
		reset($this->tabsSpec);
        foreach ($this->tabsSpec as $key => $name) {
			$label = is_string($key) ? $key : $name;
			$this->printContentDivPart($name, $label);
		}
	}
	
	/** prints the specified content part and, if $this->printDiv,
	* prints the div tags too
	* @param string $name must be alphanumeric
	* @param HTML $label 
	*/
	function printContentDivPart($name, $label) {
		if ($this->printDivs) {
			$this->handler_printContentDivTag->printContentDivTag($this, $name);
			$this->handler_printContentPart->printContentPart($this, $name, $label);
			print "\n</DIV>\n";
		} else {
			$this->printContentPart($name, $label);
		}
	}
	
	/** eventhandler method, can be copied to other class  
	* Prints the div tag of the contentPart with the specified name.
	* the id of the div tag must be the name of this concatenated with 
	* the name of the tab, concatenated with 'Content'.
	* @param PntTabsPart $tabsPart $this
	* @param string $name the name of the part, as appears in the tabsSpec, mus be alphanumeric
	*/
	function printContentDivTag($tabsPart, $name) {
		$display = $name == $tabsPart->selected ? 'block' : 'none';
		$id = $tabsPart->getName().$name.'Content';
		print "\n<DIV id='$id' style='display: $display;' class='pntTabContent'>\n";
	}
	
	/** eventhandler method, can be copied to other class  
	* Prints the contentPart with the specified name.
	* Default implementation tries to delegate to specific part,
	* if none, calls printPart on the whole 
	* with the name concatenated with 'Part'
	* @param PntTabsPart $tabsPart $this
	* @param string $name the name of the part, as appears in the tabsSpec, must be alphanumeric
	* @param HTML $label label of the tab as it appears on the tab div
	*/
	function printContentPart($tabsPart, $name, $label) {
		$part = $tabsPart->getContentPart($name);
		if ($part) return $part->printBody();
		
		$tabsPart->whole->printPart($name.'Part', $label);
	}
	
	/** if a contentPart is specified for the tab, return it.
	* @param String $name The name of the tab as appears in the tabsSpec
	* @return PntPagePart the part that will print the content for the tab
	*/
	function getContentPart($name) {
		if (isSet($this->contentParts[$name]))
			return $this->contentParts[$name];
		
		return null;
	}
}
?>