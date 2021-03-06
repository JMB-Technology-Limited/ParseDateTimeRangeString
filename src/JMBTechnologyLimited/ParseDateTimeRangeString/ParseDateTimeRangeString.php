<?php

namespace JMBTechnologyLimited\ParseDateTimeRangeString;

/**
 *
 * @link https://github.com/JMB-Technology-Limited/ParseDateTimeRangeString
 * @license https://raw.github.com/JMB-Technology-Limited/ParseDateTimeRangeString/master/LICENSE.txt 3-clause BSD
 * @copyright (c) 2013-2015, JMB Technology Limited, http://jmbtechnology.co.uk/
 * @author James Baster <james@jarofgreen.co.uk>
 */
class ParseDateTimeRangeString {
	
	protected $yearsGoingBack = 1;
	protected $yearsGoingForwards = 10;

	protected $timezone;
	/** @var \DateTime **/
	protected $currentDateTime;

    protected $defaultRangeLengthSeconds = 7200; // 2 hours

	public function __construct($currentDateTime,  $timezone='UTC', $language="EN", $country="GB") {
	
		$this->currentDateTime = $currentDateTime;
		$this->currentDateTime->setTimezone(new \DateTimeZone($timezone));
		$this->timezone = $timezone;
		
	}
	
	public function parse($string) {
		
		$string = str_replace("\t", " ", $string);
		$string = str_replace("\n", " ", $string);
		$string = str_replace("\r", " ", $string);
		$string = str_replace(",", " ", $string);
		$string = str_replace("  ", " ", $string);

        $endWasSpecified = false;

		$start = clone $this->currentDateTime;

		$stringStart = $string;
		$stringEnd="";
		if (strpos(strtolower($stringStart), " to ") !== false){
			list($stringStart, $stringEnd) = explode(" to ", strtolower($stringStart));
		} else if (strpos(strtolower($stringStart), " for ") !== false) {
			list($stringStart, $stringEnd) = explode(" for ", strtolower($stringStart));
        } else if (strpos($stringStart, " - ") !== false) {
			list($stringStart, $stringEnd) = explode(" - ", strtolower($stringStart));
		} else if (strpos(strtolower($stringStart), "between ") !== false && strpos(strtolower($stringStart), " and ") !== false) {
			$bits = explode("between", strtolower($stringStart));
			list($stringStart, $stringEnd) = explode(" and ", strtolower($bits[1]));
			$stringStart = $bits[0]." ".$stringStart;
		}

        $this->parseString($stringStart, $start);
        $end = clone $start;
        if (trim($stringEnd)) {
            if ($this->parseString($stringEnd, $end)) {
                $endWasSpecified = true;
            }
            if ($this->parseStringForInterval($stringEnd, $end)) {
                $endWasSpecified = true;
            }
        } else {
            if (!$this->parseStringForInterval($stringStart, $end)) {
                $end->add(new \DateInterval("PT".$this->defaultRangeLengthSeconds."S"));
                $endWasSpecified = false;
            } else {
                $endWasSpecified = true;
            }
        }

        return new ParseDateTimeRangeStringResult($start, $end, $endWasSpecified);
	}
	
	protected $monthNames = array(
		1=>array('January','Jan'),
		2=>array('February','Febuary','Feb'),
		3=>array('March','Mar'),
		4=>array('April','Apr'),
		5=>array('May','May'),
		6=>array('June','Jun'),
		7=>array('July','Jul'),
		8=>array('August','Aug'),
		9=>array('September','Sept','Sep'),
		10=>array('October','Oct'),
		11=>array('November','Nov'),
		12=>array('December','Dec'),
	);
	
	protected $dayOfWeekNames = array(
		1=>array('Monday','Mon'),
		2=>array('Tuesday','Tue'),
		3=>array('Wednesday','Wed'),
		4=>array('Thursday','Thu'),
		5=>array('Friday','Fri'),
		6=>array('Saturday','Sat'),
		7=>array('Sunday','Sun'),
	);

