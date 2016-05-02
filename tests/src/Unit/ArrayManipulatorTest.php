<?php

namespace Drupal\Tests\array_manipulator\Unit;


use Drupal\array_manipulator\ArrayManipulator;
use Drupal\Tests\UnitTestCase;

class ArrayManipulatorTest extends UnitTestCase
{
    public function testManipulateOneManipulatorZeroDepth() {
        $arrayManipulator = new ArrayManipulator(['a']);
        $arrayManipulator->addManipulator(function($array) {
            return array_map('strtoupper', $array);
        });
        $this->assertEquals(['A'], $arrayManipulator->manipulate());

        $arrayManipulator = new ArrayManipulator([1,2,3]);
        $arrayManipulator->addManipulator(function($array) {
            return array_map(function($value) { return $value+$value; }, $array);
        });
        $this->assertEquals([2,4,6], $arrayManipulator->manipulate());

        // With key ----------------------------------------------------

        $arrayManipulator = new ArrayManipulator(['test' => 'a']);
        $arrayManipulator->addManipulator(function($array) {
            return array_map('strtoupper', $array);
        }, 0, 'test');
        $this->assertEquals(['test' => 'A'], $arrayManipulator->manipulate());

        $arrayManipulator = new ArrayManipulator(['test' => [1,2,3]]);
        $arrayManipulator->addManipulator(function($array) {
            return array_map(function($value) { return $value+$value; }, $array);
        }, 0, 'test');
        $this->assertEquals(['test' => [2,4,6]], $arrayManipulator->manipulate());
    }

    public function testManipulateOneManipulatorMoreDepth() {
        $arrayManipulator = new ArrayManipulator([
            [[1,2,3]],
            [[1,2,3]],
        ]);
        $arrayManipulator->addManipulator(function($array) {
            return array_map(function($value) { return $value+$value; }, $array);
        }, 2);
        $expected = [
            [[2,4,6]],
            [[2,4,6]],
        ];
        $this->assertEquals($expected, $arrayManipulator->manipulate());

        // With key ----------------------------------------------------

        $arrayManipulator = new ArrayManipulator([
            ['test' => [1,2,3]],
            ['test' => [1,2,3]],
        ]);
        $arrayManipulator->addManipulator(function($array) {
            return array_map(function($value) { return $value+$value; }, $array);
        }, 1, 'test');
        $expected = [
            ['test' => [2,4,6]],
            ['test' => [2,4,6]],
        ];
        $this->assertEquals($expected, $arrayManipulator->manipulate());
    }

    public function testManipulatMoreManipulatorsZeroDepth() {
        $arrayManipulator = new ArrayManipulator(['a']);
        $arrayManipulator->addManipulator(function($array) {
                                return array_map('strtoupper', $array);
                            })
                            ->addManipulator(function($array) {
                                return array_map(function($value) { return $value.$value; }, $array);
                            });

        $this->assertEquals(['AA'], $arrayManipulator->manipulate());

        $arrayManipulator = new ArrayManipulator([1,2,3]);
        $arrayManipulator->addManipulator(function($array) {
                                return array_map(function($value) { return $value+$value; }, $array);
                            })
                            ->addManipulator(function($array) {
                                return array_map(function($value) { return $value-($value/2); }, $array);
                            });
        $this->assertEquals([1,2,3], $arrayManipulator->manipulate());


        // With key ----------------------------------------------------

        $arrayManipulator = new ArrayManipulator(['test' => 'a']);
        $arrayManipulator->addManipulator(function($array) {
                                return array_map('strtoupper', $array);
                            }, 0, 'test')
                            ->addManipulator(function($array) {
                                return array_map(function($value) { return $value.$value; }, $array);
                            }, 0, 'test');
        $this->assertEquals(['test' => 'AA'], $arrayManipulator->manipulate());

        $arrayManipulator = new ArrayManipulator(['test' => [1,2,3]]);
        $arrayManipulator->addManipulator(function($array) {
                                return array_map(function($value) { return $value+$value; }, $array);
                            }, 0, 'test')
                            ->addManipulator(function($array) {
                                return array_map(function($value) { return $value-($value/2); }, $array);
                            }, 0, 'test');
        $this->assertEquals(['test' => [1,2,3]], $arrayManipulator->manipulate());
    }

