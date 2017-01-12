<?php
/**
 * Copyright (c) 2014, cnlevy <cnlevy@gmail.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @link http://github.com/cnlevy/jsonize
 * @version 1.0
 * @author cnlevy <cnlevy@gmail.com>
 */
class Jsonize {
	public function init() {}
	public static function encode($data, $attributes=true, $onlySpecifiedRelations = false, $onlySpecifiedAttributes = false) {
		return jsonizenc($data, $attributes, $onlySpecifiedRelations, $onlySpecifiedAttributes);
	}
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
	if ($data===null) return null;
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
