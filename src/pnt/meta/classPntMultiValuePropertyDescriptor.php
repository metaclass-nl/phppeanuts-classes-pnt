<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

Gen::includeClass('PntDerivedPropertyDescriptor', 'pnt/meta');

/** An object of this class describes a multi value property of a peanut 
* and supplies default property behavior.
* @see http://www.phppeanuts.org/site/index_php/Pagina/100
* @package pnt/meta
*/
class PntMultiValuePropertyDescriptor extends PntDerivedPropertyDescriptor {
	public $twinName;
	public $derivationPath;
	public $onDelete;

	function isMultiValue() {
		return true;
	}

	/** @return boolean Wheather there must be values (currently not enforced by the UI framework)
	*/
	function getCompulsory() {
		return $this->getMinLength() > 0;
	}
	
	/**
	 * @return boolean wheather te values of this property
	 * 		should be recursively copied if the peanut is copied
	 * can be set value, default is $this->getRecurseDelete()
	 */
	function getInCopy() {
		if (isSet($this->inCopy)) return $this->inCopy;
		return $this->getRecurseDelete();
	}
	
	function setInCopy() {
		$this->inCopy = true;
	}
	/** @return boolean wheather the values are dependents of the peanut. 
	* This is the case if the twin property is compulsory
	*/
	function getHoldsDependents() {
		$twin = $this->getTwin();
		if (!$twin) return null;
		
		return $twin->getCompulsory();
	}

	/** Returns the propertyDescriptor of the corresponding id-Property
	* this is the property of the type which is named as the ownerName, extended with 'Id'
	* @return PntPropertyDescriptor
	*/
	function getIdPropertyDescriptor() {
		if (!$this->getTwinName()) return null;
		
		$className = $this->getType();
		if (!class_exists($className))
			Gen::tryIncludeClass($className, $this->getClassDir()); //parameters from won properties

		if ( !class_exists($className) )
			return null;
			
		$typeClsDesc = PntClassDescriptor::getInstance($className);
		if (!$typeClsDesc) return null;
		
		$idPropName = $this->getTwinName().'Id';
		$result = $typeClsDesc->getPropertyDescriptor($idPropName);
		if ($result) return $result;
		
		return null;
	}
	
	/** If a property implements a role in a relationship, 
	* the property that implements the role on the other side is its twin.
	* @return PntPropertyDescriptor The twin property in the relationship
	*/ 
	function getTwin() {
		if (!$this->getTwinName()) return null;

		$className = $this->getType();
		if (!class_exists($className))
			Gen::tryIncludeClass($className, $this->getClassDir()); //parameters from own properties

		if ( !class_exists($className) )
			return null;
		$typeClsDesc = PntClassDescriptor::getInstance($className);
		if (!$typeClsDesc) return null;

		return $typeClsDesc->getPropertyDescriptor($this->getTwinName());
	}

	/** Return the property value for the object
	* Called if no getter method exists.
	* If $filter===true it caches the results array in field with same name as property on object
	* and returns cached array if field is set. !Cached values are not reset before returned
	*
	* @param PntObject $obj The object whose property value to answer
	* @param PntSqlFilter $filter to apply, or boolean wheather to use global filter
	* @result array of ::getType (dynamically typed) the property value
	* @throws PntError for primitive types, if no idProperty or if the types classDescriptor can not get values
	*/
	function deriveValueFor($obj, $filter=true) {
		if ($filter===true) {
			$name = $this->getName();
			if (isSet($obj->$name)) return $obj->$name; //previously cached
		}
		$found = $this->deriveValueNoCache($obj, $filter);
		
		//only cache result on object when default is used
		if ($filter===true)  
			$obj->$name = $found; 
			
		return $found;
	}
	
	/** Derives the property value for the object without caching
	*
	* @param PntObject $obj The object whose property value to answer
	* @param PntSqlFilter ignoored
	* @result array of ::getType (dynamically typed) the property value
	* @throws PntError for primitive types, if no idProperty or if the types classDescriptor can not get values
	*/
	function deriveValueNoCache($obj, $filter=true) {
		if (isSet($this->derivationPath)) 
			return $this->deriveUsingPathFor($obj);

		$className = $this->getType();
		if (!class_exists($className))
			Gen::tryIncludeClass($className, $this->getClassDir());  //parameters from own properties

		if (!class_exists($className)) 
			throw new PntReflectionError($this. ' unable to derive value: no getter and type is not a class');

		$idProp = $this->getIdPropertyDescriptor();
		if (!$idProp) 
			throw new PntReflectionError("$this Unable to derive value: no getter and no id-property: ".$this->getTwinName().'Id');

		$clsDesc = $this->getOwner();
		$ownIdProp = $clsDesc->getPropertyDescriptor('id');
		try {
			$id = $ownIdProp->getValueFor($obj);
		} catch (PntError $err) {
			throw new PntReflectionError(
				$this. ' Unable to derive value of idProperty '
				,0 , $err
			);
		}
		if (!$id) return array();
		
		$typeClsDesc = PntClassDescriptor::getInstance($this->getType());
		try {
			//cache result on object
			return $typeClsDesc->getPeanutsWith($idProp->getName(), $id); 
		} catch (PntError $err) {
			throw new PntReflectionError(
				$this. ' Unable to derive value: no getter or '
				, 0, $err
			);
		}		
	}
	
