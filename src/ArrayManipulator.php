<?php

namespace Drupal\array_manipulator;


class ArrayManipulator
{
    protected $manipulatorCollection = [];
    protected $inputArray = [];
    protected $forcedDepthKeys = [];

    public function __construct(array $array = null)
    {
        $this->inputArray = $array;
    }
    
    public function setArray(array $array) {
        $this->inputArray = $array;

        return $this;
    }
    
    public function forceKeyOnDepth($key, $depth) {
        $this->forcedDepthKeys[$depth] = $key;
        
        return $this;
    }

    public function addManipulator(Callable $manipulator, $key = '', $depth = 0) {
        $this->manipulatorCollection[] = [
            'manipulator' => $manipulator,
            'depth' => $depth,
            'key' => $key,
        ];
        
        return $this;
    }
    
    public function addManipulators(array $manipulators) {
        foreach($manipulators as $manipulator) {
            switch(count($manipulator)) {
                case 1: $this->addManipulator($manipulator[0]); break;
                case 2: $this->addManipulator($manipulator[0], $manipulator[1]); break;
                case 3: $this->addManipulator($manipulator[0], $manipulator[1], $manipulator[2]); break;
            }
        }
    }
    
    public function manipulate() {
        $outputArray = $this->inputArray;

        foreach($this->manipulatorCollection as $item) {
            if($item['depth'] == 0) {
                if($item['key'] == '') {
                    $outputArray = array_map($item['manipulator'], $outputArray);
                } else {
                    $outputArray[$item['key']] = $item['manipulator']($outputArray[$item['key']]);
                }
            } else {
                $iterator = new \RecursiveIteratorIterator( new \RecursiveArrayIterator($outputArray), \RecursiveIteratorIterator::SELF_FIRST );
                $keys = [];
                $keyDepth = -1;

                foreach($iterator as $key => $value) {
                    $loopDepth = $iterator->getDepth();
                    if($loopDepth == $item['depth']) {
                        $strArray = '$outputArray';
                        
                        if(count($keys) < $item['depth']) {
                            continue;
                        }
                        
                        foreach ($keys as $key) {
                            $strArray .= '[';
                            $strArray .= is_int($key) ? $key : '"' . $key . '"';
                            $strArray .= ']';
                        }
                        
                        if(!empty($item['key'])) {
                            $strArray .= '["'.$item['key'].'"]';
                        }

                        $valueOld = eval('return ' . $strArray . ';');
                        $valueNew = $item['manipulator']($valueOld);

                        $strArrayNew = $strArray . '=';
                        $strArrayNew .= is_array($valueNew) ? var_export($valueNew, true) : $valueNew;
                        eval($strArrayNew.';');

                        $keys = [];
                        $keyDepth = -1;
                    } elseif($iterator->getDepth() > $item['depth']) {
                        $keys = [];
                        $keyDepth = -1;
                    } else {
                        if($keyDepth < $loopDepth) {
                            $keys[] = isset($this->forcedDepthKeys[$loopDepth]) ? $this->forcedDepthKeys[$loopDepth] : $key;
                            $keyDepth = $loopDepth;
                        }
                    }
                }
            }
        }

        return $outputArray;
    }
}