<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

/** Instances of this class build a page button list
* for browsing through pages search results
* @package pnt/web/helpers
*/
class PntPagerButtonsListBuilder {
	
	public $page;
	public $itemCount;
	public $pageItemOffset;
	public $pageItemCount = 20;
	public $maxPageButtonCount = 9;
	public $pageButtonSize = 40;

	function __construct($page) {
		$this->page = $page;
	}

	function setItemCount($value)
	{
		$this->itemCount = $value;
	}

	function setPageItemOffset($value)
	{
		$this->pageItemOffset = $value;
	}

	function setPageItemCount($value)
	{
		$this->pageItemCount = $value;
	}

	function setMaxPageButtonCount($value)
	{
		$this->maxPageButtonCount = $value;
	}

	function setPageButtonSize($value)
	{
		$this->pageButtonSize = $value;
	}

	function getItemCount()
	{
		return $this->itemCount;
	}
			
	function getPageItemOffset()
	{
		return $this->pageItemOffset;
	}
	
	function getPageItemCount()
	{
		return $this->pageItemCount;
	}
	
	function getMaxPageButtonCount()
	{
		return $this->maxPageButtonCount;
	}
	
	function getPageButtonSize()
	{
		return $this->pageButtonSize;
	}
	
	function addPageButtonsTo(&$buttons)
	{
		$current = $this->getPageItemOffset();
		$maxButtonItemRange = $this->getMaxPageButtonCount() * $this->getPageItemCount();
		$i = min(ceil($this->getItemCount()/$this->getPageItemCount()) * $this->getPageItemCount() - $maxButtonItemRange
			, $current - floor($this->getMaxPageButtonCount() / 2) * $this->getPageItemCount());
		$firstButtonItemOffset = $i = max(0,$i);
		$afterLastButtonItemOffset = min($this->getItemCount(), $firstButtonItemOffset + $maxButtonItemRange);

		$buttons[] = $this->page->getButton(
			'<'
			, $this->page->getPageButtonScript($current - $this->getPageItemCount())
			, $current < ($firstButtonItemOffset + $this->getPageItemCount())
			, $this->getPageButtonSize());
		
		while ($i < $afterLastButtonItemOffset) {
			$buttons[] = $this->page->getButton(
				floor($i/$this->getPageItemCount())+1
				, $this->page->getPageButtonScript($i)
				, $current >= $i && $current < ($i + $this->getPageItemCount())
				, $this->getPageButtonSize());
			$i += $this->getPageItemCount();
		} 

		$buttons[] = $this->page->getButton(
			'>'
			, $this->page->getPageButtonScript($current + $this->getPageItemCount())
			, $current >= ($this->getItemCount() - $this->getPageItemCount())
			, $this->getPageButtonSize());
	}

}

?>