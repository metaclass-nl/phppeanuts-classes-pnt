<?php 
// Copyright (c) MetaClass Groningen, 2003-2012


Gen::includeClass('PntSecurityManager', 'pnt/secu');

/** Class used with testing the default reasoning of PntSecurityManager.
* All check methods will return the value of the member variable
* with the same name as the method, if it is not set, call the parent implementation.
* @package pnt/test/auth
*/
#[\AllowDynamicProperties]
class TestSecurityManager extends PntSecurityManager {

    function checkAccessRef($handler, $request, $scout) {
        $pntRef = $request->getRequestParam('pntRef');
        if (null === $pntRef) return null;

        return parent::checkAccessRef($handler, $request, $scout);
    }

	function checkAccessApp($path) {
		if (isSet($this->checkAccessApp)) return $this->checkAccessApp;
		return parent::checkAccessApp($path);
	}

	function checkViewInDomainDir($path) {
		if (isSet($this->checkViewInDomainDir)) return $this->checkViewInDomainDir;
		return parent::checkViewInDomainDir($path);
	}

	function checkModifyInDomainDir($path) {
		if (isSet($this->checkModifyInDomainDir)) return $this->checkModifyInDomainDir;
		return parent::checkModifyInDomainDir($path);
	}

	
	function checkViewClass($objects, $clsDesc) {
		if (isSet($this->checkViewClass)) 
			return $this->checkViewClass == 'class' 
				? $clsDesc->getName() : $this->checkViewClass;
		return parent::checkViewClass($objects, $clsDesc);
	}

	function checkModifyClass($objects, $clsDesc) {
		if (isSet($this->checkModifyClass)) return $this->checkModifyClass;
		return parent::checkModifyClass($objects, $clsDesc);
	}

	function checkCreateClass($objects, $clsDesc) {
		if (isSet($this->checkCreateClass)) return $this->checkCreateClass;
		return parent::checkCreateClass($objects, $clsDesc);
	}

	function checkEditClass($objects, $clsDesc) {
		if (isSet($this->checkEditClass)) return $this->checkEditClass;
		return parent::checkEditClass($objects, $clsDesc);
	}

	function checkDeleteClass($objects, $clsDesc) {
		if (isSet($this->checkDeleteClass)) return $this->checkDeleteClass;
		return parent::checkDeleteClass($objects, $clsDesc);
	}


	function checkViewObject($object, $clsDesc) {
		if (isSet($this->checkViewObject)) return $this->checkViewObject;
		return parent::checkViewObject($object, $clsDesc);
	}

	function checkCreateObject($object, $clsDesc) {
		if (isSet($this->checkCreateObject)) return $this->checkCreateObject;
		return parent::checkCreateObject($object, $clsDesc);
	}

	function checkEditObject($object, $clsDesc) {
		if (isSet($this->checkEditObject)) return $this->checkEditObject;
		return parent::checkEditObject($object, $clsDesc);
	}

	function checkDeleteObject($object, $clsDesc) {
		if (isSet($this->checkDeleteObject)) return $this->checkDeleteObject;
		return parent::checkDeleteObject($object, $clsDesc);
	}


	function checkViewProperty($object, $propDesc) {
		if (isSet($this->checkViewProperty)) return $this->checkViewProperty;
		return parent::checkViewProperty($object, $propDesc);
	}
	
	function checkViewPropertyValues($object, $propDesc) {
		if (isSet($this->checkViewPropertyValues)) return $this->checkViewPropertyValues;
		return parent::checkViewPropertyValues($object, $propDesc);
	}
	
	function checkEditProperty($object, $propDesc) {
		if (isSet($this->checkEditProperty)) {
			//print "<br>\n". $propDesc->getLabel();
			return $this->checkEditProperty;
		}
		return parent::checkEditProperty($object, $propDesc);
	}

	function checkSelectProperty($objects, $clsDesc, $propertyName) {
		if (isSet($this->checkSelectProperty)) return $this->checkSelectProperty;
		return parent::checkSelectProperty($objects, $clsDesc, $propertyName);
	}
	
}
?>