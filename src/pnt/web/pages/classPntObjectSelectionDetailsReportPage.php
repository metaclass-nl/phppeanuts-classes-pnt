<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntObjectSelectionReportPage', 'pnt/web/pages');

/** Page that outputs a detailed report (like PntObjectReportPage) for each
* item that was marked. Uses ObjectReportPage.
*
* This abstract superclass provides behavior for the concrete
* subclass ObjectEditDetailsPage in the root classFolder or in the application classFolder. 
* To keep de application developers code (including localization overrides) 
* separated from the framework code override methods in the 
* concrete subclass rather then modify them here.
* @see http://www.phppeanuts.org/site/index_php/Menu/178
* @see http://www.phppeanuts.org/site/index_php/Pagina/64
* @package pnt/web/pages
*/
class PntObjectSelectionDetailsReportPage extends PntObjectSelectionReportPage {
	
	function printHeader() {
		$this->includeSkin('HeaderSelectionDetailsReporttPage');
	}
	
	function printBody() {
		$this->printPart('MainPart');
		print "<TABLE>\n";
		$this->printPart('ButtonsPanel');
		print "</TABLE>\n";
	}
	
	function printFooter() {
		print "\n</body></html>";
	}
	
	function getName() {
		return 'SelectionDetailsReport';
	}

	function isSameContextHandler($pntHandler) {
		return $pntHandler == 'SelectionDetailsReport';
	}

	function getButtonsList() {
		$buts = array();
		$this->addContextButtonTo($buts);
		$buts[]= $this->getButton("Print", "window.print();");
		return array($buts);
	}

	function printMainPart() {
		$items = $this->getRequestedObject();
		forEach (array_keys($items) as $key) {
			if ($key) 
				//print "<DIV class='FF'></DIV>\n"; //foil feed
				print "<HR class=noPrint>\n";

			$part = $this->getPart(array('ReportPage'), false);
			if (!$part) $part = $this->getPart(array('ObjectReportPage'), false);
			$this->initDetailsReportPart($part, $items[$key]);
			$part->printBody();
		}
	}
	
	function initDetailsReportPart($part, $item) {
		$part->object = $item;
		$part->showButtonsPanel = false;
	}
}
?>