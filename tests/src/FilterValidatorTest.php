<?php
namespace tests;

use Germania\OrderDispatcher\FilterValidator;
use Germania\OrderDispatcher\ValidatorInterface;

class FilterValidatorTest extends \PHPUnit\Framework\TestCase
{

    public function testInstantiation()
    {
        $sut = new FilterValidator(array());
        $this->assertInstanceOf(ValidatorInterface::class, $sut );

        return $sut;
    }


    /**
     * @depends testInstantiation
     */
    public function testHardCodedData( $sut )
    {
        $sut->setValidation([
          "email"  =>  FILTER_VALIDATE_EMAIL,
          "company" =>  FILTER_SANITIZE_STRING,
          "shouldBeAdded" =>  FILTER_SANITIZE_STRING
        ]);
        $this->assertInstanceOf(ValidatorInterface::class, $sut );

        $input = array(
            'email'   => 100, // Not email
            'company' => "ACME Corp.",
            'foo'     => "bar",
            // 'shouldBeAdded' missing
        );

        $result = $sut->validate($input);
        $this->assertFalse($result["email"]);

        $this->assertEquals($input["company"], $result["company"]);

        $this->assertArrayHasKey("shouldBeAdded", $result);
        $this->assertEquals($input["foo"], $result["foo"]);
    }




}