	protected $weekOfMonthNames = array(
		1=>array('1st','first'),
		2=>array('2nd','second'),
		3=>array('3rd','third'),
		4=>array('4th','fourth'),
		5=>array('5th'),
	);

	protected $dayOfMonthNames = array(
			31=>array('31st'),
			30=>array('30th'),
			29=>array('29th'),
			28=>array('28th'),
			27=>array('27th'),
			26=>array('26th'),
			25=>array('25th'),
			24=>array('24th'),
			23=>array('23rd'),
			22=>array('22nd'),
			21=>array('21st'),
			20=>array('20th'),
			19=>array('19th'),
			18=>array('18th'),
			17=>array('17th'),
			16=>array('16th'),
			15=>array('15th'),
			14=>array('14th'),
			13=>array('13th'),
			12=>array('12th'),
			11=>array('11th'),
			10=>array('10th'),
			9=>array('9th'),
			8=>array('8th'),
			7=>array('7th'),
			6=>array('6th'),
			5=>array('5th'),
			4=>array('4th'),
			3=>array('3rd'),
			2=>array('2nd'),
			1=>array('1st'),
		);

	protected $numbersToWords = array(
		1=>array('one'),
		2=>array('two'),
		3=>array('three'),
		4=>array('four'),
		5=>array('five'),
		6=>array('six'),
		7=>array('seven'),
		8=>array('eight'),
		9=>array('nine'),
		10=>array('ten'),
		15=>array('fithteen'),
		30=>array('thirty'),
		45=>array('fortyfive'),
	);

    protected function parseString($string, \DateTime $dateTime) {
        $r = false;
        if ($this->parseStringForDate($string, $dateTime)) {
            $r = true;
        }
        if ($this->parseStringForTime($string, $dateTime)) {
            $r = true;
        }
        return $r;
    }

