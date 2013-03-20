<?php

namespace FakerContext;

require_once __DIR__ . '/../vendor/autoload.php';

use FakerContext\FakerContext,
    Behat\Gherkin\Node\TableNode,
    Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class FakerContextTest extends \PHPUnit_Framework_TestCase
{
    public function providerTransformTestData()
    {
        return array(
            array(new TableNode(), true),
            array('test', false),
            array(null, false)
        );
    }

    /**
     * @dataProvider providerTransformTestData
     * @param $arg
     * @param $isTable
     */
    public function testTransformTestData($arg, $isTable)
    {
        $fakerContext = $this->getMock('\FakerContext\FakerContext', array('transformTable', 'transformValue'));
        $fakerContext->expects($isTable ? $this->once() : $this->never())
                     ->method('transformTable');

        $fakerContext->expects($isTable ? $this->never() : $this->once())
            ->method('transformValue');

        $fakerContext->transformTestData($arg);
    }

    public function providerGenerateTestData()
    {
        return array(
            array('text', null),
            array('text', 30),
            array('name', null)
        );
    }

    /**
     * @dataProvider providerGenerateTestData
     * @param $fakerProperty
     * @param $fakerParam
     */
    public function testGenerateTestData($fakerProperty, $fakerParam)
    {
        $faker = \Faker\Factory::create();
        $faker->seed(1234);

        if ($fakerProperty) {
            if ($fakerParam) {
                $result = $faker->$fakerProperty($fakerParam);
            } else {
                $result = $faker->$fakerProperty;
            }
        }

        $faker->seed(1234);

        $fakerContext = $this->getMock('\FakerContext\FakerContext', array('getFaker'));

        $fakerContext->expects($this->any())
                     ->method('getFaker')
                     ->will($this->returnValue($faker));

        $this->assertEquals(
            $fakerContext->generateTestData($fakerProperty, $fakerParam),
            $result
        );
    }

    public function providerGenerateTestDataException()
    {
        return array(
            array(array(), array()),
            array(null, null),
            array('', ''),
            array(1, 2)
        );
    }

    /**
     * @dataProvider providerGenerateTestDataException
     * @expectedException InvalidArgumentException
     * @param $fakerProperty
     * @param $fakerParam
     */
    public function testGenerateTestDataException($fakerProperty, $fakerParam)
    {
        $faker = \Faker\Factory::create();
        $fakerContext = $this->getMock('\FakerContext\FakerContext', array('getFaker'));

        $fakerContext->expects($this->any())
            ->method('getFaker')
            ->will($this->returnValue($faker));

        $fakerContext->generateTestData($fakerProperty, $fakerParam);
    }
}