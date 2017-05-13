<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntPagePart', 'pnt/web/parts');

/** Part that outputs html describing a menu.
* By default includes skinMenuPart.php from the includes folder.
* Includes skinSubMenu.php when printSubMenu is called with the
* application folder name as the argument.
* But if ::initMenuData and initSubmenuData are overridden, 
* menu's are printed from the initialized arrays and
* the menu with the current pntType will be highligted.
* If no pntType the applications main menu item will be highlighted 
*
* This abstract superclass provides behavior for the concrete
* subclass MainMenuPart in the root classFolder and 
* subclasses MenuPart in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/65
* @package pnt/web/parts
*/
class PntMenuPart extends PntPagePart {

	public $rowHlColor='#FBFFB3';
	public $menuData;
	public $submenuData;
	
	
	/** to be overridden by subclass MainMenuPart */
	function initMenuData() {
		$this->menuData = array();
		//$this->submenuData[] = array(module, pntType, pntHandler, title, label [, script] );
	}
	
	/** to be overridden by a subclass MenuPart in each application class folder */
	function initSubmenuData() {
		
		$this->submenuData = array();
		//$this->submenuData[] = array(indent, pntType, pntHandler, title, label [, script] );

		//or $this->submenuData = $this->parseSkinSubmenu();
	}
	
	function getName() {
		return 'MenuPart';
	}

	function isMyDir($name) {
		return $this->getDir() == 
				(($name && substr($name, -1) != '/') ? $name.'/' : $name);
	}

	function printBody() {
		$this->initMenuData();
		if (!$this->menuData) return parent::printBody(); //includes skinMenuPart
		
		forEach($this->menuData as $lineData) {
			$this->printMenuLine($lineData);
			if ($lineData[0])
				$this->printSubmenu($lineData[0]);
		}
	}

	function printSubmenu($name) {
		if (!$this->isMyDir($name)) return;
		
		$this->initSubmenuData();
		
		if (!$this->submenuData) $this->includeSkin('Submenu');
		
		forEach($this->submenuData as $lineData) 
			$this->printMenuLine($lineData, true);
	}
	
	function printMenuLine($lineData, $subMenu=false) {
		$params = $this->getMenuLineParams($lineData, $subMenu);
		print "
			<div class=\"$params[cssClass]\" $params[rowHighLight]>
				$params[indent]<A HREF=\"$params[href]\" title='$params[title]'>$params[linkText]</A>
			</div>";
		
	}
		
		
	function getMenuLineParams($lineData, $subMenu) {
		$cnv = $this->controller->converter;
		$params['cssClass']=$cnv->toHtml($this->getCssClass($lineData, $subMenu));
		$params['rowHighLight'] = $this->shouldHighlightRow($lineData, $subMenu)
			? 'style="background-color: '. $cnv->toHtml($this->rowHlColor). '"'
			: '';
		$params['title'] = $cnv->toHtml($lineData[3]);
		$params['linkText'] = $cnv->toHtml($lineData[4]);
		$params['indent'] = $subMenu 
			? $this->getIndent($lineData[0]) 
			: ($params['linkText'] ? '' : '&nbsp;');

		$url = $this->getUrl($subMenu, $lineData[0], $lineData[1], $lineData[2]);
		$params['href'] = isSet($lineData[5]) 
			? 'javascript:'. str_replace('$url', $cnv->toJsLiteral($url, ''), $lineData[5])
			: $url;
			
		return $params;
	}
	
	function getUrl($subMenu, $dir, $pntType, $pntHandler) {
		$params = array('pntScd' => 'd', 'pntRef' => $this->getFootprintId());
		if ($pntType) $params['pntType'] = $pntType;
		if ($pntHandler) $params['pntHandler'] = $pntHandler;
		
		return $this->controller->buildUrl($params, $subMenu ? '' : $dir);
	}
	
	function getCssClass($lineData, $subMenu) {
		return $subMenu ? 'pntSubmenuLine' : 'pntMenuLine';;
	}
	
	function shouldHighlightRow($lineData, $subMenu) {
		return ($subMenu || $this->isMyDir($lineData[0])) && $lineData[1] == $this->getType();
	}
	
	/** @return string html to indent the menu line */
	function getIndent($depth) {
		$result = '';
		for($i=0; $i<$depth; $i++) 
			$result .= '&nbsp;&nbsp;&nbsp;';
		return $result;
	}
	
	/** Method for converting old skinSubmenus to initSubmenuData code.
	* to be adapted to actual skin(s) */
	function parseSkinSubmenu() {
		$lines = file('skinSubMenu.php');
		$depth = 1;
		$data = array();
		forEach($lines as $line) {
			$pieces = explode('&nbsp;&nbsp;&nbsp;', $line);
			if (count($pieces) > 1)
				$depth = count($pieces)-1;
			
			$qPos = strPos($line, '?');
			if ($qPos===false) continue;
			
			$qStart = $qPos + 1; 
			$hrefEind = strPos($line, '>', $qStart);
			$qString = subStr($line, $qStart, $hrefEind-$qStart);
			$params = array();
			parse_str($qString, $params);
			if (!isSet($params['pntHandler'])) $params['pntHandler'] = '';
			
			$labelEnd = strPos($line, '</a>', $hrefEind+1);
			$label = subStr($line, $hrefEind+1, $labelEnd-$hrefEind-1);
			
			$data[] = array($depth, $params['pntType'], $params['pntHandler'], "Zoek $params[pntType]", $label);
			//to generate code:
			print "<br>\n".'$'."this->submenuData[] = array($depth, '$params[pntType]', '$params[pntHandler]', 'Zoek $params[pntType]', '$label');";
		}
		return $data;
	}
	
}
?>