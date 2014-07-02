<?php

namespace FakerContext;

require_once __DIR__ . '/../vendor/autoload.php';

use FakerContext\FakerContext,
    Behat\Gherkin\Node\TableNode,
    Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Faker\Factory;

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

    public function providerTestTransformValueGenerate()
    {
        return array(
            // valid regex for test data generation
            array('[hello=text]', $this->getSeededFaker()->text),
            array('[hello=text(100)]', $this->getSeededFaker()->text(100)),
            array('[blah=name]', $this->getSeededFaker()->name),
            array('[1=company]', $this->getSeededFaker()->company),
            array('[address=address]', $this->getSeededFaker()->address),
            array('[$=email]', $this->getSeededFaker()->email),
            array('[d=date("Y-m-d H:i:s")]', $this->getSeededFaker()->date("Y-m-d H:i:s")),

            // invalid regex
            array('[hello=text', '[hello=text'),
            array('[=text]', '[=text]'),
            array('hello=text', 'hello=text')
        );
    }

    /**
     * @dataProvider providerTestTransformValueGenerate
     * @param $testString
     * @param $expected
     */
    public function testTransformValueGenerate($testString, $expected)
    {
        $fakerContext = $this->getMock('\FakerContext\FakerContext', array('getFaker'));

        $fakerContext->expects($this->any())
                     ->method('getFaker')
                     ->will($this->returnValue($this->getSeededFaker()));

        $actual = $fakerContext->transformValue($testString);

        $this->assertEquals(
            $expected,
            $actual
        );
    }

    public function providerTestTransformValueRetrieve()
    {
        return array(
            // valid regex for test data retrieval
            array('[hello]', 'test'),
            array('[t]', 'foo'),
            array('[$]', 'bar'),

            // invalid regex
            array('[test,]', '[test,]'),
            array('asdlfkj', 'asdlfkj'),
            array(1,1)
        );
    }

    /**
     * @dataProvider providerTestTransformValueRetrieve
     * @param $testString
     * @param $expected
     */
    public function testTransformValueRetrieve($testString, $expected)
    {
        $fakerContext = $this->getMock('\FakerContext\FakerContext', array('getTestData'));

        $fakerContext->expects($this->any())
            ->method('getTestData')
            ->will($this->returnValue($expected));

        $this->assertEquals(
            $expected,
            $fakerContext->transformValue($testString)
        );
    }

    private function getSeededFaker()
    {
        if (!$this->faker) {
            $this->faker = \Faker\Factory::create();
        }

        $this->faker->seed(1234);
        return $this->faker;
    }
}