    public function testManipulateMoreManipulatorsMoreDepth() {
        $arrayManipulator = new ArrayManipulator([
            [[1,2,3]],
            [[1,2,3]],
        ]);
        $arrayManipulator->addManipulator(function($array) {
                                return array_map(function($value) { return $value+$value; }, $array);
                            }, 2)
                            ->addManipulator(function($array) {
                                return array_map(function($value) { return $value-($value/2); }, $array);
                            }, 2);
        $expected = [
            [[1,2,3]],
            [[1,2,3]],
        ];
        $this->assertEquals($expected, $arrayManipulator->manipulate());

        // With key ----------------------------------------------------

        $arrayManipulator = new ArrayManipulator([
            ['test' => [1,2,3]],
            ['test' => [1,2,3]],
        ]);
        $arrayManipulator->addManipulator(function($array) {
                                return array_map(function($value) { return $value+$value; }, $array);
                            }, 1, 'test')
                            ->addManipulator(function($array) {
                                return array_map(function($value) { return $value-($value/2); }, $array);
                            }, 1, 'test');
        $expected = [
            ['test' => [1,2,3]],
            ['test' => [1,2,3]],
        ];
        $this->assertEquals($expected, $arrayManipulator->manipulate());
    }

    public function testManipulateMoreManipulatorsMultipleDepths() {
        $arrayManipulator = new ArrayManipulator([
            1 => [
                'step_name' => 'step 1',
                'sections' => [
                    [
                        'section_name' => 'section 1',
                        'fields' => [
                            'field_1' => [],
                            'field_2' => []
                        ]
                    ]
                ]
            ]
        ]);
        $arrayManipulator
            ->addManipulator(function($array) {
                return array_map(function($item){
                    $item['visible'] = false;

                    return $item;
                }, $array);
            }, 3, 'fields')
            ->addManipulator(function($array) {
                foreach($array as $sectionKey => $sectionSettings) {
                    $hiddenFieldCount = 0;

                    foreach($sectionSettings['fields'] as $fieldName => $fieldSettings) {
                        if($fieldSettings['visible'] == false) {
                            $hiddenFieldCount++;
                        }
                    }

                    $array[$sectionKey]['visible'] = $hiddenFieldCount < count($sectionSettings['fields']);
                }

                return $array;
            }, 2)
            ->addManipulator(function($array) {
                $hiddenSectionCount = 0;

                foreach($array['sections'] as $sectionKey => $sectionSettings) {
                    if($sectionSettings['visible'] == false) {
                        $hiddenSectionCount++;
                    }
                }

                $array['visible'] = $hiddenSectionCount < count($array['sections']);

                return $array;
            }, 1);
        $expected = [
            1 => [
                'step_name' => 'step 1',
                'sections' => [
                    [
                        'section_name' => 'section 1',
                        'fields' => [
                            'field_1' => [
                                'visible' => false
                            ],
                            'field_2' => [
                                'visible' => false
                            ]
                        ],
                        'visible' => false
                    ]
                ],
                'visible' => false
            ]
        ];
        $this->assertEquals($expected, $arrayManipulator->manipulate());
    }

    public function testMissmatchedFieldKey() {
        $arrayManipulator = new ArrayManipulator([1,2,3]);
        $arrayManipulator->addManipulator(function($array) {
            return $array;
        }, 0, 'not_here');
        $this->assertEquals([1,2,3], $arrayManipulator->manipulate());

        $arrayManipulator = new ArrayManipulator([[1,2,3]]);
        $arrayManipulator->addManipulator(function($array) {
            return $array;
        }, 1, 'not_here');
        $this->assertEquals([[1,2,3]], $arrayManipulator->manipulate());
    }

}