	/** 
	 * @return PntSqlFilter or null if no id to filter by
	 * @throws PntReflectionError if not all of the properties in the (derivation)path have twins */ 
	function getMyFilter($obj) {
		Gen::includeClass('PntSqlJoinFilter', 'pnt/db/query');
		$nav = isSet($this->derivationPath)
			? $this->getDerivationNav()
			: PntNavigation::getInstance($this->getName(), $this->ownerName);
		$clsDesc = $this->getOwner();
		$prop1 = $nav->getFirstProp();
		//optimize one (but not the last) step away if normal navigation over idProp on $obj
		if (!$prop1->isMultiValue() && $nav->getNext() && get_class($nav) == 'PntObjectNavigation')  
			$ownIdProp = $prop1->getIdPropertyDescriptor();
		if (isSet($ownIdProp))
			$nav = $nav->getNext();
		else
			$ownIdProp = $clsDesc->getPropertyDescriptor('id');

		if (!$ownIdProp) 
			throw new PntReflectionError(Gen::toString($this). ' Unable to derive value: no getter and no id-property'); 

		$id = $ownIdProp->getValueFor($obj);
		if (!$id) return null;

		$myFilter = PntSqlFilter::getInstanceForNav($nav->getWayBack());
		$myFilter->by('=', $id);
		return $myFilter;
	}
	
	function getExtraFilter($obj, $filter) {
		if ($filter!==true) return $filter;
		
		return isSet($GLOBALS['site']) //global filtering requites global site to contain a PntSite
			? $GLOBALS['site']->getGlobalFilterFor($this->getType(), false)
			: null;
	}
	
	/** Release the values cached on the object by ::deriveValueFor.
	 * After releasing ::deriveValueFor must re-derive the values the first time it 
	 * is called for the object.
	 * @param PntObject $obj The object whose property value cache is to be released
	 */
	
	function releaseCacheOn($obj) {
		$name = $this->getName();
		$obj->$name = null;
	}

	/** If a property implements a role in a relationship, 
	* the property that implements the role on the other side is its twin.
	* If a properties type is not a primitive type, the default 
	* twin name is the owners name with the first letter in lower case.
	* @return The name of the twin property in the relationship
	*/ 
	function getTwinName() {
		if (isSet($this->twinName))
			return $this->twinName;
		
		if ($this->isTypePrimitive) return null;

		return lcFirst($this->ownerName);
	}

	/** @see getTwinName() 
	* Setting null will override the default
	* @param $value String the twin name
	*/
	function setTwinName($value) {
		$this->twinName = $value;
	}
	
	/** Only works wiht m to n relationships to & through pntDbObjects 
	* with id properties corrsponding to the steps and mapped to the database
	* The path should not refer to the mToNproperty but to the corresponding 1ToNproperty
	* whose type corresponds to the JOINed table, and from there to the nTo1property 
	* on that type.  
	* For example if the name of $mToNproperty is 'keywords', wich gives
	* access to an array of objects of type Keyword. The path could then be 
	* 'keywordRelations.keyword'. Property keywordRelations would give access to 
	* an array of objects of type KeywordRelation and property KeywordRelation>>keyword
	* would give access to one object of type Keyword.
	* @param String $path from a peanut whose property is described by $this
	* to the m to n related objects.
	*	(two steps, like the result of "$1ToNpropertyName.$nTo1propertyName")
	*/
	function setDerivationPath($value) {
		$this->derivationPath = $value;
	}

	/** @trhows PntReflectionError */
	function getDerivationNav() {
		if (!isSet($this->derivationPath)) {
			return null;
		}
		if (!isSet($this->derivationNav)) {
			$this->derivationNav = PntNavigation::getInstance($this->derivationPath, $this->ownerName);
		}
		return $this->derivationNav;
	}
	
	/** method to be used for m to n relationships replacing getter methods
	* only works for two step relations to & through pntDbObjects with
	* id properties corrsponding to the steps and mapped to the database
	* JOINs a relationship table and SELECTs on $this->id.
	* @precondition $derivationPath must be set.
	* @param Object $obj the object to derive the values for
	* @param PntSqlFilter $filter to apply, or boolean wheather to apply the first applicable global filter
	* @throws PntReflectionError
	*/
	function deriveUsingPathFor($obj) {
		Gen::includeClass($this->getType(), $this->getClassDir());
		$clsDesc = $this->getOwner();
		$ownIdProp = $clsDesc->getPropertyDescriptor('id');
		$id = $ownIdProp->getValueFor($obj);
		if (!$id) return array();
	
		Gen::includeClass('PntSqlJoinFilter', 'pnt/db/query');
		$nav = $this->getDerivationNav();
		$myFilter = PntSqlFilter::getInstanceForNav($nav->getWayBack());
		$myFilter->by('=', $id);
		$typeClsDesc = PntClassDescriptor::getInstance($this->getType());
		return $typeClsDesc->getPeanutsAccordingTo($myFilter);
		
	}

