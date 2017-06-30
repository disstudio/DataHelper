<?php

/**
 * Description of CDataHelper
 *
 * @author Dumchikov Ihor <disstudio1990@gmail.com>
 */
class CDataHelper {
    
    /**
     * Return model attributes as array
     * @param mixed $data Array of models or single model (instance of CActiveRecord)
     * @param mixed $key key attribute for main array; false to get array without keys; true to use model pk.
     * @return array Array of arrays with models attribute values, indexed by attribute names.
     * @author Dumchikov Ihor <disstudio1990@gmail.com>
     */
    public static function getData($data, $attributes = true, $key = false) {
        if($data === null) return null;
        
        if(!is_array($data)) {
            if(is_array($attributes)) {
                return self::resolveAttributes($data, $attributes);
            } elseif(is_array($data)) {
                return $data;
            } elseif(is_object($data)) {
                return get_object_vars($data);
            }
        } else {
            $ar_models = [];
            
            foreach($data as $model_key => $item) {
                $index = null;
                if($key===true) {
                    $index = $model_key;
                } elseif(is_string($key)) {
                    $index = self::getAttrValue($item, $key);
                }
                
                if($index) {
                    if(is_array($attributes)) {
                        $ar_models[$index] = self::resolveAttributes($item, $attributes, $model_key);
                    } elseif(is_object($item)) {
                        $ar_models[$index] = get_object_vars($item);
                    } elseif(is_array($item)) {
                        $ar_models[$index] = $item;
                    }
                } else {
                    if(is_array($attributes)) {
                        $ar_models[] = self::resolveAttributes($item, $attributes, $model_key);
                    } elseif(is_object($item)) {
                        $ar_models[] = get_object_vars($item);
                    } elseif(is_array($item)) {
                        $ar_models[] = $item;
                    }
                }
            }
            
            return $ar_models;
        }
    }
    
    /**
     * Get attributes and process it according to rules
     * @param mixed $data Input data
     * @param array $names Array with rules
     * @param mixed $key Key value
     */
    public static function resolveAttributes($data, $names, $key = null) {
        
        if($data===null) return null;
        
        $attributes = [];
        foreach($names as $name_key => $name) {
            
            if(is_array($name)) {
                // Nested attributes
                $parts = explode(' ', $name_key);
                
                // Recursive value get
                $attr_name = array_shift($parts);
                $attr_value = self::getAttrValue($data, $attr_name);
                
                if(is_array($attr_value) && in_array('array', $parts)) {
                    // Get array
                    $attr = [];
                    foreach($attr_value as $attr_item) {
                        $attr[] = self::resolveAttributes($attr_item, $name);
                    }
                } else {
                    $attr = self::resolveAttributes($attr_value, $name);
                }
            } elseif(is_callable($name)) {
                // Closure
                
                $parts = explode(' ', $name_key);
                
                // Get value
                $attr_name = array_shift($parts);
                $attr = call_user_func_array($name, ['data' => self::getAttrValue($data, $attr_name)]);
            } else {
                $parts = explode(' ', $name);
                
                // Get the attribute value from name
                $attr_name = array_shift($parts);
                if($attr_name === '%key%') {
                    $attr = $key;
                } else {
                    $attr = self::getAttrValue($data, $attr_name);
                }
            }
            
            // Check for some modifications
            foreach($parts as $part) {
                
                if(isset($new_attr_name)) {
                    // attribute alias
                    $attr_name = $part;
                    unset($new_attr_name);
                } else {
                    switch(strtolower($part)) {
                        case 'int': // cast to int
                            if(is_scalar($attr)) $attr = (int) $attr;
                            break;
                        case 'float': // cast to float
                            if(is_scalar($attr)) $attr = (float) $attr;
                            break;
                        case 'as': // attribute alias; check the next part for attribute name
                            $new_attr_name = true;
                            break;
                    }
                }
            }
            unset($new_attr_name);
            
            if($attr_name==='.') {
                $attributes = array_merge($attributes, $attr);
            } else {
                $attributes[$attr_name] = $attr;
            }
            
        }
        
        return $attributes;
    }
    
    /**
     * Recursively get attribute name from array/object (e.g. "someObject.someProperty.value")
     * @param type $data
     * @param string $attribute Attribute name
     * @return mixed Attribute value or null if failed.
     */
    public static function getAttrValue($data, $attribute) {
        if($attribute==='.') {
            return $data;
        } elseif(strpos($attribute, '.')===false) {
            if(is_object($data)) {
                return $data->$attribute;
            } elseif(is_array($data)) {
                return array_key_exists($attribute, $data) ? $data[$attribute] : null;
            } else {
                return null;
            }
        } else {
            $parts = explode('.', $attribute);
            foreach($parts as $part) {
                if(is_object($data)) {
                    $data = $data->$part;
                } elseif(is_array($data)) {
                    $data = array_key_exists($part, $data) ? $data[$part] : null;
                } else {
                    return null;
                }
            }
            return $data;
        }
    }
}
