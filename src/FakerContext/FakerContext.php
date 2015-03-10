<?php
// TODO: add support for generating test data within sentences ie "[$c=company] is my favorite company"

namespace FakerContext;

use Behat\Behat\Context\BehatContext,
    Behat\Gherkin\Node\TableNode;

class FakerContext extends BehatContext
{
    const GENERATE_TEST_DATA_REGEX = '~\[([$a-zA-Z0-9]+)=([a-zA-Z]+)(\(([,\'" 0-9a-zA-Z:-]+)\))?\]~';
    const GET_TEST_DATA_REGEX = '~\[([$a-zA-Z0-9]+)\]~';
    protected $_generatedTestData;

    /**
     * @BeforeScenario
     */
    public function setUp($event)
    {
        $this->_generatedTestData = array();
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

    /**
     * @param $fakerProperty
     * @param array $fakerParameters
     * @return mixed
     */
    public function generateTestData($fakerProperty, $fakerParameters = array())
    {
        $fakerProperty = (string) $fakerProperty;

        if ($fakerParameters) {
            if (!is_array($fakerParameters)) {
                throw new \InvalidArgumentException(
                    "generateTestData function only supports arrays for second parameter"
                );
            }

            $result = call_user_func_array(array($this->getFaker(), $fakerProperty), $fakerParameters);

            if (is_a($result, 'DateTime')) {
                $result = $result->format('Y-m-d H:i:s');
            }

            return $result;
        } else {
            return $this->getFaker()->$fakerProperty;
        }
    }

    /**
     * @param TableNode $table
     * @return TableNode
     */
    public function transformTable(TableNode $table)
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

    /**
     * @param $string
     * @return mixed
     */
    public function transformValue($string)
    {
        if (preg_match(self::GENERATE_TEST_DATA_REGEX, $string, $matches)) {
            $key = $matches[1];
            $fakerProperty = $matches[2];

            if (isset($matches[4])) {
                $fakerParameters = array_map("trim", explode(',', str_replace(array('"', "'"), "", $matches[4])));
            } else {
                $fakerParameters = array();
            }

            $testData = $this->generateTestData($fakerProperty, $fakerParameters);

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
     * @param $key
     * @param $value
     */
    protected function setTestData($key, $value)
    {
        $this->_generatedTestData[$key] = $value;
    }

    /**
     * @param $position
     * @return mixed
     */
    protected function getTestData($position)
    {
        return $this->_generatedTestData[$position];
    }

    protected function getFaker()
    {
        if (!$this->faker) {
            $this->faker = \Faker\Factory::create();
        }

        return $this->faker;
    }
}