	/** Method to update m to n relationship. 
	* Called by PntObjectSaveAction if no corrsponding update method
	* or from update method
	* ( $updatemethodName = $mToNproperty~>getName(). 'NtoMmodIds'; )
	* update methods have the same arguments as the last two arguments of this method.
	* only works for two step relations to & through pntDbObjectswith
	* id properties corrsponding to the steps and mapped to the database
	* 
	* You should avoid overlap between $idsToAdd and $idsToRemove because 
	* duplicate relations with ids in $idsToRemove will all be removed without respect to
	* the number of (duplicate) ids in $idsToRemove.
	* @precondition $derivationPath must be set.
	* @param String $path from $this to the m to n related objects 
	*	(two steps, like the result of "$1ToNpropertyName.$nTo1propertyName")
	* @param Array $idsToAdd ids of the objects that should be added to the relation
	* @param Array $idsToRemove ids of the objets that should be removed from the relation
	* @throws PntReflectionError
	*/
	function mutateRelationFor_ids($obj, $idsToAdd, $idsToRemove) {
		//to be refactored
		try {
			$nav = $this->getDerivationNav();
			
			$relationObjCls = $nav->getStepResultType();
			$relationObjClsDes = PntClassDescriptor::getInstance($relationObjCls);
			$firstProp = $nav->getFirstProp();
			$ownIdProp = $firstProp->getIdPropertyDescriptor();
			$lastProp = $nav->getLastProp();
			$otherIdProp = $lastProp->getIdPropertyDescriptor();
			$ownId = $obj->get('id');
	
			//deleteFromRelation_ids
			if (count($idsToRemove) > 0) {
				$qh = $relationObjClsDes->getSelectQueryHandler();
				$qh->where_equals($ownIdProp->getColumnName(), $ownId);
				$qh->query .= " AND ";
				$qh->in($otherIdProp->getColumnName(), $idsToRemove);
				$toDelete = $relationObjClsDes->getPeanutsRunQueryHandler($qh);
				forEach(array_keys($toDelete) as $key) 
					$toDelete[$key]->delete();
			}
			
			//addToRelation_ids
			forEach($idsToAdd as $otherId) {
				$newObj = new $relationObjCls();
				$result = $ownIdProp->setValue_for($ownId, $newObj);
				if (Gen::is_a($result, 'PntError') )
					trigger_error($result->getLabel(), E_USER_ERROR);
				$result = $otherIdProp->setValue_for($otherId, $newObj);
				if (Gen::is_a($result, 'PntError') )
					trigger_error($result->getLabel(), E_USER_ERROR);
				$newObj->save();
			}
		} catch (PntError $err) {
			throw new PntReflectionError("$this could not update relation for $obj", 0, $err);
		}
	}
	
	/** @return string Return a letter identifying the action to be taken with respect to the 
	* properties values when the peanut is deleted.
	*/
	function getOnDelete() {
		return $this->onDelete;
	}
	
	/** @return Array with onDelete labels by onDelete settings 
	* NB: verify has not yet been implemented and currently behaves like delete
	*/
	static function getOnDeleteLabels() {
		return array(
			'd' => 'delete' //delete the values of the property without warning
			, 'v' => 'verify' //verify with the user that the values can be deleted
			, 'c' => 'check' //check that the property has no values
			);
	}
	
	/** Sets what should happen to values of this property if the owning peanut is deleted 
	* If not set, nothing happens. If set to 'd' the delete will cascade to the property values.
	* If set to 'c' the the owning peanut can only be deleted if the property has no values.
	* If set to 'v' (verify) the user interface should verify the recursive delete with the user first.
	* 	(in versions earlier then 2.0 this was not implemented)
	*/
	function setOnDelete($letter) {
		if ($letter !== null && strPos('dvc', $letter) === false)
			trigger_error('onDelete letter not recognised', E_USER_WARNING);
		$this->onDelete = $letter;
	}
	
	/** @return boolean Wheather delete should recursively delete values
	*/
	function getRecurseDelete() {
		return strPos('dv', $this->getOnDelete()) !== false;
	}
	
	/** @return boolean Wheather the user interface should verify
	* the recursive delete of values of this property, or some of
	* their properties recursively
	*/
	function getVerifyOnDelete() {
		if ($this->getOnDelete() == 'v') return true;
		if ($this->getOnDelete() != 'd') return false;

		Gen::includeClass($this->getType(), $this->getClassDir());
		$clsDes = PntClassDescriptor::getInstance($this->getType());
		return $clsDes->getVerifyOnDelete();
	}

}
?>