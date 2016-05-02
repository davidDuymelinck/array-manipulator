<?php

namespace Drupal\array_manipulator;


class ArrayManipulator
{
    protected $manipulatorCollection = [];
    protected $inputArray = [];

    public function __construct(array $array = null)
    {
        $this->inputArray = $array;
    }
    
    public function setArray(array $array) {
        $this->inputArray = $array;

        return $this;
    }

    public function addManipulator(Callable $manipulator, $depth = 0, $key = '') {
        $this->manipulatorCollection[] = [
            'manipulator' => $manipulator,
            'depth' => $depth,
            'key' => $key,
        ];
        
        return $this;
    }
    
    public function addManipulators(array $manipulators) {
        foreach($manipulators as $manipulatorArr) {
            $manipulator = array_shift($manipulatorArr);
            call_user_func_array($manipulator, $manipulatorArr);
        }
    }

    public function manipulate() {
        $outputArray = $this->inputArray;

        foreach($this->manipulatorCollection as $manipulator) {
            $outputArray = $this->manipulateSingle($outputArray, $manipulator);
        }

        return $outputArray;
    }

    private function manipulateSingle($item, $manipulator, $currentDepth = 0) {
        if($currentDepth == $manipulator['depth']) {
            if(isset($item[$manipulator['key']]) && is_array($item[$manipulator['key']])) {
                $outputArray = $item;
                
                $outputArray[$manipulator['key']] = $manipulator['manipulator']($item[$manipulator['key']]);
                
                return $outputArray;
            } else {
                return $manipulator['manipulator']($item);
            }
        } else {
            $outputArray = $item;
            if(is_array($item)) {
                foreach($item as $key => $value) {
                    if(is_array($value)) {
                        $newDepth = $currentDepth + 1;
                        if(!empty($manipulator['key']) && $newDepth == $manipulator['depth']){
                            if(isset($value[$manipulator['key']]) && is_array($value[$manipulator['key']])) {
                                $outputArray[$key][$manipulator['key']] = $manipulator['manipulator']($value[$manipulator['key']]);
                            }else {
                                $outputArray[$key] = $value;
                            }
                        } else{
                            $outputArray[$key] = $this->manipulateSingle($value, $manipulator, $newDepth);
                        }
                    }else {
                        $outputArray[$key] = $value;
                    }
                }
            }
            return $outputArray;
        }
    }
}