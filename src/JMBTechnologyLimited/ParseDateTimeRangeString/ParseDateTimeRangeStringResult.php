<?php

namespace JMBTechnologyLimited\ParseDateTimeRangeString;

/**
 *
 * @link https://github.com/JMB-Technology-Limited/ParseDateTimeRangeString
 * @license https://raw.github.com/JMB-Technology-Limited/ParseDateTimeRangeString/master/LICENSE.txt 3-clause BSD
 * @copyright (c) 2013-2014, JMB Technology Limited, http://jmbtechnology.co.uk/
 * @author James Baster <james@jarofgreen.co.uk>
 */
class ParseDateTimeRangeStringResult {
	/** @var \DateTime **/
	protected $start;
	/** @var \DateTime **/
	protected $end;

    protected $endWasSpecified;
	
	function __construct(\DateTime $start, \DateTime $end, $endWasSpecified = true) {
		$this->start = $start;
		$this->end = $end;
        $this->endWasSpecified = $endWasSpecified;
	}

	public function getStart() {
		return $this->start;
	}

	public function getEnd() {
		return $this->end;
	}

    /**
     * @return boolean
     */
    public function getEndWasSpecified()
    {
        return $this->endWasSpecified;
    }

}
	