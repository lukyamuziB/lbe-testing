<?php

namespace Tests\App\Models;

use TestCase;
use App\Models\UserNotification;

class UserNotificationTest extends TestCase
{
    /**
     * Test if a user has subscribed to email
     * notifications of a given notification type
     *
     * @param string $recipient - Unique user email
     * @param string $notificationType - Unique notification type
     *
     * @return void
     */
    public function testHasUserAcceptedNotification()
    {
        $recipient = "adebayo.adesanya@andela.com";
        $notificationType = "FILE_NOTIFICATIONS";

        $shouldRecieveEmail = UserNotification::hasUserAcceptedEmail($recipient, $notificationType);

        $this->assertEquals(true, $shouldRecieveEmail[0]);
    }

    /**
     * Test for users who should receive email
     * notifications when a request with skills matching
     * skills in their profile is made
     *
     * @param intenger $id - unique request Id
     *
     * @return void
     */
    public function testGetUsersWithMatchingRequestSkills()
    {
        $usersToBeNotified = UserNotification::getUsersWithMatchingRequestSkills(21);
         
        $this->assertGreaterThan(0, count((array)$usersToBeNotified));
    }

    /**
     * Test for users who should receive email
     * notifications if a reqiuest they expressed interest 
     * in is withdrawn/cancelled
     *
     * @param intenger $id - unique request Id
     *
     * @return void
     */
    public function testGetInterestedUsers()
    {
        $usersToBeNotified = UserNotification::getInterestedUsers(25);

        $this->assertContains("-KesEogCwjq6lkOzKmLI", $usersToBeNotified);
    }

    /**
     * Test for users who should receive email
     * notifications if a request with skills
     * they have an opening for is made
     *
     *@param intenger $id - unique request Id
     *
     * @return void
     */
    public function testGetUsersWithMatchingOpenSkills()
    {
        $usersToBeNotified = UserNotification::getUsersWithMatchingOpenSkills(25);

        $this->assertNotContains("-KesEogCwjq6lkOzKmLI", $usersToBeNotified);
    }
}
