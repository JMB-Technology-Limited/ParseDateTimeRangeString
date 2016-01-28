<?php

namespace JMBTechnologyLimited\ParseDateTimeRangeString;

/**
 *
 * @link https://github.com/JMB-Technology-Limited/ParseDateTimeRangeString
 * @license https://raw.github.com/JMB-Technology-Limited/ParseDateTimeRangeString/master/LICENSE.txt 3-clause BSD
 * @copyright (c) 2013-2016, JMB Technology Limited, http://jmbtechnology.co.uk/
 * @author James Baster <james@jarofgreen.co.uk>
 */
class ParseDateTimeRangeStringEnGBStartDefaultTest extends \PHPUnit_Framework_TestCase{

	
	function dataProvider() {
		return array(
			array('15th dec  7pm', 0, 2016,12,15,19,0,  2016,12,15,19,0),
			array('15th dec  7pm', 60, 2016,12,15,19,0,  2016,12,15,19,1),
			array('15th dec  7pm', 60*60, 2016,12,15,19,0,  2016,12,15,20,0),
			array('15th dec  7pm', 2*60*60, 2016,12,15,19,0,  2016,12,15,21,0),
			array('15th dec  7pm', 3*60*60, 2016,12,15,19,0,  2016,12,15,22,0),
			array('15th dec  7pm', 4*60*60, 2016,12,15,19,0,  2016,12,15,23,0),
			array('15th dec  7pm', 5*60*60, 2016,12,15,19,0,  2016,12,16,0,0),
			array('15th dec  7pm', 6*60*60, 2016,12,15,19,0,  2016,12,16,1,0),
			array('15th dec  7pm', 7*60*60, 2016,12,15,19,0,  2016,12,16,2,0),

		);
	}
	
	/**
	* @dataProvider dataProvider
	*/ 
	function testStartDefault($stringIn, $defaultRangeLengthSeconds, $year, $month, $day, $hour, $minute, $eyear, $emonth, $eday, $ehour, $eminute) {
		$dt = new \DateTime;
		$dt->setTimezone(new \DateTimeZone("Europe/London"));
		$dt->setDate(2016, 1, 1);
		$dt->setTime(13, 0, 0);
		$parse = new ParseDateTimeRangeString($dt, "Europe/London", "EN", "GB");
        $parse->setDefaultRangeLengthSeconds($defaultRangeLengthSeconds);
		$result = $parse->parse($stringIn);
		$this->assertFalse(is_null($result->getStart()));
		$this->assertEquals($year, $result->getStart()->format('Y'));
		$this->assertEquals($month, $result->getStart()->format('n'));
		$this->assertEquals($day, $result->getStart()->format('j'));
		$this->assertEquals($hour, $result->getStart()->format('G'));
		$this->assertEquals($minute, $result->getStart()->format('i'));
        $this->assertFalse($result->getEndWasSpecified());
        $this->assertEquals($eyear, $result->getEnd()->format('Y'));
        $this->assertEquals($emonth, $result->getEnd()->format('n'));
        $this->assertEquals($eday, $result->getEnd()->format('j'));
        $this->assertEquals($ehour, $result->getEnd()->format('G'));
        $this->assertEquals($eminute, $result->getEnd()->format('i'));
	}


	
}

