<?php

use Behat\Behat\Context\BehatContext,
    Behat\Gherkin\Node\TableNode;

class FakerContext extends BehatContext
{
    const GENERATE_TEST_DATA_REGEX = '~\[([0-9]*)=([a-zA-Z]*)\(([0-9]*)\)\]~';
    const GET_TEST_DATA_REGEX = '~\[([a-zA-Z]*)\]~';
    private $generatedTestData;

    /**
     * @BeforeScenario
     */
    public function tearDown($event)
    {
        $this->generatedTestData = array();
        $this->faker = Faker\Factory::create();
    }

    /**
     * @Transform /^([^"]*)$/
     */
    public function transformTestData($arg)
    {
        if ($arg instanceof TableNode) {
            return $this->transformTable($arg);
        } else {
            return $this->transformValue($arg);
        }
    }

    private function transformTable(TableNode $table)
    {
        $rows = array();

        foreach ($table->getRows() as $row) {
            foreach ($row as $key => $value) {
                $row[$key] = $this->transformValue($value);
            }

            $rows[] = $row;
        }

        $tableNode = new TableNode();
        $tableNode->setRows($rows);
        return $tableNode;
    }

    private function transformValue($string)
    {
        if (preg_match(self::GENERATE_TEST_DATA_REGEX, $string, $matches)) {
            $key = $matches[1];
            $fakerProperty = $matches[2];
            $fakerParameter = $matches[3];

            $testData = $this->generateTestData($fakerProperty, $fakerParameter);

            $this->setTestData($key,$testData);

            return $testData;
        } else if (preg_match(self::GET_TEST_DATA_REGEX, $string, $matches)) {
            $position = $matches[1];
            $testData = $this->getTestData($position);

            return $testData;
        }

        return $string;
    }

    /**
     * @param $fakerProperty
     * @param null $fakerParameter
     * @return mixed
     */
    private function generateTestData($fakerProperty, $fakerParameter = null)
    {
        if ($fakerParameter) {
            return $this->faker->$fakerProperty($fakerParameter);
        } else {
            return $this->faker->$fakerProperty;
        }
    }

    /**
     * @param $key
     * @param $value
     */
    private function setTestData($key, $value)
    {
        $this->generatedTestData[$key] = $value;
    }

    /**
     * @param $position
     * @return mixed
     */
    private function getTestData($position)
    {
        return $this->generatedTestData[$position];
    }
}
