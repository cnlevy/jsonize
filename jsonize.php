<?php
/**
 * @link http://github.com/cnlevy/jsonize
 * @version 1.0
 * @author cnlevy <cnlevy@gmail.com>
 */
class Jsonize {
	public function init() {}
}

/**
 * calls json_encode(jsonize())
 * @see jsonize
 */
function jsonizenc($data, $attributes=true, $onlySpecifiedRelations = false, $onlySpecifiedAttributes = false) {
	return json_encode(jsonize($data, $attributes, $onlySpecifiedRelations, $onlySpecifiedAttributes));
}
/**
 * 
 * @param mixed $data an ActiveRecord or array of ActiveRecord
 * @param $attributes array of relations to be loaded e.g array('client','items'=>array('product')) // nested relations
 * @param bool $onlySpecifiedRelations if to send all loaded relations or only the ones specified in $attributes 
 * @param bool $onlySpecifiedAttributes if to send all attributes or only the ones specified in $attributes 
 */
function jsonize($data, $attributes=true, $onlySpecifiedRelations = false, $onlySpecifiedAttributes = false) {
	if (is_array($data)) { // can be an empty array
		$json=array();
		foreach($data as $key => $ar) {
			$json[$key]=jsonize($ar,$attributes, $onlySpecifiedRelations, $onlySpecifiedAttributes);
		}
		return $json;
	}
	if ($data==null) return null;
	if (!is_object($data)) return $data; //json_encode($data);
	$ar=$data;
	//Yii::log('jsonize class '.get_class($ar));
	$relations=$ar->relations();
	if ($onlySpecifiedAttributes) {
		$json=array();
	} else {
		$json=$ar->getAttributes(); // on va dire sans ca ?
		foreach($json as $name=>$value) {
			if (is_object($value) || is_array($value)) { // relation defined by virtual attribute 
				if ($onlySpecifiedRelations) 
					unset($json[$name]); // will be retrieved later 
				else { // can lead to stack overflow if it loads recursively more objects, therefore, only load explicitly specified relations
					$json[$name]=jsonize($value, @$attributes[$name]?:true, true, $onlySpecifiedAttributes);
				}
			}
		}
		if (!$onlySpecifiedRelations) { // include eagerly loaded relations
			foreach($relations as $relName=>$relDef) {
				if ($ar->hasRelated($relName)) {
					$rel = $ar->getRelated($relName);
					$spec = true; // (!is_array($rel) && $rel && $rel->isNewRecord)?true:$onlySpecifiedRelations;
					$json[$relName]=jsonize($rel, @$attributes[$relName]?:true, $spec, $onlySpecifiedAttributes);
				}
			}
		}
	}
	// include explicitly requested relations or virtual columns or attributes 
	if (is_array($attributes)) {
		foreach($attributes as $name=>$value) {
			if (is_numeric($name)) {// simple relation, handle scalar values, activerecord objects and arrays of ActiveRecord
				$related = $ar->$value;
				$json[$value]=(is_object($related) || is_array($related))?jsonize($related, true, true, $onlySpecifiedAttributes):$related;
			} else // nested rels
				$json[$name]=jsonize($ar->$name, $value, true, $onlySpecifiedAttributes);
		}
	}
	return $json;
}