    protected function parseStringForDate($string, \DateTime $dateTime) {

        $foundAnything = false;

        // some short hands
		
		foreach(array('today','toady') as $token) {
			if (strpos(strtolower($string), $token) !== false) {
				return true;
			}
		}
		
		foreach(array('tomorrow','tomorow','tommorrow') as $token) {
			if (strpos(strtolower($string), $token) !== false) {
				$dateTime->add(new \DateInterval("P1D"));
				return true;
			}
		}

		
		// Y/M/D in short format ... year at start
		$matches = array();
		if (preg_match("/(\d{4})(\/|-)(\d{1,2})(\/|-)(\d{1,2})/", $string, $matches)) {
			return $this->parseShortFormatForDate($matches[1], $matches[3], $matches[5], $dateTime);
		}

		// D/M/Y in short format ... year at end
		$matches = array();
		if (preg_match("/(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{4})/", $string, $matches)) {
			return $this->parseShortFormatForDate($matches[5], $matches[3], $matches[1], $dateTime);
		}
		
		// D/M/Y or Y/M/D in short format ... year unknown, assume at end
		$matches = array();
		if (preg_match("/(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{2})/", $string, $matches)) {
			return $this->parseShortFormatForDate($matches[5], $matches[3], $matches[1], $dateTime);
		}
		
		
		// year
		if (strpos(strtolower($string), "next year") !== false) {
			$dateTime->setDate($dateTime->format('Y')+1,  $dateTime->format('n'), $dateTime->format('j'));
		}
		
		
		$now = clone $this->currentDateTime;
		$from = $now->format('Y') - $this->yearsGoingBack;
		$to = $now->format('Y') + $this->yearsGoingForwards;
		for($i = $from; $i <= $to; $i++) {
			if (strpos(strtolower($string), strtolower($i)) !== false) {
					$string = str_ireplace($i, " ", $string);
					$dateTime->setDate($i,  $dateTime->format('n'), $dateTime->format('j'));
					// We are only setting year here, so can't return, may miss day and month.
                    $foundAnything = true;
				}
		}
		
		// month as word
		for ($i = 1; $i <= 12; $i++) {
			foreach($this->monthNames[$i] as $monthName) {
				$matches = array();
				// month as word with a 1 or 2 digit number in front, assume it's the date!
				if (preg_match("/ (\d{1,2}) ".strtolower($monthName)."/", strtolower(" ".$string), $matches)) {
					$dateTime->setDate($dateTime->format('Y'), $i, $matches[1]);
					$this->ifDateInPastAddAYear($dateTime);
					return true;
				}
				// month as word with a 1 or 2 digit number at end, assume it's the date!
				if (preg_match("/".strtolower($monthName)." (\d{1,2}) /", strtolower(" ".$string), $matches)) {
					$dateTime->setDate($dateTime->format('Y'), $i, $matches[1]);
					$this->ifDateInPastAddAYear($dateTime);
					return true;
				}
				// month as word with a 1 or 2 digit number in front and "of", assume it's the date!
				if (preg_match("/ (\d{1,2}) of ".strtolower($monthName)."/", strtolower(" ".$string), $matches)) {
					$dateTime->setDate($dateTime->format('Y'), $i, $matches[1]);
					$this->ifDateInPastAddAYear($dateTime);
					return true;
				}
				// month by itself.
				if (strpos(strtolower($string), strtolower($monthName)) !== false) {
					$string = str_ireplace($monthName, " ", $string);
					if (checkdate($i, $dateTime->format('j'), $dateTime->format('Y'))) {
						$dateTime->setDate($dateTime->format('Y'), $i, $dateTime->format('j'));
					} else {
						// Today may be the 31st March, and trying to set "April" ... setting 31st April will make odd results!
						$dateTime->setDate($dateTime->format('Y'), $i, 1);
					}
					// We are only setting month here, so can't return, may miss day.
                    $foundAnything = true;
				}
			}
		}


		// We've set the year and the month.
		// at this point, we can check if it's this year or next year.
		// We have to do this now so that working out things like "1st Sun Jan" happens in the correct year!
		$this->ifDateInPastAddAYear($dateTime);

		// day
		// order done very carefully ....
		// "1st tue" is first tuesday of month
		// "tue 1st" is the 1st, which is a tuesday
		
		
		// first we check for "1st monday"
		for ($iWeekOfMonth = 1; $iWeekOfMonth <= 5; $iWeekOfMonth++) {
			for ($iDayOfWeek = 1; $iDayOfWeek <= 7; $iDayOfWeek++) {
				foreach($this->weekOfMonthNames[$iWeekOfMonth] as $weekOfMonthName) {
					foreach($this->dayOfWeekNames[$iDayOfWeek] as $dayOfWeekName) {
						if (strpos(strtolower($string), strtolower($weekOfMonthName." ".$dayOfWeekName)) !== false) {
							$dateTime->setDate($dateTime->format('Y'),  $dateTime->format('n'), 1);
							$count = 0;
							for ($i = 1; $i <= 30; $i++) {
								if ($dateTime->format('N') == $iDayOfWeek) {
									$count++;
									if ($count == $iWeekOfMonth) {
										return true;
									}
								} 
								$dateTime->add(new \DateInterval("P1D"));
							}
						}
					}
				}
			}
		}
		
		// check for last day of month
		for ($iDayOfWeek = 1; $iDayOfWeek <= 7; $iDayOfWeek++) {
			foreach($this->dayOfWeekNames[$iDayOfWeek] as $dayOfWeekName) {
				if (strpos(strtolower($string), strtolower("last ".$dayOfWeekName)) !== false) {
					// set to first day of next month
					if ($dateTime->format('n') == 12) {
						$dateTime->setDate($dateTime->format('Y')+1,  1, 1);
					} else {
						$dateTime->setDate($dateTime->format('Y'),  $dateTime->format('n')+1, 1);
					}
					// Go back one day so on last on month
					$dateTime->sub(new \DateInterval("P1D"));
					// Now keep going back till on right day of week
					while($dateTime->format('N') != $iDayOfWeek) {
						$dateTime->sub(new \DateInterval("P1D"));
					}
					// Done, yay!
					return true;
				}
			}
		}

		// now we check for "1st" as in the actual 1st of month, with no day on end
		for($i = 31; $i > 0; $i--) {
			foreach($this->dayOfMonthNames[$i] as $dayOfMonthName) {
				if (strpos(strtolower($string), strtolower($dayOfMonthName)) !== false) {
					$dateTime->setDate($dateTime->format('Y'), $dateTime->format('n'), $i);
					return true;
				}
			}			
		}

		// now check for "tue" or "next tue". 
		// Do this last so  if "tue 2nd" specified we use the "2nd" part in previous clause 
		// and ignore "tue" 
		for ($i = 1; $i <= 7; $i++) {
			foreach($this->dayOfWeekNames[$i] as $dayOfWeekName) {
				if (strpos(strtolower($string), strtolower("next ".$dayOfWeekName)) !== false) {
					$dateTime->add(new \DateInterval("P7D"));
					while($dateTime->format("N") != $i) {
						$dateTime->add(new \DateInterval("P1D"));
					}
					return true;
				}
				if (strpos(strtolower($string), strtolower($dayOfWeekName)) !== false) {
					while($dateTime->format("N") != $i) {
						$dateTime->add(new \DateInterval("P1D"));
					}
					return true;
				}
			}
		}

        return $foundAnything;

	}

