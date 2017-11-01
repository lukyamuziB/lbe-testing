<?php

namespace Test\App\Helpers;

use App\Exceptions\NotFoundException;
use InvalidArgumentException;
use TestCase;

/**
 * Test class for google calendar helper functions
 *
 */
class GoogleCalendarHelperTest extends TestCase
{
    /**
     * Test get calendar recursion rule
     *
     * @return void
     */
    public function testGetCalendarRecursionRuleSuccess()
    {
        $session_days = ['monday','friday'];
        $end_date = "2017-10-20 12:30";

        $recursion_rule = getCalendarRecursionRule($session_days, $end_date);
        $this->assertEquals(
            "RRULE:FREQ=WEEKLY;BYDAY=MO,FR;UNTIL=20171020 1230Z",
            $recursion_rule
        );
    }


    /**
     * Test calculate event start date
     *
     * @return void
     */
    public function testCalculateEventStartDateSuccess()
    {
        $session_days = ['monday','tuesday'];
        $match_date = "2017-08-23";
        $event_start_date = calculateEventStartDate($session_days, $match_date);
        $this->assertEquals("2017-08-28", $event_start_date);
    }


    /**
     * Test format calendar date
     *
     * @return void
     */
    public function testFormatCalendarDateSuccess()
    {
        $date = "2017-08-25";
        $time = "12:30:00";
        $formatted_date = formatCalendarDate($date, $time);

        $this->assertEquals("2017-08-25T12:30:00", $formatted_date);

        $duration = 2;
        $formatted_date = formatCalendarDate($date, $time, $duration);
        $this->assertEquals("2017-10-25T12:30:00", $formatted_date);
    }


    /**
     * Data provider for test cases testGetCalendarRecursionRuleFailure and
     * testFormatCalendarDateFailure
     *
     * @return array
     */
    public function recursionRuleAndStartDateProvider()
    {
        return [
            ["monday", "2017-10-20 12:30", "Days must be passed as an array"],
            [[], "2017-10-20 12:30", "Missing days values"],
            [['monday','friday'], "", "Missing date value"]
        ];
    }


    /**
     * Test get calendar recursion rule failure
     *
     * @dataProvider recursionRuleAndStartDateProvider
     * @return void
     */
    public function testGetCalendarRecursionRuleFailure($session_days, $end_date, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        getCalendarRecursionRule($session_days, $end_date);
    }


    /**
     * Test calculate event start date failure
     *
     * @dataProvider recursionRuleAndStartDateProvider
     * @return void
     */
    public function testCalculateEventStartDateFailure($session_days, $match_date, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        calculateEventStartDate($session_days, $match_date);
    }


    /**
     * Data provider for testFormatCalendarDateFailure
     *
     * @return array
     */
    public function calendarDateProvider()
    {
        return [
            ["", "12:30:00", 0, "Invalid date format"],
            ["2017-08-25", "", 0, "Invalid time format"],
            ["2017-08-25", "12:30:00", "xyz", "Invalid duration value"]
        ];
    }

    /**
     * Test format calendar date failure
     *
     * @dataProvider calendarDateProvider
     * @return void
     */
    public function testFormatCalendarDateFailure($date, $time, $duration, $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        formatCalendarDate($date, $time, $duration);
    }
}