	protected function ifDateInPastAddAYear(\DateTime $dateTime) {
		if ($dateTime < $this->currentDateTime) {
			$dateTime->add(new \DateInterval("P1Y"));
		}
	}

	protected function parseShortFormatForDate($year, $probableMonth, $probableDay, \DateTime $dateTime) {

		
		
		if ($year < 2000) $year += 2000;
		if ($probableDay > 12) {
			$dateTime->setDate($year, $probableMonth, $probableDay);
		} else if ($probableMonth > 12) {
			$dateTime->setDate($year, $probableDay, $probableMonth);
		} else {
			$dateTime->setDate($year, $probableMonth, $probableDay);
		}
        return true;
		
	}
	
	protected function parseStringForTime($string, \DateTime $dateTime) {

		if (strpos(strtolower($string), "noon") !== false) {
			$dateTime->setTime(12, 0, 0);
			return true;
		}
		
		if (strpos(strtolower($string), "midnight") !== false) {
			$dateTime->setTime(0, 0, 0);
			$dateTime->add(new \DateInterval("P1D"));
			return true;
		}
		
		$matches = array();
		if (preg_match("/(\d{1,2})(\:|\.)(\d{1,2})am/", $string, $matches)) {
			if ($matches[1] == 12) {
				$dateTime->setTime(0, $matches[3], 0);
			} else {
				$dateTime->setTime($matches[1], $matches[3], 0);		
			}
			return true;
		}
		if (preg_match("/(\d{1,2})(\:|\.)(\d{1,2})pm/", $string, $matches)) {
			if ($matches[1] == 12) {
				$dateTime->setTime(12, $matches[3], 0);
			} else {
				$dateTime->setTime($matches[1]+12, $matches[3], 0);
			}
			return true;
		}
		// same as above bit with a space in it. I'm sure there is a clever regex to do this in one.
		if (preg_match("/(\d{1,2})(\:|\.)(\d{1,2}) am/", $string, $matches)) {
			if ($matches[1] == 12) {
				$dateTime->setTime(0, $matches[3], 0);
			} else {
				$dateTime->setTime($matches[1], $matches[3], 0);
			}
			return true;
		}
		if (preg_match("/(\d{1,2})(\:|\.)(\d{1,2}) pm/", $string, $matches)) {
			if ($matches[1] == 12) {
				$dateTime->setTime(12, $matches[3], 0);
			} else {
				$dateTime->setTime($matches[1]+12, $matches[3], 0);
			}
			return true;
		}

		if (preg_match("/(\d{1,2})am/", $string, $matches)) {
			if ($matches[1] == 12) {
				$dateTime->setTime(0, 0, 0);
			} else {
				$dateTime->setTime($matches[1], 0, 0);
			}
			return true;
		}
		if (preg_match("/(\d{1,2})pm/", $string, $matches)) {
			if ($matches[1] == 12) { 
				$dateTime->setTime(12, 0, 0);
			} else {
				$dateTime->setTime($matches[1]+12, 0, 0);
			}
			return true;
		}

		// same as above bit with a space in it. I'm sure there is a clever regex to do this in one.
		if (preg_match("/(\d{1,2}) am/", $string, $matches)) {
			if ($matches[1] == 12) {
				$dateTime->setTime(0, 0, 0);
			} else {
				$dateTime->setTime($matches[1], 0, 0);
			}
			return true;
		}
		if (preg_match("/(\d{1,2}) pm/", $string, $matches)) {
			if ($matches[1] == 12) {
				$dateTime->setTime(12, 0, 0);
			} else {
				$dateTime->setTime($matches[1]+12, 0, 0);
			}
			return true;
		}
		
		if (preg_match("/(\d{1,2})(\:|\.)(\d{1,2})/", $string, $matches)) {
			$dateTime->setTime($matches[1], $matches[3], 0);
			return true;
		}
		
		if (preg_match("/(\d{2})(\d{2})/", $string, $matches)) {
			$ifItWasAYear = $matches[1]*100 + $matches[2];
			$now = clone $this->currentDateTime;
			$from = $now->format('Y') - $this->yearsGoingBack;
			$to = $now->format('Y') + $this->yearsGoingForwards;
			if ($ifItWasAYear < $from || $ifItWasAYear > $to) {
				$dateTime->setTime($matches[1], $matches[2], 0);
				return true;
			}
		}

        return false;
		
	}
	
	
	protected function parseStringForInterval($string, \DateTime $dateTime) {
		
		$foundAnything = false;
		
		$matches = array();
		if (preg_match("/(\d+)(| )hour/", $string, $matches)) {
			$hours = intval($matches[1]);
			$dateTime->add(new \DateInterval("PT".$hours."H"));
			$foundAnything = true;
		}
		
		$matches = array();
		if (preg_match("/(\d+)(| )hr/", $string, $matches)) {
			$hours = intval($matches[1]);
			$dateTime->add(new \DateInterval("PT".$hours."H"));
			$foundAnything = true;
		}
		
		$matches = array();
		if (preg_match("/(\d+)(| )min/", $string, $matches)) {
			$mins = intval($matches[1]);
			$dateTime->add(new \DateInterval("PT".$mins."M"));
			$foundAnything = true;
		}
		
		foreach($this->numbersToWords as $number=>$words) {
			foreach($words as $word) {
				if (strpos(strtolower($string), $word." hour") !== false) {
					$dateTime->add(new \DateInterval("PT".$number."H"));
					$foundAnything = true;
				} else if (strpos(strtolower($string), $word." hr") !== false) {
					$dateTime->add(new \DateInterval("PT".$number."H"));
					$foundAnything = true;
				}
				if (strpos(strtolower($string), $word." min") !== false) {
					$dateTime->add(new \DateInterval("PT".$number."M"));
					$foundAnything = true;
				}
			}
		}
		
		return $foundAnything;
	}

	
	public function getTimezone() {
		return $this->timezone;
	}

    /**
     * @param int $defaultRangeLengthSeconds
     */
    public function setDefaultRangeLengthSeconds($defaultRangeLengthSeconds)
    {
        $this->defaultRangeLengthSeconds = $defaultRangeLengthSeconds;
    }

    /**
     * @return int
     */
    public function getDefaultRangeLengthSeconds()
    {
        return $this->defaultRangeLengthSeconds;
    }

}

